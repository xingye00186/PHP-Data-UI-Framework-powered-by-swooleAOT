<?php
/**
 * SFC Compiler v3 Unit Tests
 * 
 * Usage: php tests/sfc-compiler-test.php
 * 
 * Covers M1 acceptance criteria:
 *   1. Template parser: Calculator.vue → AST
 *   2. CSS mappings: hexToBgr, borderColor, parseStyleBlock, 8+ properties
 *   3. AOT validator: filename dots, const arrays, variable property, variable method, PHP8 functions
 *   4. Code generation: layout output matches expected values
 *   5. Error reporting: line numbers in parse errors
 */

require_once __DIR__ . '/../tools/compiler/ast-nodes.php';
require_once __DIR__ . '/../tools/compiler/css-mappings.php';
require_once __DIR__ . '/../tools/compiler/template-parser.php';
require_once __DIR__ . '/../tools/compiler/aot-validator.php';

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        $passed++;
        echo "  PASS: $name\n";
    } catch (Throwable $e) {
        $failed++;
        echo "  FAIL: $name — {$e->getMessage()}\n";
    } catch (AssertionError $e) {
        $failed++;
        echo "  FAIL: $name — {$e->getMessage()}\n";
    }
}

echo "=== SFC Compiler v3 Unit Tests ===\n\n";

// ============================================================
// 1. CSS Mappings Tests
// ============================================================
echo "--- 1. CSS Mappings ---\n";

test('hexToBgr: #1e1e1e → BGR int', function () {
    $bgr = CssMappings::hexToBgr('#1e1e1e');
    assert($bgr === 1973790, "Expected 1973790, got $bgr");
});

test('hexToBgr: #ffffff → BGR int', function () {
    $bgr = CssMappings::hexToBgr('#ffffff');
    assert($bgr === 16777215, "Expected 16777215, got $bgr");
});

test('hexToBgr: #ff9500 → BGR int', function () {
    $bgr = CssMappings::hexToBgr('#ff9500');
    // r=255, g=149, b=0 → BGR = B<<16 | G<<8 | R = 0<<16 | 149<<8 | 255 = 38144+255 = 38399
    assert($bgr === 38399, "Expected 38399, got $bgr");
});

test('hexToBgr: shorthand #RGB (#1e1)', function () {
    $bgr = CssMappings::hexToBgr('#1e1');
    // Expanded: #11ee11 → R=17, G=238, B=17 → BGR = 17<<16 | 238<<8 | 17 = 1114112+60928+17 = 1175057
    assert($bgr === 1175057, "Expected 1175057, got $bgr");
});

test('hexToBgr: invalid hex returns 0', function () {
    $bgr = CssMappings::hexToBgr('#xyz123');
    assert($bgr === 0, "Expected 0 for invalid hex, got $bgr");
});

test('borderColor: lightens channels by +20', function () {
    $bg = 1973790; // #1e1e1e
    $border = CssMappings::borderColor($bg);
    // R=30, G=30, B=30 → +20 each → R=50, G=50, B=50
    $expected = (50 << 16) | (50 << 8) | 50; // 3282450
    assert($border === $expected, "Expected $expected, got $border");
});

test('borderColor: clamps at 255', function () {
    $bg = 0xFFFFFF; // 255,255,255 → all should clamp at 255
    $border = CssMappings::borderColor($bg);
    assert($border === 16777215, "Expected 16777215, got $border");
});

test('parseStyleBlock: extracts 8 classes from Calculator.vue style', function () {
    $styles = ".app-bg { background: #1e1e1e; }\n.display-bg { background: #2d2d2d; }\n"
            . ".expr-text { font-size: 16px; color: #969696; }\n"
            . ".display-text { font-size: 32px; color: #ffffff; font-weight: bold; }\n"
            . ".btn-num { background: #323232; color: #ffffff; }\n"
            . ".btn-op { background: #ff9500; color: #ffffff; }\n"
            . ".btn-eq { background: #007aff; color: #ffffff; }\n"
            . ".btn-func { background: #505050; color: #ffffff; }";
    
    $result = CssMappings::parseStyleBlock($styles);
    assert(count($result) === 8, "Expected 8 classes, got " . count($result));
    assert(isset($result['btn-num']['bg']), "btn-num should have bg");
    assert(isset($result['btn-num']['fg']), "btn-num should have fg");
    assert(isset($result['display-text']['bold']), "display-text should have bold=1");
    assert($result['display-text']['bold'] === 1, "display-text bold should be 1");
});

test('PROPERTY_MAP: supports 8+ CSS properties', function () {
    $propCount = count(CssMappings::PROPERTY_MAP);
    assert($propCount >= 8, "PROPERTY_MAP should have 8+ entries, got $propCount");
});

// ============================================================
// 2. Template Parser TESTS
// ============================================================
echo "\n--- 2. Template Parser ---\n";

test('Parser: parses full Calculator.vue template correctly', function () {
    $template = file_get_contents(__DIR__ . '/../src/Calculator.vue');
    // Extract template block
    preg_match('#<template[^>]*>(.*?)</template>#s', $template, $m);
    $tpl = $m[1];
    
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    assert(count($errors) === 0, "Expected 0 errors, got " . count($errors) . ": " . implode('; ', $errors));
    assert($app->width === 328, "App width should be 328");
    assert($app->height === 420, "App height should be 420");
    assert(count($app->children) === 5, "Expected 5 children (2 rects + 2 texts + 1 grid)");
    
    // Check grid has 18 buttons
    $grid = $app->children[4];
    assert($grid instanceof GridNode, "5th child should be GridNode");
    assert(count($grid->buttons) === 18, "Grid should have 18 buttons");
});

test('Parser: line numbers are tracked', function () {
    $tpl = "  <app title=\"Test\" width=\"100\" height=\"100\">\n  <rect x=\"0\" y=\"0\" w=\"10\" h=\"10\" class=\"bg\" />\n  </app>";
    
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    
    assert($app->line === 1, "App node line should be 1, got {$app->line}");
    $rect = $app->children[0];
    assert($rect->line === 2, "Rect node line should be 2, got {$rect->line}");
});

test('Parser: unknown tags produce UnknownNode + error', function () {
    $tpl = "<app title=\"X\" width=\"100\" height=\"100\"><unknown-tag /></app>";
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    assert(count($errors) >= 1, "Should report unknown tag error");
    assert(count($app->children) === 1, "Should have 1 child (UnknownNode)");
    assert($app->children[0] instanceof UnknownNode, "Should be UnknownNode");
});

test('Parser: reports missing class attribute', function () {
    $tpl = "<app title=\"X\" width=\"100\" height=\"100\"><rect x=\"0\" y=\"0\" w=\"10\" h=\"10\" /></app>";
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    $found = false;
    foreach ($errors as $e) {
        if (strpos($e->message, 'missing class') !== false) {
            $found = true;
        }
    }
    assert($found, "Should report missing class on rect");
});

test('Parser: btn outside grid is rejected', function () {
    $tpl = "<app title=\"X\" width=\"100\" height=\"100\"><btn row=\"0\" col=\"0\" label=\"X\" class=\"btn\" @click=\"test\" /></app>";
    $parser = new TemplateParser();
    $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    $found = false;
    foreach ($errors as $e) {
        if (strpos($e->message, 'inside <grid>') !== false) {
            $found = true;
        }
    }
    assert($found, "Should reject btn outside grid");
});

test('Parser: @click with argument is parsed correctly', function () {
    $tpl = "<app title=\"X\" width=\"100\" height=\"100\"><grid x=\"0\" y=\"0\" cols=\"1\" rows=\"1\" cell-w=\"50\" cell-h=\"50\" margin=\"1\"><btn row=\"0\" col=\"0\" label=\"+\" class=\"btn\" @click=\"handleButton('+')\" /></grid></app>";
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    
    $grid = $app->children[0];
    $btn = $grid->buttons[0];
    assert($btn->handler === 'handleButton', "Handler should be handleButton, got {$btn->handler}");
    assert($btn->arg === '+', "Arg should be '+', got {$btn->arg}");
});

test('Parser: AST dump produces valid JSON', function () {
    $tpl = "<app title=\"X\" width=\"100\" height=\"100\"><rect x=\"0\" y=\"0\" w=\"10\" h=\"10\" class=\"bg\" /></app>";
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    $json = $parser->dumpAst($app);
    
    $decoded = json_decode($json, true);
    assert($decoded !== null, "AST dump should be valid JSON");
    assert($decoded['_type'] === 'AppNode', "Root should be AppNode");
});

// ============================================================
// 3. AST → Layout lowerToLayout() tests
// ============================================================
echo "\n--- 3. AST → Layout Lowering ---\n";

test('lowerToLayout: produces correct elements + buttons arrays', function () {
    $template = file_get_contents(__DIR__ . '/../src/Calculator.vue');
    preg_match('#<template[^>]*>(.*?)</template>#s', $template, $m);
    $tpl = $m[1];
    
    preg_match('#<style[^>]*>(.*?)</style>#s', $template, $m);
    $styles = $m[1] ?? '';
    
    $classStyles = CssMappings::parseStyleBlock($styles);
    $parser = new TemplateParser();
    $app = $parser->parse($tpl);
    $layout = $parser->lowerToLayout($app, $classStyles);
    
    assert(count($layout['elements']) === 4, "Should have 4 elements");
    assert(count($layout['buttons']) === 18, "Should have 18 buttons");
    
    // Check first button coordinates
    $btnC = $layout['buttons'][0];
    assert($btnC['label'] === 'C', "First button should be C");
    assert($btnC['x'] === 2 && $btnC['y'] === 82, "C should be at (2,82)");
    
    // Check = button
    $btnEq = $layout['buttons'][15];
    assert($btnEq['label'] === '=', "Button 15 should be =");
});

// ============================================================
// 4. AOT Validator Tests
// ============================================================
echo "\n--- 4. AOT Validator ---\n";

test('AotValidator: Calculator.gen.php passes', function () {
    $v = new AotValidator();
    $code = "<?php\nclass Calculator extends ReactiveComponent {}\n";
    $result = $v->validate($code, 'Calculator.gen.php');
    assert($result === true, "Calculator.gen.php should pass validation");
    assert(count($v->getErrors()) === 0, "Should have 0 errors: " . implode('; ', $v->getErrors()));
});

test('AotValidator: CalculatorLayout_gen.php passes', function () {
    $v = new AotValidator();
    $code = "<?php\nfunction getLayout(): array { return []; }\n";
    $result = $v->validate($code, 'CalculatorLayout_gen.php');
    assert($result === true, "CalculatorLayout_gen.php should pass");
});

test('AotValidator: rejects multi-dot filename stem', function () {
    $v = new AotValidator();
    $code = "<?php\nclass Foo {}\n";
    $result = $v->validate($code, 'Foo.Layout.gen.php');
    assert($result === false, "Multi-dot stem should fail");
    $errors = $v->getErrors();
    assert(strpos($errors[0], 'dots') !== false, "Error should mention dots");
});

test('AotValidator: rejects const array', function () {
    $v = new AotValidator();
    $code = "<?php\nconst LAYOUT = ['a' => [1, 2, 3]];\n";
    $result = $v->validate($code, 'test.php');
    assert($result === false, "const array should fail");
});

test('AotValidator: rejects $obj->$var (variable property access)', function () {
    $v = new AotValidator();
    $code = '<?php $result = $obj->$propName; ?>';
    $result = $v->validate($code, 'test.php');
    assert($result === false, "Variable property access should fail");
});

test('AotValidator: rejects $obj->$method() (variable method call)', function () {
    $v = new AotValidator();
    $code = '<?php $obj->$methodName(); ?>';
    $result = $v->validate($code, 'test.php');
    assert($result === false, "Variable method call should fail");
});

test('AotValidator: warns on str_contains (PHP8 only)', function () {
    $v = new AotValidator();
    $code = '<?php if (str_contains($haystack, $needle)) {} ?>';
    $result = $v->validate($code, 'test.php');
    // Warnings are non-fatal
    $warnings = $v->getWarnings();
    assert(count($warnings) >= 1, "Should warn about str_contains");
});

// ============================================================
// 5. Full Pipeline Integration Test
// ============================================================
echo "\n--- 5. Full Pipeline Integration ---\n";

test('Integration: compile Calculator.vue and verify output consistency', function () {
    // Run the compiler programmatically (simulate CLI)
    $vueFile = __DIR__ . '/../src/Calculator.vue';
    $source = file_get_contents($vueFile);
    $dir = dirname(realpath($vueFile));
    $baseName = pathinfo($vueFile, PATHINFO_FILENAME);
    
    // Step 1: Extract blocks
    preg_match('#<template[^>]*>(.*?)</template>#s', $source, $m);
    $template = $m[1] ?? '';
    preg_match('#<script[^>]*lang=["\']php["\'][^>]*>(.*?)</script>#s', $source, $m);
    $script = trim($m[1] ?? '');
    preg_match('#<style[^>]*>(.*?)</style>#s', $source, $m);
    $styles = $m[1] ?? '';
    
    assert($template !== '', "Template should not be empty");
    assert($script !== '', "Script should not be empty");
    
    // Step 2: Parse styles
    $classStyles = CssMappings::parseStyleBlock($styles);
    assert(count($classStyles) === 8, "Should parse 8 CSS classes");
    
    // Step 3: Parse template → AST  
    $parser = new TemplateParser();
    $app = $parser->parse($template);
    $errors = $parser->getErrors();
    assert(count($errors) === 0, "Should have 0 parse errors: " . implode('; ', $errors));
    
    // Step 4: AST → layout arrays
    $layout = $parser->lowerToLayout($app, $classStyles);
    assert(count($layout['elements']) === 4, "Should have 4 layout elements");
    assert(count($layout['buttons']) === 18, "Should have 18 layout buttons");
    
    // Step 5: Simulate code generation output
    $buttonsExport = var_export($layout['buttons'], true);
    $elementsExport = var_export($layout['elements'], true);
    
    $layoutCode = "<?php\nconst WINDOW_WIDTH = {$app->width};\nconst WINDOW_HEIGHT = {$app->height};\n\n"
                . "function getLayout(): array { return [\n"
                . "'elements' => $elementsExport,\n'buttons' => $buttonsExport,\n]; }\n";
    
    $classCode = "<?php\nuse native_types;\n\nclass $baseName extends ReactiveComponent {\n$script\n}\n";
    
    // Step 6: AOT validation
    $v = new AotValidator();
    $layoutOk = $v->validate($layoutCode, "$baseName" . "Layout_gen.php");
    $classOk = $v->validate($classCode, "$baseName.gen.php");
    
    assert($layoutOk, "Layout code should pass AOT validation: " . implode('; ', $v->getErrors()));
    assert($classOk, "Class code should pass AOT validation: " . implode('; ', $v->getErrors()));
});

// ============================================================
// Summary
// ============================================================
echo "\n=== Results: $passed passed, $failed failed ===\n";

if ($failed > 0) {
    exit(1);
}
echo "All tests passed!\n";
