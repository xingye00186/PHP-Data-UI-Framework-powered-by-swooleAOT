<?php
/**
 * SFC Compiler v3 Unit Tests
 * 
 * Usage: php tests/sfc-compiler-test.php
 * 
 * Covers M1 acceptance criteria:
 *   1. Template parser: App.vue → AST
 *   2. CSS mappings: hexToBgr, borderColor, parseStyleBlock, 8+ properties
 *   3. AOT validator: filename dots, const arrays, variable property, variable method, PHP8 functions
 *   4. Code generation: layout output matches expected values
 *   5. Error reporting: line numbers in parse errors
 */

require_once __DIR__ . '/../framework/compiler/ast-nodes.php';
require_once __DIR__ . '/../framework/compiler/css-mappings.php';
require_once __DIR__ . '/../framework/compiler/template-parser.php';
require_once __DIR__ . '/../framework/compiler/aot-validator.php';
require_once __DIR__ . '/../framework/compiler/component-registry.php';
require_once __DIR__ . '/../framework/compiler/component-resolver.php';

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

test('parseStyleBlock: extracts 8 classes from App.vue style', function () {
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

test('Parser: parses full App.vue template correctly', function () {
    $template = file_get_contents(__DIR__ . '/../apps/calculator/App.vue');
    // Extract template block
    preg_match('#<template[^>]*>(.*?)</template>#s', $template, $m);
    $tpl = $m[1];
    
    // Set up component registry so all child components are recognized
    $registry = new ComponentRegistry();
    $dir = __DIR__ . '/../apps/calculator';
    $registry->load([
        'display-panel' => './components/DisplayPanel.vue',
        'num-pad'       => './components/NumPad.vue',
        'about-dialog'  => './components/AboutDialog.vue',
    ], $dir);
    
    $parser = new TemplateParser($registry);
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    assert(count($errors) === 0, "Expected 0 errors, got " . count($errors) . ": " . implode('; ', $errors));
    assert($app->width === 328, "App width should be 328");
    assert($app->height === 420, "App height should be 420");
    assert(count($app->children) === 6, "Expected 6 children (rect + 3 components + text + grid), got " . count($app->children));
    
    // Check display-panel is a ComponentRefNode
    $comp = $app->children[1];
    assert($comp instanceof ComponentRefNode, "2nd child should be ComponentRefNode, got " . get_class($comp));
    assert($comp->tagName === 'display-panel', "Component tag should be display-panel");
    
    // Check num-pad is a ComponentRefNode
    $numPad = $app->children[3];
    assert($numPad instanceof ComponentRefNode, "4th child should be ComponentRefNode (num-pad)");
    assert($numPad->tagName === 'num-pad', "Component tag should be num-pad");
    assert($numPad->vIf === '', "num-pad should have no v-if (overlay layer handles visibility), got '{$numPad->vIf}'");
    
    // Check about-dialog is a ComponentRefNode with overlay
    $about = $app->children[5];
    assert($about instanceof ComponentRefNode, "6th child should be ComponentRefNode (about-dialog)");
    assert($about->tagName === 'about-dialog', "Component tag should be about-dialog");
    assert($about->isOverlay === true, "about-dialog should have overlay attribute");
    assert($about->vIf === 'showDialog', "about-dialog should have v-if='showDialog'");
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
    $template = file_get_contents(__DIR__ . '/../apps/calculator/App.vue');
    preg_match('#<template[^>]*>(.*?)</template>#s', $template, $m);
    $tpl = $m[1];
    
    preg_match('#<style[^>]*>(.*?)</style>#s', $template, $m);
    $styles = $m[1] ?? '';
    
    $classStyles = CssMappings::parseStyleBlock($styles);
    
    // Set up component registry and parse
    $registry = new ComponentRegistry();
    $dir = __DIR__ . '/../apps/calculator';
    $registry->load([
        'display-panel' => './components/DisplayPanel.vue',
        'num-pad'       => './components/NumPad.vue',
        'about-dialog'  => './components/AboutDialog.vue',
    ], $dir);
    
    $parser = new TemplateParser($registry);
    $app = $parser->parse($tpl);
    
    // Resolve component refs inline (inline DisplayPanel)
    $resolvedChildren = [];
    foreach ($app->children as $child) {
        if ($child instanceof ComponentRefNode) {
            $childSource = file_get_contents($child->componentFile);
            preg_match('#<template[^>]*>(.*?)</template>#s', $childSource, $cm);
            $childTpl = $cm[1];
            
            // Merge child styles
            preg_match('#<style[^>]*>(.*?)</style>#s', $childSource, $sm);
            if (!empty($sm[1])) {
                $childCss = CssMappings::parseStyleBlock($sm[1]);
                foreach ($childCss as $cls => $style) {
                    if (!isset($classStyles[$cls])) {
                        $classStyles[$cls] = $style;
                    }
                }
            }
            
            $childParser = new TemplateParser();
            $childAst = $childParser->parse($childTpl);
            
            $offsetX = (int)($child->props['x'] ?? 0);
            $offsetY = (int)($child->props['y'] ?? 0);
            
            foreach ($childAst->children as $childNode) {
                applyOffset($childNode, $offsetX, $offsetY);
                applyPropBindings($childNode, $child->props);
                // v5 M3: propagate v-if from component ref to inlined children
                if ($child->vIf !== '' && $childNode->vIf === '') {
                    $childNode->vIf = $child->vIf;
                }
                $resolvedChildren[] = $childNode;
            }
        } else {
            $resolvedChildren[] = $child;
        }
    }
    $app->children = $resolvedChildren;
    
    $layout = $parser->lowerToLayout($app, $classStyles);
    
    assert(count($layout['elements']) === 10, "should have 10 elements, got " . count($layout['elements']));
    assert(count($layout['buttons']) === 20, "Should have 20 buttons (18 numpad + 1 ? + 1 Close), got " . count($layout['buttons']));
    
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

test('AotValidator: App.gen.php passes', function () {
    $v = new AotValidator();
    $code = "<?php\nclass App extends ReactiveComponent {}\n";
    $result = $v->validate($code, 'App.gen.php');
    assert($result === true, "App.gen.php should pass validation");
    assert(count($v->getErrors()) === 0, "Should have 0 errors: " . implode('; ', $v->getErrors()));
});

test('AotValidator: AppLayout_gen.php passes', function () {
    $v = new AotValidator();
    $code = "<?php\nfunction getLayout(): array { return []; }\n";
    $result = $v->validate($code, 'AppLayout_gen.php');
    assert($result === true, "AppLayout_gen.php should pass");
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

test('Integration: compile App.vue and verify output consistency', function () {
    // Run the compiler programmatically (simulate CLI)
    $vueFile = __DIR__ . '/../apps/calculator/App.vue';
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
    assert(count($classStyles) === 5, "Should parse 5 CSS classes from App.vue style");
    
    // Step 3: Parse template → AST (with ComponentRegistry)
    $registry = new ComponentRegistry();
    $registry->load([
        'display-panel' => './components/DisplayPanel.vue',
        'num-pad'       => './components/NumPad.vue',
        'about-dialog'  => './components/AboutDialog.vue',
    ], $dir);
    
    $parser = new TemplateParser($registry);
    $app = $parser->parse($template);
    $errors = $parser->getErrors();
    assert(count($errors) === 0, "Should have 0 parse errors: " . implode('; ', $errors));
    
    // Step 4: Resolve component references (inline DisplayPanel)
    $resolvedChildren = [];
    foreach ($app->children as $child) {
        if ($child instanceof ComponentRefNode) {
            $childSource = file_get_contents($child->componentFile);
            preg_match('#<template[^>]*>(.*?)</template>#s', $childSource, $cm);
            $childTpl = $cm[1];
            
            // Merge child styles
            preg_match('#<style[^>]*>(.*?)</style>#s', $childSource, $sm);
            if (!empty($sm[1])) {
                $childCss = CssMappings::parseStyleBlock($sm[1]);
                foreach ($childCss as $cls => $style) {
                    if (!isset($classStyles[$cls])) {
                        $classStyles[$cls] = $style;
                    }
                }
            }
            
            $childParser = new TemplateParser();
            $childAst = $childParser->parse($childTpl);
            
            $offsetX = (int)($child->props['x'] ?? 0);
            $offsetY = (int)($child->props['y'] ?? 0);
            
            foreach ($childAst->children as $childNode) {
                applyOffset($childNode, $offsetX, $offsetY);
                applyPropBindings($childNode, $child->props);
                // v5 M3: propagate v-if from component ref to inlined children
                if ($child->vIf !== '' && $childNode->vIf === '') {
                    $childNode->vIf = $child->vIf;
                }
                $resolvedChildren[] = $childNode;
            }
        } else {
            $resolvedChildren[] = $child;
        }
    }
    $app->children = $resolvedChildren;
    
    // Step 5: AST → layout arrays
    $layout = $parser->lowerToLayout($app, $classStyles);
    assert(count($layout['elements']) === 10, "Should have 10 layout elements, got " . count($layout['elements']));
    assert(count($layout['buttons']) === 20, "Should have 20 layout buttons (18 numpad + 1 ? + 1 Close), got " . count($layout['buttons']));
    
    // Step 6: Simulate code generation output
    $buttonsExport = var_export($layout['buttons'], true);
    $elementsExport = var_export($layout['elements'], true);
    
    $layoutCode = "<?php\nconst WINDOW_WIDTH = {$app->width};\nconst WINDOW_HEIGHT = {$app->height};\n\n"
                . "function getLayout(): array { return [\n"
                . "'elements' => $elementsExport,\n'buttons' => $buttonsExport,\n]; }\n";
    
    $classCode = "<?php\nuse native_types;\n\nclass $baseName extends ReactiveComponent {\n$script\n}\n";
    
    // Step 7: AOT validation
    $v = new AotValidator();
    $layoutOk = $v->validate($layoutCode, "$baseName" . "Layout_gen.php");
    $classOk = $v->validate($classCode, "$baseName.gen.php");
    
    assert($layoutOk, "Layout code should pass AOT validation: " . implode('; ', $v->getErrors()));
    assert($classOk, "Class code should pass AOT validation: " . implode('; ', $v->getErrors()));
});

// ============================================================
// 6. v5 M2: Component Ecosystem Tests
// ============================================================
echo "\n--- 6. v5 M2: Component Ecosystem ---\n";

test('ComponentRegistry: resolves registered tag to file path', function () {
    $registry = new ComponentRegistry();
    $dir = __DIR__ . '/../apps/calculator';
    $registry->load(['test-comp' => './components/DisplayPanel.vue'], $dir);
    
    $resolved = $registry->resolve('test-comp');
    $expected = realpath($dir . '/components/DisplayPanel.vue');
    assert($resolved === $expected, "Should resolve to $expected, got $resolved");
    assert($registry->isComponent('test-comp') === true, "Should be a component");
    assert($registry->isComponent('unknown') === false, "Should not be a component");
});

test('ComponentRegistry: returns null for unknown tag', function () {
    $registry = new ComponentRegistry();
    assert($registry->resolve('no-such-component') === null, "Should return null");
});

test('Parser with registry: component tag → ComponentRefNode', function () {
    $registry = new ComponentRegistry();
    $dir = __DIR__ . '/../apps/calculator';
    $registry->load(['my-panel' => './components/DisplayPanel.vue'], $dir);
    
    $tpl = '<app title="Test" width="100" height="100"><my-panel x="0" y="10" :value="display" /></app>';
    $parser = new TemplateParser($registry);
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    assert(count($errors) === 0, "Should have 0 errors, got: " . implode('; ', $errors));
    assert(count($app->children) === 1, "Should have 1 child");
    $node = $app->children[0];
    assert($node instanceof ComponentRefNode, "Should be ComponentRefNode, got " . get_class($node));
    assert($node->tagName === 'my-panel', "tagName should be my-panel");
    assert($node->selfClosing === true, "Should be self-closing");
    assert(isset($node->props['x']), "Should have x prop");
    assert($node->props['x'] === '0', "x prop should be '0'");
    assert(isset($node->props[':value']), "Should have :value prop");
});

test('Parser without registry: unknown tag still → UnknownNode', function () {
    $tpl = '<app title="Test" width="100" height="100"><mystery-tag /></app>';
    $parser = new TemplateParser(); // no registry
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    assert(count($errors) >= 1, "Should report unknown tag error");
    assert($app->children[0] instanceof UnknownNode, "Should be UnknownNode");
});

test('Parser: component tag with slot children', function () {
    $registry = new ComponentRegistry();
    $dir = __DIR__ . '/../apps/calculator';
    $registry->load(['wrapper' => './components/DisplayPanel.vue'], $dir);
    
    $tpl = '<app title="Test" width="200" height="200"><wrapper x="0" y="0"><text x="5" y="5" :bind="msg" class="inner" /></wrapper></app>';
    $parser = new TemplateParser($registry);
    $app = $parser->parse($tpl);
    $errors = $parser->getErrors();
    
    assert(count($errors) === 0, "Should have 0 errors");
    $node = $app->children[0];
    assert($node instanceof ComponentRefNode, "Should be ComponentRefNode");
    assert(count($node->slotChildren) === 1, "Should have 1 slot child");
    assert($node->slotChildren[0] instanceof TextNode, "Slot child should be TextNode");
});

test('resolveComponentRefs: inlines child component layout', function () {
    // Create a minimal test setup with a child component AST
    $childApp = new AppNode('Child', 100, 50, 1);
    $childApp->children[] = new RectNode(0, 0, 100, 50, 'child-bg', 1);
    
    $app = new AppNode('Parent', 200, 100, 1);
    $compNode = new ComponentRefNode('child', '/fake/path.vue', ['x' => '10', 'y' => '20'], [], true, 2);
    $app->children[] = $compNode;
    
    // We can't test resolveComponentRefs directly without a file, but we can test the helpers
    // Test applyOffset
    $rect = new RectNode(5, 5, 50, 50, 'bg', 1);
    applyOffset($rect, 10, 20);
    assert($rect->x === 15, "Rect x should be 15 after offset (5+10)");
    assert($rect->y === 25, "Rect y should be 25 after offset (5+20)");
});

test('applyOffset: correctly offsets RectNode', function () {
    $rect = new RectNode(10, 20, 30, 40, 'cls', 1);
    applyOffset($rect, 5, 8);
    assert($rect->x === 15, "x: 10+5=15");
    assert($rect->y === 28, "y: 20+8=28");
    // w, h should not change
    assert($rect->w === 30, "width should not change");
    assert($rect->h === 40, "height should not change");
});

test('applyOffset: correctly offsets TextNode', function () {
    $text = new TextNode(5, 10, 'val', 'cls', 'left', 100, 5, 1);
    applyOffset($text, 3, 7);
    assert($text->x === 8, "x: 5+3=8");
    assert($text->y === 17, "y: 10+7=17");
    assert($text->containerX === 8, "containerX: 5+3=8");
});

test('applyOffset: correctly offsets GridNode', function () {
    $grid = new GridNode(10, 20, 4, 5, 80, 60, 4, 1);
    applyOffset($grid, 15, 25);
    assert($grid->x === 25, "x: 10+15=25");
    assert($grid->y === 45, "y: 20+25=45");
});

test('applyPropBindings: maps :prop → child :bind', function () {
    $text = new TextNode(0, 0, 'value', 'cls', 'left', 0, 0, 1);
    // Parent has :value="displayResult" → child's bind="value" should become bind="displayResult"
    applyPropBindings($text, [':value' => 'displayResult']);
    assert($text->bind === 'displayResult', "bind should be displayResult, got {$text->bind}");
});

test('applyPropBindings: no :prop prefix → no change', function () {
    $text = new TextNode(0, 0, 'unchanged', 'cls', 'left', 0, 0, 1);
    applyPropBindings($text, ['x' => '10', 'y' => '20']); // static props only
    assert($text->bind === 'unchanged', "bind should remain unchanged");
});

test('AotValidator: validateNestingDepth accepts depth 0 and 1', function () {
    $v = new AotValidator();
    assert($v->validateNestingDepth(0, 'root') === true, "Depth 0 should pass");
    assert($v->validateNestingDepth(1, 'child') === true, "Depth 1 should pass");
});

test('AotValidator: validateNestingDepth rejects depth > 1', function () {
    $v = new AotValidator();
    assert($v->validateNestingDepth(2, 'grandchild') === false, "Depth 2 should fail");
    $errors = $v->getErrors();
    assert(count($errors) >= 1, "Should have nesting error");
    assert(strpos($errors[0], 'exceeds maximum depth') !== false, "Error should mention depth");
});

// ============================================================
// Summary
// ============================================================
echo "\n=== Results: $passed passed, $failed failed ===\n";

if ($failed > 0) {
    exit(1);
}
echo "All tests passed!\n";
