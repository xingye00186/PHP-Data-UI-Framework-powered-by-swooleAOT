<?php
/**
 * CSS → GDI Mapping Table
 * 
 * Defines which CSS properties are supported, how they map to GDI rendering
 * parameters, and what keys they produce in the layout output.
 * 
 * Usage:
 *   $mapped = CssMappings::parseStyleBlock($styleCss);
 *   // → ['app-bg' => ['bg'=>1973790], 'display-text' => ['fg'=>16777215, 'fontSize'=>32, 'bold'=>1], ...]
 * 
 * Extend $PROPERTY_MAP to add new CSS property support.
 */

class CssMappings
{
    /**
     * CSS property → [outputKey, parserFunction, default]
     * 
     * Each entry maps a CSS property name to:
     *   - outputKey: the key in the generated layout array
     *   - parser: a callable that converts the CSS value string to the output type
     *   - default: fallback value if property is not specified
     * 
     * Adding a new CSS property is as simple as adding one entry here.
     */
    const PROPERTY_MAP = [
        'background' => [
            'key'     => 'bg',
            'parser'  => 'CssMappings::parseHexColor',
            'default' => 0,
        ],
        'color' => [
            'key'     => 'fg',
            'parser'  => 'CssMappings::parseHexColor',
            'default' => 0xFFFFFF,
        ],
        'font-size' => [
            'key'     => 'fontSize',
            'parser'  => 'CssMappings::parsePixels',
            'default' => 16,
        ],
        'font-weight' => [
            'key'     => 'bold',
            'parser'  => 'CssMappings::parseFontWeight',
            'default' => 0,
        ],
        // ---- Extensions for future GDI/Direct2D support ----
        'border-radius' => [
            'key'     => 'borderRadius',
            'parser'  => 'CssMappings::parsePixels',
            'default' => 0,
        ],
        'padding' => [
            'key'     => 'padding',
            'parser'  => 'CssMappings::parsePixels',
            'default' => 0,
        ],
        'margin' => [
            'key'     => 'margin',
            'parser'  => 'CssMappings::parsePixels',
            'default' => 0,
        ],
        'text-align' => [
            'key'     => 'textAlign',
            'parser'  => 'CssMappings::parseTextAlign',
            'default' => 'left',
        ],
    ];

    // ============================================================
    // Color helpers
    // ============================================================

    /**
     * Convert CSS hex color #RRGGBB to GDI BGR integer (COLORREF).
     * Supports shorthand #RGB (expanded to #RRGGBB).
     */
    public static function hexToBgr(string $hex): int
    {
        $hex = ltrim($hex, '#');

        // Support shorthand: #RGB → #RRGGBB
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return 0; // Invalid color → black
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return ($b << 16) | ($g << 8) | $r;
    }

    /**
     * Derive border color from background (lighten each channel by a delta).
     */
    public static function borderColor(int $bg, int $delta = 20): int
    {
        $r = min(255, (($bg >> 16) & 0xFF) + $delta);
        $g = min(255, (($bg >> 8)  & 0xFF) + $delta);
        $b = min(255, ($bg         & 0xFF) + $delta);
        return ($r << 16) | ($g << 8) | $b;
    }

    // ============================================================
    // Property parsers (each returns a typed value from CSS string)
    // ============================================================

    /**
     * Parse "#RRGGBB" / "#RGB" → BGR integer
     */
    public static function parseHexColor(string $value): int
    {
        return self::hexToBgr(trim($value));
    }

    /**
     * Parse "16px" → 16 (int)
     */
    public static function parsePixels(string $value): int
    {
        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Parse "bold" / "700" → 1, "normal" / "400" → 0
     */
    public static function parseFontWeight(string $value): int
    {
        $v = trim(strtolower($value));
        if ($v === 'bold' || (int)$v >= 600) {
            return 1;
        }
        return 0;
    }

    /**
     * Parse "left" / "right" / "center" → align string
     */
    public static function parseTextAlign(string $value): string
    {
        $v = trim(strtolower($value));
        if (in_array($v, ['left', 'right', 'center'], true)) {
            return $v;
        }
        return 'left';
    }

    // ============================================================
    // Block-level parsing
    // ============================================================

    /**
     * Parse a <style> block and return class→properties map.
     * 
     * @param string $styleCss  Raw content of <style>...</style>
     * @param array  $warnings  Output: collects parse warnings
     * @return array  [className => [outputKey => value], ...]
     */
    public static function parseStyleBlock(string $styleCss, array &$warnings = []): array
    {
        $classStyles = [];

        if (!preg_match_all('#\.([a-zA-Z0-9_-]+)\s*\{([^}]*)\}#s', $styleCss, $rules, PREG_SET_ORDER)) {
            return $classStyles;
        }

        foreach ($rules as $rule) {
            $className = $rule[1];
            $body      = $rule[2];
            $props     = [];

            foreach (self::PROPERTY_MAP as $cssProp => $map) {
                $pattern = '~' . preg_quote($cssProp, '~') . '\s*:\s*([^;]+)~';
                if (preg_match($pattern, $body, $m)) {
                    $value = trim($m[1]);
                    $props[$map['key']] = call_user_func($map['parser'], $value);
                }
            }

            // If neither background nor color was specified, log a warning
            if (!isset($props['bg']) && !isset($props['fg'])) {
                $warnings[] = "CSS class '$className': no background or color property (will render as transparent)";
            }

            $classStyles[$className] = $props;
        }

        return $classStyles;
    }

    /**
     * Apply class styles to a layout element, merging with inline overrides.
     * 
     * @param array $classStyles  Result of parseStyleBlock()
     * @param string $className   CSS class name
     * @param array $overrides    Inline property overrides (e.g., from template attrs)
     * @return array  Merged style properties
     */
    public static function resolveStyle(array $classStyles, string $className, array $overrides = []): array
    {
        $style = $classStyles[$className] ?? [];
        return array_merge($style, $overrides);
    }
}
