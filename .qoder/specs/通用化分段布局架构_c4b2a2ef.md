# 通用化分段布局架构

## 设计要点

- SFC 编译器生成 `callLayoutSegment(string $name): array` 分发函数（if/else 显式调用，AOT 安全）
- BaseRenderer 遍历 `$this->activeLayouts`，通过分发函数获取每个布局数据
- 用 `(array)` 类型转换告诉 AOT 子数组的正确类型
- 移除 `getLayout()` 聚合器（死代码）

## Task 1: 修改 SFC 编译器 — 生成 callLayoutSegment 分发函数

文件: `f:\work\swoole_compiler\examples\vue-calc\framework\sfc-compiler.php`

将 line 377-391 的 `getLayout()` 聚合器替换为 `callLayoutSegment()` 分发函数：

```php
// 生成分发函数 (AOT 兼容: if/else 显式调用, 不使用变量函数)
$dispatchCases = '';
foreach ($allGroups as $gid) {
    $fnSuffix = groupIdToCamel($gid);
    $fnName = "getLayout_{$fnSuffix}";
    $dispatchCases .= "    if (\$name === '{$gid}') return {$fnName}();\n";
}
$callLayoutSegment = <<<PHP

function callLayoutSegment(string \$name): array
{
{$dispatchCases}    return ['elements' => [], 'buttons' => []];
}
PHP;
```

同时更新 `$layoutContent` 模板（line 393-404），将 `{$getLayoutCompat}` 替换为 `{$callLayoutSegment}`。

## Task 2: 修改 BaseRenderer — 通用化

文件: `f:\work\swoole_compiler\examples\vue-calc\framework\BaseRenderer.php`

### render() 方法（line 146-164）替换为：

```php
        // v6 M1: 从 activeLayouts 动态收集布局数据
        $elements = [];
        $buttons = [];
        foreach ($this->activeLayouts as $name => $idx) {
            $seg = callLayoutSegment($name);
            foreach ((array) $seg['elements'] as $el) $elements[] = $el;
            foreach ((array) $seg['buttons'] as $btn) $buttons[] = $btn;
        }
```

### getActiveLayout() 方法（line 45-67）替换为：

```php
    public function getActiveLayout(): array
    {
        $allElements = [];
        $allButtons = [];
        foreach ($this->activeLayouts as $name => $idx) {
            $seg = callLayoutSegment($name);
            foreach ((array) $seg['elements'] as $el) $allElements[] = $el;
            foreach ((array) $seg['buttons'] as $btn) $allButtons[] = $btn;
        }
        return ['elements' => $allElements, 'buttons' => $allButtons];
    }
```

### 更新文件头部注释（line 10-14），移除 any() 相关说明，改为：

```php
 * AOT 限制:
 *   - 不支持 $fn() 变量函数调用 → 使用 callLayoutSegment() 显式分发
 *   - 函数返回嵌套数组后子数组类型丢失 → 使用 (array) 类型转换修复
```

## Task 3: 修改 Application.php — (array) 转换

文件: `f:\work\swoole_compiler\examples\vue-calc\apps\calculator\Application.php`

`handleClick()` 方法 line 111-112，将：
```php
$layout = $this->renderer->getActiveLayout();
$buttons = $layout['buttons'];
```
改为：
```php
$layout = $this->renderer->getActiveLayout();
$buttons = (array) $layout['buttons'];
```

## Task 4: 重新生成 + 编译验证

```
build.bat calculator --run
```

验证：
- SFC 编译生成包含 `callLayoutSegment()` 的 AppLayout_gen.php
- AOT 编译成功
- 运行正常，无闪退

## Task 5: 更新 aot-validator.php 变量函数调用规则

在 Task 4 通过后，补完之前 Felix 未完成的 validator 规则更新（新增 Rule 7 检测 `$fn()` 模式），使用 `build.bat calculator` 验证无误报。
