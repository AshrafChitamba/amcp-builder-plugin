# AC MCP for Bricks and Elementor design guide

Read this before you build or redesign anything. It applies to Bricks and Elementor alike. The site owner's own rules come at the end of this guide. **Where they conflict with anything here, they win.**

## 0. Non-negotiables

These hold on every page, every change, every time.

1. **Read the owner's rules first.** They are at the end of this guide. Every tool result includes `guidelinesVersion`; if it ever differs from the version you read, call `get-design-guide` again before the next change. The owner may have updated the rules mid-session.
2. **No new colors or fonts.** Use only the site's existing palette, global colors, variables and font families. If the design truly needs something new, stop and ask the user for permission first. Never add one silently.
3. **One design system across the whole site.** Typography scale, colors, spacing, corner radius, shadows and button styles stay identical from page to page. Layouts may differ (see section 3).
4. **No eyebrows.** Never put a small label, kicker or uppercase tagline above a heading. The heading carries its own weight.
5. **Report every change.** After each saved change, give the user the preview link(s) from the tool result and describe each changed section or element in 1-3 plain lines. For one element, one line is enough: "Changed the button background to red and its corner radius to 28px."
6. **Never invent content.** No made-up facts, statistics, testimonials, clients, people, prices or news. Use the site's content or the user's. Mark anything that is a placeholder.

## 1. Read the brief and the site

Before adding anything, write one line for yourself:
"This is a <page kind> for <audience>. The business is <what it does and how it wants to feel>. I will use <the site's existing design system>."

- Call site-info, read the home page and one or two other pages, and check the design tokens, global classes and theme styles. The site's brand is the starting point, not your taste.
- Decide the mode of the page. **Persuade** (home, landing, services, pricing): earn attention and action. **Operate** (forms, account, tools): clarity and speed over expression. **Read** (articles, docs, policies): comfortable reading first.
- Trust-first businesses (legal, health, finance, public sector, education) want calm layouts, high contrast, plain language and little motion (where needed). Creative and consumer brands can take more layout risk.
- If one important point is unclear, ask the user one question. Otherwise decide and continue.

## 2. The hero: one captivating moment

The hero is the first impression. It must look like _this_ business, not like a template.

- **Lead with the business's real world.** Use its strongest real image: the product, the place, the team at work, the result it delivers. A real photo of the actual thing beats any abstract shape or gradient.
- **Say what makes it different in one headline**, at most two lines, in the business's own voice. Specific beats clever: "Rules your board can use at the next meeting" beats "Elevate your meetings".
- **Subtext of at most about 20 words**, one primary button and at most one secondary action. The primary call to action is visible without scrolling on a laptop.
- **Create contrast of scale.** A confident, large headline set tight (line-height 1.05-1.15, slight negative letter spacing) against generous space reads as premium. Small, timid headlines read as generic.
- **Pick a hero form that fits the brand**, not the same one every time:
  - split: headline on one side, a large real image on the other;
  - full-bleed image or video with a dark overlay for legible text;
  - typographic hero on the brand color with one striking product visual;
  - hero with an embedded working element (a form, a search, a booking widget) when the page's job is a task.
- **One authored motion moment at most where needed** (for example the image settling in, or the headline revealing), never a different animation on every element.
- Keep logo walls, feature lists, statistics rows and small print out of the hero. Give them their own section below.
- One h1 per page. Section titles are h2, items inside sections are h3.

## 3. Consistency without sameness

- **Fixed across the site:** type scale and fonts, color roles (primary, accent, text, surfaces), spacing scale, section padding, corner radius, shadow style, button and link styles, form field style, icon set.
- **Free per page:** layout and hero form. An About page can open with a photo banner and breadcrumbs; that does not mean every page must. Choose the layout from each page's job.
- Reuse the site's global classes and components before creating anything. When you need a new reusable style, create one new, uniquely named class (never edit an existing class to fix one page; it changes every page that uses it).
- Avoid class names that contain framework prefixes the site already uses (for example "btn--" or "link--" on Automatic.css sites).
- Never change headers, footers, navigation or other site-wide templates unless the user asked.

## 4. Layout and rhythm

- Vary layouts between sections. A page with six sections should use at least four different layouts: split, asymmetric grid, list rows, full-width band, media with caption, quote. Never repeat the same image-left/text-right block three times in a row.
- Do not default to "three identical cards in a row". Prefer an asymmetric grid (one large tile and smaller ones), a two-column list, or rows. Use cards only when items are truly parallel and the brief calls for them, and never nest cards.
- A grid has exactly as many cells as items. No empty cells.
- Headings sit above their content, not in a column of their own with a tiny paragraph floating beside them.
- Spacing: tight inside a group, generous between groups, more space above a heading than below it. Keep the site's section padding.
- Text blocks stay around 60-75 characters per line.
- Every multi-column layout needs an explicit tablet and mobile fallback (usually two columns on tablet, one on mobile) with side padding on small screens. Nothing may scroll sideways.
- No section numbers (01 / 02 / 03) unless the order itself is information.

## 5. Typography

- Use the site's fonts only. Build hierarchy from size, weight and line-height together, not from extra colors.
- Large headings: line-height 1.05-1.2 and slightly negative letter spacing (about -0.01em to -0.02em). Body: line-height about 1.6 and letter spacing near 0. Small text and labels: slightly positive tracking.
- Sentence case for headings and buttons. No all-caps labels.
- Size text in rem or the site's variables so it scales with the visitor's settings.
- No gradient text. Emphasis comes from weight or size.

## 6. Color, shape and depth

- One accent color, used for actions and key emphasis, consistently. A second strong color is reserved for one meaning (for example red only for errors).
- Large surfaces use the site's tinted neutrals rather than pure black or pure white when the brand has them.
- One corner-radius rule for the whole site: for example buttons 6px, cards 12px, inputs 6px. Follow the rule the site already uses.
- Shadows only where elevation means something (a raised card on hover, a floating panel). They have an offset and a soft blur, and are tinted towards the background. No shadow on everything; no hard offset "block" shadows; no colored glows.
- No colored border-left or border-right stripes on cards, list items or callouts.
- Keep the page in one theme. A single deliberate color band (for example a call-to-action strip in the brand color) is fine.
- Translucency and blur only when they serve a purpose (a sticky header over content), never as decoration.

## 7. Copy

- Plain, specific language in the business's voice. Avoid filler: "elevate", "seamless", "unleash", "next-gen", "revolutionize", "cutting-edge".
- No em-dashes or en-dashes in visible text. Use a period, a comma or a colon. Use a hyphen for ranges (2021-2026).
- Each button names one action ("Book a consultation", "Request a training"). Do not use two labels for the same action on one page.
- Errors say what went wrong and how to fix it.
- Re-read every visible string before you finish. Fix anything vague, broken or that sounds generated.

## 8. Images and icons

- Use real images from the media library (list-media) or images the user provides (upload-media). Never invent image URLs.
- Choose images that show the business's real world. Every meaningful image gets alt text that describes it.
- Use the builder's icon libraries in one consistent style. Never use emoji or text symbols as icons.
- Do not overlay labels or captions on photos as decoration. Text over a photo needs an overlay that keeps it readable.

## 9. Interaction, motion and accessibility

- Text meets WCAG AA contrast: 4.5:1 for body text and placeholders, 3:1 for large text, including over photos.
- Every link, button and form field has visible hover, focus and pressed states. Feedback is immediate: a button reacts the moment it is pressed.
- Motion has a purpose (hierarchy, feedback, a state change). Prefer short, soft ease-out transitions on transform and opacity; avoid bouncy or looping effects. Respect reduced-motion preferences: replace movement with a simple fade or nothing.
- Interactive states are complete: hover, focus, disabled, loading, error, empty.
- Links go somewhere real. If a destination does not exist yet, tell the user instead of linking to "#".
- Forms: label above every field, never placeholder-as-label, clear required markers, inline error messages.

## 10. Working method

1. site-info, then get-design-guide ("design", then the active builder). Note `guidelinesVersion`.
2. Read the site: existing pages, design tokens, global classes, theme styles.
3. Build one section per call. Use dryRun first for large changes.
4. Read the page back and check structure, then check every breakpoint.
5. After each saved change, report it: the preview link(s) from the result, then 1-3 plain lines per changed section or element.
6. At the end, list what is a placeholder and how to undo any change (restore-snapshot with the snapshot ID).
