<?php

namespace Buildr\Render;

/**
 * The canonical base layer, shipped IDENTICALLY to the public page and the
 * editor canvas (scoped under .buildr-page) — this is what guarantees the
 * editor is a 1:1 preview, and it carries the element defaults new elements
 * start from. Every descendant rule is wrapped in :where() so its
 * specificity stays at (0,1,0) — user styling (.bN, compiled after this)
 * always wins on a tie because it comes later in the sheet.
 */
final class BaseCss
{
    public static function css(): string
    {
        return <<<'CSS'
.buildr-page{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;font-size:16px;line-height:1.6;color:#1f2933;background:#fff;-webkit-font-smoothing:antialiased}
.buildr-page :where(*,*::before,*::after){box-sizing:border-box}
.buildr-page :where(h1,h2,h3,h4,h5,h6,p,figure,ul,ol){margin:0;padding:0}
.buildr-page :where(h1){font-size:40px;font-weight:700;line-height:1.15}
.buildr-page :where(h2){font-size:32px;font-weight:700;line-height:1.2}
.buildr-page :where(h3){font-size:26px;font-weight:600;line-height:1.25}
.buildr-page :where(h4){font-size:21px;font-weight:600;line-height:1.3}
.buildr-page :where(h5){font-size:18px;font-weight:600;line-height:1.35}
.buildr-page :where(h6){font-size:16px;font-weight:600;line-height:1.4}
.buildr-page :where(ul,ol){padding-left:1.2em}
.buildr-page :where(img){max-width:100%;height:auto;display:block}
.buildr-page :where(a){color:inherit;text-decoration:none}
.buildr-page :where(.b-text a){text-decoration:underline}
.buildr-page :where(hr){border:none;border-top:1px solid currentColor}
.buildr-page :where(.b-button){display:inline-block;background:#1f2933;color:#fff;padding:12px 24px;border-radius:6px;font-weight:600;font-size:15px;line-height:1.2;text-align:center;text-decoration:none;transition:opacity .15s}
.buildr-page :where(.b-button:hover){opacity:.88}
.buildr-page :where(figcaption){font-size:13px;color:#6b7280;margin-top:6px}
.buildr-page :where(.bcol){display:flex;flex-direction:column;gap:12px;min-width:0}
CSS;
    }
}
