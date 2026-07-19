<?php

namespace Buildr\Render;

/**
 * The canonical base layer, shipped IDENTICALLY to the public page and the
 * editor canvas (scoped under .buildr-page) — this is what guarantees the
 * editor is a 1:1 preview. Also provides the sensible element defaults new
 * elements start from; global fonts/colors will override via CSS variables.
 */
final class BaseCss
{
    public static function css(): string
    {
        return <<<'CSS'
.buildr-page{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;font-size:16px;line-height:1.6;color:#1f2933;background:#fff;-webkit-font-smoothing:antialiased}
.buildr-page *,.buildr-page *::before,.buildr-page *::after{box-sizing:border-box}
.buildr-page h1,.buildr-page h2,.buildr-page h3,.buildr-page h4,.buildr-page h5,.buildr-page h6,.buildr-page p,.buildr-page figure,.buildr-page ul,.buildr-page ol{margin:0;padding:0}
.buildr-page h1{font-size:40px;font-weight:700;line-height:1.15}
.buildr-page h2{font-size:32px;font-weight:700;line-height:1.2}
.buildr-page h3{font-size:26px;font-weight:600;line-height:1.25}
.buildr-page h4{font-size:21px;font-weight:600;line-height:1.3}
.buildr-page h5{font-size:18px;font-weight:600;line-height:1.35}
.buildr-page h6{font-size:16px;font-weight:600;line-height:1.4}
.buildr-page ul,.buildr-page ol{padding-left:1.2em}
.buildr-page img{max-width:100%;height:auto;display:block}
.buildr-page a{color:inherit}
.buildr-page hr{border:none;border-top:1px solid currentColor}
.buildr-page .bcol{display:flex;flex-direction:column;gap:12px;min-width:0}
CSS;
    }
}
