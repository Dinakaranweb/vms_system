# The project presentation

`../VMS_Complete_Project_Presentation.pptx` — 37 slides, 16:9, built from
[`../VMS_Complete_Flow.md`](../VMS_Complete_Flow.md) as of 20 November 2026.

Every flow in it is drawn from native PowerPoint shapes rather than pasted in as a picture, so
boxes and text stay crisp at any zoom, and anything can be edited in PowerPoint directly —
move a box, retype a label, recolour a node. The Mermaid renders in [`../flow/`](../flow/) are
tall portrait images (one is 1920 × 5697) and would be unreadable shrunk onto a landscape
slide, which is why they are not used here.

## Structure

| Slides | |
|---|---|
| 1–5 | The system at a glance, the roles, and the one ordered list that drives three chains |
| 6–9 | **Part 1** — vendor registration and the approval chain |
| 10–14 | **Part 2** — raising a tender, as the officer and as a Tender Owner, and the website feed |
| 15–18 | **Part 3** — how a vendor bids, and what the seal means |
| 19–23 | **Part 4** — the opening gate, who holds a code, and the mail report |
| 24–27 | **Part 5** — per-tender seats and committee review |
| 28–32 | **Part 6** — recommendation, the award gate, contracts and queries |
| 33–37 | **Part 7** — status reference, where each gate lives, the known gaps, and the close |

Every slide carries speaker notes.

## Changing it

Edit the `.pptx` in PowerPoint for anything one-off. Regenerate only when the underlying
process changes — the generator is the source for the layout, not the file.

```bash
cd docs/presentation
npm install pptxgenjs
NODE_PATH="$PWD/node_modules" node build-deck.js ../VMS_Complete_Project_Presentation.pptx
```

The theme — fonts and the seven palette colours — is the `THEME` object at the top of
`build-deck.js`. Colours are written into the deck's theme after the file is saved, so changing
them there restyles every slide at once.

The flows share one colour language, set out in the legend on slide 2: slate indigo for a
person acting, a pale panel for a record state, amber for a branch, coral for a refusal, green
for approved or active, violet for codes and committee, teal for somebody being notified.
