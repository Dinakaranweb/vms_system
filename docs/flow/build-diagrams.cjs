#!/usr/bin/env node
/**
 * Regenerates everything that is derived from docs/VMS_Complete_Flow.md:
 *
 *   docs/flow/NN-name.svg          one diagram, vector
 *   docs/flow/NN-name.png          one diagram, raster at 3x for Word/PowerPoint
 *   docs/VMS_Complete_Flow_A4.pdf  the whole document, laid out for A4 portrait
 *   docs/VMS_Complete_Flow_A3.pdf  the same, laid out for A3 portrait
 *
 * Every diagram is measured and fitted to whichever sheet it is going on: scaled to the
 * largest size the page allows and centred, with no manual placement anywhere. On A4 a tall
 * diagram that would otherwise print too small is split across two sheets with an overlap;
 * on A3 there is room for all of them on one sheet each, so none is split.
 *
 * The Markdown is the single source. Edit a Mermaid block there, run this, and the images
 * and both printable PDFs follow. Nothing here edits the Markdown.
 *
 * Usage, from the repository root:
 *
 *   npm install --no-save @mermaid-js/mermaid-cli@11.4.2 marked@14.1.3
 *   node docs/flow/build-diagrams.cjs            # images + both PDFs
 *   node docs/flow/build-diagrams.cjs --page a3  # just the A3 PDF
 *   node docs/flow/build-diagrams.cjs --no-images
 *
 * Puppeteer is driven against an already-installed Chrome rather than downloading its own, so
 * set PUPPETEER_SKIP_DOWNLOAD=true when installing. Override the browser with CHROME_PATH if
 * it is not in one of the usual places.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');

const DOCS = path.resolve(__dirname, '..');
const REPO = path.resolve(DOCS, '..');
const MD = path.join(DOCS, 'VMS_Complete_Flow.md');
const OUT_DIR = __dirname;
const WORK = fs.mkdtempSync(path.join(os.tmpdir(), 'vms-flow-'));

// Where mermaid-cli, marked and puppeteer live. Point VMS_FLOW_MODULES at another
// node_modules to run this without installing the tooling into the project.
const MODULES = process.env.VMS_FLOW_MODULES || path.join(REPO, 'node_modules');

// ---------------------------------------------------------------- page geometry
const OVERLAP_PX = 240;    // taller than any single node, so a split never hides one
const MIN_FONT_MM = 2.9;   // ~8pt; below this a printed diagram stops being readable
const INLINE_MAX_MM = 120; // a diagram this short sits in the text instead of taking a page
const BASE_FONT_PX = 15;   // must match mermaidConfig.themeVariables.fontSize

/**
 * One entry per paper size. `wAvail` / `hAvail` are what is left for a diagram after the
 * margins and its caption, in millimetres, and are the only numbers the fitting uses.
 *
 * A4 is 210x297mm, A3 is 297x420mm. On A3 every diagram in this document fits one sheet at
 * a readable size, so `maxPages` is 1 and nothing is ever split; there is no reason to cut a
 * chart in half when the paper is big enough to hold it.
 *
 * `textMaxMm` caps the width of the prose on A3. A full-width line on a 297mm sheet runs to
 * well over a hundred characters, which is tiring to read; the diagrams still use the whole
 * sheet.
 */
const PROFILES = {
  a4: {
    label: 'A4',
    pageSize: 'A4 portrait',
    margin: '15mm 14mm 16mm 14mm',
    wAvail: 182,
    hAvail: 252,
    figPageH: 261,
    maxPages: 2,
    textMaxMm: null,
    bodyPt: 10.5,
    h1Pt: 21,
    h2Pt: 14.5,
    h3Pt: 11.5,
    tablePt: 9,
    codePt: 9,
    capPt: 9.5,
  },
  a3: {
    label: 'A3',
    pageSize: 'A3 portrait',
    margin: '15mm 14mm 16mm 14mm',
    wAvail: 269,
    hAvail: 376,
    figPageH: 386,
    maxPages: 1,
    textMaxMm: 200,
    bodyPt: 12.5,
    h1Pt: 26,
    h2Pt: 18,
    h3Pt: 14,
    tablePt: 11,
    codePt: 11,
    capPt: 12,
  },
};

function findChrome() {
  if (process.env.CHROME_PATH) return process.env.CHROME_PATH;
  const candidates = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  ];
  const hit = candidates.find((p) => fs.existsSync(p));
  if (!hit) {
    throw new Error('No Chrome or Edge found. Set CHROME_PATH to the browser executable.');
  }
  return hit;
}

const mermaidConfig = {
  theme: 'base',
  themeVariables: {
    fontFamily: 'Segoe UI, Arial, sans-serif',
    fontSize: '15px',
    primaryColor: '#eef2ff',
    primaryTextColor: '#111827',
    primaryBorderColor: '#4f46e5',
    lineColor: '#374151',
    secondaryColor: '#f1f5f9',
    tertiaryColor: '#f8fafc',
  },
  flowchart: { useMaxWidth: false, htmlLabels: true, padding: 12 },
  sequence: { useMaxWidth: false },
};

// ---------------------------------------------------------------- 1. extract blocks
function slugFor(heading, fallbackIndex) {
  const m = heading && heading.match(/^(\d+)\.\s*(.*)$/);
  const n = m ? String(m[1]).padStart(2, '0') : String(fallbackIndex);
  const rest = (m ? m[2] : heading || 'diagram')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 40);
  return `${n}-${rest}`;
}

function extract(markdown) {
  const lines = markdown.split(/\r?\n/);
  const found = [];
  let heading = null;
  let inBlock = false;
  let buf = [];

  for (const line of lines) {
    if (!inBlock && /^## /.test(line)) {
      heading = line.replace(/^## /, '').trim();
      continue;
    }
    if (!inBlock && line.trim() === '```mermaid') {
      inBlock = true;
      buf = [];
      continue;
    }
    if (inBlock && line.trim() === '```') {
      inBlock = false;
      found.push({ heading, code: buf.join('\n') });
      continue;
    }
    if (inBlock) buf.push(line);
  }
  if (inBlock) throw new Error('unterminated ```mermaid block in the Markdown');

  return found.map((b, i) => ({ ...b, name: slugFor(b.heading, i + 1) }));
}

// ---------------------------------------------------------------- 2. render
function render(blocks, chrome) {
  const pconf = path.join(WORK, 'puppeteer.json');
  const mconf = path.join(WORK, 'mermaid.json');
  fs.writeFileSync(pconf, JSON.stringify({ executablePath: chrome, args: ['--no-sandbox', '--disable-gpu'] }));
  fs.writeFileSync(mconf, JSON.stringify(mermaidConfig));

  // The package's entry point, run through node: no shell, so paths with spaces are safe.
  const mmdc = path.join(MODULES, '@mermaid-js', 'mermaid-cli', 'src', 'cli.js');
  if (!fs.existsSync(mmdc)) {
    throw new Error('mermaid-cli not found. Run:\n  npm install --no-save @mermaid-js/mermaid-cli@11.4.2 marked@14.1.3');
  }

  fs.mkdirSync(OUT_DIR, { recursive: true });
  for (const b of blocks) {
    const src = path.join(WORK, b.name + '.mmd');
    fs.writeFileSync(src, b.code + '\n');

    for (const [ext, extra] of [['svg', []], ['png', ['-s', '3']]]) {
      execFileSync(
        process.execPath,
        [mmdc, '-i', src, '-o', path.join(OUT_DIR, `${b.name}.${ext}`),
         '-p', pconf, '-c', mconf, '-b', 'white', ...extra],
        { stdio: 'ignore' }
      );
    }
    console.log(`  rendered  ${b.name}.svg + .png`);
  }
}

// ---------------------------------------------------------------- 3. paginate
/**
 * How many pages a diagram needs, and at what scale, to stay above MIN_FONT_MM.
 *
 * Splitting only ever buys height. Once the page's width is what limits the scale, another
 * page gains nothing, so the search stops there rather than slicing a diagram pointlessly.
 */
function plan(w, h, prof) {
  const sWidth = prof.wAvail / w;

  for (let n = 1; n <= prof.maxPages; n++) {
    const step = n === 1 ? h : (h - OVERLAP_PX) / n;
    const tileH = n === 1 ? h : step + OVERLAP_PX;
    const s = Math.min(sWidth, prof.hAvail / tileH);
    const widthBound = s >= sWidth - 1e-9;

    if (widthBound || BASE_FONT_PX * s >= MIN_FONT_MM || n === prof.maxPages) {
      return { n, step, tileH, s };
    }
  }
}

function figureHtml(name, heading, figNo, prof) {
  const raw = fs.readFileSync(path.join(OUT_DIR, name + '.svg'), 'utf8');
  const vb = raw.match(/viewBox="([^"]+)"/);
  if (!vb) throw new Error(`no viewBox in ${name}.svg`);
  const [, , w, h] = vb[1].split(/\s+/).map(Number);
  const { n, step, tileH, s } = plan(w, h, prof);

  const fullW = (w * s).toFixed(2);
  const fullH = (h * s).toFixed(2);
  const pt = (BASE_FONT_PX * s * 2.835).toFixed(1);

  // Each inlined copy needs its own id: the SVG's <style> is scoped to #my-svg.
  const uniq = (i) => raw.replace(/my-svg/g, `fig${figNo}-${i}`);

  if (n === 1 && h * s <= INLINE_MAX_MM) {
    console.log(`  Figure ${figNo}  ${name}  -> inline (${(h * s).toFixed(0)}mm), text ~${pt}pt`);
    return `<figure class="figinline">
        <div class="tileinner" style="width:${fullW}mm;height:${fullH}mm;">${uniq(0)}</div>
        <figcaption class="figcap">Figure ${figNo} &mdash; ${heading}</figcaption>
      </figure>`;
  }

  console.log(`  Figure ${figNo}  ${name}  -> ${n} page(s), text ~${pt}pt`);
  const pages = [];
  for (let i = 0; i < n; i++) {
    const offset = i * step;
    const visible = Math.min(tileH, h - offset);
    const part = n > 1 ? ` <span class="part">(part ${i + 1} of ${n})</span>` : '';
    pages.push(
      `<section class="figpage">
         <div class="figcap">Figure ${figNo} &mdash; ${heading}${part}</div>
         <div class="tile" style="width:${fullW}mm;height:${(visible * s).toFixed(2)}mm;">
           <div class="tileinner" style="width:${fullW}mm;height:${fullH}mm;margin-top:-${(offset * s).toFixed(2)}mm;">${uniq(i)}</div>
         </div>
       </section>`
    );
  }
  return pages.join('\n');
}

function css(prof) {
  // On a wide sheet the prose is held to a readable measure and centred; the figures are
  // deliberately left out of this so they still use the full width of the paper.
  const measure = prof.textMaxMm
    ? `body > h1, body > h2, body > h3, body > p, body > ul, body > ol,
       body > table, body > blockquote, body > pre, body > hr {
         max-width: ${prof.textMaxMm}mm; margin-left: auto; margin-right: auto;
       }`
    : '';

  return `
  @page { size: ${prof.pageSize}; margin: ${prof.margin}; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body {
    font-family: "Segoe UI", Calibri, Arial, sans-serif;
    font-size: ${prof.bodyPt}pt; line-height: 1.45; color: #111827;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  h1 { font-size: ${prof.h1Pt}pt; color: #312e81; margin: 0 0 2mm; line-height: 1.2; }
  h2 {
    font-size: ${prof.h2Pt}pt; color: #3730a3; margin: 0 0 3mm;
    padding-bottom: 1.5mm; border-bottom: 0.5mm solid #c7d2fe;
    break-before: page; break-after: avoid;
  }
  h3 { font-size: ${prof.h3Pt}pt; color: #1f2937; margin: 5mm 0 2mm; break-after: avoid; }
  h1 + p, h2 + p, h3 + p { margin-top: 0; }
  p { margin: 0 0 2.6mm; orphans: 2; widows: 2; }
  ul, ol { margin: 0 0 2.6mm; padding-left: 6mm; }
  li { margin-bottom: 1.1mm; }
  code {
    font-family: Consolas, "Courier New", monospace; font-size: ${prof.codePt}pt;
    background: #f1f5f9; padding: 0.3mm 1mm; border-radius: 0.8mm;
  }
  pre { background: #f8fafc; border: 0.3mm solid #e2e8f0; padding: 2.5mm;
        font-size: ${(prof.codePt - 0.5).toFixed(1)}pt;
        overflow-wrap: break-word; white-space: pre-wrap; }
  blockquote { margin: 0 0 3mm; padding: 2mm 3mm; background: #eef2ff;
               border-left: 1mm solid #6366f1; }
  blockquote p:last-child { margin-bottom: 0; }
  table { width: 100%; border-collapse: collapse; font-size: ${prof.tablePt}pt; margin: 0 0 3.5mm; }
  th, td { border: 0.25mm solid #cbd5e1; padding: 1.4mm 2mm; text-align: left; vertical-align: top; }
  th { background: #eef2ff; color: #312e81; font-weight: 600; }
  tr { break-inside: avoid; }
  thead { display: table-header-group; }
  hr { border: 0; border-top: 0.3mm solid #e2e8f0; margin: 4mm 0; }
  ${measure}

  /* Each diagram page centres its figure on the sheet, vertically and horizontally. */
  .figpage {
    break-before: page; break-after: page;
    height: ${prof.figPageH}mm;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
  }
  .figcap { font-size: ${prof.capPt}pt; font-weight: 600; color: #3730a3;
            margin: 0 0 3mm; text-align: center; width: 100%; }
  .figcap .part { font-weight: 400; color: #4b5563; }
  .tile { overflow: hidden; margin: 0 auto; }
  .tileinner > svg { width: 100%; height: 100%; display: block; }
  .figinline { margin: 4mm auto 5mm; padding: 0; break-inside: avoid;
               display: flex; flex-direction: column; align-items: center; }
  .figinline .figcap { margin: 2.5mm 0 0; }
`;
}

async function buildPdf(blocks, chrome, prof) {
  const { marked } = require(path.join(MODULES, 'marked'));
  const outPdf = path.join(DOCS, `VMS_Complete_Flow_${prof.label}.pdf`);

  let md = fs.readFileSync(MD, 'utf8');
  let i = 0;
  const figures = [];
  md = md.replace(/```mermaid\r?\n[\s\S]*?\r?\n```/g, () => {
    const b = blocks[i];
    if (!b) throw new Error('more Mermaid blocks than were extracted');
    const token = `@@FIGURE_${i}@@`;
    figures.push({ token, name: b.name, heading: b.heading, no: i + 1 });
    i++;
    return `\n${token}\n`;
  });
  if (i !== blocks.length) {
    throw new Error(`replaced ${i} blocks but extracted ${blocks.length}`);
  }

  marked.setOptions({ gfm: true, breaks: false });
  let body = marked.parse(md);

  for (const f of figures) {
    const html = figureHtml(f.name, f.heading.replace(/&/g, '&amp;'), f.no, prof);
    const before = body;
    body = body.replace(new RegExp(`<p>\\s*${f.token}\\s*</p>`), html);
    if (body === before) body = body.replace(f.token, html);
    if (body.includes(f.token)) throw new Error(`token ${f.token} survived substitution`);
  }

  const htmlPath = path.join(WORK, `print-${prof.label}.html`);
  fs.writeFileSync(
    htmlPath,
    `<!doctype html><html lang="en"><head><meta charset="utf-8">
<title>VMS &mdash; Complete Process Flow</title><style>${css(prof)}</style></head>
<body>\n${body}\n</body></html>`,
    'utf8'
  );

  const puppeteer = require(path.join(MODULES, 'puppeteer'));
  const browser = await puppeteer.launch({
    executablePath: chrome,
    args: ['--no-sandbox', '--disable-gpu'],
  });
  try {
    const page = await browser.newPage();
    await page.goto('file:///' + htmlPath.split(path.sep).join('/'), { waitUntil: 'load' });
    await page.pdf({
      path: outPdf,
      format: prof.label,
      printBackground: true,
      preferCSSPageSize: true,
      displayHeaderFooter: true,
      headerTemplate: '<div></div>',
      footerTemplate:
        '<div style="width:100%;font-family:Segoe UI,Arial,sans-serif;font-size:7.5pt;' +
        'color:#6b7280;padding:0 14mm;display:flex;justify-content:space-between;">' +
        '<span>VMS &mdash; Complete Process Flow</span>' +
        '<span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>' +
        '</div>',
    });
  } finally {
    await browser.close();
  }

  const kb = (fs.statSync(outPdf).size / 1024).toFixed(0);
  console.log(`  -> ${path.relative(REPO, outPdf)} (${kb} KB)`);
}

function parseArgs(argv) {
  const want = [];
  let images = true;

  for (let i = 0; i < argv.length; i++) {
    const a = argv[i];
    if (a === '--no-images') {
      images = false;
    } else if (a === '--page' || a === '-p') {
      const v = String(argv[++i] || '').toLowerCase();
      for (const key of v.split(',')) {
        if (key === 'both' || key === 'all') want.push('a4', 'a3');
        else if (PROFILES[key]) want.push(key);
        else throw new Error(`unknown page size "${key}". Use a4, a3 or both.`);
      }
    } else {
      throw new Error(`unknown option "${a}". Use --page a4|a3|both and --no-images.`);
    }
  }
  return { pages: want.length ? [...new Set(want)] : ['a4', 'a3'], images };
}

(async () => {
  const { pages, images } = parseArgs(process.argv.slice(2));
  const chrome = findChrome();
  console.log('browser: ' + chrome);

  const blocks = extract(fs.readFileSync(MD, 'utf8'));
  console.log(`\n${blocks.length} diagrams found in ${path.relative(REPO, MD)}\n`);

  if (images) {
    render(blocks, chrome);
  } else {
    console.log('  (skipping image render, reusing the SVGs already in docs/flow)');
  }

  for (const key of pages) {
    const prof = PROFILES[key];
    console.log(`\n${prof.label} — fitting each diagram to the sheet:`);
    await buildPdf(blocks, chrome, prof);
  }
})()
  .catch((e) => {
    console.error('\nFAILED: ' + e.message);
    process.exitCode = 1;
  })
  .finally(() => {
    // VMS_FLOW_KEEP=1 leaves the intermediate print HTML behind, for checking the layout.
    if (process.env.VMS_FLOW_KEEP) {
      console.log(`\nintermediate files kept in ${WORK}`);
      return;
    }
    fs.rmSync(WORK, { recursive: true, force: true });
  });
