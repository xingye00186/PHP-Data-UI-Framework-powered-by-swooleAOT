<?php
/**
 * SFC Compiler v4 — Vue-like Single File Component compiler for AOT desktop apps
 * 
 * Usage: php tools/sfc-compiler.php src/Calculator.vue [--dump-ast]
 * 
 * Architecture (M2 refactored):
 *   1. Block Extraction:     template / script / style from .vue
 *   2. Style Parsing:        CSS class → GDI properties (via CssMappings)
 *   3. Template Parsing:     recursive descent → AST  (via TemplateParser)
 *   4. AST → Layout Arrays:  compile-time coordinate calculation + bindKeys
 *   5. AOT Validation:       check generated code before write (via AotValidator)
 *   6. Code Generation:      .gen.php ×2 output + auto dirty + auto getBindValue
 * 
 * This tool runs OUTSIDE the AOT pipeline (standard PHP CLI).
 * Generated .gen.php files are consumed by the AOT compiler.
 */

// ---- Load compiler modules ----
$compilerDir = __DIR__ . '/compiler';
require_once $compilerDir . '/ast-nodes.php';
require_once $compilerDir . '/css-mappings.php';
require_once $compilerDir . '/template-parser.php';
require_once $compilerDir . '/aot-validator.php';
require_once $compilerDir . '/script-analyzer.php';

// ---- CLI ----
if ($argc < 2) {
    echo "Usage: php sfc-compiler.php <path/to/component.vue> [--dump-ast]\n";
    exit(1);
}

$vueFile = $argv[1];
$dumpAst = in_array('--dump-ast', $argv, true);

if (!file_exists($vueFile)) {
    echo "Error: File not found: $vueFile\n";
    exit(1);
}

$source = file_get_contents($vueFile);
$baseName = pathinfo($vueFile, PATHINFO_FILENAME);

// Output to gen/ directory (relative to project root)
$projectRoot = dirname(__DIR__);
$outDir = $projectRoot . DIRECTORY_SEPARATOR . 'gen';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

echo "SFC Compiler v4: $vueFile\n";

// ============================================================
// Step 1: Extract blocks (template / script / style)
// ============================================================
$template = '';
$script   = '';
$styles   = '';
$blockErrors = [];

// Track line numbers for error reporting
$lines = explode("\n", $source);

if (preg_match('#<template[^>]*>(.*?)</template>#s', $source, $m)) {
    $template = $m[1];
} else {
    $blockErrors[] = "No <template> block found in $vueFile";
}

if (preg_match('#<script[^>]*lang=["\']php["\'][^>]*>(.*?)</script>#s', $source, $m)) {
    $script = trim($m[1]);
} else {
    $blockErrors[] = "No <script lang=\"php\"> block found in $vueFile";
}

if (preg_match('#<style[^>]*>(.*?)</style>#s', $source, $m)) {
    $styles = $m[1];
}
// style block is optional — not an error if missing

if (count($blockErrors) > 0) {
    foreach ($blockErrors as $err) {
        echo "Error: $err\n";
    }
    exit(1);
}

echo "  Template: " . strlen($template) . " bytes\n";
echo "  Script:   " . strlen($script) . " bytes\n";
echo "  Style:    " . strlen($styles) . " bytes\n";

// ============================================================
// Step 2: Parse styles → class map (via CssMappings)
// ============================================================
$styleWarnings = [];
$classStyles = CssMappings::parseStyleBlock($styles, $styleWarnings);
echo "  Classes:  " . count($classStyles) . " parsed\n";

foreach ($styleWarnings as $w) {
    echo "  [WARN] CSS: $w\n";
}

// ============================================================
// Step 3: Parse template → AST (via TemplateParser)
// ============================================================
$parser = new TemplateParser();
$app = $parser->parse($template);
$parseErrors = $parser->getErrors();

// Report parse errors
if (count($parseErrors) > 0) {
    echo "\n=== Template Parse Errors (" . count($parseErrors) . ") ===\n";
    foreach ($parseErrors as $err) {
        echo "  $err\n";
    }
    echo "========================================\n\n";
}

// --dump-ast mode
if ($dumpAst) {
    echo "\n=== AST Dump ===\n";
    echo $parser->dumpAst($app);
    echo "\n=== End AST ===\n\n";
}

// ============================================================
// Step 4: AST → Layout Arrays (compiler-time coordinate calculation)
// ============================================================
$layout = $parser->lowerToLayout($app, $classStyles);
$elements = $layout['elements'];
$buttons  = $layout['buttons'];
$bindKeys = $layout['bindKeys'] ?? [];       // v4 M2.2
$handlerMap = $layout['handlerMap'] ?? [];  // v4 M2.3
$condProps = $layout['condProps'] ?? [];    // v4 M2.5

echo "  Elements: " . count($elements) . " (rects + texts)\n";
echo "  Buttons:  " . count($buttons) . "\n";
echo "  BindKeys: " . count($bindKeys) . "\n";
echo "  Handlers: " . count($handlerMap) . "\n";
echo "  CondProps: " . count($condProps) . "\n";

// ============================================================
// Step 5: Generate output files
// ============================================================

// --- Generate CalculatorLayout_gen.php ---
$buttonsExport  = var_export($buttons, true);
$elementsExport = var_export($elements, true);
$appWidth  = $app->width;
$appHeight = $app->height;

$layoutContent = <<<PHP
<?php
/**
 * AUTO-GENERATED by SFC Compiler v4 — DO NOT EDIT
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

// --- Generate Calculator.gen.php (class file) ---
// v4: Auto-inject $this->dirty = true into methods that modify reactive properties
$analyzer = new ScriptAnalyzer();
$classBody = $analyzer->injectDirty($script);

// v4 M2.2: Generate getBindValue() body from collected bindKeys
$getBindValueBody = '';
if (count($bindKeys) > 0) {
    foreach ($bindKeys as $key) {
        $getBindValueBody .= "        if (\$bindKey === '$key') {\n";
        $getBindValueBody .= "            return \$this->$key;\n";
        $getBindValueBody .= "        }\n";
    }
    $getBindValueBody .= "        return '';";
} else {
    $getBindValueBody = "        return '';";
}

// v4 M2.3: Generate dispatchClick() body from collected handlerMap
$dispatchClickBody = '';
if (count($handlerMap) > 0) {
    $dispatchClickBody .= "        \$handler = \$btn['handler'];\n";
    $first = true;
    foreach ($handlerMap as $handler => $hasArg) {
        $prefix = $first ? 'if' : 'elseif';
        $first = false;
        if ($hasArg) {
            $dispatchClickBody .= "        {$prefix} (\$handler === '$handler') {\n";
            $dispatchClickBody .= "            \$this->$handler(\$btn['arg']);\n";
            $dispatchClickBody .= "        }\n";
        } else {
            $dispatchClickBody .= "        {$prefix} (\$handler === '$handler') {\n";
            $dispatchClickBody .= "            \$this->$handler();\n";
            $dispatchClickBody .= "        }\n";
        }
    }
} else {
    $dispatchClickBody = "        // No handlers defined";
}

// v4 M2.5: Generate evalCondition() body from collected condProps
$evalConditionBody = '';
if (count($condProps) > 0) {
    $evalConditionBody .= "        \$prop = \$cond['prop'];\n";
    $evalConditionBody .= "        \$op = \$cond['op'];\n";
    // truthy check for each prop
    $evalConditionBody .= "        if (\$op === 'truthy') {\n";
    foreach ($condProps as $prop) {
        $evalConditionBody .= "            if (\$prop === '$prop') return \$this->$prop ? true : false;\n";
    }
    $evalConditionBody .= "            return false;\n";
    $evalConditionBody .= "        }\n";
    // == comparison for each prop
    $evalConditionBody .= "        if (\$op === '==') {\n";
    $evalConditionBody .= "            \$value = \$cond['value'];\n";
    foreach ($condProps as $prop) {
        $evalConditionBody .= "            if (\$prop === '$prop') return \$this->$prop === \$value;\n";
    }
    $evalConditionBody .= "            return false;\n";
    $evalConditionBody .= "        }\n";
    // != comparison
    $evalConditionBody .= "        if (\$op === '!=') {\n";
    $evalConditionBody .= "            \$value = \$cond['value'];\n";
    foreach ($condProps as $prop) {
        $evalConditionBody .= "            if (\$prop === '$prop') return \$this->$prop !== \$value;\n";
    }
    $evalConditionBody .= "            return false;\n";
    $evalConditionBody .= "        }\n";
    $evalConditionBody .= "        return false;";
} else {
    $evalConditionBody = "        return true; // no conditions defined";
}

$classContent = <<<PHP
<?php
/**
 * AUTO-GENERATED by SFC Compiler v4 — DO NOT EDIT
 * Source: $baseName.vue
 */

use native_types;

class $baseName extends ReactiveComponent
{
$classBody

    public function getBindValue(string \$bindKey): string
    {
$getBindValueBody
    }

    public function dispatchClick(array \$btn): void
    {
$dispatchClickBody
    }

    public function evalCondition(array \$cond): bool
    {
$evalConditionBody
    }

    public function __construct(string \$componentId = '$baseName')
    {
        parent::__construct(\$componentId);
    }
}
PHP;

// ============================================================
// Step 6: AOT Validation (before writing to disk)
// ============================================================
$validator = new AotValidator();

$layoutPath = $outDir . DIRECTORY_SEPARATOR . $baseName . 'Layout_gen.php';
$classPath  = $outDir . DIRECTORY_SEPARATOR . $baseName . '.gen.php';

$layoutOk = $validator->validate($layoutContent, $layoutPath);
$classOk  = $validator->validate($classContent, $classPath);

echo "\n" . $validator->report();

if (!$layoutOk || !$classOk) {
    echo "\nAOT validation FAILED. Generated files NOT written.\n";
    echo "Fix the issues above and re-run the compiler.\n";
    exit(1);
}

// ---- Passed validation → write files ----
file_put_contents($layoutPath, $layoutContent);
echo "  Generated:  $layoutPath (" . strlen($layoutContent) . " bytes)\n";

file_put_contents($classPath, $classContent);
echo "  Generated:  $classPath (" . strlen($classContent) . " bytes)\n";

echo "\nDone.\n";
