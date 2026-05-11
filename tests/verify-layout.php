<?php
/**
 * Quick verification of generated layout output.
 * Run: php tests/verify-layout.php
 */
require_once __DIR__ . '/../gen/CalculatorLayout_gen.php';

$l = getLayout();

echo "Window: " . WINDOW_WIDTH . "x" . WINDOW_HEIGHT . "\n";
echo "Elements: " . count($l['elements']) . " (expect 4)\n";
echo "Buttons:  " . count($l['buttons']) . " (expect 18)\n\n";

// Check first rect (app-bg)
$r0 = $l['elements'][0];
assert($r0['type'] === 'rect', 'First element should be rect');
assert($r0['x'] === 0 && $r0['y'] === 0, 'First rect at (0,0)');
assert($r0['w'] === 328 && $r0['h'] === 420, 'First rect should be 328x420');
echo "OK: First rect = {$r0['w']}x{$r0['h']}\n";

// Check first text (expression)
$t0 = $l['elements'][2];
assert($t0['type'] === 'text', 'Third element should be text');
assert($t0['bind'] === 'expression', 'Expression text bind should be "expression"');
assert($t0['fontSize'] === 16, 'Expression font size should be 16');
echo "OK: Expression text bind='{$t0['bind']}' fontSize={$t0['fontSize']}\n";

// Check display text (right-aligned)
$t1 = $l['elements'][3];
assert($t1['bind'] === 'display', 'Display text bind should be "display"');
assert($t1['align'] === 'right', 'Display text should be right-aligned');
assert($t1['fontSize'] === 32, 'Display font size should be 32');
assert($t1['bold'] === 1, 'Display should be bold');
assert($t1['containerW'] === 320, 'Display containerW should be 320');
echo "OK: Display text bind='{$t1['bind']}' align={$t1['align']} fontSize={$t1['fontSize']} bold={$t1['bold']}\n";

// Check button count
assert(count($l['buttons']) === 18, 'Should have 18 buttons');
echo "OK: 18 buttons\n";

// Check first button (C/reset)
$b0 = $l['buttons'][0];
assert($b0['label'] === 'C', 'First button should be C');
assert($b0['handler'] === 'reset', 'C handler should be reset');
assert($b0['arg'] === NULL, 'C should have no arg');
echo "OK: First btn label='{$b0['label']}' handler='{$b0['handler']}'\n";

// Check last button (.)
$b17 = $l['buttons'][17];
assert($b17['label'] === '.', 'Last button should be .');
assert($b17['handler'] === 'handleButton', '. handler should be handleButton');
assert($b17['arg'] === '.', '. arg should be .');
echo "OK: Last btn label='{$b17['label']}' handler='{$b17['handler']}' arg='{$b17['arg']}'\n";

// Check = button (index 15)
$b15 = $l['buttons'][15];
assert($b15['label'] === '=', 'Button 15 should be =');
assert($b15['handler'] === 'calculate', '= handler should be calculate');
assert($b15['arg'] === NULL, '= should have no arg');
echo "OK: Equals btn handler='{$b15['handler']}'\n";

// Check grid coordinates for button 7 (row=1, col=0)
$b4 = $l['buttons'][4]; // 7 is the 5th button
assert($b4['label'] === '7', 'Button 4 should be 7');
// Grid x=0, y=80, margin=2, cellW=80, cellH=60
// bx = 0 + 0*80 + 2 = 2
// by = 80 + 1*60 + 2 = 142
assert($b4['x'] === 2 && $b4['y'] === 142, "Btn 7 should be at (2,142), got ({$b4['x']},{$b4['y']})");
echo "OK: Btn 7 at ({$b4['x']},{$b4['y']})\n";

echo "\n=== ALL CHECKS PASSED ===\n";
