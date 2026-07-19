<?php

namespace Buildr\Render;

use Buildr\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * Site-level globals compiled to CSS variables + base overrides, layered
 * between BaseCss and the per-node compiled styles. Global colors become
 * --g-<slug> vars, so element values like var(--g-primary) update site-wide.
 */
final class GlobalCss
{
    public static function css(): string
    {
        $vars = '';
        foreach ((array) SiteSetting::get('colors', []) as $color) {
            if (! empty($color['name']) && ! empty($color['value'])) {
                $vars .= '--g-'.Str::slug($color['name']).':'.$color['value'].';';
            }
        }

        $body = SiteSetting::get('font_body');
        $baseSize = SiteSetting::get('base_size');

        $css = '.buildr-page{'.$vars
            .($body ? 'font-family:"'.$body.'",system-ui,sans-serif;' : '')
            .($baseSize ? 'font-size:'.(int) $baseSize.'px;' : '')
            .'}';

        if ($heading = SiteSetting::get('font_heading')) {
            $weight = SiteSetting::get('font_heading_weight');
            $css .= '.buildr-page :where(h1,h2,h3,h4,h5,h6){font-family:"'.$heading.'",system-ui,sans-serif;'
                .($weight ? 'font-weight:'.(int) $weight.';' : '').'}';
        }

        return $css;
    }

    /** Google Fonts <link> tag when global fonts are set; empty otherwise. */
    public static function fontLink(): string
    {
        $families = [];
        foreach (['font_heading' => 'font_heading_weight', 'font_body' => 'font_body_weight'] as $font => $weightKey) {
            if ($name = SiteSetting::get($font)) {
                $weight = (int) (SiteSetting::get($weightKey) ?: 400);
                $weights = array_unique([400, $weight, min(900, $weight + 200)]);
                sort($weights);
                $families[] = 'family='.urlencode($name).':wght@'.implode(';', $weights);
            }
        }

        if ($families === []) {
            return '';
        }

        return '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            .'<link href="https://fonts.googleapis.com/css2?'.implode('&', $families).'&display=swap" rel="stylesheet">';
    }

    /** name/value pairs for panel quick swatches. */
    public static function swatches(): array
    {
        $out = [];
        foreach ((array) SiteSetting::get('colors', []) as $color) {
            if (! empty($color['name']) && ! empty($color['value'])) {
                $out[] = ['name' => $color['name'], 'value' => $color['value'], 'var' => 'var(--g-'.Str::slug($color['name']).')'];
            }
        }

        return $out;
    }
}
