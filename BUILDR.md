# Buildr — conventions for AI-assisted development

Buildr is a lean, code-first page builder for Laravel. This file is the map
for any Claude session working in this package or in a site that installs it.
The full control spec lives at https://buildr-mockups.pages.dev/spec (row IDs
like BTN-6 are referenced in code comments).

## Core principles (non-negotiable)

1. **Lean DOM.** Containers render as ONE element with CSS grid tracks —
   never wrapper divs per column. Elements render as bare semantic tags
   (`<h2>`, `<img>`, `<a>`). Styling goes through the compiled stylesheet,
   never inline style attributes.
2. **No role gating.** Clients and admins see every control.
3. **Free values, real units.** Full color pickers; every measurement is a
   number + unit (px/%/em/rem/vw/vh). Never S/M/L presets.
4. **Design in code, content in DB.** Per-site sections are coded Blade
   views + schema classes. The DB stores a node tree with JSON settings.

## Anatomy

- `Buildr\Fields\Field` — one fluent class for every control type.
  Value shapes are documented on the class (unit = `{value, unit}`,
  responsive = keyed by `desktop/tablet/mobile`, sides = keyed by side).
- `Buildr\Elements\Element` — base for elements, containers, AND per-site
  blocks. Declare `contentFields()` / `styleFields()`, point `view()` at a
  Blade view. `advancedFields()` is shared (ADV-1..7).
- `buildr_nodes` — the page tree: `page_id, parent_id, type, sort, data`.
  `data` JSON = `{content: {}, style: {}, advanced: {}}`.
- `Buildr\Render\PageRenderer` — walks the tree, renders views, feeds every
  node to `StyleCompiler`, returns `{html, css}`. CSS is inlined in `<head>`.
- `Buildr\DynamicTags\TagRegistry` — resolves `{{site.phone}}`,
  `{{date:F j, Y}}`, `{{year}}`, `{{page.title}}` server-side. Sites add
  custom tags in `config/buildr.php` under `tags`.

## Per-site work (in a client app, not this package)

- `php artisan buildr:make-block Hero` scaffolds `app/Blocks/Hero.php` +
  `resources/views/blocks/hero.blade.php`; register in `config/buildr.php`.
- Custom dynamic tags: `'tags' => ['review_count' => fn () => ...]`.
- MySQL in production — keep JSON queries portable (no SQLite-only SQL).

## Testing

Orchestra Testbench; `composer test`. Rendering tests assert on exact HTML
shape — if a change adds a wrapper div, a test should fail. Keep it that way.
