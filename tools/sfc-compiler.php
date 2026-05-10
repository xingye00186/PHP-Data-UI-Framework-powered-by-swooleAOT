<?php
/**
 * SFC Compiler - Vue-like Single File Component compiler for AOT desktop apps
 * 
 * Usage: php tools/sfc-compiler.php src/Calculator.vue
 * 
 * Outputs:
 *   src/Calculator.gen.php         - ReactiveComponent subclass
 *   src/CalculatorLayout.gen.php   - Layout constants + element arrays
 * 
 * This tool runs OUTSIDE the AOT pipeline (standard PHP CLI).
 * Generated .gen.php files are consumed by the AOT compiler.
 */

if ($argc < 2) {
    echo "Usage: php sfc-compiler.php <path/to/component.vue>\n";
    exit(1);
}

$vueFile = $argv[1];
if (!file_exists($vueFile)) {
    echo "Error: File not found: $vueFile\n";
    exit(1);
}

$source = file_get_contents($vueFile);
$dir = dirname(realpath($vueFile));
$baseName = pathinfo($vueFile, PATHINFO_FILENAME);

echo "SFC Compiler: $vueFile\n";

// ============================================================
// Step 1: Extract blocks
// ============================================================
$template = '';
$script   = '';
$styles   = '';

if (preg_match('#<template[^>]*>(.*?)</template>#s', $source, $m)) {
    $template = $m[1];
}
if (preg_match('#<script[^>]*lang=["\']php["\'][^>]*>(.*?)</script>#s', $source, $m)) {
    $script = trim($m[1]);
}
if (preg_match('#<style[^>]*>(.*?)</style>#s', $source, $m)) {
    $styles = $m[1];
}

if ($template === '') {
    echo "Error: No <template> block found\n";
    exit(1);
}
if ($script === '') {
    echo "Error: No <script lang=\"php\"> block found\n";
    exit(1);
}

echo "  Template: " . strlen($template) . " bytes\n";
echo "  Script:   " . strlen($script) . " bytes\n";
echo "  Style:    " . strlen($styles) . " bytes\n";

// ============================================================
// Step 2: Parse styles → class map
// ============================================================
$classStyles = [];
if (preg_match_all('#\.([a-zA-Z0-9_-]+)\s*\{([^}]*)\}#s', $styles, $styleMatches, PREG_SET_ORDER)) {
    foreach ($styleMatches as $rule) {
        $className = $rule[1];
        $body = $rule[2];
        $props = [];
        if (preg_match('~background\s*:\s*#([0-9a-fA-F]{6})~', $body, $m)) {
            $props['bg'] = hexToBgr($m[1]);
        }
        if (preg_match('~color\s*:\s*#([0-9a-fA-F]{6})~', $body, $m)) {
            $props['fg'] = hexToBgr($m[1]);
        }
        if (preg_match('#font-size\s*:\s*(\d+)#', $body, $m)) {
            $props['fontSize'] = (int)$m[1];
        }
        if (preg_match('#font-weight\s*:\s*bold#', $body)) {
            $props['bold'] = 1;
        } else {
            $props['bold'] = $props['bold'] ?? 0;
        }
        $classStyles[$className] = $props;
    }
}
echo "  Classes:   " . count($classStyles) . " parsed\n";

/**
 * Convert CSS hex color #RRGGBB to GDI BGR integer
 */
function hexToBgr(string $hex): int {
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return ($b << 16) | ($g << 8) | $r;
}

/**
 * Derive border color from background (lighten each channel by 20)
 */
function borderColor(int $bg): int {
    $r = min(255, (($bg >> 16) & 0xFF) + 20);
    $g = min(255, (($bg >> 8)  & 0xFF) + 20);
    $b = min(255, ($bg         & 0xFF) + 20);
    return ($r << 16) | ($g << 8) | $b;
}

// ============================================================
// Step 3: Parse template elements
// ============================================================
$appWidth  = 336;
$appHeight = 430;
$elements  = [];
$buttons   = [];

// --- Parse <app> ---
if (preg_match('#<app\s+title="([^"]*)"\s+width="(\d+)"\s+height="(\d+)"\s*>#', $template, $m)) {
    $appWidth  = (int)$m[2];
    $appHeight = (int)$m[3];
}

// --- Parse <rect> elements ---
if (preg_match_all('#<rect\s+x="(\d+)"\s+y="(\d+)"\s+w="(\d+)"\s+h="(\d+)"\s+class="([^"]+)"\s*/>#', $template, $m, PREG_SET_ORDER)) {
    foreach ($m as $r) {
        $cls = $r[5];
        $style = $classStyles[$cls] ?? [];
        $elements[] = [
            'type'  => 'rect',
            'x'     => (int)$r[1],
            'y'     => (int)$r[2],
            'w'     => (int)$r[3],
            'h'     => (int)$r[4],
            'color' => $style['bg'] ?? 0,
        ];
    }
}

// --- Parse <text> elements ---
if (preg_match_all('#<text\s+((?:[a-z:@-]+="[^"]*"\s*)+)\s*/>#', $template, $m, PREG_SET_ORDER)) {
    foreach ($m as $t) {
        $attrs = parseAttrs($t[1]);
        $cls = $attrs['class'] ?? '';
        $style = $classStyles[$cls] ?? [];
        $textEl = [
            'type' => 'text',
            'bind' => $attrs[':bind'] ?? '',
            'x'    => isset($attrs['x']) ? (int)$attrs['x'] : 0,
            'y'    => isset($attrs['y']) ? (int)$attrs['y'] : 0,
            'align'=> $attrs['align'] ?? 'left',
            'fontSize' => $style['fontSize'] ?? 16,
            'color'    => $style['fg'] ?? 0xFFFFFF,
            'bold'     => $style['bold'] ?? 0,
        ];
        if (isset($attrs['container-w'])) {
            $textEl['containerW'] = (int)$attrs['container-w'];
        }
        if (isset($attrs['container-x'])) {
            $textEl['containerX'] = (int)$attrs['container-x'];
        }
        $elements[] = $textEl;
    }
}

// --- Parse <grid> and <btn> ---
$gridX = 0; $gridY = 0;
$gridCols = 4; $gridRows = 5;
$cellW = 80; $cellH = 60;
$margin = 4;

if (preg_match('#<grid\s+((?:[a-z-]+="[^"]*"\s*)+)\s*>#', $template, $m)) {
    $gAttrs = parseAttrs($m[1]);
    $gridX    = (int)($gAttrs['x'] ?? 0);
    $gridY    = (int)($gAttrs['y'] ?? 0);
    $gridCols = (int)($gAttrs['cols'] ?? 4);
    $gridRows = (int)($gAttrs['rows'] ?? 5);
    $cellW    = (int)($gAttrs['cell-w'] ?? 80);
    $cellH    = (int)($gAttrs['cell-h'] ?? 60);
    $margin   = (int)($gAttrs['margin'] ?? 4);
}

// --- Parse <btn> elements ---
if (preg_match_all('#<btn\s+((?:[a-z@-]+(?:="[^"]*")?\s*)+)\s*/>#', $template, $m, PREG_SET_ORDER)) {
    foreach ($m as $b) {
        $attrs = parseAttrs($b[1]);
        $row   = (int)($attrs['row'] ?? 0);
        $col   = (int)($attrs['col'] ?? 0);
        $label = $attrs['label'] ?? '';
        $cls   = $attrs['class'] ?? '';
        $handler = $attrs['@click'] ?? '';

        $style = $classStyles[$cls] ?? [];
        $bg = $style['bg'] ?? 0x323232;
        $fg = $style['fg'] ?? 0xFFFFFF;

        $bx = $gridX + $col * $cellW + $margin;
        $by = $gridY + $row * $cellH + $margin;
        $bw = $cellW - $margin * 2;
        $bh = $cellH - $margin * 2;

        // Parse handler: "method" or "method('arg')"
        $handlerName = $handler;
        $handlerArg  = null;
        if (preg_match("/^(\w+)\(['\"]([^'\"]*)['\"]\)$/", $handler, $hm)) {
            $handlerName = $hm[1];
            $handlerArg  = $hm[2];
        }

        $buttons[] = [
            'label'   => $label,
            'x'       => $bx,
            'y'       => $by,
            'w'       => $bw,
            'h'       => $bh,
            'bg'      => $bg,
            'fg'      => $fg,
            'border'  => borderColor($bg),
            'handler' => $handlerName,
            'arg'     => $handlerArg,
        ];
    }
}

echo "  Elements:  " . count($elements) . " (rects + texts)\n";
echo "  Buttons:   " . count($buttons) . "\n";

/**
 * Parse attribute string like: x="4" y="4" w="328" h="72" class="display-bg"
 */
function parseAttrs(string $str): array {
    $attrs = [];
    if (preg_match_all('#([a-z@:-]+)(?:="([^"]*)")?#', trim($str), $m, PREG_SET_ORDER)) {
        foreach ($m as $a) {
            $attrs[$a[1]] = $a[2] ?? '';
        }
    }
    return $attrs;
}

// ============================================================
// Step 4: Generate output files
// ============================================================

// --- Generate CalculatorLayout_gen.php ---
$buttonsExport = var_export($buttons, true);
$elementsExport = var_export($elements, true);

$layoutContent = <<<PHP
<?php
/**
 * AUTO-GENERATED by SFC Compiler — DO NOT EDIT
 * Source: $baseName.vue
 */

const WINDOW_WIDTH  = $appWidth;
const WINDOW_HEIGHT = $appHeight;

function getLayout(): array
{
    return [
        'window_width'  => $appWidth,
        'window_height' => $appHeight,
        'elements'      => $elementsExport,
        'buttons'       => $buttonsExport,
    ];
}
PHP;

$layoutPath = $dir . DIRECTORY_SEPARATOR . $baseName . 'Layout_gen.php';
file_put_contents($layoutPath, $layoutContent);
echo "  Generated:  $layoutPath\n";

// --- Generate Calculator.gen.php (class file, OK with dot in filename) ---
// Extract class body from script block (properties + methods, no class wrapper)
$classBody = $script;

$classContent = <<<PHP
<?php
/**
 * AUTO-GENERATED by SFC Compiler — DO NOT EDIT
 * Source: $baseName.vue
 */

use native_types;

class $baseName extends ReactiveComponent
{
$classBody

    public function __construct(string \$componentId = '$baseName')
    {
        parent::__construct(\$componentId);
    }
}
PHP;

$classPath = $dir . DIRECTORY_SEPARATOR . $baseName . '.gen.php';
file_put_contents($classPath, $classContent);
echo "  Generated:  $classPath\n";

echo "Done.\n";
