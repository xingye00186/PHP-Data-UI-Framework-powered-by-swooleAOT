# v5 M4: Groups + Dirty 增量渲染

## 背景

当前状态 (v5 M3 已完成):
- 布局为 flat array，有 `layer` 字段支持 overlay
- 渲染采用组件级 dirty 标记 (`$this->dirty = true` -> 全量重绘)
- ChangeQueue 已定义但**从未被 push** (预留基础设施)
- 30 个元素全量遍历 (O(n) condition 检查)

v5 M4 目标:
- 布局中添加 `group_id` 保留组件边界
- ChangeQueue 真正接入 (属性变化时 push)
- dirty 粒度从"整个组件" 细化到 "哪些 group 需要重绘"
- 收益: O(n) -> O(dirty groups)，为 300+ 元素场景奠基

## 涉及文件 (4 文件, ~80 行)

| 文件 | 改动 |
|------|------|
| `framework/sfc-compiler.php` | resolveComponentRefs 中为每个子节点标记所属组件名 |
| `framework/compiler/template-parser.php` | lowerToLayout 输出 `group_id` 字段 |
| `framework/ReactiveComponent.php` | ChangeQueue push + dirtyGroups 追踪 |
| `framework/BaseRenderer.php` | group-aware 增量渲染 (消费 ChangeQueue) |

---

## Task 1: 编译器侧 - group_id 标记

### 1.1 sfc-compiler.php - resolveComponentRefs

在 `resolveComponentRefs()` 中，为每个内联的子节点设置 `group_id`:

```php
// 主 App 直属元素: group_id = 'app' (默认)
// 子组件内联元素: group_id = 组件 tagName (如 'num-pad', 'display-panel', 'about-dialog')

foreach ($childAst->children as $childNode) {
    applyOffset($childNode, $offsetX, $offsetY);
    applyPropBindings($childNode, $child->props);
    if ($child->vIf !== '' && $childNode->vIf === '') {
        $childNode->vIf = $child->vIf;
    }
    if ($child->isOverlay) {
        $childNode->layer = $nextOverlayLayer;
    }
    // v5 M4: 标记组件边界
    $childNode->groupId = $child->tagName;
    $resolvedChildren[] = $childNode;
}
```

### 1.2 ast-nodes.php - TemplateNode 添加 groupId

```php
abstract class TemplateNode {
    public int $line;
    public string $vIf = '';
    public int $layer = 0;
    public string $groupId = 'app';  // v5 M4: 组件边界标记
    // ...
}
```

### 1.3 template-parser.php - lowerToLayout 输出 group_id

在 `lowerToLayout()` 生成的每个 element 和 button 条目中添加 `'group_id' => $node->groupId`。

编译后 AppLayout_gen.php 中每个元素将携带:
```php
['type' => 'rect', 'x' => 2, 'y' => 82, ..., 'layer' => 0, 'group_id' => 'num-pad']
```

---

## Task 2: 运行时侧 - ChangeQueue 接入 + group-aware dirty

### 2.1 ReactiveComponent.php - 属性变更追踪

扩展 ReactiveComponent，属性变化时 push ChangeQueue 并记录 dirty groups:

```php
abstract class ReactiveComponent {
    protected static ?ChangeQueue $queue = null;
    protected static string $componentId = '';
    public bool $dirty = false;

    // v5 M4: group 级 dirty 追踪
    protected array $dirtyGroups = [];   // ['num-pad' => true, ...]
    protected bool $fullDirty = false;   // true = 全量重绘 (首帧/强制)

    // v5 M4: 标记特定 group 为 dirty
    public function markGroupDirty(string $groupId): void {
        $this->dirtyGroups[$groupId] = true;
        $this->dirty = true;
    }

    // v5 M4: 标记全量 dirty
    public function markFullDirty(): void {
        $this->fullDirty = true;
        $this->dirty = true;
    }

    // v5 M4: 消费 dirty 状态 (渲染器调用)
    public function consumeDirty(): array {
        $groups = $this->dirtyGroups;
        $full = $this->fullDirty;
        $this->dirtyGroups = [];
        $this->fullDirty = false;
        return ['full' => $full, 'groups' => $groups];
    }
}
```

### 2.2 生成代码适配 (sfc-compiler.php codegen)

编译器生成的 `App.gen.php` 中，ScriptAnalyzer 注入的 dirty 标记需要升级:
- 当前: `$this->dirty = true;`
- v5 M4: 保持兼容，`$this->dirty = true` 仍然有效 (触发全量重绘)
- 后续可选: 编译器分析属性→group 映射，生成 `$this->markGroupDirty('xxx')`

**本阶段策略**: 保持 `$this->dirty = true` 不变 (向后兼容)，BaseRenderer 在消费时