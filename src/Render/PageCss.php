<?php

namespace Buildr\Render;

use Buildr\Models\Page;

/** Page-level background settings compiled onto the .buildr-page scope. */
final class PageCss
{
    public static function for(Page $page): string
    {
        $bg = $page->settings['background'] ?? [];
        $decl = '';

        if (! empty($bg['color'])) {
            $decl .= 'background-color:'.$bg['color'].';';
        }
        if (! empty($bg['image'])) {
            $decl .= "background-image:url('".$bg['image']."');";
            $decl .= 'background-position:'.($bg['position'] ?: 'center').';';
            $decl .= 'background-repeat:'.($bg['repeat'] ?: 'no-repeat').';';
            $decl .= 'background-size:'.($bg['size'] ?: 'cover').';';
            if (! empty($bg['attachment'])) {
                $decl .= 'background-attachment:'.$bg['attachment'].';';
            }
        }

        return $decl === '' ? '' : '.buildr-page{'.$decl.'}';
    }
}
