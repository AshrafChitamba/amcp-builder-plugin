# Bricks guide

## Structure

- A Bricks page is a flat list of elements linked by parent/children. You send nested trees; AC MCP for Bricks and Elementor flattens them and creates the IDs.
- Standard layout: section > container > block/div > content elements. Sections give vertical spacing and full width; containers give the content width; blocks and divs group and lay out content.
- Content elements: heading, text-basic (plain text), text (rich text), button, image, icon, icon-box, list, video, divider, nav-menu, form, accordion-nested, tabs-nested, slider-nested.
- Always check names with bricks-list-elements and settings with bricks-get-element-schema before using a new element type. Unknown keys are rejected.

## Settings that are easy to get wrong

- Style keys start with "_": _padding, _margin, _typography, _background, _border, _boxShadow, _width, _display, _direction, _gridTemplateColumns, _gridGap, _rowGap, _columnGap, _alignItems, _justifyContent.
- Responsive values add a breakpoint suffix: "_gridTemplateColumns:tablet_portrait", "_padding:mobile_portrait". States add a pseudo-class: "_background:hover". Breakpoints come from bricks-get-element-schema.
- Grid item spans are raw CSS values: "_gridItemColumnSpan": "span 2" and "_gridItemRowSpan": "span 2". A bare "2" places the item on grid line 2 instead of spanning.
- Colors: {"hex":"#105903"} or, better, a site variable {"raw":"var(--primary)"}.
- Spacing: {"top":"24px","right":"24px","bottom":"24px","left":"24px"}.
- Background image: {"image":{"id":12,"url":"https://…","size":"full"},"size":"cover","position":"center center"}. Overlay: "_gradient": {"applyTo":"overlay","angle":"90","colors":[{"id":"a1b2c3","color":{"raw":"rgba(0,0,0,0.6)"}},{"id":"d4e5f6","color":{"raw":"transparent"}}]}.
- Box shadow: {"values":{"offsetX":"0","offsetY":"12","blur":"24","spread":"-6"},"color":{"raw":"rgba(0,0,0,0.15)"}}.
- Icons: {"library":"fontawesomeSolid","icon":"fas fa-arrow-right"} (also fontawesomeBrands "fab fa-…", themify "ti-…", ionicons).
- Links: {"type":"internal","postId":42} or {"type":"external","url":"https://…","newTab":true}.
- Images: {"id":123,"url":"https://…/image.jpg","size":"large"} from list-media or upload-media, plus altText.
- Custom CSS (_cssCustom on elements and global classes): write %root% for the element or class selector; AC MCP for Bricks and Elementor replaces it with "#brxe-ID" or ".class-name" when saving, as the Bricks builder does.

## Design system

- Call bricks-get-design-tokens first. Use palette colors and variables (var(--…)) instead of hard-coded values, and reuse existing global classes (bricks-list-global-classes).
- Many Bricks sites run Automatic.css (ACSS): spacing, colors and type are variables such as var(--space-m), var(--primary), var(--text-l). Its button and link styles are applied to any class containing "btn--" or "link--", so never use those fragments in your own class names.
- Theme button styles can override fonts and text transform on buttons; set them explicitly in a class if the design needs it.
- Create new global classes with a unique prefix instead of editing classes the site already uses: an existing class may style many pages.
- Use global classes, never plain classes. Bricks shows plain classes ("_cssClasses") only under Style → CSS, so the user cannot see them in the class selector next to the element ID. Create the class with bricks-upsert-global-class and attach it through "_cssGlobalClasses" (class IDs) or bricks-apply-global-class. If you do send "_cssClasses", AC MCP for Bricks and Elementor turns each name into a global class (reusing one with the same name) and lists new ones in globalClassesCreated.

## Templates

- Headers, footers, single layouts, archive layouts, reusable sections and popups are templates (bricks-list-templates, bricks-create-template). Edit their elements with the normal page tools using the template ID as postId.
- Where a template shows is set with bricks-set-template-conditions. Only published templates are used. A published header or footer with "any" applies to the whole site: confirm with the user before publishing.
- Page-level options (hide header/footer, body classes, SEO title and description, page CSS) are set with bricks-update-page-settings.

## Query loops (lists of posts)

- Put "hasLoop": true and a "query" object on a container, block or div; its children are the template for each item. Example query: {"objectType":"post","post_type":["post"],"posts_per_page":3,"orderby":"date","order":"desc"}.
- Inside the loop use dynamic data: {post_title}, {post_url}, {post_date:F j, Y}, {post_excerpt:20}, image {"useDynamicData":"{featured_image}","size":"large"}, link {"type":"meta","useDynamicData":"{post_url}"}.
- Check that the posts exist and are relevant (list-pages with postType "post") before building a loop.

## Custom code

- Build with native elements. The Code element, SVG code and page scripts are custom code: they are refused unless an administrator enabled custom code in AC MCP for Bricks and Elementor.
- Code that runs PHP or HTML only executes after an administrator signs it in Bricks (Settings → Custom code → Regenerate code signatures). AC MCP for Bricks and Elementor never signs code. bricks-code-status shows what still needs signing.
