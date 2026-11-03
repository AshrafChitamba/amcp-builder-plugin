# Elementor guide

## Structure

- Elementor pages are nested elements: containers (flexbox or grid) holding widgets and other containers. Legacy layouts use section > column > widget.
- Atomic (v4) elements (e-heading, e-flexbox, …) are read-only in AC MCP; edit them with Elementor's own tools or the editor.
- Check widget names with elementor-list-widgets and settings keys with elementor-get-widget-schema before using a new widget. Unknown keys are rejected.

## Settings that are easy to get wrong

- Responsive values use suffixes: padding_tablet, padding_mobile, flex_direction_mobile (device keys come from the widget schema).
- Dimensions: {"unit":"px","top":"24","right":"24","bottom":"24","left":"24","isLinked":true}. Sizes: {"unit":"px","size":18}.
- Containers: "content_width":"boxed" or "full", "flex_direction":"row" or "column", "flex_gap":{"unit":"px","size":24,"column":"24","row":"24"}, grid via "container_type":"grid" and "grid_columns_grid":{"unit":"fr","size":2}.
- Global colors and fonts: use "__globals__": {"title_color":"globals/colors?id=primary","typography_typography":"globals/typography?id=primary"} instead of hard-coded values. elementor-get-globals lists the ids.
- Images: {"id":123,"url":"https://…/image.jpg"} from list-media or upload-media.

## Design system

- Call elementor-get-globals first and build with the site's global colors and fonts.
- Change globals (elementor-update-globals) only when the user asks: they are site-wide. Fonts use flat fields: {"title":"Display","font_family":"Playfair Display","font_weight":"700","font_size":{"unit":"px","size":48}}.

## Templates (Elementor Pro Theme Builder)

- elementor-list-templates shows existing templates and which types this site supports. elementor-create-template makes a draft; fill it with elementor-add-elements using the template ID as postId.
- Display conditions: elementor-set-conditions with strings such as "include/general", "include/singular/page/42", "include/archive/category/5", "exclude/singular/front_page".
- Headers and footers only show with a classic theme (such as Hello Elementor), not with block themes; the tools warn when that applies.
- Theme templates and the Template widget only work when the template is published. A published header or footer for "include/general" changes the whole site: confirm with the user first.
- Page settings (template, hide title, background, custom CSS with Pro) go through elementor-update-page-settings.

## Lists of posts

- With Elementor Pro, use a Loop Grid (widgetType "loop-grid") with "template_id" of a loop-item template, or the Posts widget ("posts"). Check the posts exist first (list-pages with postType "post").

## Custom code

- Build with native widgets. The HTML widget is custom code and is refused unless an administrator enabled custom code in AC MCP.
- Elementor removes scripts and unsafe HTML for accounts without unfiltered HTML; read the page back after saving.
