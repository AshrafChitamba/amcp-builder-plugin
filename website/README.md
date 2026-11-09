# AC MCP website

Static product site for AC MCP for Bricks and Elementor. Deploy the `website` directory as the Vercel project root; no build command or framework preset is required; set the output directory to `public` (also configured in `vercel.json`). The ready-to-download plugin ZIPs live in `public/downloads/` and are served from `/downloads/`.

The site uses the project’s current package artifacts:

- `ac-mcp-full-v1.0.0.zip`: full 1.0.0 package (the personal build artifact).
- `ac-mcp-wordpress-directory-v1.0.1.zip`: 1.0.1 package prepared for WordPress.org submission.

When publishing a later release, replace the ZIP and update its link, version, and feature notes in `index.html` together. The directory package is described as a submission build; the site does not claim WordPress.org approval or listing.
