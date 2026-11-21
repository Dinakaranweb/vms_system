/**
 * VMS — Complete Process Flow deck.
 * Source of truth for the content: docs/VMS_Complete_Flow.md (20 Nov 2026).
 * Every flow is drawn natively from shapes: the Mermaid renders are tall portrait
 * images that would be unreadable scaled onto a 13.3in landscape slide.
 */
const pptxgen = require('pptxgenjs');
const path = require('path');
const { applyTheme } = require(path.join(
  'C:/Users/Admin/.claude/skills/synced/bb16500c-743a-44ee-8dfe-ddc065fd5e54_d3597144-cf36-4a6b-915d-2d0258cd85b6/pptx',
  'scripts', 'apply_theme.js'
));

const THEME = {
  name: 'VMRF Procurement',
  headFontFace: 'Cambria',
  bodyFontFace: 'Calibri',
  colors: {
    dk1: '151A38', // deep indigo ink
    lt1: 'FFFFFF',
    dk2: '2E3769', // indigo
    lt2: 'F1F4FC', // pale indigo wash
    accent1: '00A5A0', // teal   — movement, notification
    accent2: 'FFB020', // amber  — a decision, a gate
    accent3: 'F0506E', // coral  — refusal, block, gap
    accent4: '6C5CE7', // violet — the committee, the codes
    accent5: '19B978', // green  — approved, active
    accent6: '3D4785', // slate indigo — actors, structure
    hlink: '00A5A0',
    folHlink: '6C5CE7',
  },
};

const pres = new pptxgen();
pres.layout = 'LAYOUT_WIDE'; // 13.333 x 7.5
pres.theme = { headFontFace: THEME.headFontFace, bodyFontFace: THEME.bodyFontFace };
pres.title = 'Vendor Management System — Complete Process Flow';
pres.subject = 'VMRF-DU procurement: registration to signed contract';
pres.author = 'Purchase Department, VMRF-DU';
pres.company = 'Vinayaka Mission Research Foundation (Deemed to be University)';

const C = pres.SchemeColor;
const S = pres.ShapeType;
const K = THEME.colors;

// ---------------------------------------------------------------- geometry
const SW = 13.333;
const M = 0.62;                 // side margin
const CW = SW - 2 * M;          // 12.093 usable width
const TITLE_Y = 0.44;
const TITLE_H = 0.78;
const BODY_T = 1.52;            // first pixel of content on a content slide
const FOOT_Y = 6.93;

// ---------------------------------------------------------------- helpers
/** Fresh shadow object per call — pptxgenjs mutates option objects in place. */
function sh(o) {
  return Object.assign(
    { type: 'outer', color: '0B1030', blur: 9, offset: 2, angle: 90, opacity: 0.13 },
    o || {}
  );
}

/** A rounded panel. Pass flat:true for no shadow. */
function card(s, o) {
  const d = {
    x: o.x, y: o.y, w: o.w, h: o.h,
    rectRadius: o.r === undefined ? 0.09 : o.r,
    fill: { color: o.fill === undefined ? C.background2 : o.fill },
    objectName: o.name || 'panel',
  };
  if (o.line) d.line = { color: o.line, width: o.lw || 1 };
  if (!o.flat) d.shadow = sh(o.shadow);
  if (o.trans) d.fill.transparency = o.trans;
  s.addShape(S.roundRect, d);
}

/** Plain text box. Always isTextBox, always margin 0 so it lines up with shapes. */
function txt(s, text, o) {
  s.addText(
    text,
    Object.assign(
      { isTextBox: true, margin: 0, fontSize: 14, color: C.text1, valign: 'top', lineSpacingMultiple: 1.08 },
      o
    )
  );
}

/** Small capsule label. */
function pill(s, text, o) {
  const h = o.h || 0.27;
  s.addShape(S.roundRect, {
    x: o.x, y: o.y, w: o.w, h: h, rectRadius: h / 2,
    fill: { color: o.fill },
    objectName: 'pill',
  });
  txt(s, text, {
    x: o.x, y: o.y, w: o.w, h: h, align: 'center', valign: 'middle',
    fontSize: o.fs || 9.5, bold: true, color: o.color === undefined ? C.background1 : o.color,
    charSpacing: o.cs === undefined ? 0.5 : o.cs,
  });
}

/** Numbered disc — the deck's repeated mark for an ordered step. */
function disc(s, label, o) {
  const d = o.d || 0.42;
  s.addShape(S.ellipse, {
    x: o.x, y: o.y, w: d, h: d,
    fill: { color: o.fill === undefined ? C.accent6 : o.fill },
    objectName: 'step-disc',
  });
  txt(s, label, {
    x: o.x, y: o.y, w: d, h: d, align: 'center', valign: 'middle',
    fontSize: o.fs || 12, bold: true, color: o.color === undefined ? C.background1 : o.color,
  });
}

/** The deck's mark: a 2x2 grid of accent squares. Repeated on title/dividers/close. */
function mark(s, x, y, size) {
  const g = size * 0.1;
  const u = (size - g) / 2;
  const cols = [C.accent1, C.accent2, C.accent4, C.accent5];
  [[0, 0], [1, 0], [0, 1], [1, 1]].forEach((p, i) => {
    s.addShape(S.roundRect, {
      x: x + p[0] * (u + g), y: y + p[1] * (u + g), w: u, h: u,
      rectRadius: u * 0.26, fill: { color: cols[i] }, objectName: 'mark',
    });
  });
}

// ------------------------------------------------- flow primitives
// One visual language across every flow in the deck:
//   actor    slate indigo, white text   — a person doing something
//   state    pale panel, indigo rule    — what the record now is
//   decision amber                      — a branch
//   stop     coral, white text          — refused, blocked, ended
//   good     green, white text          — approved, active
//   seal     violet, white text         — codes, committee, the seal
//   notify   teal, white text           — somebody is told
const TONE = {
  actor:    { fill: C.accent6,     color: C.background1, line: null },
  state:    { fill: C.background2, color: C.text1,       line: K.dk2 },
  decision: { fill: C.accent2,     color: '3A2600',      line: null },
  stop:     { fill: C.accent3,     color: C.background1, line: null },
  good:     { fill: C.accent5,     color: C.background1, line: null },
  seal:     { fill: C.accent4,     color: C.background1, line: null },
  notify:   { fill: C.accent1,     color: C.background1, line: null },
};
const TONE_HEX = {
  actor: K.accent6, state: K.dk2, decision: K.accent2, stop: K.accent3,
  good: K.accent5, seal: K.accent4, notify: K.accent1,
};

/**
 * A flow node. o = {x,y,w,h,tone,head,sub,fs,subfs,n}
 * head is the action; sub is the quiet detail under it.
 */
function node(s, o) {
  const t = TONE[o.tone || 'state'];
  const h = o.h || 0.72;
  card(s, {
    x: o.x, y: o.y, w: o.w, h: h, fill: t.fill, line: o.tone === 'state' ? t.line : null,
    lw: 0.75, r: 0.08, name: 'node',
  });
  const pad = 0.12;
  const runs = [{ text: o.head, options: { fontSize: o.fs || 11.5, bold: true, color: t.color, breakLine: !!o.sub } }];
  if (o.sub) runs.push({ text: o.sub, options: { fontSize: o.subfs || 9.5, bold: false, color: t.color, transparency: 18 } });
  s.addText(runs, {
    isTextBox: true, margin: 0, x: o.x + pad, y: o.y + 0.04, w: o.w - 2 * pad, h: h - 0.08,
    align: 'center', valign: 'middle', lineSpacingMultiple: 1.0, objectName: 'node-text',
  });
}

/** Right-pointing connector. */
function aR(s, x, y, w, color) {
  s.addShape(S.line, {
    x: x, y: y, w: w, h: 0,
    line: { color: color || K.dk2, width: 1.6, endArrowType: 'triangle' }, objectName: 'arrow',
  });
}
/** Down-pointing connector. */
function aD(s, x, y, h, color) {
  s.addShape(S.line, {
    x: x, y: y, w: 0, h: h,
    line: { color: color || K.dk2, width: 1.6, endArrowType: 'triangle' }, objectName: 'arrow',
  });
}
/** Plain segment, no head — for building elbows. */
function seg(s, x, y, w, h, color) {
  s.addShape(S.line, {
    x: x, y: y, w: w, h: h,
    line: { color: color || K.dk2, width: 1.6 }, objectName: 'seg',
  });
}
/** Elbow: down from (x,y) by dy, then across by dx, arrow at the end. */
function elbowDR(s, x, y, dy, dx, color) {
  seg(s, x, y, 0, dy, color);
  aR(s, x, y + dy, dx, color);
}
/** Elbow: across from (x,y) by dx, then down by dy, arrow at the end. */
function elbowRD(s, x, y, dx, dy, color) {
  seg(s, x, y, dx, 0, color);
  aD(s, x + dx, y, dy, color);
}
/** Branch label sat on a connector. */
function tag(s, text, o) {
  txt(s, text, {
    x: o.x, y: o.y, w: o.w || 0.62, h: 0.22, align: 'center', valign: 'middle',
    fontSize: 8.5, bold: true, color: o.color || K.dk2, fill: { color: C.background1 },
  });
}

// ---------------------------------------------------------------- layouts
const FOOT = 'Vendor Management System  ·  VMRF-DU  ·  Complete process flow';

function footObjs(dark) {
  return [
    { text: {
        text: FOOT,
        options: { x: M, y: FOOT_Y, w: 8.4, h: 0.3, fontSize: 9, color: dark ? C.background2 : C.text2,
          transparency: dark ? 40 : 35, isTextBox: true, margin: 0, valign: 'middle', charSpacing: 0.3 },
    } },
  ];
}
function titlePh(dark) {
  return { placeholder: {
    options: { name: 'title', type: 'title', x: M, y: TITLE_Y, w: CW, h: TITLE_H,
      fontSize: 33, bold: true, color: dark ? C.background1 : C.text1, align: 'left', valign: 'middle', margin: 0 },
    text: '',
  } };
}
const SNUM = (dark) => ({ x: 12.45, y: FOOT_Y, w: 0.5, h: 0.3, fontSize: 9.5,
  color: dark ? C.background2 : C.text2, align: 'right' });

pres.defineSlideMaster({ title: 'COVER', background: { color: K.dk1 }, objects: [] });

pres.defineSlideMaster({
  title: 'DIVIDER', background: { color: K.dk1 },
  objects: [
    { placeholder: { options: { name: 'kicker', type: 'body', x: M, y: 2.52, w: 8.2, h: 0.32,
      fontSize: 12, bold: true, color: C.accent1, charSpacing: 1.6, margin: 0, valign: 'middle' }, text: '' } },
    { placeholder: { options: { name: 'title', type: 'title', x: M, y: 2.92, w: 9.4, h: 0.95,
      fontSize: 42, bold: true, color: C.background1, align: 'left', margin: 0, valign: 'middle' }, text: '' } },
    { placeholder: { options: { name: 'lead', type: 'body', x: M, y: 4.02, w: 8.0, h: 0.8,
      fontSize: 14.5, color: C.background2, transparency: 22, margin: 0, valign: 'top' }, text: '' } },
  ],
  slideNumber: SNUM(true),
});

pres.defineSlideMaster({
  title: 'CONTENT', background: { color: K.lt1 },
  objects: [titlePh(false)].concat(footObjs(false)), slideNumber: SNUM(false),
});
pres.defineSlideMaster({
  title: 'CONTENT TINT', background: { color: K.lt2 },
  objects: [titlePh(false)].concat(footObjs(false)), slideNumber: SNUM(false),
});
pres.defineSlideMaster({
  title: 'CONTENT DARK', background: { color: K.dk1 },
  objects: [titlePh(true)].concat(footObjs(true)), slideNumber: SNUM(true),
});
pres.defineSlideMaster({
  title: 'TABLE', background: { color: K.lt1 },
  objects: [
    titlePh(false),
    { placeholder: { options: { name: 'table', type: 'body', x: M, y: BODY_T, w: CW, h: 5.0,
      color: C.text1, fontSize: 11 }, text: '' } },
  ].concat(footObjs(false)),
  slideNumber: SNUM(false),
});

/** Add a content slide and set its title. */
function sl(master, section, title) {
  const s = pres.addSlide({ masterName: master, sectionTitle: section });
  if (title) s.addText(title, { placeholder: 'title' });
  return s;
}
/** A deck-navigation divider. */
function divider(section, kicker, title, lead) {
  const s = pres.addSlide({ masterName: 'DIVIDER', sectionTitle: section });
  mark(s, M, 1.62, 0.62);
  s.addText(kicker, { placeholder: 'kicker' });
  s.addText(title, { placeholder: 'title' });
  if (lead) s.addText(lead, { placeholder: 'lead' });
  return s;
}
/** Standard caption under a block of content. */
function caption(s, text, y, o) {
  txt(s, text, Object.assign({ x: M, y: y, w: CW, h: 0.5, fontSize: 11, color: C.text2,
    italic: true, transparency: 12 }, o || {}));
}

// ================================================================ 1. COVER
pres.addSection({ title: 'Opening' });
{
  const s = pres.addSlide({ masterName: 'COVER', sectionTitle: 'Opening' });
  mark(s, M, 0.92, 0.98);

  txt(s, 'VMRF-DU  ·  PURCHASE DEPARTMENT', {
    x: M, y: 2.28, w: 8, h: 0.3, fontSize: 12, bold: true, color: C.accent1, charSpacing: 2.2,
  });
  txt(s, 'Vendor Management System', {
    x: M, y: 2.66, w: 10.4, h: 0.95, fontSize: 50, bold: true, color: C.background1, fontFace: THEME.headFontFace,
  });
  txt(s, 'Every path a record takes through the system — from a vendor registering\nto a contract signed by both sides.', {
    x: M, y: 3.78, w: 8.6, h: 0.85, fontSize: 16.5, color: C.background2, transparency: 18, lineSpacingMultiple: 1.25,
  });

  const stats = [
    ['6', 'roles, each with its own route', C.accent1],
    ['3', 'ordered approval chains on one list', C.accent2],
    ['0', 'people who can open a tender alone', C.accent4],
  ];
  const w = 3.85, gap = 0.26;
  stats.forEach((st, i) => {
    const x = M + i * (w + gap);
    card(s, { x: x, y: 5.08, w: w, h: 1.26, fill: C.text2, flat: true, r: 0.1, name: 'stat' });
    txt(s, st[0], { x: x + 0.22, y: 5.35, w: 1.0, h: 0.72, fontSize: 40, bold: true, color: st[2],
      valign: 'middle', fontFace: THEME.headFontFace });
    txt(s, st[1], { x: x + 1.16, y: 5.25, w: w - 1.36, h: 0.92, fontSize: 11.5, color: C.background1,
      valign: 'middle', lineSpacingMultiple: 1.15 });
  });

  txt(s, 'Reference as of 20 November 2026', {
    x: M, y: 6.6, w: 6, h: 0.3, fontSize: 10.5, color: C.background2, transparency: 45,
  });
  s.addNotes('Opening frame. The three figures are the spine of the whole deck: six roles, three ordered approval chains running off one committee list, and a code gate that leaves nobody able to open a tender by themselves.');
}

// ================================================================ 2. JOURNEY
{
  const s = sl('CONTENT', 'Opening', 'From registration to a signed contract');
  txt(s, 'Three phases. Each row is a phase; each box is a step the system records.', {
    x: M, y: 1.18, w: 9.5, h: 0.3, fontSize: 13, color: C.text2,
  });

  const LBW = 1.72;                       // phase label column
  const nx0 = M + LBW + 0.3;
  const nw = (CW - LBW - 0.3 - 2 * 0.22) / 3;
  const rows = [
    ['Onboarding', [
      ['actor', 'Vendor registers', 'company, GST, PAN'],
      ['actor', 'Purchase Officer clears it', '/admin/vendors'],
      ['seal', 'Committee approves in order', 'one member at a time'],
    ]],
    ['Tendering', [
      ['actor', 'Tender raised', 'Officer or Tender Owner'],
      ['state', 'Published', 'portal, website optional'],
      ['actor', 'Vendors apply', 'online, postal or hybrid'],
    ]],
    ['Opening to contract', [
      ['seal', 'Opened with codes', 'collected in person'],
      ['state', 'Committee reviews', 'comments, compares bids'],
      ['good', 'Award, then contract', 'both sides sign'],
    ]],
  ];
  const ry = [1.68, 3.38, 5.08], rh = 1.14;
  rows.forEach((row, ri) => {
    const y = ry[ri];
    disc(s, String(ri + 1), { x: M, y: y + 0.08, d: 0.44, fill: C.accent6, fs: 13 });
    txt(s, row[0], { x: M + 0.56, y: y + 0.06, w: LBW - 0.5, h: 0.95, fontSize: 13.5, bold: true,
      color: C.text1, valign: 'top', fontFace: THEME.headFontFace, lineSpacingMultiple: 1.05 });
    if (ri < 2) seg(s, M + 0.22, y + 0.56, 0, ry[ri + 1] - y, K.accent6);

    row[1].forEach((n, i) => {
      const x = nx0 + i * (nw + 0.22);
      node(s, { x: x, y: y, w: nw, h: rh, tone: n[0], head: n[1], sub: n[2], fs: 12.5, subfs: 10 });
      if (i < 2) aR(s, x + nw + 0.03, y + rh / 2, 0.16, K.dk2);
    });
  });

  // Legend — the visual language used by every flow in the deck.
  const leg = [
    ['actor', 'A person acts'], ['state', 'Record state'], ['decision', 'A branch'],
    ['stop', 'Refused or blocked'], ['good', 'Approved, active'],
    ['seal', 'Codes, committee'], ['notify', 'Somebody is told'],
  ];
  let lx = M;
  leg.forEach((l) => {
    s.addShape(S.roundRect, { x: lx, y: 6.42, w: 0.17, h: 0.17, rectRadius: 0.05,
      fill: { color: TONE[l[0]].fill }, line: { color: K.dk2, width: 0.75 }, objectName: 'legend-swatch' });
    const tw = 1.45;
    txt(s, l[1], { x: lx + 0.25, y: 6.38, w: tw, h: 0.26, fontSize: 9.5, color: C.text2, valign: 'middle' });
    lx += tw + 0.3;
  });
  s.addNotes('Walk the three rows. Every later slide zooms into one box on this page, and the colour code in the legend holds for every flow in the deck.');
}

// ================================================================ 3. THE RULE
{
  const s = sl('CONTENT DARK', 'Opening', 'The rule the whole design turns on');
  txt(s, 'No single person can carry a tender from raised to awarded. Three mechanisms enforce it — none of them by hiding a button.', {
    x: M, y: 1.26, w: 10.2, h: 0.5, fontSize: 14.5, color: C.background2, transparency: 15, lineSpacingMultiple: 1.2,
  });

  const items = [
    ['Ordered approval chains', C.accent2,
      'Three separate chains run off one committee list. Only the lowest pending step is actionable, so a stale tab or a guessed URL cannot jump the queue. Any one rejection ends the chain where it stands.'],
    ['Bids stay sealed', C.accent4,
      'Submitted documents and bid values are unreadable — by the officer, the owner and the committee alike — until the opening completes. That is the point of the code gate.'],
    ['The opener holds no code', C.accent1,
      'Whoever starts an opening is never issued a code for it. Holders read their codes out in person and the opener keys them in together. Even the Super Admin fallback holds none.'],
  ];
  const w = (CW - 2 * 0.3) / 3;
  items.forEach((it, i) => {
    const x = M + i * (w + 0.3);
    card(s, { x: x, y: 2.08, w: w, h: 3.14, fill: C.text2, flat: true, r: 0.11, name: 'pillar' });
    s.addShape(S.roundRect, { x: x + 0.28, y: 2.38, w: 0.5, h: 0.5, rectRadius: 0.14,
      fill: { color: it[1] }, objectName: 'pillar-mark' });
    txt(s, String(i + 1), { x: x + 0.28, y: 2.38, w: 0.5, h: 0.5, align: 'center', valign: 'middle',
      fontSize: 15, bold: true, color: C.text1 });
    txt(s, it[0], { x: x + 0.28, y: 3.0, w: w - 0.56, h: 0.66, fontSize: 17, bold: true,
      color: C.background1, fontFace: THEME.headFontFace, lineSpacingMultiple: 1.05 });
    txt(s, it[2], { x: x + 0.28, y: 3.72, w: w - 0.56, h: 1.3, fontSize: 11.5, color: C.background2,
      transparency: 12, lineSpacingMultiple: 1.22 });
  });

  card(s, { x: M, y: 5.52, w: CW, h: 0.9, fill: C.accent6, flat: true, r: 0.1, name: 'note' });
  txt(s, [
    { text: 'Where this lives:  ', options: { fontSize: 12, bold: true, color: C.accent2 } },
    { text: 'every entitlement above is enforced in model or service code — Tender::mayBeOpenedBy(), VendorApprovalChain::actionableStepFor(), Tender::isReviewUnlockedFor() — not in the view layer.',
      options: { fontSize: 12, color: C.background1 } },
  ], { x: M + 0.28, y: 5.52, w: CW - 0.56, h: 0.9, valign: 'middle', lineSpacingMultiple: 1.15 });
  s.addNotes('If the audience remembers one slide, it should be this one. The three mechanisms are independent: break one and the other two still hold.');
}

// ================================================================ 4. ROLES
{
  const s = sl('CONTENT TINT', 'Opening', 'Who is in the system');
  txt(s, 'Six roles. The words on the left are what this deck calls them; the code uses the slug.', {
    x: M, y: 1.2, w: 10, h: 0.3, fontSize: 13, color: C.text2,
  });

  const roles = [
    ['Purchase Officer', 'admin', C.accent6, 'Runs tenders for the whole university. Clears vendor registrations, and finalizes every award — no contract exists without that step.'],
    ['Tender Owner', 'tender-owner', C.accent1, 'Raises and runs tenders for their own department, and opens what they raised. Scoped to their own records throughout.'],
    ['Purchase Committee', 'purchase-committee', C.accent4, 'The standing committee, ordered by the Super Admin. Approves in that order and holds the opening codes.'],
    ['Per-tender member', 'committee-member', C.accent2, 'Added onto one particular tender only, by an approved request. Reads documents; holds no opening code.'],
    ['Vendor', 'vendor', C.accent5, 'External supplier. Registers, waits for approval, applies to tenders, signs the contract.'],
    ['Super Admin', 'super-admin', C.accent3, 'Configures the system and can act anywhere. The documented fallback opener — and deliberately holds no code.'],
  ];
  const w = (CW - 2 * 0.28) / 3, h = 1.92;
  roles.forEach((r, i) => {
    const x = M + (i % 3) * (w + 0.28);
    const y = 1.66 + Math.floor(i / 3) * (h + 0.3);
    card(s, { x: x, y: y, w: w, h: h, fill: C.background1, r: 0.11, name: 'role' });
    s.addShape(S.roundRect, { x: x + 0.26, y: y + 0.26, w: 0.34, h: 0.34, rectRadius: 0.1,
      fill: { color: r[2] }, objectName: 'role-mark' });
    txt(s, r[0], { x: x + 0.7, y: y + 0.22, w: w - 0.96, h: 0.42, fontSize: 15.5, bold: true,
      color: C.text1, valign: 'middle', fontFace: THEME.headFontFace });
    txt(s, r[1], { x: x + 0.26, y: y + 0.7, w: w - 0.52, h: 0.24, fontSize: 10, bold: true,
      color: r[2], fontFace: 'Courier New', charSpacing: 0 });
    txt(s, r[3], { x: x + 0.26, y: y + 1.02, w: w - 0.52, h: 0.76, fontSize: 11, color: C.text2,
      lineSpacingMultiple: 1.2 });
  });
  caption(s, 'A role is not a department. One person may hold two — a Tender Owner who also sits on the standing committee is common, and the chain filters them out of decisions about their own records.', 5.98);
  s.addNotes('Flag the overlap point: the chain builder removes a candidate from the list of approvers for their own seat, so nobody is ever asked to approve themselves.');
}

// ================================================================ 5. ONE LIST, THREE CHAINS
{
  const s = sl('CONTENT', 'Opening', 'One ordered list, three chains');
  txt(s, 'The Super Admin maintains a single standing committee at /super-admin/purchase-committee. Its order drives everything below.', {
    x: M, y: 1.2, w: 10.6, h: 0.3, fontSize: 13, color: C.text2,
  });

  // The list
  const lw = 3.95;
  card(s, { x: M, y: 1.68, w: lw, h: 3.42, fill: C.background2, line: K.dk2, lw: 0.75, r: 0.11, name: 'list' });
  txt(s, 'The standing committee', { x: M + 0.26, y: 1.86, w: lw - 0.52, h: 0.3, fontSize: 13,
    bold: true, color: C.text1, fontFace: THEME.headFontFace });
  txt(s, 'ordered by the Super Admin', { x: M + 0.26, y: 2.14, w: lw - 0.52, h: 0.24, fontSize: 10,
    color: C.text2, italic: true });
  ['Chairman', 'Member', 'Technical expert', 'Financial expert'].forEach((m, i) => {
    const y = 2.5 + i * 0.6;
    card(s, { x: M + 0.26, y: y, w: lw - 0.52, h: 0.48, fill: C.background1, r: 0.07, name: 'member', flat: true, line: 'DDE3F2', lw: 0.75 });
    disc(s, String(i + 1), { x: M + 0.38, y: y + 0.07, d: 0.34, fill: C.accent6, fs: 10 });
    txt(s, m, { x: M + 0.82, y: y, w: lw - 1.2, h: 0.48, fontSize: 11.5, color: C.text1, valign: 'middle' });
  });

  // Three chains
  const cx = M + lw + 0.92;
  const cwid = CW - lw - 0.92;
  const chains = [
    ['Vendor registrations', 'Part 1', C.accent5, 'Each active member approves in turn before an account goes live.'],
    ['Seats on one tender', 'Part 5', C.accent2, 'A nomination is a pending seat until the same list clears it.'],
    ['Award recommendations', 'Part 6', C.accent4, 'Sequential sign-off, each step with its own OTP. Advisory only.'],
  ];
  // One spine out of the list, fanning into the three chains.
  const spineX = M + lw + 0.46;
  seg(s, M + lw + 0.04, 3.39, 0.42, 0, K.accent6);
  seg(s, spineX, 2.19, 0, 2.4, K.accent6);
  chains.forEach((c, i) => {
    const y = 1.68 + i * 1.2;
    card(s, { x: cx, y: y, w: cwid, h: 1.02, fill: C.background1, line: 'DDE3F2', lw: 0.75, r: 0.1, name: 'chain' });
    s.addShape(S.roundRect, { x: cx + 0.26, y: y + 0.32, w: 0.38, h: 0.38, rectRadius: 0.11,
      fill: { color: c[2] }, objectName: 'chain-mark' });
    txt(s, c[0], { x: cx + 0.78, y: y + 0.14, w: cwid - 1.94, h: 0.34, fontSize: 14.5, bold: true,
      color: C.text1, valign: 'middle', fontFace: THEME.headFontFace });
    txt(s, c[3], { x: cx + 0.78, y: y + 0.52, w: cwid - 1.08, h: 0.38, fontSize: 11, color: C.text2 });
    pill(s, c[1], { x: cx + cwid - 1.02, y: y + 0.2, w: 0.82, h: 0.26, fill: c[2], fs: 9 });
    aR(s, spineX, y + 0.51, 0.42, K.accent6);
  });

  card(s, { x: M, y: 5.38, w: CW, h: 1.3, fill: C.background2, flat: true, r: 0.1, name: 'note' });
  txt(s, [
    { text: 'Reordering the list changes who is asked next. It never rewrites a decision already taken.', options: { fontSize: 13.5, bold: true, color: C.text1, breakLine: true } },
    { text: 'Each chain copies the order and the designation into its own rows at the moment it starts, so a chain already running keeps the order it began with. The same order also decides who holds the opening codes (Part 4).', options: { fontSize: 11.5, color: C.text2 } },
  ], { x: M + 0.3, y: 5.38, w: CW - 0.6, h: 1.3, valign: 'middle', lineSpacingMultiple: 1.2 });
  s.addNotes('This is the slide that explains why the deck keeps saying in order. One list, copied into each chain at start time.');
}

/** Vertical, horizontal, vertical route with the arrowhead on arrival. */
function routeVHV(s, x0, y0, ym, x1, y1, color) {
  seg(s, x0, y0, 0, ym - y0, color);
  if (Math.abs(x1 - x0) > 0.01) seg(s, Math.min(x0, x1), ym, Math.abs(x1 - x0), 0, color);
  aD(s, x1, ym, y1 - ym, color);
}

// ================================================================ DIVIDER: PART 1
pres.addSection({ title: 'Part 1 - Vendor onboarding' });
divider('Part 1 - Vendor onboarding', 'PART ONE',
  'A vendor gets on the list',
  'GST is mandatory — both the number and the certificate. There is one registration shape, not two. Nothing about an account is live until an ordered chain has finished with it.');

// ================================================================ 6. REGISTRATION
{
  const SEC = 'Part 1 - Vendor onboarding';
  const s = sl('CONTENT', SEC, 'A vendor registers, and the officer decides');
  txt(s, 'Three outcomes, and only one of them puts the account straight into service.', {
    x: M, y: 1.2, w: 10, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w1 = 2.678, g1 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w1 + g1));
  const r1y = 1.72, r1h = 1.08;
  const row1 = [
    ['actor', 'Vendor fills the form', 'company, GST number,\nPAN + GST certificates'],
    ['state', 'Account created', 'vendors.status = pending\nsign-in refused meanwhile'],
    ['actor', 'Purchase Officer reviews', '/admin/vendors'],
    ['decision', 'The officer decides', 'clearance recorded on\nofficer_approved_by'],
  ];
  row1.forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w1, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 12, subfs: 9.5 });
    if (i < 3) aR(s, xs[i] + w1 + 0.05, r1y + r1h / 2, g1 - 0.1, K.dk2);
  });

  // Distribution bus from the decision down to three outcomes.
  const r2y = 4.3, r2h = 1.2, busY = 3.46;
  const o = [
    { x: M, w: 3.6, tone: 'stop', head: 'Rejected', sub: 'vendors.status = rejected\nthe vendor is emailed the reason', tag: 'Reject' },
    { x: 4.55, w: 3.6, tone: 'good', head: 'Approved outright', sub: 'no active committee members —\nthe officer’s clearance stands alone', tag: 'Approve, no committee' },
    { x: 8.48, w: 4.23, tone: 'seal', head: 'Sent to the committee', sub: 'vendors.status = committee_review\none approval row per active member', tag: 'Approve, committee active' },
  ];
  const srcX = xs[3] + w1 / 2;
  seg(s, srcX, r1y + r1h, 0, busY - (r1y + r1h), K.dk2);
  const centers = o.map((t) => t.x + t.w / 2);
  const lo = Math.min.apply(null, centers.concat([srcX]));
  const hi = Math.max.apply(null, centers.concat([srcX]));
  seg(s, lo, busY, hi - lo, 0, K.dk2);
  o.forEach((t) => {
    const cx = t.x + t.w / 2;
    aD(s, cx, busY, r2y - busY, K.dk2);
    node(s, { x: t.x, y: r2y, w: t.w, h: r2h, tone: t.tone, head: t.head, sub: t.sub, fs: 13, subfs: 10 });
    tag(s, t.tag, { x: cx - 0.95, y: busY + 0.21, w: 1.9, color: TONE_HEX[t.tone] });
  });

  card(s, { x: M, y: 5.78, w: CW, h: 0.82, fill: C.background2, flat: true, r: 0.1, name: 'note' });
  txt(s, [
    { text: 'Approved outright is not a shortcut.  ', options: { fontSize: 12, bold: true, color: C.text1 } },
    { text: 'It is what happens when the standing committee is empty. Add one active member and the same approval becomes a chain — the officer does nothing differently.', options: { fontSize: 12, color: C.text2 } },
  ], { x: M + 0.3, y: 5.78, w: CW - 0.6, h: 0.82, valign: 'middle', lineSpacingMultiple: 1.15 });
  s.addNotes('Point at the third outcome: committee_review is a holding state, not a decision. The next slide is what happens inside it.');
}

// ================================================================ 7. THE CHAIN
{
  const SEC = 'Part 1 - Vendor onboarding';
  const s = sl('CONTENT TINT', SEC, 'The committee chain, one member at a time');
  txt(s, 'Members are asked in the Super Admin’s order at /purchase-committee/vendor-approvals. Only the lowest pending step is actionable.', {
    x: M, y: 1.2, w: 11, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w1 = 2.678, g1 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w1 + g1));
  const r1y = 1.72, r1h = 1.08;
  const chain = [
    ['seal', 'Member 1 is asked', 'the only actionable step'],
    ['seal', 'Member 2 is asked', 'once member 1 approves'],
    ['seal', '… and so on', 'in the Super Admin’s order'],
    ['seal', 'The final member approves', 'and the chain is complete'],
  ];
  chain.forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w1, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 12.5, subfs: 10 });
    if (i < 3) aR(s, xs[i] + w1 + 0.05, r1y + r1h / 2, g1 - 0.1, K.dk2);
  });

  // The single rejection exit, from anywhere in the chain.
  const stopX = 3.62, stopW = 4.74, stopY = 3.22, stopH = 0.84;
  aD(s, xs[1] + w1 / 2, r1y + r1h, stopY - (r1y + r1h), K.accent3);
  tag(s, 'Any one rejection', { x: xs[1] + w1 / 2 - 0.85, y: 2.9, w: 1.7, color: K.accent3 });
  node(s, { x: stopX, y: stopY, w: stopW, h: stopH, tone: 'stop',
    head: 'The chain ends where it stands', sub: 'vendors.status = rejected  ·  no later member is asked', fs: 12.5, subfs: 10 });

  // Success tail.
  const r3y = 4.62, r3h = 1.02, w3 = 3.85, g3 = 0.27;
  const x3 = [0, 1, 2].map((i) => M + i * (w3 + g3));
  routeVHV(s, xs[3] + w1 / 2, r1y + r1h, 4.26, x3[0] + w3 / 2, r3y, K.accent5);
  const tail = [
    ['good', 'Account approved', 'approved_by = the final approver'],
    ['notify', 'The approval email goes out', 'sent here and nowhere else'],
    ['good', 'The vendor signs in and applies', ''],
  ];
  tail.forEach((n, i) => {
    node(s, { x: x3[i], y: r3y, w: w3, h: r3h, tone: n[0], head: n[1], sub: n[2], fs: 13, subfs: 10 });
    if (i < 2) aR(s, x3[i] + w3 + 0.04, r3y + r3h / 2, g3 - 0.08, K.dk2);
  });

  card(s, { x: M, y: 5.82, w: CW, h: 0.84, fill: C.background1, line: 'DDE3F2', lw: 0.75, r: 0.1, name: 'note' });
  txt(s, [
    { text: 'What the vendor is told while waiting:   ', options: { fontSize: 11.5, bold: true, color: C.text1 } },
    { text: 'pending', options: { fontSize: 11, bold: true, color: K.accent2, fontFace: 'Courier New' } },
    { text: '  “Your account is pending admin approval.”      ', options: { fontSize: 11.5, italic: true, color: C.text2 } },
    { text: 'committee_review', options: { fontSize: 11, bold: true, color: K.accent4, fontFace: 'Courier New' } },
    { text: '  “…cleared and is with the Purchase Committee for final approval.”', options: { fontSize: 11.5, italic: true, color: C.text2 } },
  ], { x: M + 0.3, y: 5.82, w: CW - 0.6, h: 0.84, valign: 'middle', lineSpacingMultiple: 1.2 });
  s.addNotes('The sign-in message changes as the record moves, so a vendor ringing up can be placed in the chain without opening the admin screens.');
}

// ================================================================ 8. WHAT HOLDS THE CHAIN
{
  const SEC = 'Part 1 - Vendor onboarding';
  const s = sl('CONTENT', SEC, 'Four rules, enforced in code');
  txt(s, 'All four live in VendorApprovalChain. None of them depends on a button being hidden, so a guessed URL or a stale browser tab gets nowhere.', {
    x: M, y: 1.2, w: 11.2, h: 0.3, fontSize: 13, color: C.text2,
  });

  const rules = [
    ['No queue-jumping', C.accent2,
      'Only the lowest-ordered pending step can be acted on. A member further down the list who opens the URL early is refused.'],
    ['One rejection is final', C.accent3,
      'Any single rejection ends the chain at that point. No later member is asked, and the requester is told the reason.'],
    ['The officer’s clearance is kept apart', C.accent6,
      'It is stored on officer_approved_by, so approved_by always names the member who finished the chain — not the officer who started it.'],
    ['Everybody involved hears the outcome', C.accent1,
      'The officer, the Super Admins and every member who already approved are all notified, because the officer would otherwise hear nothing for as long as the committee takes.'],
  ];
  const w = (CW - 0.32) / 2, h = 1.72;
  rules.forEach((r, i) => {
    const x = M + (i % 2) * (w + 0.32);
    const y = 1.72 + Math.floor(i / 2) * (h + 0.3);
    card(s, { x: x, y: y, w: w, h: h, fill: C.background2, r: 0.11, name: 'rule' });
    s.addShape(S.roundRect, { x: x + 0.3, y: y + 0.32, w: 0.44, h: 0.44, rectRadius: 0.13,
      fill: { color: r[1] }, objectName: 'rule-mark' });
    txt(s, String(i + 1), { x: x + 0.3, y: y + 0.32, w: 0.44, h: 0.44, align: 'center', valign: 'middle',
      fontSize: 14, bold: true, color: C.background1 });
    txt(s, r[0], { x: x + 0.92, y: y + 0.3, w: w - 1.22, h: 0.48, fontSize: 16, bold: true,
      color: C.text1, valign: 'middle', fontFace: THEME.headFontFace });
    txt(s, r[2], { x: x + 0.92, y: y + 0.84, w: w - 1.22, h: 0.72, fontSize: 11.5, color: C.text2,
      lineSpacingMultiple: 1.22 });
  });

  const gapY = 5.56;
  card(s, { x: M, y: gapY, w: CW, h: 1.04, fill: K.accent3, flat: true, r: 0.1, name: 'gap' });
  txt(s, [
    { text: 'Known gap  ', options: { fontSize: 11, bold: true, color: '4A0010', charSpacing: 1.2, breakLine: true } },
    { text: 'A dropped approval email is not retried. If the mail to the vendor fails at the moment of final approval, the status is still approved and nothing re-sends it — the vendor can sign in, but was never told so.',
      options: { fontSize: 12, color: 'FFFFFF' } },
  ], { x: M + 0.3, y: gapY, w: CW - 0.6, h: 1.04, valign: 'middle', lineSpacingMultiple: 1.2 });
  s.addNotes('The gap is listed here rather than hidden at the back: it is the one part of this chain that can fail quietly.');
}

/** Deck-wide table styling. */
function dtable(s, head, rows, o) {
  const hdr = head.map((h) => ({
    text: h,
    options: { bold: true, color: C.background1, fill: { color: K.accent6 }, fontSize: o.hfs || 11.5,
      valign: 'middle', align: 'left' },
  }));
  const body = rows.map((r, ri) => r.map((cell) => {
    const isObj = cell && typeof cell === 'object';
    const text = isObj ? cell.text : cell;
    const opt = Object.assign({
      color: C.text1, fontSize: o.fs || 11, valign: 'middle',
      fill: { color: ri % 2 ? K.lt2 : K.lt1 },
    }, isObj ? cell.options : {});
    return { text: text, options: opt };
  }));
  s.addTable([hdr].concat(body), {
    placeholder: o.placeholder, x: o.x === undefined ? M : o.x, y: o.y === undefined ? BODY_T : o.y,
    w: o.w === undefined ? CW : o.w, colW: o.colW, rowH: o.rowH || 0.38,
    autoPageSlideStartY: o.y === undefined ? BODY_T : o.y,
    border: { type: 'solid', color: 'D7DEEF', pt: 0.75 },
    margin: o.margin === undefined ? 0.1 : o.margin,
    fontFace: THEME.bodyFontFace, autoPage: false,
  });
}

// ================================================================ DIVIDER: PART 2
pres.addSection({ title: 'Part 2 - Raising a tender' });
divider('Part 2 - Raising a tender', 'PART TWO',
  'Two people can raise a tender',
  'The Purchase Officer runs tenders for the whole university. A Tender Owner runs them for their own department, on screens scoped to their own records — and opens what they raised.');

// ================================================================ 9. OFFICER RAISES
{
  const SEC = 'Part 2 - Raising a tender';
  const s = sl('CONTENT', SEC, 'The Purchase Officer raises a tender');
  txt(s, 'Two forks: who may see it, and whether it appears on the university website.', {
    x: M, y: 1.16, w: 10, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w = 3.85, g = 0.27;
  const xc = [M, M + w + g, M + 2 * (w + g)];
  const cen = xc.map((x) => x + w / 2);

  // Row 1
  const r1y = 1.62, rh = 1.0;
  [['actor', 'Creates the tender', '/admin/tenders/create  ·  status = draft'],
   ['state', 'Adds the detail', 'documents, eligibility criteria,\ntype: online / postal / hybrid'],
   ['decision', 'Private tender?', 'decides who may see it']
  ].forEach((n, i) => {
    node(s, { x: xc[i], y: r1y, w: w, h: rh, tone: n[0], head: n[1], sub: n[2], fs: 13, subfs: 10 });
    if (i < 2) aR(s, xc[i] + w + 0.04, r1y + rh / 2, g - 0.08, K.dk2);
  });

  // Fork from the decision into two visibility outcomes
  const r2y = 3.04, r2h = 0.82, forkY = 2.86;
  seg(s, cen[2], r1y + rh, 0, forkY - (r1y + rh), K.dk2);
  seg(s, cen[1], forkY, cen[2] - cen[1], 0, K.dk2);
  [['Yes', 'Only the named vendors see it', 1], ['No', 'Open to all approved vendors', 2]].forEach((b) => {
    const i = b[2];
    aD(s, cen[i], forkY, r2y - forkY, K.dk2);
    node(s, { x: xc[i], y: r2y, w: w, h: r2h, tone: 'state', head: b[1], sub: '', fs: 12 });
    tag(s, b[0], { x: cen[i] - 0.33, y: forkY + 0.01, w: 0.66 });
  });

  // Merge back and continue
  const r3y = 4.46, mergeY = 4.2;
  seg(s, cen[1], r2y + r2h, 0, mergeY - (r2y + r2h), K.dk2);
  seg(s, cen[2], r2y + r2h, 0, mergeY - (r2y + r2h), K.dk2);
  seg(s, cen[0], mergeY, cen[2] - cen[0], 0, K.dk2);
  aD(s, cen[0], mergeY, r3y - mergeY, K.dk2);
  [['actor', 'Publishes it', 'status = published  ·  visible to vendors'],
   ['decision', 'Show on the university website?', 'the globe button on the tender'],
   ['notify', 'show_on_university = true', 'served by the public JSON feed']
  ].forEach((n, i) => {
    node(s, { x: xc[i], y: r3y, w: w, h: rh, tone: n[0], head: n[1], sub: n[2], fs: 13, subfs: 10 });
    if (i < 2) aR(s, xc[i] + w + 0.04, r3y + rh / 2, g - 0.08, K.dk2);
  });

  // The "No" exit from the website decision
  const r4y = 5.74;
  aD(s, cen[1], r3y + rh, r4y - (r3y + rh), K.dk2);
  tag(s, 'No', { x: cen[1] - 0.33, y: r3y + rh + 0.09, w: 0.66 });
  node(s, { x: xc[1], y: r4y, w: w, h: 0.66, tone: 'state', head: 'Portal only', sub: '', fs: 12 });
  s.addNotes('Both forks are reversible at any time. Unticking the globe takes a tender off the university website within a minute, because the feed is cached for sixty seconds and nothing else is stored downstream.');
}

// ================================================================ 10. OWNER'S ROUTE
{
  const SEC = 'Part 2 - Raising a tender';
  const s = sl('CONTENT TINT', SEC, 'The Tender Owner’s route, end to end');
  txt(s, 'The same shape as the officer’s, with two differences: every screen is scoped to their own tenders, and they open what they raised.', {
    x: M, y: 1.14, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w = 2.678, g = 0.46, rh = 1.02;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w + g));
  const ry = [1.58, 3.2, 4.82];
  const steps = [
    ['actor', 'Signs in', '/tender-owner/dashboard'],
    ['actor', 'Creates a tender', 'status = draft\nowner_id = created_by'],
    ['state', 'Adds the detail', 'documents, eligibility, type,\nprivate vendor picker'],
    ['actor', 'Publishes', 'tenders/{id}/publish\nstatus = published'],
    ['state', 'University display toggle', 'the same feed as the\nofficer’s tenders'],
    ['actor', 'Vendors apply', 'online / postal / hybrid'],
    ['actor', 'Reads the applications', '/tender-owner/applications'],
    ['state', 'Shortlists, rejects,\nor raises a query', ''],
    ['actor', 'May close submissions early', 'tenders/{id}/close'],
    ['seal', 'Starts the opening', 'because they raised it\n— see Part 4'],
    ['actor', 'The officer finalizes the award', 'the owner cannot'],
    ['good', 'Contract', 'sign, extend, terminate,\nwork order, documents'],
  ];
  steps.forEach((n, i) => {
    const r = Math.floor(i / 4), c = i % 4;
    node(s, { x: xs[c], y: ry[r], w: w, h: rh, tone: n[0], head: n[1], sub: n[2], fs: 11.5, subfs: 9 });
    disc(s, String(i + 1), { x: xs[c] - 0.09, y: ry[r] - 0.1, d: 0.3, fill: C.text1, fs: 9.5 });
    if (c < 3) aR(s, xs[c] + w + 0.05, ry[r] + rh / 2, g - 0.1, K.dk2);
  });
  [0, 1].forEach((r) => {
    routeVHV(s, xs[3] + w / 2, ry[r] + rh, ry[r] + rh + 0.3, xs[0] + w / 2, ry[r + 1], K.dk2);
  });

  caption(s, 'Step 11 is the one the owner never performs. The award is finalized by the Purchase Officer on every tender, whoever raised it — and no contract exists without that record.', 6.08, { w: 11.6 });
  s.addNotes('Twelve steps, three rows, read left to right. The two numbers worth calling out are 10 and 11: the owner opens their own tender, but cannot finalize their own award.');
}

// ================================================================ 11. OWNER SCOPE
{
  const SEC = 'Part 2 - Raising a tender';
  const s = sl('TABLE', SEC, 'What a Tender Owner holds');
  txt(s, 'Every screen below is filtered to their own records. ownerShow() returns 403 on somebody else’s tender.', {
    x: M, y: 1.18, w: 11, h: 0.3, fontSize: 12.5, color: C.text2,
  });

  dtable(s,
    ['Area', 'Screen', 'What it allows'],
    [
      ['Tenders', { text: '/tender-owner/tenders', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Only tenders they own. Create, edit, publish, close, delete'],
      ['University display', { text: 'tenders/{id}/university-display', options: { fontFace: 'Courier New', fontSize: 10 } }, 'The same globe toggle as the officer'],
      [{ text: 'Open a tender', options: { bold: true, color: K.accent4 } }, { text: 'tenders/{id}/open-bidding', options: { fontFace: 'Courier New', fontSize: 10 } }, { text: 'Only tenders they raised themselves — see Part 4', options: { bold: true } }],
      ['Collect codes', { text: '/purchase-committee/tenders/{id}/opening-codes', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Reached on their own tenders, to key in the holders’ codes'],
      ['Applications', { text: '/tender-owner/applications', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Shortlist, reject, query, and see the award'],
      ['Per-tender committee', { text: 'tenders/{id}/committee', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Requests a seat for somebody — it is not granted here'],
      ['Contracts', { text: '/tender-owner/contracts', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Sign, extend, terminate, work order, supporting documents'],
      ['Eligibility failures', { text: '/tender-owner/eligibility-failures', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Who was filtered out of their tenders, and why'],
      ['Reports', { text: '/tender-owner/reports', options: { fontFace: 'Courier New', fontSize: 10 } }, 'Their own tenders and contracts only'],
    ],
    { y: 1.58, colW: [2.1, 4.4, 5.593], rowH: 0.395 }
  );

  const cy = 5.72;
  card(s, { x: M, y: cy, w: CW, h: 1.0, fill: C.text1, flat: true, r: 0.1, name: 'cannot' });
  txt(s, [
    { text: 'What they cannot do  ', options: { fontSize: 11, bold: true, color: K.accent2, charSpacing: 1.2, breakLine: true } },
    { text: 'Approve a vendor registration  ·  finalize an award  ·  open a tender somebody else raised  ·  view another owner’s tender', options: { fontSize: 13, color: 'FFFFFF' } },
  ], { x: M + 0.3, y: cy, w: CW - 0.6, h: 1.0, valign: 'middle', lineSpacingMultiple: 1.25 });
  s.addNotes('If somebody asks why an owner can open but not award: opening is a sealed, multi-person act with codes; awarding is a single recorded decision, and the university keeps that with one office.');
}

// ================================================================ 12. UNIVERSITY WEBSITE
{
  const SEC = 'Part 2 - Raising a tender';
  const s = sl('CONTENT', SEC, 'On the university website');
  txt(s, 'The website is not a page in this system. It reads a public JSON feed and renders the list itself.', {
    x: M, y: 1.18, w: 11, h: 0.3, fontSize: 13, color: C.text2,
  });

  // The feed line
  card(s, { x: M, y: 1.66, w: CW, h: 0.82, fill: C.text1, flat: true, r: 0.1, name: 'endpoint' });
  txt(s, [
    { text: 'GET  ', options: { fontSize: 12, bold: true, color: K.accent2, fontFace: 'Courier New' } },
    { text: 'https://tenders.vinayakamissions.edu.in/api/public/university/tenders?limit=10', options: { fontSize: 12.5, color: 'FFFFFF', fontFace: 'Courier New' } },
  ], { x: M + 0.32, y: 1.66, w: CW - 2.9, h: 0.82, valign: 'middle' });
  pill(s, 'NO KEY  ·  CACHED 60s', { x: M + CW - 2.5, y: 1.93, w: 2.18, h: 0.28, fill: K.accent1, fs: 9 });

  // Three conditions
  txt(s, 'A tender appears in the feed only when all three are true', {
    x: M, y: 2.74, w: 7, h: 0.3, fontSize: 13.5, bold: true, color: C.text1, fontFace: THEME.headFontFace,
  });
  const cw = (CW - 2 * 0.28) / 3;
  [['Published', 'status = published', C.accent5],
   ['Ticked for the website', 'show_on_university = true', C.accent1],
   ['Still open', 'the submission deadline has not passed', C.accent2]
  ].forEach((c, i) => {
    const x = M + i * (cw + 0.28);
    card(s, { x: x, y: 3.14, w: cw, h: 1.14, fill: C.background2, r: 0.1, name: 'cond' });
    disc(s, String(i + 1), { x: x + 0.26, y: 3.34, d: 0.38, fill: c[2], fs: 11.5 });
    txt(s, c[0], { x: x + 0.74, y: 3.3, w: cw - 1.0, h: 0.34, fontSize: 14, bold: true, color: C.text1,
      valign: 'middle', fontFace: THEME.headFontFace });
    txt(s, c[1], { x: x + 0.26, y: 3.74, w: cw - 0.52, h: 0.42, fontSize: 10.5, color: C.text2,
      fontFace: 'Courier New' });
  });

  // What it carries / what it does not
  const hw = (CW - 0.32) / 2;
  card(s, { x: M, y: 4.52, w: hw, h: 1.78, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.11, name: 'carries' });
  txt(s, 'What the feed carries', { x: M + 0.28, y: 4.72, w: hw - 0.56, h: 0.32, fontSize: 14, bold: true,
    color: K.accent5, fontFace: THEME.headFontFace });
  txt(s, 'Tender number and title  ·  the public summary staff write separately  ·  type  ·  estimated value and EMD  ·  submission deadline and time  ·  opening date, time and venue  ·  postal address  ·  days remaining',
    { x: M + 0.28, y: 5.1, w: hw - 0.56, h: 1.34, fontSize: 11.5, color: C.text2, lineSpacingMultiple: 1.3 });

  const x2 = M + hw + 0.32;
  card(s, { x: x2, y: 4.52, w: hw, h: 1.78, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.11, name: 'withheld' });
  txt(s, 'What it deliberately does not', { x: x2 + 0.28, y: 4.72, w: hw - 0.56, h: 0.32, fontSize: 14,
    bold: true, color: K.accent3, fontFace: THEME.headFontFace });
  txt(s, 'The internal description  ·  tender documents and annexures  ·  anything at all about bids, applicants or vendors. There is no public page for one tender either: a bidder registers and signs in before reading documents or submitting.',
    { x: x2 + 0.28, y: 5.1, w: hw - 0.56, h: 1.34, fontSize: 11.5, color: C.text2, lineSpacingMultiple: 1.3 });
  s.addNotes('Two ready-made samples sit in docs/university-embed: a WordPress shortcode that renders server-side so Google indexes the list, and a paste-in HTML widget for any other site.');
}

/** Left-pointing connector (arrowhead at the start of the segment). */
function aL(s, x, y, w, color) {
  s.addShape(S.line, { x: x, y: y, w: w, h: 0,
    line: { color: color || K.dk2, width: 1.6, beginArrowType: 'triangle' }, objectName: 'arrow' });
}
/** Up-pointing connector; y is the top of the segment. */
function aU(s, x, y, h, color) {
  s.addShape(S.line, { x: x, y: y, w: 0, h: h,
    line: { color: color || K.dk2, width: 1.6, beginArrowType: 'triangle' }, objectName: 'arrow' });
}

// ================================================================ DIVIDER: PART 3
pres.addSection({ title: 'Part 3 - Bidding' });
divider('Part 3 - Bidding', 'PART THREE',
  'A vendor bids, and nobody reads it',
  'Three ways to submit, one eligibility gate, and a seal that holds against the officer, the owner and the committee alike until the codes are in.');

// ================================================================ 13. VENDOR APPLIES
{
  const SEC = 'Part 3 - Bidding';
  const s = sl('CONTENT', SEC, 'A vendor applies');
  txt(s, 'Eligibility is checked before anything is uploaded, and a failure is recorded rather than silently dropped.', {
    x: M, y: 1.14, w: 11.2, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w4 = 2.678, g4 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w4 + g4));
  const r1y = 1.58, r1h = 0.95;
  [['actor', 'Browses the tenders', '/vendor/tenders'],
   ['actor', 'Opens one', 'reads the notice\nand the criteria'],
   ['decision', 'Eligibility check', 'against the tender’s criteria'],
   ['stop', 'Fails \u2014 failure recorded', 'visible to the officer\nand the owner']
  ].forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w4, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 11.5, subfs: 9 });
    if (i < 3) aR(s, xs[i] + w4 + 0.05, r1y + r1h / 2, g4 - 0.1, i === 2 ? K.accent3 : K.dk2);
  });

  // Fork by tender type
  const w3 = 3.85, g3 = 0.27;
  const x3 = [0, 1, 2].map((i) => M + i * (w3 + g3));
  const c3 = x3.map((x) => x + w3 / 2);
  const r2y = 3.0, r2h = 0.95, busY = 2.78;
  seg(s, xs[2] + w4 / 2, r1y + r1h, 0, busY - (r1y + r1h), K.accent5);
  seg(s, c3[0], busY, c3[2] - c3[0], 0, K.accent5);
  [['online', 'Upload the documents and the bid amount in the portal'],
   ['postal', 'Register intent in the portal, send the documents by post'],
   ['hybrid', 'Either route, or both']
  ].forEach((t, i) => {
    aD(s, c3[i], busY, r2y - busY, K.accent5);
    node(s, { x: x3[i], y: r2y, w: w3, h: r2h, tone: 'state', head: t[1], sub: '', fs: 11.5 });
    tag(s, t[0], { x: c3[i] - 0.45, y: busY + 0.02, w: 0.9, color: K.accent5 });
  });
  tag(s, 'Passes  —  tender type decides the route', { x: xs[2] + w4 / 2 - 1.7, y: busY - 0.26, w: 3.4, color: K.accent5 });

  // Merge to submitted
  const mY = 4.16, r3y = 4.4, r3h = 0.78, r3w = 5.6, r3x = (SW - r3w) / 2;
  c3.forEach((cx) => seg(s, cx, r2y + r2h, 0, mY - (r2y + r2h), K.dk2));
  seg(s, c3[0], mY, c3[2] - c3[0], 0, K.dk2);
  aD(s, SW / 2, mY, r3y - mY, K.dk2);
  node(s, { x: r3x, y: r3y, w: r3w, h: r3h, tone: 'state', head: 'status = submitted',
    sub: 'sealed until the opening — nobody reads the bid, staff included', fs: 13, subfs: 10 });

  // Two things a submitted application still allows
  const fY = 5.3, r4y = 5.48, r4h = 0.78, r4w = 4.76;
  const r4 = [[M, 'May withdraw before the deadline', 'status = withdrawn', 'state'],
              [SW - M - r4w, 'Queries, either way', 'vendor to officer or owner, and back', 'notify']];
  seg(s, SW / 2, r3y + r3h, 0, fY - (r3y + r3h), K.dk2);
  seg(s, r4[0][0] + r4w / 2, fY, (r4[1][0] + r4w / 2) - (r4[0][0] + r4w / 2), 0, K.dk2);
  r4.forEach((n) => {
    aD(s, n[0] + r4w / 2, fY, r4y - fY, K.dk2);
    node(s, { x: n[0], y: r4y, w: r4w, h: r4h, tone: n[3], head: n[1], sub: n[2], fs: 12, subfs: 9.5 });
  });
  s.addNotes('Worth saying out loud: an eligibility failure is a record, not a dead end. The officer and the owner both see who was filtered out and why, so a criterion set too tightly shows up instead of quietly shrinking the field.');
}

// ================================================================ 14. THREE WAYS TO BID
{
  const SEC = 'Part 3 - Bidding';
  const s = sl('CONTENT TINT', SEC, 'Three ways to bid, and what each costs');
  txt(s, 'The tender type is set when the tender is raised and decides how a bid arrives — and how soon a number exists to compare.', {
    x: M, y: 1.18, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const ways = [
    ['Online', C.accent1, 'Documents and the bid amount go into the portal.',
      ['A number exists the moment bids open', 'Ranked spreadsheet goes out automatically', 'Nothing to key in by hand']],
    ['Postal', C.accent2, 'Intent is registered in the portal; the papers come by post.',
      ['No bid amount at opening time', 'The committee records what arrived, scans it in', 'Values keyed in from the physical quotes']],
    ['Hybrid', C.accent4, 'Either route, or both — the vendor chooses.',
      ['Mixed field, compared on one screen', 'Online values rank straight away', 'Postal entries join once keyed in']],
  ];
  const w = (CW - 2 * 0.3) / 3;
  ways.forEach((wy, i) => {
    const x = M + i * (w + 0.3);
    card(s, { x: x, y: 1.66, w: w, h: 3.5, fill: C.background1, r: 0.12, name: 'way' });
    s.addShape(S.roundRect, { x: x + 0.3, y: 1.96, w: 0.52, h: 0.52, rectRadius: 0.15,
      fill: { color: wy[1] }, objectName: 'way-mark' });
    txt(s, String(i + 1), { x: x + 0.3, y: 1.96, w: 0.52, h: 0.52, align: 'center', valign: 'middle',
      fontSize: 16, bold: true, color: C.background1 });
    txt(s, wy[0], { x: x + 0.96, y: 1.96, w: w - 1.26, h: 0.52, fontSize: 20, bold: true, color: C.text1,
      valign: 'middle', fontFace: THEME.headFontFace });
    txt(s, wy[2], { x: x + 0.3, y: 2.62, w: w - 0.6, h: 0.56, fontSize: 12, color: C.text2,
      lineSpacingMultiple: 1.25 });
    wy[3].forEach((b, bi) => {
      const by = 3.3 + bi * 0.58;
      s.addShape(S.ellipse, { x: x + 0.32, y: by + 0.08, w: 0.13, h: 0.13,
        fill: { color: wy[1] }, objectName: 'dot' });
      txt(s, b, { x: x + 0.62, y: by, w: w - 0.94, h: 0.52, fontSize: 11.5, color: C.text1,
        lineSpacingMultiple: 1.18 });
    });
  });

  card(s, { x: M, y: 5.42, w: CW, h: 1.2, fill: C.text1, flat: true, r: 0.11, name: 'consequence' });
  txt(s, [
    { text: 'Why this matters at the opening', options: { fontSize: 13.5, bold: true, color: K.accent2, breakLine: true } },
    { text: 'A purely postal tender has no bid amounts when it opens — they are keyed in afterwards from the physical quotes. That is why the ranked spreadsheet is only emailed when online values already exist: a ranking of nothing would be worse than none.',
      options: { fontSize: 12, color: 'FFFFFF' } },
  ], { x: M + 0.32, y: 5.42, w: CW - 0.64, h: 1.2, valign: 'middle', lineSpacingMultiple: 1.2 });
  s.addNotes('Procurement rules often require a postal route. The cost is that comparison is manual for a while, and the deck says so rather than pretending every tender ranks itself.');
}

// ================================================================ 15. THE SEAL
{
  const SEC = 'Part 3 - Bidding';
  const s = sl('CONTENT DARK', SEC, 'What the seal actually means');
  txt(s, 'Submitted documents and bid values are not readable by anyone until the opening in Part 4 completes. Not by the officer who raised the tender, not by the owner, not by the committee that will judge it.', {
    x: M, y: 1.22, w: 11.6, h: 0.56, fontSize: 14, color: C.background2, transparency: 14, lineSpacingMultiple: 1.2,
  });

  const hw = (CW - 0.4) / 2;
  const sides = [
    ['Before the codes are in', K.accent3, '✕', [
      ['Purchase Officer', 'sees that a bid arrived, not what is in it'],
      ['Tender Owner', 'the same, on their own tender'],
      ['Purchase Committee', 'no documents, no values'],
      ['Per-tender member', 'no access at all until their seat is approved'],
    ]],
    ['Once every code is keyed in', K.accent5, '✓', [
      ['Purchase Committee', 'documents appear in each member’s login'],
      ['Approved per-tender member', 'reads the documents too'],
      ['Vendors', 'told their bids have been opened'],
      ['Ranked spreadsheet', 'emailed, if online bid values exist'],
    ]],
  ];
  sides.forEach((sd, i) => {
    const x = M + i * (hw + 0.4);
    card(s, { x: x, y: 2.0, w: hw, h: 3.62, fill: C.text2, flat: true, r: 0.12, name: 'seal-side' });
    txt(s, sd[0], { x: x + 0.3, y: 2.22, w: hw - 0.6, h: 0.44, fontSize: 18, bold: true,
      color: C.background1, valign: 'middle', fontFace: THEME.headFontFace });
    sd[3].forEach((r, ri) => {
      const y = 2.84 + ri * 0.68;
      s.addShape(S.roundRect, { x: x + 0.3, y: y + 0.04, w: 0.3, h: 0.3, rectRadius: 0.09,
        fill: { color: sd[1] }, objectName: 'seal-mark' });
      txt(s, sd[2], { x: x + 0.3, y: y + 0.04, w: 0.3, h: 0.3, align: 'center', valign: 'middle',
        fontSize: 11, bold: true, color: 'FFFFFF' });
      txt(s, [
        { text: r[0] + '  ', options: { fontSize: 12.5, bold: true, color: C.background1 } },
        { text: r[1], options: { fontSize: 11, color: C.background2, transparency: 22 } },
      ], { x: x + 0.74, y: y, w: hw - 1.04, h: 0.6, valign: 'middle', lineSpacingMultiple: 1.15 });
    });
  });

  card(s, { x: M, y: 5.86, w: CW, h: 0.8, fill: C.accent4, flat: true, r: 0.1, name: 'seal-note' });
  txt(s, 'Starting an opening while bids are still arriving also closes submissions, in the same action. Reading part of the field while more arrives behind it would break the seal.', {
    x: M + 0.32, y: 5.86, w: CW - 0.64, h: 0.8, fontSize: 12.5, color: 'FFFFFF', valign: 'middle',
    lineSpacingMultiple: 1.15,
  });
  s.addNotes('The seal is the reason the code gate exists at all. Everything in Part 4 is machinery for breaking it in public, with several people present.');
}

// ================================================================ DIVIDER: PART 4
pres.addSection({ title: 'Part 4 - The opening gate' });
divider('Part 4 - The opening gate', 'PART FOUR',
  'The opening gate',
  'The heart of the system. Whoever starts an opening never holds a code for it, so no one can open a tender alone — including the Super Admin.');

// ================================================================ 16. WHO OPENS
{
  const SEC = 'Part 4 - The opening gate';
  const s = sl('CONTENT', SEC, 'Who opens, and who holds a code');
  txt(s, 'The fork is decided by one question, answered the same way on the owner’s screen, the officer’s screen and the JSON endpoint.', {
    x: M, y: 1.14, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const dw = 4.6, dx = (SW - dw) / 2, dy = 1.6, dh = 0.74;
  node(s, { x: dx, y: dy, w: dw, h: dh, tone: 'decision', head: 'Who raised the tender?',
    sub: 'Tender::mayBeOpenedBy()', fs: 13.5, subfs: 9.5 });

  const hw = (CW - 0.44) / 2;
  const hc = [M + hw / 2, M + hw + 0.44 + hw / 2];
  const forkY = 2.56, oy = 2.86, oh = 0.76, cy = 3.84, ch = 1.0;
  seg(s, SW / 2, dy + dh, 0, forkY - (dy + dh), K.dk2);
  seg(s, hc[0], forkY, hc[1] - hc[0], 0, K.dk2);

  const branches = [
    ['the Purchase Officer', 'The officer opens it', 'Codes go to the standing Purchase Committee', 'four holders in the Super Admin’s order'],
    ['a Tender Owner', 'That owner opens it', 'Codes go to the standing committee + the Purchase Officer', 'one extra holder, because the opener is not the officer'],
  ];
  branches.forEach((b, i) => {
    aD(s, hc[i], forkY, oy - forkY, K.dk2);
    tag(s, 'Raised by ' + b[0], { x: hc[i] - 1.25, y: forkY + 0.03, w: 2.5 });
    node(s, { x: hc[i] - hw / 2, y: oy, w: hw, h: oh, tone: 'actor', head: b[1], sub: '', fs: 14 });
    aD(s, hc[i], oy + oh, cy - (oy + oh), K.dk2);
    node(s, { x: hc[i] - hw / 2, y: cy, w: hw, h: ch, tone: 'seal', head: b[2], sub: b[3], fs: 12.5, subfs: 10 });
  });

  const my = 5.18, ny = 5.38, nh = 0.8;
  hc.forEach((x) => seg(s, x, cy + ch, 0, my - (cy + ch), K.dk2));
  seg(s, hc[0], my, hc[1] - hc[0], 0, K.dk2);
  aD(s, SW / 2, my, ny - my, K.dk2);
  node(s, { x: M, y: ny, w: CW, h: nh, tone: 'good', head: 'Whoever opened it holds no code',
    sub: 'and a code is never shown to the opener — it reaches the holder’s notification bell and inbox only', fs: 15, subfs: 11 });

  card(s, { x: M, y: 6.32, w: CW, h: 0.5, fill: C.background2, flat: true, r: 0.08, name: 'fallback' });
  txt(s, [
    { text: 'The fallback:  ', options: { fontSize: 11.5, bold: true, color: C.text1 } },
    { text: 'a Super Admin can open either kind — somebody has to be able to act when an owner has left — and deliberately holds no code, so the fallback cannot open a tender alone either.',
      options: { fontSize: 11.5, color: C.text2 } },
  ], { x: M + 0.28, y: 6.32, w: CW - 0.56, h: 0.5, valign: 'middle' });
  s.addNotes('The asymmetry on the right branch is the whole idea: when an owner opens, the Purchase Officer becomes a code holder, so the department cannot open its own tender without the central office in the room.');
}

// ================================================================ 17. ENTITLEMENT TABLE
{
  const SEC = 'Part 4 - The opening gate';
  const s = sl('TABLE', SEC, 'The same question, three answers');
  txt(s, 'One method on the model, so no screen can disagree with another.', {
    x: M, y: 1.18, w: 10, h: 0.3, fontSize: 12.5, color: C.text2,
  });

  dtable(s,
    ['Tender raised by', 'Opens it', 'Also opens it', 'Holds the codes'],
    [
      ['Purchase Officer', { text: 'The officer', options: { bold: true } }, 'Super Admin', 'The standing committee'],
      ['Tender Owner', { text: 'That owner', options: { bold: true } }, 'Super Admin', { text: 'The standing committee + the Purchase Officer', options: { bold: true, color: K.accent4 } }],
    ],
    { y: 1.6, colW: [2.9, 2.6, 2.6, 3.993], rowH: 0.48, fs: 12, hfs: 12 }
  );

  const facts = [
    ['Opening closes submissions', C.accent2,
      'Starting an opening while bids are still arriving closes them in the same action. It spares the opener the old two-step of Close, then Open.'],
    ['Opening early is allowed', C.accent1,
      'A tender can be opened before the advertised date — but the confirmation says so first, so it cannot happen by accident.'],
    ['The opener never sees a code', C.accent4,
      'Codes reach the holders’ notification bells and inboxes. The opener learns each one when the holder reads it out.'],
  ];
  const w = (CW - 2 * 0.3) / 3;
  facts.forEach((f, i) => {
    const x = M + i * (w + 0.3);
    card(s, { x: x, y: 3.12, w: w, h: 1.86, fill: C.background2, r: 0.11, name: 'fact' });
    s.addShape(S.roundRect, { x: x + 0.28, y: 3.4, w: 0.38, h: 0.38, rectRadius: 0.11,
      fill: { color: f[1] }, objectName: 'fact-mark' });
    txt(s, f[0], { x: x + 0.28, y: 3.9, w: w - 0.56, h: 0.6, fontSize: 14.5, bold: true, color: C.text1,
      fontFace: THEME.headFontFace, lineSpacingMultiple: 1.05 });
    txt(s, f[2], { x: x + 0.28, y: 4.22, w: w - 0.56, h: 0.64, fontSize: 11, color: C.text2,
      lineSpacingMultiple: 1.2 });
  });

  card(s, { x: M, y: 5.22, w: CW, h: 1.4, fill: C.text1, flat: true, r: 0.11, name: 'why' });
  txt(s, [
    { text: 'Why a standing committee, rather than a committee per tender', options: { fontSize: 14, bold: true, color: K.accent2, breakLine: true } },
    { text: 'The set of people who can open a tender must not change from tender to tender — that is what a standing committee is for. It is also why somebody added to a single tender holds no opening code: approving them mid-opening neither delays the opening nor adds a code to collect.',
      options: { fontSize: 12.5, color: 'FFFFFF' } },
  ], { x: M + 0.32, y: 5.22, w: CW - 0.64, h: 1.4, valign: 'middle', lineSpacingMultiple: 1.22 });
  s.addNotes('Keep this slide for the question that always comes: why not let each tender pick its own openers. Answer: because then the department choosing the openers could choose friendly ones.');
}

// ================================================================ 18. COLLECTING CODES
{
  const SEC = 'Part 4 - The opening gate';
  const s = sl('CONTENT TINT', SEC, 'Collecting the codes');
  txt(s, 'Holders read their codes out in person. The opener keys them in together, on one screen, and may key in part of the set.', {
    x: M, y: 1.14, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w4 = 2.678, g4 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w4 + g4));
  const r1y = 1.56, r1h = 1.0;
  [['notify', 'Each holder gets their code', 'in their own login\nand by email'],
   ['state', 'The mail result is recorded', 'per holder — accepted,\nrefused, failed, no address'],
   ['actor', 'Holders read them out', 'in person, together'],
   ['actor', 'The opener keys them in', '/purchase-committee/\ntenders/{id}/opening-codes']
  ].forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w4, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 11.5, subfs: 9 });
    disc(s, String(i + 1), { x: xs[i] - 0.09, y: r1y - 0.1, d: 0.3, fill: C.text1, fs: 9.5 });
    if (i < 3) aR(s, xs[i] + w4 + 0.05, r1y + r1h / 2, g4 - 0.1, K.dk2);
  });

  // The decision, and the partial loop back
  const dy = 2.98, dh = 0.8;
  const dcx = xs[3] + w4 / 2;
  aD(s, dcx, r1y + r1h, dy - (r1y + r1h), K.dk2);
  node(s, { x: xs[3], y: dy, w: w4, h: dh, tone: 'decision', head: 'Every code in?', sub: '', fs: 13 });

  const pw = 3.7, px = 6.0;
  node(s, { x: px, y: dy, w: pw, h: dh, tone: 'state', head: 'Partly in \u2014 and saved',
    sub: 'the rest stay outstanding  \u00b7  resend available per holder', fs: 12, subfs: 9.5 });
  aL(s, px + pw + 0.04, dy + dh / 2, xs[3] - (px + pw) - 0.08, K.accent2);
  // loop back up into step 4
  seg(s, px + pw / 2, 2.78, 0, dy - 2.78, K.accent2);
  seg(s, px + pw / 2, 2.78, (xs[3] + 0.5) - (px + pw / 2), 0, K.accent2);
  aU(s, xs[3] + 0.5, r1y + r1h, 2.78 - (r1y + r1h), K.accent2);

  // Opened
  const oy = 4.26, oh = 0.74;
  aD(s, dcx, dy + dh, oy - (dy + dh), K.accent5);
  tag(s, 'Yes', { x: dcx - 0.33, y: dy + dh + 0.07, w: 0.66, color: K.accent5 });
  node(s, { x: M, y: oy, w: CW, h: oh, tone: 'good', head: 'The tender is open',
    sub: 'bid_opening_at and review_opened_at are set', fs: 15, subfs: 11 });

  // Three consequences
  const w3 = 3.85, g3 = 0.27;
  const x3 = [0, 1, 2].map((i) => M + i * (w3 + g3));
  const c3 = x3.map((x) => x + w3 / 2);
  const cbY = 5.22, r3y = 5.44, r3h = 0.96;
  seg(s, SW / 2, oy + oh, 0, cbY - (oy + oh), K.dk2);
  seg(s, c3[0], cbY, c3[2] - c3[0], 0, K.dk2);
  [['notify', 'Vendors are told', 'their bids have been opened'],
   ['seal', 'Documents reach the committee', 'in each member’s own login'],
   ['notify', 'Ranked bid spreadsheet emailed', 'only if online bid values exist']
  ].forEach((n, i) => {
    aD(s, c3[i], cbY, r3y - cbY, K.dk2);
    node(s, { x: x3[i], y: r3y, w: w3, h: r3h, tone: n[0], head: n[1], sub: n[2], fs: 12.5, subfs: 10 });
  });
  s.addNotes('The loop on the right is the realistic case: four holders, three in the room, one on the phone. Partial entry is saved and the rest stay outstanding, with a resend per holder.');
}

// ================================================================ 19. MAIL REPORT
{
  const SEC = 'Part 4 - The opening gate';
  const s = sl('CONTENT', SEC, 'What the mail report tells you');
  txt(s, 'Each holder’s row on the collect-codes screen carries what the mail server said about their code.', {
    x: M, y: 1.18, w: 11, h: 0.3, fontSize: 13, color: C.text2,
  });

  const rows = [
    ['Accepted by the mail server', 'The relay took it and gave it a queue ID', 'Not conclusive', C.accent2, false],
    ['Refused by the mail server', 'Rejected during the SMTP conversation — nothing was sent', 'Conclusive', C.accent3, true],
    ['Send failed', 'Failed outside the conversation', 'Conclusive', C.accent3, true],
    ['No email address', 'Nothing on the account; the code is in their login only', 'Conclusive', C.accent6, true],
  ];
  rows.forEach((r, i) => {
    const y = 1.64 + i * 0.84;
    card(s, { x: M, y: y, w: CW, h: 0.72, fill: i % 2 ? C.background2 : C.background1,
      line: 'DDE3F2', lw: 0.75, r: 0.09, name: 'mailrow', flat: true });
    s.addShape(S.roundRect, { x: M + 0.26, y: y + 0.19, w: 0.34, h: 0.34, rectRadius: 0.1,
      fill: { color: r[3] }, objectName: 'mail-mark' });
    txt(s, r[0], { x: M + 0.74, y: y, w: 3.5, h: 0.72, fontSize: 13, bold: true, color: C.text1,
      valign: 'middle' });
    txt(s, r[1], { x: M + 4.34, y: y, w: 5.6, h: 0.72, fontSize: 11.5, color: C.text2, valign: 'middle' });
    pill(s, r[2], { x: M + CW - 1.86, y: y + 0.22, w: 1.6, h: 0.28,
      fill: r[4] ? K.accent6 : K.accent2, fs: 9, color: r[4] ? 'FFFFFF' : '3A2600' });
  });

  card(s, { x: M, y: 5.12, w: CW, h: 1.5, fill: C.text1, flat: true, r: 0.11, name: 'warn' });
  txt(s, [
    { text: '“Accepted” is not “delivered”', options: { fontSize: 16, bold: true, color: K.accent2, breakLine: true } },
    { text: 'A relay can accept an address whose domain resolves but has no mailbox, then bounce it minutes later, out of band. Gmail does exactly this. Whatever the status says, the code is always in the holder’s login notifications — and that is the reliable path.',
      options: { fontSize: 12.5, color: 'FFFFFF' } },
  ], { x: M + 0.34, y: 5.12, w: CW - 0.68, h: 1.5, valign: 'middle', lineSpacingMultiple: 1.25 });
  s.addNotes('Say plainly that the mail column is diagnostic, not a guarantee. If a holder says they have no code, the answer is to look in their notification bell, not to resend and hope.');
}

// ================================================================ DIVIDER: PART 5
pres.addSection({ title: 'Part 5 - The committee' });
divider('Part 5 - The committee', 'PART FIVE',
  'Seats, and who may read what',
  'Inviting somebody onto a single tender does not add them. It files a pending seat and asks the same standing committee, in the same order.');

// ================================================================ 20. A SEAT ON ONE TENDER
{
  const SEC = 'Part 5 - The committee';
  const s = sl('CONTENT', SEC, 'Adding a member to one tender');
  txt(s, 'A nomination grants nothing by itself. Until the chain finishes, the candidate is not even told they were put forward.', {
    x: M, y: 1.12, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w4 = 2.678, g4 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w4 + g4));
  const r1y = 1.54, r1h = 1.0;
  [['actor', 'Somebody is put forward', 'tenders/{id}/committee/add\nby the owner or the officer'],
   ['state', 'A pending seat is filed', 'tender_committee.status = pending\nthe candidate is not told'],
   ['seal', 'The standing committee is asked', 'in the Super Admin’s order\n/committee-requests'],
   ['good', 'The seat is approved', 'and now the candidate\nis emailed']
  ].forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w4, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 11.5, subfs: 9 });
    disc(s, String(i + 1), { x: xs[i] - 0.09, y: r1y - 0.1, d: 0.3, fill: C.text1, fs: 9.5 });
    if (i < 3) aR(s, xs[i] + w4 + 0.05, r1y + r1h / 2, g4 - 0.1, K.dk2);
  });

  // Rejection exit from the chain
  const sx = 6.0, sw = 5.4, sy = 2.96, shh = 0.8;
  aD(s, xs[2] + w4 / 2, r1y + r1h, sy - (r1y + r1h), K.accent3);
  tag(s, 'Any one rejection', { x: xs[2] + w4 / 2 - 0.85, y: 2.62, w: 1.7, color: K.accent3 });
  node(s, { x: sx, y: sy, w: sw, h: shh, tone: 'stop', head: 'The seat is rejected',
    sub: 'no later member is asked  ·  the requester is told the reason', fs: 12.5, subfs: 9.5 });

  // Success continues on the left
  const w2 = 5.9, g2 = 0.3;
  const x2 = [M, M + w2 + g2];
  const c2 = x2.map((x) => x + w2 / 2);
  const r3y = 4.2, r3h = 0.9;
  routeVHV(s, 12.3, r1y + r1h, 4.0, c2[0], r3y, K.accent5);
  node(s, { x: x2[0], y: r3y, w: w2, h: r3h, tone: 'state', head: 'They can see the tender',
    sub: 'Tender::isReviewUnlockedFor() now accepts their approved seat', fs: 13, subfs: 10 });
  aR(s, x2[0] + w2 + 0.04, r3y + r3h / 2, g2 - 0.08, K.dk2);
  node(s, { x: x2[1], y: r3y, w: w2, h: r3h, tone: 'decision', head: 'Is the tender open yet?',
    sub: '', fs: 13.5 });

  // Two outcomes
  const fY = 5.3, r4y = 5.5, r4h = 0.9;
  seg(s, c2[1], r3y + r3h, 0, fY - (r3y + r3h), K.dk2);
  seg(s, c2[0], fY, c2[1] - c2[0], 0, K.dk2);
  [['Not yet', 'state', 'They wait — holding no opening code', 'documents arrive once the committee’s codes are all in', 0],
   ['Open', 'good', 'They read the documents immediately', 'the seal was already broken by the code holders', 1]
  ].forEach((b) => {
    const i = b[4];
    aD(s, c2[i], fY, r4y - fY, K.dk2);
    tag(s, b[0], { x: c2[i] - 0.42, y: fY + 0.02, w: 0.84 });
    node(s, { x: x2[i], y: r4y, w: w2, h: r4h, tone: b[1], head: b[2], sub: b[3], fs: 12.5, subfs: 9.5 });
  });
  s.addNotes('Step 2 is the part people are surprised by. Nobody is told they have been nominated, so a rejected nomination never becomes an awkward conversation.');
}

// ================================================================ 21. WHY NO CODE
{
  const SEC = 'Part 5 - The committee';
  const s = sl('CONTENT TINT', SEC, 'Why a per-tender member holds no code');
  txt(s, 'Three statements, in order. Together they explain why adding somebody to a tender cannot weaken the seal.', {
    x: M, y: 1.18, w: 11.2, h: 0.3, fontSize: 13, color: C.text2,
  });

  const steps = [
    ['The openers must not change', C.accent4,
      'Who can open a tender has to be the same from tender to tender. That is what a standing committee is for, so a per-tender seat adds no code at all.'],
    ['Reading is a separate question', C.accent1,
      'Tender::isReviewUnlockedFor() accepts either a collected opening code or an approved seat on that tender. A seat is enough to read, and never enough to open.'],
    ['So the seal is untouched', C.accent5,
      'Nothing is readable until review is open, and only code holders can get it there. The committee that breaks the seal is still the standing one, whoever joins afterwards.'],
  ];
  const w = (CW - 2 * 0.3) / 3;
  steps.forEach((st, i) => {
    const x = M + i * (w + 0.3);
    card(s, { x: x, y: 1.66, w: w, h: 2.7, fill: C.background1, r: 0.12, name: 'why' });
    disc(s, String(i + 1), { x: x + 0.3, y: 1.94, d: 0.46, fill: st[1], fs: 14 });
    txt(s, st[0], { x: x + 0.3, y: 2.52, w: w - 0.6, h: 0.76, fontSize: 16.5, bold: true, color: C.text1,
      fontFace: THEME.headFontFace, lineSpacingMultiple: 1.05 });
    txt(s, st[2], { x: x + 0.3, y: 3.18, w: w - 0.6, h: 1.12, fontSize: 11.5, color: C.text2,
      lineSpacingMultiple: 1.22 });
    if (i < 2) {
      txt(s, '→', { x: x + w + 0.02, y: 2.6, w: 0.26, h: 0.4, align: 'center', valign: 'middle',
        fontSize: 20, bold: true, color: C.accent6 });
    }
  });

  const hw = (CW - 0.32) / 2;
  card(s, { x: M, y: 4.56, w: hw, h: 1.02, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.1, name: 'f1' });
  txt(s, [
    { text: 'Nobody approves their own seat', options: { fontSize: 13, bold: true, color: C.text1, breakLine: true } },
    { text: 'The candidate is filtered out when the chain is built, so they can never be asked.', options: { fontSize: 11.5, color: C.text2 } },
  ], { x: M + 0.28, y: 4.56, w: hw - 0.56, h: 1.02, valign: 'middle', lineSpacingMultiple: 1.2 });

  const x2 = M + hw + 0.32;
  card(s, { x: x2, y: 4.56, w: hw, h: 1.02, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.1, name: 'f2' });
  txt(s, [
    { text: 'Five designations, one meaning', options: { fontSize: 13, bold: true, color: C.text1, breakLine: true } },
    { text: 'chairman, member, observer, technical_expert, financial_expert — a label on the seat, not a different level of access.', options: { fontSize: 11.5, color: C.text2 } },
  ], { x: x2 + 0.28, y: 4.56, w: hw - 0.56, h: 1.02, valign: 'middle', lineSpacingMultiple: 1.2 });

  card(s, { x: M, y: 5.78, w: CW, h: 0.84, fill: C.text1, flat: true, r: 0.1, name: 'bottom' });
  txt(s, [
    { text: 'Counted where it matters:  ', options: { fontSize: 12.5, bold: true, color: K.accent2 } },
    { text: 'Tender::committeeMembers() is scoped to seats that are both approved and active, so a rejected nomination or a deactivated account never silently counts towards a quorum.',
      options: { fontSize: 12.5, color: 'FFFFFF' } },
  ], { x: M + 0.32, y: 5.78, w: CW - 0.64, h: 0.84, valign: 'middle', lineSpacingMultiple: 1.18 });
  s.addNotes('Three statements, read left to right, answer the objection that adding members dilutes the control. It does not, because reading and opening are different permissions.');
}

// ================================================================ 22. COMMITTEE REVIEW
{
  const SEC = 'Part 5 - The committee';
  const s = sl('CONTENT', SEC, 'Committee review');
  txt(s, 'Once review is open, the documents are in every member’s own login. A postal or hybrid tender takes one extra lane first.', {
    x: M, y: 1.1, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w4 = 2.678, g4 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w4 + g4));
  const r1y = 1.5, r1h = 0.96;
  [['state', 'Review opens', 'review_opened_at is set'],
   ['seal', 'Documents in each login', '/purchase-committee/\ntenders/{id}/documents'],
   ['state', 'The applications list', ''],
   ['decision', 'Postal or hybrid?', '']
  ].forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w4, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 12, subfs: 9 });
    if (i < 3) aR(s, xs[i] + w4 + 0.05, r1y + r1h / 2, g4 - 0.1, K.dk2);
  });

  // Yes lane: the physical documents
  const laneY = 2.9, laneH = 0.9;
  const lx = [xs[0], xs[1], xs[2]];
  const lc = lx.map((x) => x + w4 / 2);
  seg(s, xs[3] + w4 / 2, r1y + r1h, 0, 2.7 - (r1y + r1h), K.dk2);
  seg(s, lc[0], 2.7, (xs[3] + w4 / 2) - lc[0], 0, K.dk2);
  aD(s, lc[0], 2.7, laneY - 2.7, K.dk2);
  tag(s, 'Yes — record what physically arrived', { x: 5.2, y: 2.58, w: 3.4 });
  [['actor', 'Record what arrived', '/received-documents'],
   ['actor', 'Scan it in', 'attached to the application'],
   ['actor', 'Key in the bid value', 'from the physical quote']
  ].forEach((n, i) => {
    node(s, { x: lx[i], y: laneY, w: w4, h: laneH, tone: n[0], head: n[1], sub: n[2], fs: 12, subfs: 9.5 });
    if (i < 2) aR(s, lx[i] + w4 + 0.05, laneY + laneH / 2, g4 - 0.1, K.dk2);
  });

  // Both lanes meet
  const w2 = 5.9, g2 = 0.3;
  const x2 = [M, M + w2 + g2];
  const c2 = x2.map((x) => x + w2 / 2);
  const busY = 4.06, r3y = 4.3, r3h = 0.92;
  seg(s, lc[2], laneY + laneH, 0, busY - (laneY + laneH), K.dk2);
  seg(s, 12.1, r1y + r1h, 0, busY - (r1y + r1h), K.dk2);
  tag(s, 'No', { x: 12.1 - 0.33, y: r1y + r1h + 0.12, w: 0.66 });
  seg(s, c2[0], busY, 12.1 - c2[0], 0, K.dk2);
  [['state', 'Compare the bids side by side', 'online values already there, postal values once keyed in', 0],
   ['notify', 'Comment on an application', 'visible to the committee and to the officer', 1]
  ].forEach((n) => {
    const i = n[3];
    aD(s, c2[i], busY, r3y - busY, K.dk2);
    node(s, { x: x2[i], y: r3y, w: w2, h: r3h, tone: n[0], head: n[1], sub: n[2], fs: 13, subfs: 10 });
  });

  card(s, { x: M, y: 5.52, w: CW, h: 1.12, fill: C.background2, flat: true, r: 0.11, name: 'note' });
  txt(s, [
    { text: 'What the committee does not do here', options: { fontSize: 13.5, bold: true, color: C.text1, breakLine: true } },
    { text: 'It does not award. The comparisons and the comments feed the Purchase Officer’s finalization in Part 6 — and the committee’s own award recommendation, if one is raised, is advisory. A purely postal tender has no bid amounts at all until this lane is done.',
      options: { fontSize: 12, color: C.text2 } },
  ], { x: M + 0.32, y: 5.52, w: CW - 0.64, h: 1.12, valign: 'middle', lineSpacingMultiple: 1.2 });
  s.addNotes('The lane on the left exists because paper arrives at a desk, not in a database. Nothing can be ranked until somebody records what turned up and reads the number off the quote.');
}

// ================================================================ DIVIDER: PART 6
pres.addSection({ title: 'Part 6 - Award and contract' });
divider('Part 6 - Award and contract', 'PART SIX',
  'Advice, decision, contract',
  'The committee recommends and the officer decides. Only one of those two is required — and only one of them can be blocked.');

// ================================================================ 23. ADVICE VS DECISION
{
  const SEC = 'Part 6 - Award and contract';
  const s = sl('CONTENT', SEC, 'Two things, often confused');
  txt(s, 'Both are called “the award” in conversation. Only the second one creates a contract.', {
    x: M, y: 1.18, w: 11, h: 0.3, fontSize: 13, color: C.text2,
  });

  const hw = (CW - 0.4) / 2;
  const cols = [
    ['Award recommendation', 'ADVISORY', C.accent2, '/purchase-committee/applications/{id}/recommend', [
      'Raised optionally — many tenders never have one',
      'The committee signs off in order, each step with its own OTP',
      'A member may support, oppose, or simply remark',
      'The outcome is shown to the officer as advice',
    ]],
    ['Award finalization', 'REQUIRED', C.accent5, '/purchase-committee/tenders/{id}/finalize-award', [
      'The Purchase Officer’s own recorded decision',
      'Records the chosen applicant and the awarded value',
      'No contract exists anywhere without this record',
      'Can be revoked, with a reason, which reopens it',
    ]],
  ];
  cols.forEach((c, i) => {
    const x = M + i * (hw + 0.4);
    card(s, { x: x, y: 1.62, w: hw, h: 3.36, fill: C.background2, r: 0.12, name: 'award-col' });
    pill(s, c[1], { x: x + 0.3, y: 1.9, w: 1.42, h: 0.3, fill: c[2], fs: 9.5,
      color: i === 0 ? '3A2600' : 'FFFFFF' });
    txt(s, c[0], { x: x + 0.3, y: 2.3, w: hw - 0.6, h: 0.5, fontSize: 22, bold: true, color: C.text1,
      fontFace: THEME.headFontFace, valign: 'middle' });
    txt(s, c[3], { x: x + 0.3, y: 2.84, w: hw - 0.6, h: 0.3, fontSize: 10, color: C.text2,
      fontFace: 'Courier New' });
    c[4].forEach((b, bi) => {
      const by = 3.26 + bi * 0.43;
      s.addShape(S.ellipse, { x: x + 0.32, y: by + 0.09, w: 0.12, h: 0.12,
        fill: { color: c[2] }, objectName: 'dot' });
      txt(s, b, { x: x + 0.6, y: by, w: hw - 0.9, h: 0.38, fontSize: 11.5, color: C.text1, valign: 'middle' });
    });
  });

  card(s, { x: M, y: 5.2, w: CW, h: 1.44, fill: C.text1, flat: true, r: 0.11, name: 'why-gate' });
  txt(s, [
    { text: 'Why the officer’s decision is the gate, and not the committee’s chain', options: { fontSize: 14, bold: true, color: K.accent2, breakLine: true } },
    { text: 'The committee’s view is advisory, and the officer weighs it alongside the bid values and the documents that arrived. What the officer cannot do is award without putting the decision on record. The single exception: a recorded committee ',
      options: { fontSize: 12.5, color: 'FFFFFF' } },
    { text: 'rejection', options: { fontSize: 12.5, bold: true, color: K.accent3 } },
    { text: ' does block the award — letting one through over an explicit rejection would make that step meaningless.',
      options: { fontSize: 12.5, color: 'FFFFFF' } },
  ], { x: M + 0.34, y: 5.2, w: CW - 0.68, h: 1.44, valign: 'middle', lineSpacingMultiple: 1.22 });
  s.addNotes('This is the slide that settles the recurring argument about whether the committee awards the tender. It does not; it advises, and it can veto by formally rejecting.');
}

// ================================================================ 24. THE AWARD GATE
{
  const SEC = 'Part 6 - Award and contract';
  const s = sl('CONTENT TINT', SEC, 'The award gate');
  txt(s, 'One decision point, two inputs, and a record that cannot be skipped.', {
    x: M, y: 1.14, w: 11, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w4 = 2.678, g4 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w4 + g4));
  const r1y = 1.58, r1h = 1.0;
  [['state', 'Tender reviewed', 'comments and comparisons in'],
   ['actor', 'The officer finalizes', '/purchase-committee/\ntenders/{id}/finalize-award'],
   ['decision', 'Is the gate clear?', 'AwardRecommendation::\ngateBlocksAward()'],
   ['good', 'Application awarded', 'status = awarded']
  ].forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w4, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 12, subfs: 9 });
    if (i < 3) aR(s, xs[i] + w4 + 0.05, r1y + r1h / 2, g4 - 0.1, i === 2 ? K.accent5 : K.dk2);
  });

  // The blocking input, arriving from below-left
  const bw = 5.4, by = 2.98, bh = 0.84;
  node(s, { x: M, y: by, w: bw, h: bh, tone: 'stop', head: 'A recorded committee rejection blocks it',
    sub: 'it must be withdrawn or revisited first', fs: 12.5, subfs: 9.5 });
  seg(s, M + bw + 0.04, by + bh / 2, 7.2 - (M + bw + 0.04), 0, K.accent3);
  aU(s, 7.2, r1y + r1h, (by + bh / 2) - (r1y + r1h), K.accent3);

  // The refusal exit
  const fx = 8.2, fw = 4.51;
  aD(s, 9.3, r1y + r1h, by - (r1y + r1h), K.accent3);
  tag(s, 'No', { x: 9.37, y: 2.66, w: 0.62, color: K.accent3 });
  node(s, { x: fx, y: by, w: fw, h: bh, tone: 'stop', head: 'The award is refused, with the reason',
    sub: 'nothing is recorded against the applicant', fs: 12.5, subfs: 9.5 });

  // What a finalization puts on record
  card(s, { x: M, y: 4.16, w: CW, h: 1.62, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.12, name: 'record' });
  txt(s, 'What a finalization puts on record', { x: M + 0.32, y: 4.32, w: 8, h: 0.34, fontSize: 14.5,
    bold: true, color: C.text1, fontFace: THEME.headFontFace });
  const chips = ['Chosen applicant', 'Discussion mode', 'When it happened', 'Who took part',
    'Committee summary', 'Decision note', 'Awarded value'];
  const cwid = 1.548, cgap = 0.149;
  chips.forEach((ch, i) => {
    const x = M + 0.18 + i * (cwid + cgap);
    card(s, { x: x, y: 4.78, w: cwid, h: 0.78, fill: C.background2, flat: true, r: 0.08, name: 'chip' });
    txt(s, ch, { x: x + 0.08, y: 4.78, w: cwid - 0.16, h: 0.78, fontSize: 10, bold: true,
      color: C.text1, align: 'center', valign: 'middle', lineSpacingMultiple: 1.1 });
  });

  card(s, { x: M, y: 5.98, w: CW, h: 0.66, fill: C.accent6, flat: true, r: 0.09, name: 'revoke' });
  txt(s, [
    { text: 'Revocable:  ', options: { fontSize: 12, bold: true, color: K.accent2 } },
    { text: 'a finalization can be revoked, with a reason, which reopens the decision. The record of the first decision stays.', options: { fontSize: 12, color: 'FFFFFF' } },
  ], { x: M + 0.3, y: 5.98, w: CW - 0.6, h: 0.66, valign: 'middle' });
  s.addNotes('Trace the two arrows into the gate: the officer from the left, a blocking rejection from below. The seven chips are the audit trail that makes the decision reviewable a year later.');
}

// ================================================================ 25. CONTRACT
{
  const SEC = 'Part 6 - Award and contract';
  const s = sl('CONTENT', SEC, 'From an award to an active contract');
  txt(s, 'A contract is created from the awarded application, carrying a Work Order or a Purchase Order.', {
    x: M, y: 1.14, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const w4 = 2.678, g4 = 0.46;
  const xs = [0, 1, 2, 3].map((i) => M + i * (w4 + g4));
  const r1y = 1.58, r1h = 0.96;
  [['good', 'Application awarded', ''],
   ['state', 'Contract created', 'with a Work Order\nor a Purchase Order'],
   ['actor', 'Both sides sign', 'the vendor, and\nthe university'],
   ['good', 'status = active', '']
  ].forEach((n, i) => {
    node(s, { x: xs[i], y: r1y, w: w4, h: r1h, tone: n[0], head: n[1], sub: n[2], fs: 12.5, subfs: 9.5 });
    if (i < 3) aR(s, xs[i] + w4 + 0.05, r1y + r1h / 2, g4 - 0.1, K.dk2);
  });

  const w3 = 3.85, g3 = 0.27;
  const x3 = [0, 1, 2].map((i) => M + i * (w3 + g3));
  const c3 = x3.map((x) => x + w3 / 2);
  const busY = 3.0, r2y = 3.2, r2h = 0.9;
  seg(s, xs[3] + w4 / 2, r1y + r1h, 0, busY - (r1y + r1h), K.dk2);
  seg(s, c3[0], busY, Math.max(c3[2], xs[3] + w4 / 2) - c3[0], 0, K.dk2);
  [['state', 'Extended', 'a later end date recorded'],
   ['stop', 'Terminated', 'with a reason'],
   ['good', 'Runs its course', 'expired, or completed']
  ].forEach((n, i) => {
    aD(s, c3[i], busY, r2y - busY, K.dk2);
    node(s, { x: x3[i], y: r2y, w: w3, h: r2h, tone: n[0], head: n[1], sub: n[2], fs: 13, subfs: 10 });
  });

  const hw = (CW - 0.32) / 2;
  card(s, { x: M, y: 4.44, w: hw, h: 2.18, fill: C.background2, r: 0.12, name: 'manage' });
  txt(s, 'Who manages it', { x: M + 0.3, y: 4.64, w: hw - 0.6, h: 0.34, fontSize: 15, bold: true,
    color: C.text1, fontFace: THEME.headFontFace });
  [['Purchase Officer', 'any contract in the university'],
   ['Tender Owner', 'contracts from the tenders they raised'],
   ['Both can', 'sign, extend, terminate, attach the work order and supporting documents']
  ].forEach((r, i) => {
    const y = 5.08 + i * 0.5;
    s.addShape(S.ellipse, { x: M + 0.32, y: y + 0.13, w: 0.13, h: 0.13, fill: { color: C.accent1 }, objectName: 'dot' });
    txt(s, [
      { text: r[0] + '  ', options: { fontSize: 12, bold: true, color: C.text1 } },
      { text: r[1], options: { fontSize: 11, color: C.text2 } },
    ], { x: M + 0.6, y: y, w: hw - 0.9, h: 0.46, valign: 'middle', lineSpacingMultiple: 1.1 });
  });

  const x2 = M + hw + 0.32;
  card(s, { x: x2, y: 4.44, w: hw, h: 2.18, fill: C.background2, r: 0.12, name: 'states' });
  txt(s, 'The states, in order', { x: x2 + 0.3, y: 4.64, w: hw - 0.6, h: 0.34, fontSize: 15, bold: true,
    color: C.text1, fontFace: THEME.headFontFace });
  // Two lines: the run every contract takes, then the three endings it chooses between.
  // (One line of five pills overran the slide edge.)
  const stRows = [
    [['draft', C.accent6], ['active', C.accent5]],
    [['expired', C.accent2], ['terminated', C.accent3], ['completed', C.accent1]],
  ];
  stRows.forEach((row, ri) => {
    const y = 5.06 + ri * 0.46;
    let px = x2 + 0.3;
    row.forEach((st, i) => {
      const pw = st[0].length * 0.085 + 0.46;
      pill(s, st[0], { x: px, y: y, w: pw, h: 0.34, fill: st[1], fs: 10.5,
        color: st[0] === 'expired' ? '3A2600' : 'FFFFFF' });
      px += pw;
      if (i < row.length - 1) {
        txt(s, ri === 0 ? '→' : '/', { x: px, y: y, w: 0.26, h: 0.34, align: 'center',
          valign: 'middle', fontSize: ri === 0 ? 13 : 12, bold: ri === 0, color: C.text2 });
        px += 0.26;
      }
    });
    if (ri === 0) {
      txt(s, '→   then exactly one of', { x: px + 0.12, y: y, w: 2.5, h: 0.34,
        valign: 'middle', fontSize: 11, italic: true, color: C.text2 });
    }
  });
  txt(s, 'A contract reaches exactly one ending. Extending moves the end date; it does not change the state.', {
    x: x2 + 0.3, y: 5.98, w: hw - 0.6, h: 0.56, fontSize: 11.5, color: C.text2, lineSpacingMultiple: 1.2,
  });
  s.addNotes('Both signatures are recorded separately, so a contract waiting on one side is visible as such rather than merely not active.');
}

// ================================================================ 26. QUERIES
{
  const SEC = 'Part 6 - Award and contract';
  const s = sl('CONTENT TINT', SEC, 'Queries: two independent threads');
  txt(s, 'Both notify everyone in the thread rather than only the other side, so a reply does not get lost between an officer and an owner.', {
    x: M, y: 1.16, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const lw = 2.4;
  const nx = M + lw + 0.3;
  const nw = (CW - lw - 0.3 - 2 * 0.3) / 3;
  const lanes = [
    ['Tender-level', 'staff only', C.accent6, [
      ['actor', 'Officer or owner raises it', 'tenders/{id}/queries'],
      ['state', 'Posted against the tender', 'not against one bid'],
      ['notify', 'Everyone in the thread is told', ''],
    ]],
    ['Application-level', 'both ways', C.accent1, [
      ['actor', 'Vendor or staff raises it', 'applications/{id}/queries'],
      ['state', 'Either side replies', 'as often as needed'],
      ['notify', 'Everyone in the thread is told', ''],
    ]],
  ];
  lanes.forEach((ln, li) => {
    const y = 1.72 + li * 1.5;
    const h = 1.1;
    s.addShape(S.roundRect, { x: M, y: y + 0.12, w: 0.42, h: 0.42, rectRadius: 0.12,
      fill: { color: ln[2] }, objectName: 'lane-mark' });
    txt(s, String(li + 1), { x: M, y: y + 0.12, w: 0.42, h: 0.42, align: 'center', valign: 'middle',
      fontSize: 15, bold: true, color: C.background1 });
    txt(s, ln[0], { x: M + 0.54, y: y + 0.08, w: lw - 0.54, h: 0.42, fontSize: 15, bold: true,
      color: C.text1, fontFace: THEME.headFontFace, valign: 'middle' });
    txt(s, ln[1], { x: M + 0.54, y: y + 0.52, w: lw - 0.54, h: 0.3, fontSize: 11, color: C.text2, italic: true });
    ln[3].forEach((n, i) => {
      const x = nx + i * (nw + 0.3);
      node(s, { x: x, y: y, w: nw, h: h, tone: n[0], head: n[1], sub: n[2], fs: 12, subfs: 9.5 });
      if (i < 2) aR(s, x + nw + 0.04, y + h / 2, 0.22, K.dk2);
    });
  });

  const hw = (CW - 0.32) / 2;
  card(s, { x: M, y: 4.84, w: hw, h: 1.1, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.1, name: 'q1' });
  txt(s, [
    { text: 'Independent of each other', options: { fontSize: 13, bold: true, color: C.text1, breakLine: true } },
    { text: 'A tender query is not copied onto the applications, and an application query is never visible to the other bidders.', options: { fontSize: 11.5, color: C.text2 } },
  ], { x: M + 0.28, y: 4.84, w: hw - 0.56, h: 1.1, valign: 'middle', lineSpacingMultiple: 1.2 });

  const x2 = M + hw + 0.32;
  card(s, { x: x2, y: 4.84, w: hw, h: 1.1, fill: K.accent3, flat: true, r: 0.1, name: 'q-gap' });
  txt(s, [
    { text: 'Known gap', options: { fontSize: 11, bold: true, color: '4A0010', charSpacing: 1.2, breakLine: true } },
    { text: 'A vendor cannot raise a tender-level query. Only application-level queries are open to them; tender queries are staff-initiated.', options: { fontSize: 11.5, color: 'FFFFFF' } },
  ], { x: x2 + 0.28, y: 4.84, w: hw - 0.56, h: 1.1, valign: 'middle', lineSpacingMultiple: 1.2 });

  caption(s, 'In practice a vendor with a question about the tender itself asks it against their own application, and the officer repeats the answer to the others by hand.', 6.12, { w: 11.8 });
  s.addNotes('The gap on the right is worth raising with the Purchase Department: a public clarifications thread is the normal expectation in tendering, and today it has to be done by hand.');
}

// ================================================================ DIVIDER: PART 7
pres.addSection({ title: 'Part 7 - Reference' });
divider('Part 7 - Reference', 'PART SEVEN',
  'Reference, and what is missing',
  'The states a record can hold, the exact places each gate is enforced, and the four gaps recorded so the flows are not read as more complete than the system is.');

// ================================================================ 27. STATUS REFERENCE
{
  const SEC = 'Part 7 - Reference';
  const s = sl('TABLE', SEC, 'Every state a record can hold');
  txt(s, 'Each arrow is a transition the system performs; a slash separates alternative endings.', {
    x: M, y: 1.16, w: 11, h: 0.3, fontSize: 12.5, color: C.text2,
  });

  const mono = { fontFace: 'Courier New', fontSize: 10.5 };
  dtable(s,
    ['Table', 'Column', 'Values'],
    [
      [{ text: 'tenders', options: mono }, { text: 'status', options: mono }, 'draft  →  published  →  closed'],
      [{ text: 'tenders', options: mono }, { text: 'tender_type', options: mono }, 'online  ·  postal  ·  hybrid'],
      [{ text: 'tender_applications', options: mono }, { text: 'status', options: mono }, 'submitted  ·  under_review  ·  shortlisted  ·  rejected  ·  awarded  ·  withdrawn'],
      [{ text: 'vendors', options: mono }, { text: 'status', options: mono }, 'pending  →  committee_review  →  approved / rejected'],
      [{ text: 'contracts', options: mono }, { text: 'status', options: mono }, 'draft  →  active  →  expired / terminated / completed'],
      [{ text: 'tender_committee', options: mono }, { text: 'status', options: mono }, 'pending  →  approved / rejected'],
      [{ text: 'tender_committee', options: mono }, { text: 'role', options: mono }, 'chairman  ·  member  ·  observer  ·  technical_expert  ·  financial_expert'],
      [{ text: 'tender_review_otps', options: mono }, { text: 'holder_role', options: mono }, 'main (the standing committee)  ·  officer'],
      [{ text: 'tender_review_otps', options: mono }, { text: 'mail_status', options: mono }, 'accepted  ·  rejected  ·  error  ·  no-address'],
    ],
    { y: 1.56, colW: [2.6, 2.3, 7.193], rowH: 0.445, fs: 11 }
  );

  caption(s, 'A vendor never returns to pending, and a contract reaches exactly one ending. The two columns on tender_review_otps are what the collect-codes screen reads to tell the opener who still owes a code.', 6.12, { w: 11.9 });
  s.addNotes('Hand this page to anybody writing a report or a query against the database. These are the only values each column takes.');
}

// ================================================================ 28. WHERE THE GATES LIVE
{
  const SEC = 'Part 7 - Reference';
  const s = sl('TABLE', SEC, 'Where each gate is actually enforced');
  txt(s, 'Not one of these is a hidden button. A guessed URL or a stale browser tab meets the same check.', {
    x: M, y: 1.16, w: 11, h: 0.3, fontSize: 12.5, color: C.text2,
  });

  const mono = { fontFace: 'Courier New', fontSize: 11, color: K.dk2 };
  dtable(s,
    ['The question', 'Answered by', 'Seen in'],
    [
      ['Who may open this tender', { text: 'Tender::mayBeOpenedBy()', options: mono }, 'Part 4'],
      ['Who holds an opening code', { text: 'Tender::openingCodeHolders()', options: mono }, 'Part 4'],
      ['Who may read the documents', { text: 'Tender::isReviewUnlockedFor()', options: mono }, 'Parts 4 and 5'],
      ['Whose turn it is — vendor registrations', { text: 'VendorApprovalChain::actionableStepFor()', options: mono }, 'Part 1'],
      ['Whose turn it is — tender seats', { text: 'TenderCommitteeApprovalChain::actionableStepFor()', options: mono }, 'Part 5'],
      ['Whether an award may proceed', { text: 'AwardRecommendation::gateBlocksAward()', options: mono }, 'Part 6'],
      ['Which committee seats count', { text: 'Tender::committeeMembers()', options: mono }, 'Part 5'],
    ],
    { y: 1.56, colW: [4.2, 6.2, 1.693], rowH: 0.47, fs: 11.5, hfs: 12 }
  );

  card(s, { x: M, y: 5.58, w: CW, h: 1.04, fill: C.text1, flat: true, r: 0.11, name: 'why-code' });
  txt(s, [
    { text: 'Why it is written this way', options: { fontSize: 14, bold: true, color: K.accent2, breakLine: true } },
    { text: 'Each question is answered in exactly one place, on the model or in a service. The owner’s screen, the officer’s screen and the JSON endpoint all call the same method, so they cannot drift apart — and a permission can be audited by reading one function rather than every page that might show a button.',
      options: { fontSize: 12.5, color: 'FFFFFF' } },
  ], { x: M + 0.34, y: 5.58, w: CW - 0.68, h: 1.04, valign: 'middle', lineSpacingMultiple: 1.22 });
  s.addNotes('For a security review, this is the page that matters: seven functions cover every entitlement in the system.');
}

// ================================================================ 29. KNOWN GAPS
{
  const SEC = 'Part 7 - Reference';
  const s = sl('CONTENT', SEC, 'Four gaps, recorded on purpose');
  txt(s, 'So the flows in this deck are not read as more complete than the system is. None of them is a security hole; all four are work.', {
    x: M, y: 1.18, w: 11.4, h: 0.3, fontSize: 13, color: C.text2,
  });

  const gaps = [
    ['Vendors cannot raise a tender query', 'Part 6',
      'Only application-level queries are open to them. A clarification that ought to reach every bidder has to be repeated by hand.'],
    ['Three vendor buttons do nothing', 'Part 1',
      'vendors/{id}/suspend, vendors/{id}/activate and vendors/bulk-action are routed to controller methods that do not exist, so those buttons fail.'],
    ['Registration checks the wrong table', 'Part 1',
      'It validates unique:users but not unique:vendors on the email address, so a duplicate can get in by one route.'],
    ['A dropped approval email is not retried', 'Part 1',
      'If the mail fails at the moment of final approval, the status is still approved and nothing re-sends it. The vendor can sign in, but was never told.'],
  ];
  const w = (CW - 0.32) / 2, h = 1.74;
  gaps.forEach((g, i) => {
    const x = M + (i % 2) * (w + 0.32);
    const y = 1.66 + Math.floor(i / 2) * (h + 0.3);
    card(s, { x: x, y: y, w: w, h: h, fill: C.background2, r: 0.11, name: 'gap' });
    s.addShape(S.roundRect, { x: x + 0.3, y: y + 0.3, w: 0.42, h: 0.42, rectRadius: 0.12,
      fill: { color: C.accent3 }, objectName: 'gap-mark' });
    txt(s, '!', { x: x + 0.3, y: y + 0.3, w: 0.42, h: 0.42, align: 'center', valign: 'middle',
      fontSize: 16, bold: true, color: 'FFFFFF' });
    txt(s, g[0], { x: x + 0.9, y: y + 0.26, w: w - 2.1, h: 0.5, fontSize: 15, bold: true, color: C.text1,
      valign: 'middle', fontFace: THEME.headFontFace, lineSpacingMultiple: 1.0 });
    pill(s, g[1], { x: x + w - 1.1, y: y + 0.34, w: 0.8, h: 0.26, fill: C.accent6, fs: 9 });
    txt(s, g[2], { x: x + 0.9, y: y + 0.84, w: w - 1.2, h: 0.78, fontSize: 11.5, color: C.text2,
      lineSpacingMultiple: 1.22 });
  });

  card(s, { x: M, y: 5.56, w: CW, h: 1.06, fill: C.background1, line: 'C9D3EA', lw: 0.9, r: 0.11, name: 'note' });
  txt(s, [
    { text: 'What is not on this list:  ', options: { fontSize: 12.5, bold: true, color: C.text1 } },
    { text: 'nothing that lets somebody open a tender alone, read a sealed bid early, jump an approval queue, or award without a record. Those four are the gates in Part 4 and Part 6, and they hold.',
      options: { fontSize: 12.5, color: C.text2 } },
  ], { x: M + 0.32, y: 5.56, w: CW - 0.64, h: 1.06, valign: 'middle', lineSpacingMultiple: 1.2 });
  s.addNotes('Showing the gaps is deliberate. A flow diagram that hides the rough edges gets trusted in exactly the places it should not be.');
}

// ================================================================ 30. CLOSE
{
  const SEC = 'Part 7 - Reference';
  const s = pres.addSlide({ masterName: 'COVER', sectionTitle: SEC });
  mark(s, M, 0.88, 0.72);

  txt(s, 'Three things worth carrying out of the room', {
    x: M, y: 1.86, w: 10.6, h: 0.72, fontSize: 36, bold: true, color: C.background1,
    fontFace: THEME.headFontFace, valign: 'middle',
  });

  const take = [
    ['One list drives three chains', 'Reorder the standing committee and you change who is asked next — on vendor registrations, on tender seats, and on award recommendations alike.', C.accent2],
    ['The opener never holds a code', 'That single asymmetry is what stops one person opening a tender. When a department opens its own, the Purchase Officer becomes a holder.', C.accent4],
    ['The officer’s record is the gate', 'The committee advises and can veto by rejecting. No contract exists anywhere without the officer’s finalization on record.', C.accent1],
  ];
  take.forEach((t, i) => {
    const y = 2.84 + i * 1.08;
    disc(s, String(i + 1), { x: M, y: y + 0.1, d: 0.46, fill: t[2], fs: 14, color: C.text1 });
    txt(s, t[0], { x: M + 0.68, y: y, w: 4.5, h: 0.44, fontSize: 17, bold: true, color: C.background1,
      fontFace: THEME.headFontFace, valign: 'middle' });
    txt(s, t[1], { x: M + 5.3, y: y - 0.02, w: 7.0, h: 0.86, fontSize: 12, color: C.background2,
      transparency: 16, lineSpacingMultiple: 1.22 });
  });

  card(s, { x: M, y: 6.1, w: CW, h: 0.76, fill: C.text2, flat: true, r: 0.1, name: 'where' });
  txt(s, [
    { text: 'Written down in:  ', options: { fontSize: 11.5, bold: true, color: K.accent2 } },
    { text: 'docs/VMS_Complete_Flow.md', options: { fontSize: 11.5, color: 'FFFFFF', fontFace: 'Courier New' } },
    { text: '  (the source for every flow here)  ·  ', options: { fontSize: 11.5, color: C.background2 } },
    { text: 'VMS_Complete_Flow_A3.pdf', options: { fontSize: 11.5, color: 'FFFFFF', fontFace: 'Courier New' } },
    { text: '  for the wall  ·  ', options: { fontSize: 11.5, color: C.background2 } },
    { text: 'docs/flow/', options: { fontSize: 11.5, color: 'FFFFFF', fontFace: 'Courier New' } },
    { text: '  for each diagram on its own', options: { fontSize: 11.5, color: C.background2 } },
  ], { x: M + 0.32, y: 6.1, w: CW - 0.64, h: 0.76, valign: 'middle' });
  s.addNotes('Close on the three asymmetries. Anyone who wants the detail has the Markdown source and the A3 print.');
}

// ---------------------------------------------------------------- write
(async () => {
  const out = process.argv[2] || 'VMS_Complete_Project_Presentation.pptx';
  await pres.writeFile({ fileName: out });
  await applyTheme(out, THEME);
  console.log('wrote ' + out);
})().catch((e) => { console.error('FAILED: ' + e.message); process.exit(1); });
