<?php
// AOT Compatibility Test Suite v2
// 验证 Swoole AOT 编译器对 PHP 动态特性的支持程度
// v2: 包含已知不支持特性的实际代码，验证编译期行为

// ============================================================================
// 辅助函数
// ============================================================================

function helperReturnFlat(): array {
    return [1, 2, 3];
}

function helperReturnNested(): array {
    return ['items' => [10, 20, 30], 'meta' => ['count' => 3]];
}

function funcAlpha(): string { return 'alpha-result'; }
function funcBeta(): string { return 'beta-result'; }

function dispatchByName(string $name): string {
    if ($name === 'alpha') return funcAlpha();
    if ($name === 'beta') return funcBeta();
    return 'unknown';
}

function testDefaultArgs(string $val = 'default'): string {
    return $val;
}

function testTypedFn(int $a, int $b): int {
    return $a + $b;
}

function testVariadic(int ...$nums): int {
    $total = 0;
    foreach ($nums as $n) {
        $total += $n;
    }
    return $total;
}

// ============================================================================
// 包装函数模式测试 — 模拟 calculator 的 callLayoutSegment 模式
// ============================================================================

function mockGetLayout_app(): array {
    return [
        'elements' => [
            ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => 328, 'h' => 420, 'color' => 0x1E1E1E],
            ['type' => 'text', 'x' => 10, 'y' => 10, 'color' => 0xFFFFFF],
        ],
        'buttons' => [
            ['label' => 'C', 'x' => 2, 'y' => 82, 'w' => 76, 'h' => 56, 'bg' => 0x505050, 'fg' => 0xFFFFFF, 'border' => 0x646464],
        ],
    ];
}

function mockGetLayout_numPad(): array {
    return [
        'elements' => [],
        'buttons' => [
            ['label' => '1', 'x' => 2, 'y' => 140, 'w' => 76, 'h' => 56, 'bg' => 0x323232, 'fg' => 0xFFFFFF, 'border' => 0x464646],
            ['label' => '2', 'x' => 82, 'y' => 140, 'w' => 76, 'h' => 56, 'bg' => 0x323232, 'fg' => 0xFFFFFF, 'border' => 0x464646],
        ],
    ];
}

// 包装器函数 — 模拟 callLayoutSegment
function mockCallLayoutSegment(string $name): array {
    if ($name === 'app') return mockGetLayout_app();
    if ($name === 'num-pad') return mockGetLayout_numPad();
    return ['elements' => [], 'buttons' => []];
}

// ============================================================================
// 类定义
// ============================================================================

class BaseAnimal {
    protected string $name;
    public function __construct(string $name) { $this->name = $name; }
    public function speak(): string { return $this->name . ' speaks'; }
}

class Dog extends BaseAnimal {
    public function speak(): string { return $this->name . ' barks'; }
}

abstract class Shape {
    abstract public function area(): float;
}

class Circle extends Shape {
    private float $r;
    public function __construct(float $r) { $this->r = $r; }
    public function area(): float { return 3.14159 * $this->r * $this->r; }
}

class StaticDemo {
    public static int $counter = 0;
    public static function increment(): void { self::$counter++; }
}

// B2/B3 测试用：含普通方法和属性的类
class MethodHost {
    public string $label = 'original';
    public function greet(): string { return 'hello'; }
}

class MockRenderer {
    private array $activeLayouts = [];
    
    public function attach(string $name, int $idx): void {
        $this->activeLayouts[$name] = $idx;
    }
    
    public function collect(): array {
        $elements = [];
        $buttons = [];
        foreach ($this->activeLayouts as $name => $idx) {
            $seg = mockCallLayoutSegment($name);
            foreach ((array) $seg['elements'] as $el) $elements[] = $el;
            foreach ((array) $seg['buttons'] as $btn) $buttons[] = $btn;
        }
        return ['elements' => $elements, 'buttons' => $buttons];
    }
    
    public function collectWithAny(): array {
        $elements = [];
        $buttons = [];
        foreach ($this->activeLayouts as $name => $idx) {
            $seg = any(mockCallLayoutSegment($name));
            foreach ($seg['elements'] as $el) $elements[] = $el;
            foreach ($seg['buttons'] as $btn) $buttons[] = $btn;
        }
        return ['elements' => $elements, 'buttons' => $buttons];
    }
}

// ============================================================================
// 主测试函数
// ============================================================================

function main(): void
{
    $pass = 0;
    $fail = 0;
    $expected_fail = 0;

    echo "========================================\n";
    echo "  AOT Syntax Compatibility Test Suite\n";
    echo "========================================\n\n";

    // --- A. 基础类型与数组 ---
    echo "--- A. 基础类型与数组 ---\n";

    // A1: 基础标量类型
    $i = 42;
    $f = 3.14;
    $s = "hello";
    $b = true;
    if ($i === 42 && $f > 3.0 && $s === "hello" && $b === true) {
        echo "[PASS] A1: 基础标量类型赋值和运算\n";
        $pass++;
    } else {
        echo "[FAIL] A1: 基础标量类型赋值和运算\n";
        $fail++;
    }

    // A2: 一维数组
    $arr = [1, 2, 3, 4, 5];
    $sum = 0;
    foreach ($arr as $v) { $sum += $v; }
    if ($sum === 15) {
        echo "[PASS] A2: 一维数组创建和 foreach 遍历\n";
        $pass++;
    } else {
        echo "[FAIL] A2: 一维数组创建和 foreach 遍历 (sum={$sum})\n";
        $fail++;
    }

    // A3: 关联数组
    $map = ['name' => 'test', 'value' => 99];
    if ($map['name'] === 'test' && $map['value'] === 99) {
        echo "[PASS] A3: 关联数组创建和 key 访问\n";
        $pass++;
    } else {
        echo "[FAIL] A3: 关联数组创建和 key 访问\n";
        $fail++;
    }

    // A4: 嵌套数组直接构造和访问
    $nested = ['key' => ['subkey' => 42]];
    $val = $nested['key']['subkey'];
    if ($val === 42) {
        echo "[PASS] A4: 嵌套数组直接构造和访问\n";
        $pass++;
    } else {
        echo "[FAIL] A4: 嵌套数组直接构造和访问 (val={$val})\n";
        $fail++;
    }

    // A5: 函数返回一维数组后访问元素
    $flat = helperReturnFlat();
    if ($flat[0] === 1 && $flat[2] === 3) {
        echo "[PASS] A5: 函数返回一维数组后访问元素\n";
        $pass++;
    } else {
        echo "[FAIL] A5: 函数返回一维数组后访问元素\n";
        $fail++;
    }

    // A6: 函数返回嵌套数组后访问子数组 — 已知问题：子数组类型丢失变 int
    $nested_result = helperReturnNested();
    $a6_ok = false;
    try {
        $items = $nested_result['items'];
        $a6_val = $items[0];
        if ($a6_val === 10) {
            $a6_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($a6_ok) {
        echo "[PASS] A6: 函数返回嵌套数组后访问子数组\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] A6: 函数返回嵌套数组后访问子数组 — 类型丢失\n";
        $expected_fail++;
    }

    // A7: (array) 转换修复嵌套数组类型
    $nested_result2 = helperReturnNested();
    $a7_ok = false;
    try {
        $sub = (array) $nested_result2['items'];
        $a7_val = $sub[0];
        if ($a7_val === 10) {
            $a7_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($a7_ok) {
        echo "[PASS] A7: (array) 转换修复嵌套数组类型\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] A7: (array) 转换修复嵌套数组类型\n";
        $expected_fail++;
    }

    // A8: any() 包裹修复嵌套数组类型
    $nested_result3 = any(helperReturnNested());
    $a8_ok = false;
    try {
        $items3 = $nested_result3['items'];
        $a8_val = $items3[0];
        if ($a8_val === 10) {
            $a8_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($a8_ok) {
        echo "[PASS] A8: any() 包裹修复嵌套数组类型\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] A8: any() 包裹修复嵌套数组类型\n";
        $expected_fail++;
    }

    echo "\n";

    // --- B. 变量动态性 ---
    echo "--- B. 变量动态性 ---\n";

    // B1: 变量函数调用 $fn() — 已知不支持，尝试编译
    $b1_ok = false;
    $b1_fn = 'funcAlpha';
    try {
        $b1_result = $b1_fn();
        if ($b1_result === 'alpha-result') {
            $b1_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($b1_ok) {
        echo "[PASS] B1: 变量函数调用 \$fn()\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] B1: 变量函数调用 \$fn()\n";
        $expected_fail++;
    }

    // B2: 变量方法调用 $obj->$method() — 已知不支持，尝试编译
    $b2_obj = new MethodHost();
    $b2_method = 'greet';
    $b2_ok = false;
    try {
        $b2_result = $b2_obj->$b2_method();
        if ($b2_result === 'hello') {
            $b2_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($b2_ok) {
        echo "[PASS] B2: 变量方法调用 \$obj->\$method()\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] B2: 变量方法调用 \$obj->\$method()\n";
        $expected_fail++;
    }

    // B3: 变量属性访问 $obj->$prop — 已知不支持，尝试编译
    $b3_obj = new MethodHost();
    $b3_prop = 'label';
    $b3_ok = false;
    try {
        $b3_val = $b3_obj->$b3_prop;
        if ($b3_val === 'original') {
            $b3_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($b3_ok) {
        echo "[PASS] B3: 变量属性访问 \$obj->\$prop\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] B3: 变量属性访问 \$obj->\$prop\n";
        $expected_fail++;
    }

    // B4: 动态类实例化 new $className — 待验证
    $b4_class = 'Circle';
    $b4_ok = false;
    try {
        $b4_obj = new $b4_class(1.0);
        $b4_area = $b4_obj->area();
        if ($b4_area > 3.0 && $b4_area < 4.0) {
            $b4_ok = true;
        }
    } catch (\Throwable $e) {
        // 运行时异常
    }
    if ($b4_ok) {
        echo "[PASS] B4: 动态类实例化 new \$className\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] B4: 动态类实例化 new \$className\n";
        $expected_fail++;
    }

    echo "\n";

    // --- C. 类与对象 ---
    echo "--- C. 类与对象 ---\n";

    // C1: 类继承 + 方法覆盖
    $dog = new Dog("Rex");
    if ($dog->speak() === "Rex barks") {
        echo "[PASS] C1: 类继承 + 方法覆盖\n";
        $pass++;
    } else {
        echo "[FAIL] C1: 类继承 + 方法覆盖\n";
        $fail++;
    }

    // C2: 抽象类 + 实现
    $circle = new Circle(2.0);
    $area = $circle->area();
    if ($area > 12.0 && $area < 13.0) {
        echo "[PASS] C2: 抽象类 + 实现\n";
        $pass++;
    } else {
        echo "[FAIL] C2: 抽象类 + 实现 (area={$area})\n";
        $fail++;
    }

    // C3: 构造函数参数传递
    $animal = new BaseAnimal("Cat");
    if ($animal->speak() === "Cat speaks") {
        echo "[PASS] C3: 构造函数参数传递\n";
        $pass++;
    } else {
        echo "[FAIL] C3: 构造函数参数传递\n";
        $fail++;
    }

    // C4: __get/__set 魔术方法 — 已知 AOT 不支持自动调用
    // 注意：定义带 __get/__set 的类在顶级作用域，此处仅测试调用行为
    $c4_obj = new MagicProp();
    $c4_ok = false;
    try {
        $c4_val = $c4_obj->nonexist;
        if ($c4_val === 'magic-get') {
            $c4_ok = true;
        }
    } catch (\Throwable $e) {
        // AOT 不会自动调用 __get
    }
    if ($c4_ok) {
        echo "[PASS] C4: __get/__set 魔术方法\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] C4: __get/__set 魔术方法 — AOT 不自动调用\n";
        $expected_fail++;
    }

    // C5: 静态属性和静态方法
    StaticDemo::$counter = 0;
    StaticDemo::increment();
    StaticDemo::increment();
    StaticDemo::increment();
    if (StaticDemo::$counter === 3) {
        echo "[PASS] C5: 静态属性和静态方法\n";
        $pass++;
    } else {
        echo "[FAIL] C5: 静态属性和静态方法 (counter=" . StaticDemo::$counter . ")\n";
        $fail++;
    }

    // C6: $this 在方法中正常使用
    $dog2 = new Dog("Buddy");
    if ($dog2->speak() === "Buddy barks") {
        echo "[PASS] C6: \$this 在方法中正常使用\n";
        $pass++;
    } else {
        echo "[FAIL] C6: \$this 在方法中正常使用\n";
        $fail++;
    }

    echo "\n";

    // --- D. 控制流 ---
    echo "--- D. 控制流 ---\n";

    // D1: if/else/elseif
    $d1 = 0;
    $x = 5;
    if ($x > 10) {
        $d1 = 1;
    } elseif ($x > 3) {
        $d1 = 2;
    } else {
        $d1 = 3;
    }
    if ($d1 === 2) {
        echo "[PASS] D1: if/else/elseif\n";
        $pass++;
    } else {
        echo "[FAIL] D1: if/else/elseif (d1={$d1})\n";
        $fail++;
    }

    // D2: for/foreach/while 循环
    $d2_for = 0;
    for ($idx = 1; $idx <= 5; $idx++) {
        $d2_for += $idx;
    }
    $d2_arr = [10, 20, 30];
    $d2_foreach = 0;
    foreach ($d2_arr as $item) {
        $d2_foreach += $item;
    }
    $d2_while = 0;
    $w = 1;
    while ($w <= 4) {
        $d2_while += $w;
        $w++;
    }
    if ($d2_for === 15 && $d2_foreach === 60 && $d2_while === 10) {
        echo "[PASS] D2: for/foreach/while 循环\n";
        $pass++;
    } else {
        echo "[FAIL] D2: for/foreach/while 循环 (for={$d2_for}, foreach={$d2_foreach}, while={$d2_while})\n";
        $fail++;
    }

    // D3: match 表达式
    $status = 2;
    $d3 = match($status) {
        1 => 'one',
        2 => 'two',
        3 => 'three',
        default => 'other'
    };
    if ($d3 === 'two') {
        echo "[PASS] D3: match 表达式\n";
        $pass++;
    } else {
        echo "[FAIL] D3: match 表达式 (result={$d3})\n";
        $fail++;
    }

    // D4: try/catch 异常处理
    $d4 = 'no-exception';
    try {
        throw new \Exception("test exception");
    } catch (\Exception $e) {
        $d4 = $e->getMessage();
    }
    if ($d4 === 'test exception') {
        echo "[PASS] D4: try/catch 异常处理\n";
        $pass++;
    } else {
        echo "[FAIL] D4: try/catch 异常处理 (msg={$d4})\n";
        $fail++;
    }

    // D5: 多层 break/continue (break 2) — 已知不支持
    // 原始代码: break 2; 导致 AOT 编译错误:
    // Fatal error: Cannot break more than 1 level
    echo "[COMPILE-FAIL] D5: 多层 break/continue (break 2) — 编译期拒绝\n";
    $expected_fail++;

    echo "\n";

    // --- E. 函数特性 ---
    echo "--- E. 函数特性 ---\n";

    // E1: 默认参数值
    $e1 = testDefaultArgs();
    if ($e1 === 'default') {
        echo "[PASS] E1: 默认参数值\n";
        $pass++;
    } else {
        echo "[FAIL] E1: 默认参数值 (result={$e1})\n";
        $fail++;
    }

    // E2: 类型声明 (参数 + 返回值)
    $e2 = testTypedFn(10, 20);
    if ($e2 === 30) {
        echo "[PASS] E2: 类型声明 (参数 + 返回值)\n";
        $pass++;
    } else {
        echo "[FAIL] E2: 类型声明 (参数 + 返回值) (result={$e2})\n";
        $fail++;
    }

    // E3: 闭包/匿名函数
    $adder = function(int $a, int $b): int { return $a + $b; };
    $e3 = $adder(3, 4);
    if ($e3 === 7) {
        echo "[PASS] E3: 闭包/匿名函数\n";
        $pass++;
    } else {
        echo "[FAIL] E3: 闭包/匿名函数 (result={$e3})\n";
        $fail++;
    }

    // E4: 可变参数 ...$args
    $e4 = testVariadic(1, 2, 3, 4);
    if ($e4 === 10) {
        echo "[PASS] E4: 可变参数 ...\$args\n";
        $pass++;
    } else {
        echo "[FAIL] E4: 可变参数 ...\$args (result={$e4})\n";
        $fail++;
    }

    echo "\n";

    // --- F. 项目已遇问题专项 ---
    echo "--- F. 项目已遇问题专项 ---\n";

    // F1: const 嵌套数组 — 已知不支持，尝试编译
    $f1_ok = false;
    $f1_val = F1_CONST['a']['x'];
    if ($f1_val === 1) {
        $f1_ok = true;
    }
    if ($f1_ok) {
        echo "[PASS] F1: const 嵌套数组\n";
        $pass++;
    } else {
        echo "[EXPECTED-FAIL] F1: const 嵌套数组\n";
        $expected_fail++;
    }

    // F2: 类型不可变性 (同一变量先 string 后赋 int)
    // AOT文档：无strict_types时自动转为字符串
    $f2_var = "hello";
    $f2_var = 42;
    // AOT可能将42转为"42"，检查实际行为
    $f2_is_int = is_int($f2_var);
    $f2_is_str = is_string($f2_var);
    if ($f2_is_str && !$f2_is_int) {
        echo "[EXPECTED-FAIL] F2: 类型不可变性 — int 自动转为 string (值={$f2_var})\n";
        $expected_fail++;
    } elseif ($f2_is_int && !$f2_is_str) {
        echo "[FAIL] F2: 类型不可变性 — 变量类型被改变为 int 但未报错\n";
        $fail++;
    } else {
        echo "[PASS] F2: 类型不可变性 — 行为待确认 (int={$f2_is_int}, str={$f2_is_str})\n";
        $pass++;
    }

    // F3: if/else 分发替代变量函数调用 (workaround 验证)
    $f3 = dispatchByName('alpha');
    if ($f3 === 'alpha-result') {
        echo "[PASS] F3: if/else 分发替代变量函数调用 (workaround)\n";
        $pass++;
    } else {
        echo "[FAIL] F3: if/else 分发替代变量函数调用 (result={$f3})\n";
        $fail++;
    }

    // --- A9-A13: 包装函数模式测试 ---
    echo "\n--- A9-A12: 包装函数模式 ---\n";
    
    // A9: 包装函数 — 裸访问（无 cast 无 any）
    $seg9 = mockCallLayoutSegment('app');
    $items9 = $seg9['elements'];
    if (is_array($items9) && count($items9) === 2) {
        echo "[PASS] A9: 包装函数模式 — 裸访问\n";
        $pass++;
    } else {
        echo "[FAIL] A9: 包装函数模式 — 裸访问 (type=" . gettype($items9) . ")\n";
        $fail++;
    }
    
    // A10: 包装函数 — (array) 转换
    $seg10 = mockCallLayoutSegment('app');
    $items10 = (array) $seg10['elements'];
    if (is_array($items10) && count($items10) === 2) {
        echo "[PASS] A10: 包装函数模式 — (array) 转换\n";
        $pass++;
    } else {
        echo "[FAIL] A10: 包装函数模式 — (array) 转换 (type=" . gettype($items10) . ")\n";
        $fail++;
    }
    
    // A11: 包装函数 — any() 包裹
    $seg11 = any(mockCallLayoutSegment('app'));
    $items11 = $seg11['elements'];
    if (is_array($items11) && count($items11) === 2) {
        echo "[PASS] A11: 包装函数模式 — any() 包裹\n";
        $pass++;
    } else {
        echo "[FAIL] A11: 包装函数模式 — any() 包裹 (type=" . gettype($items11) . ")\n";
        $fail++;
    }
    
    // A12: 在类方法中 foreach 遍历 + 调用包装函数（完全模拟 BaseRenderer）
    $mockRenderer = new MockRenderer();
    $mockRenderer->attach('app', 1);
    $mockRenderer->attach('num-pad', 2);
    $result12 = $mockRenderer->collect();
    $el12 = (array) $result12['elements'];
    $btn12 = (array) $result12['buttons'];
    if (is_array($el12) && count($el12) === 2 && is_array($btn12) && count($btn12) === 3) {
        echo "[PASS] A12: 类方法 + foreach + 包装函数 — (array) 模式\n";
        $pass++;
    } else {
        echo "[FAIL] A12: 类方法 + foreach + 包装函数 — (array) 模式 (els=" . count($el12) . " btns=" . count($btn12) . ")\n";
        $fail++;
    }
    
    // A13: 在类方法中 foreach 遍历 + any() 模式
    $result13 = $mockRenderer->collectWithAny();
    $el13 = $result13['elements'];
    $btn13 = $result13['buttons'];
    if (is_array($el13) && count($el13) === 2 && is_array($btn13) && count($btn13) === 3) {
        echo "[PASS] A13: 类方法 + foreach + 包装函数 — any() 模式\n";
        $pass++;
    } else {
        echo "[FAIL] A13: 类方法 + foreach + 包装函数 — any() 模式 (els=" . count($el13) . " btns=" . count($btn13) . ")\n";
        $fail++;
    }

    // 最终统计
    echo "\n========== AOT 语法兼容性报告 ==========\n";
    echo "PASS: {$pass}  FAIL: {$fail}  EXPECTED-FAIL: {$expected_fail}\n";
    $total = $pass + $fail + $expected_fail;
    echo "Total: {$total} tests\n";
    echo "==========================================\n";
}

// C4 测试用：含 __get 的类
class MagicProp {
    public function __get(string $name): string {
        return 'magic-get';
    }
}

// F1 测试用：const 嵌套数组
const F1_CONST = ['a' => ['x' => 1, 'y' => 2], 'b' => ['x' => 3]];
