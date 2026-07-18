# Buildr

A lean, code-first page builder for Laravel. Elementor-style editing for
clients; clean, hand-written-quality markup for visitors.

- **Containers + elements** — CSS grid tracks, not div soup. One element =
  one semantic tag.
- **Per-site coded sections** — design lives in Blade + schema classes;
  content lives in the database.
- **Dynamic tags** — `{{site.phone}}`, `{{date:F j, Y}}`, plus any Laravel
  data a site registers.
- **Compiled styles** — settings become one inlined stylesheet per page.
  Zero builder assets on the public site.

## Install

```bash
composer require buildr/buildr
php artisan buildr:install
php artisan buildr:make-block Hero
```

## Status

v0.1 — rendering engine (schema system, node tree, style compiler, dynamic
tags, core elements). Editor UI (Livewire) is the next phase. UI mockups and
the full control spec: https://buildr-mockups.pages.dev

See `BUILDR.md` for architecture conventions.
