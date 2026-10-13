# Design guide: building pages that do not look generated

Read this before you build or redesign a page. It applies to Bricks and Elementor alike.

## 1. Read the brief first

Before adding anything, write one line for yourself:
"This is a <page kind> for <audience>, in a <tone> style, using <the site's existing design system>."

- Look at the site first. Call site-info, read one or two existing pages, and check global classes, global colors and typography. The site's brand is the starting point, not your taste.
- Public-sector, legal, health and finance sites are trust-first: calm layouts, high contrast, plain language, few animations.
- Marketing and portfolio sites can take more layout risk.
- If the brief is unclear on one important point, ask the user one question. Otherwise decide and continue.

## 2. Respect what exists

- Reuse the site's colors, fonts, spacing, corner radius and global classes. Do not invent a new palette.
- Never change headers, footers, navigation or other site-wide templates unless the user asked.
- Never rename or restyle an existing global class to fix one page; that changes every page that uses it. Create a new, uniquely named class instead.
- Avoid class names that contain framework prefixes the site already uses (for example "btn--" or "link--" on sites with Automatic.css), or they will inherit that framework's styles.
- Build on a draft or test page first and let the user review before touching published pages.

## 3. Hierarchy and the hero

- The hero is one moment: an optional small label, a headline of at most two lines, a subtext of at most about 20 words, and one primary plus at most one secondary button. Nothing else.
- Keep the call to action visible without scrolling on a laptop screen.
- Do not put logo walls, feature lists or small print inside the hero; give them their own section below.
- Use one h1 per page. Section titles are h2, items inside sections are h3.

## 4. Layout

- Vary layouts between sections. A page with six sections should use at least four different layouts (split, grid of tiles, list rows, full-width band, cards). Never repeat the same image-left/text-right block three times in a row.
- Avoid the default "three identical cards in a row". Prefer a two-column list, an asymmetric grid (one large tile and several smaller ones), or rows.
- A grid has exactly as many cells as items. No empty cells.
- Put headings above their content, not in a separate column with a tiny paragraph floating beside them.
- Small uppercase labels above headings (eyebrows) are allowed on at most one section in three.
- Give sections generous, consistent vertical spacing. Keep text blocks to about 60-70 characters per line.
- Every multi-column layout needs an explicit tablet and mobile fallback (usually two columns on tablet, one on mobile) and side padding on small screens.

## 5. Color, shape and depth

- One accent color, used consistently. A second strong color is reserved for one meaning (for example red only for "report a problem").
- Do not use pure black or pure white for large areas when the brand has tinted neutrals.
- Pick one corner-radius rule for the whole page and keep it.
- Shadows only where elevation means something (an overlapping panel). Never shadows on every button or card. Tint shadows towards the background color.
- Keep the page in one theme. Do not drop a dark section into a light page unless it is a single, deliberate color block (for example a call-to-action band).

## 6. Typography and copy

- Use the site's fonts. Create hierarchy with weight and size, not with many colors.
- Headlines: tight line-height (1.05-1.2), slight negative letter spacing for large sizes. Body text: line-height around 1.6.
- Sentence case for headings and buttons.
- Write plain, specific copy. Avoid filler words such as "elevate", "seamless", "unleash", "next-gen".
- Do not use em-dashes or en-dashes in visible text. Use a period, a comma or a colon. Use a hyphen for ranges (2021-2026).
- Never use lorem ipsum. Never invent facts, statistics, testimonials, people or news. Use only content from the site or the user, and say clearly what is a placeholder.
- Each button label states one action. Do not use two different labels for the same action on one page.

## 7. Images and icons

- Use real images from the media library (list-media). Never invent image URLs.
- Give every meaningful image alt text that describes it.
- Use the builder's icon libraries (for example Font Awesome). Do not draw icons from scratch.
- Do not overlay labels or captions on photos as decoration.

## 8. Interaction and accessibility

- Text must meet WCAG AA contrast against its background, including buttons over photos (use an overlay).
- Every link and button needs a visible hover and keyboard-focus state. Hover effects should be subtle (color fill, small arrow movement), and animations should respect reduced-motion preferences.
- Links must go somewhere real. If a destination does not exist yet, tell the user instead of silently linking to "#".

## 9. Working method with MCP Builder

1. site-info, then get-design-guide for this builder.
2. Read existing pages and global styles.
3. Build one section per call, with dryRun first for large changes.
4. Read the page back and check structure.
5. Tell the user what you built, what is a placeholder, and how to undo it (restore-snapshot).
