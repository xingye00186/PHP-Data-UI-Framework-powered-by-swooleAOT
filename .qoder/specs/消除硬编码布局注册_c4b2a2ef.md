# 消除硬编码布局注册

## 问题

Application.php 中手动硬编码了所有布局段名称，每次新增/删除组件都要手动同步，且名称格式（camelCase vs kebab-case）容易出错。

## 解决方案

SFC 编译器在 `AppLayout_gen.php` 中额外生成一个 `getLayoutSegmentNames(): array` 函数，返回所有可用的布局段名称列表。Application.php 通过调用此函数动态注册。

## Task 1: 修改 SFC 编译器 — 生成 getLayoutSegmentNames()

文件: `f:\work\swoole_compiler\examples\vue-calc\framework\sfc-compiler.php`

在 `$callLayoutSegment` 生成之后（约 line 390），追加生成 `getLayoutSegmentNames()`：

```php
// 生成布局段名称列表函数
$segNamesExport = var_export(array_values($allGroups), true);
$getSegmentNames = <<<PHP

function getLayoutSegmentNames(): array
{
    return {$segNamesExport};
}
PHP;
```

在 `$layoutContent` 模板中（line 402），在 `{$callLayoutSegment}` 之后添加 `{$getSegmentNames}`：

```php
$layoutContent = <<<PHP
<?php
...
{$segmentFunctions}
{$callLayoutSegment}
{$getSegmentNames}
PHP;
```

## Task 2: 修改 Application.php — 动态注册

文件: `f:\work\swoole_compiler\examples\vue-calc\apps\calculator\Application.php`

将 initWindow() 中的硬编码 attachLayout 调用（line 42-45）：

```php
$this->renderer->attachLayout('app', 1);
$this->renderer->attachLayout('display-panel', 2);
$this->renderer->attachLayout('num-pad', 3);
$this->renderer->attachLayout('about-dialog', 4);
```

替换为：

```php
$segNames = getLayoutSegmentNames();
for ($i = 0; $i < count($segNames); $i++) {
    $this->renderer->attachLayout($segNames[$i], $i + 1);
}
```

## Task 3: 编译验证

```
build.bat calculator --run
```

验证：
- SFC 编译生成的 AppLayout_gen.php 包含 `getLayoutSegmentNames()` 函数
- AOT 编译成功
- 运行正常，不闪退

## Task 4: 更新开发文档

在 `f:\work\swoole_compiler\examples\vue-calc\docs\开发经验与教训_v2.md` 中追加一条反模式记录：

**反模式：硬编码布局段名称列表**
- 问题：Application.php 手动列出所有 group_id，新增/删除组件需手动同步，且 camelCase/kebab-case 不一致导致闪退
- 正确做法：SFC 编译器生成 `getLayoutSegmentNames()` 提供可用段列表，应用层动态遍历注册
- 原则：生成代码的元信息应由生成器自身提供，消费端不应假设具体内容
