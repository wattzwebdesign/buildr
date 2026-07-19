<?php

namespace Buildr\Support;

/** Curated Google Fonts roster for the font dropdowns. */
final class Fonts
{
    public const LIST = [
        'Archivo', 'Barlow', 'Bebas Neue', 'Cabin', 'Cormorant Garamond', 'DM Sans',
        'DM Serif Display', 'Figtree', 'Fraunces', 'Gelasio', 'Inter', 'Josefin Sans',
        'Karla', 'Lato', 'Libre Baskerville', 'Lora', 'Manrope', 'Merriweather',
        'Montserrat', 'Nunito', 'Nunito Sans', 'Open Sans', 'Oswald', 'Outfit',
        'Playfair Display', 'Plus Jakarta Sans', 'Poppins', 'Quicksand', 'Roboto',
        'Rubik', 'Sora', 'Source Sans 3', 'Space Grotesk', 'Work Sans', 'JetBrains Mono',
    ];

    public static function options(string $emptyLabel = 'Default'): array
    {
        $rest = array_values(array_diff(GoogleFonts::LIST, self::LIST));

        return ['' => $emptyLabel]
            + array_combine(self::LIST, self::LIST)
            + array_combine($rest, $rest);
    }
}
