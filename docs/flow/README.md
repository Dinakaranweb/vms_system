# Flow diagrams

Everything in this folder is **generated** from the Mermaid blocks in
[`../VMS_Complete_Flow.md`](../VMS_Complete_Flow.md). That file is the source of truth —
edit a diagram there and regenerate, rather than editing an image by hand.

| File | Use it for |
|---|---|
| `../VMS_Complete_Flow_A3.pdf` | **Printing on A3.** Every diagram on one sheet, whole. The meeting table, the wall, anything someone has to read across a desk. |
| `../VMS_Complete_Flow_A4.pdf` | **Printing on A4.** An ordinary office printer. |
| `NN-name.png` | Dropping one diagram into Word, PowerPoint or an email. Rendered at 3x, so it stays sharp when printed. |
| `NN-name.svg` | Any size without blurring — a poster, or editing in Illustrator / Inkscape. |

The numbers match the section numbers in the source document, so `06-...` is section 6.

## Printing

Open the PDF for the paper you are using and print it. Leave scaling at **100% / Actual size**,
not "Fit to page" — the page size is already set inside the file, so fitting only shrinks it.

**On A3** every one of the ten diagrams fits a single sheet at between 8 and 14pt. Nothing is
split, and nothing needs assembling. This is the better printout if anyone has to read the
charts rather than glance at them.

**On A4** there is not enough room for the tall charts at a readable size, so those run across
two sheets, labelled *part 1 of 2* and *part 2 of 2*. The split is overlapped on purpose: the
rows either side of the join are printed on **both** sheets, so no box is ever sliced in half.
Lay the two sheets end to end and the chart is continuous.

One diagram is noticeably tighter on A4: §2, vendor registration and approval, lands at about
7pt, because it is wide rather than tall and cannot be widened past the margins. It is about
9pt on A3. If A4 is all you have and that chart matters, print
`02-vendor-registration-and-approval.png` on its own.

## How the fitting works

No diagram is placed by hand. Each one is measured from its SVG, scaled to the largest size
that fits the page's printable area, and centred. Whether a chart is split, and into how many
sheets, follows from that measurement and the paper size:

- a chart is only split when splitting actually gains legibility — if the page's **width** is
  what limits the scale, another sheet would not help, so it is left alone;
- the overlap is fixed at 240px of diagram space, which is taller than any single node, so a
  node always appears complete on at least one sheet;
- a chart short enough to sit in the running text does, rather than taking a sheet and leaving
  most of it blank.

Change paper sizes or margins in the `PROFILES` table at the top of `build-diagrams.cjs`; the
layout follows from those numbers alone.

## Regenerating

From the repository root:

```bash
npm install --no-save @mermaid-js/mermaid-cli@11.4.2 marked@14.1.3
node docs/flow/build-diagrams.cjs
```

The install is only needed once per machine, and `--no-save` keeps both tools out of
`package.json` — they are documentation tooling, not application dependencies.

| Command | Does |
|---|---|
| `node docs/flow/build-diagrams.cjs` | Images, plus both PDFs |
| `... --page a3` | Only the A3 PDF (`a4`, `a3`, or `both`) |
| `... --no-images` | Reuse the SVGs already here; just rebuild the PDFs |

Rendering needs a real browser. The script finds an installed Chrome or Edge automatically; set
`CHROME_PATH` if yours is somewhere unusual. Install with `PUPPETEER_SKIP_DOWNLOAD=true` to stop
Puppeteer fetching its own 150 MB copy of Chromium when one is already on the machine. Set
`VMS_FLOW_KEEP=1` to leave the intermediate print HTML behind if a layout needs inspecting.

The script rewrites every `.png` and `.svg` here and both PDFs. It never modifies the Markdown.
