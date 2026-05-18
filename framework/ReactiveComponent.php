<?php

use native_types;

/**
 * ReactiveComponent - 响应式组件基类
 * 
 * AOT 兼容版本: 去掉 __get/__set 魔术方法，改用直接属性声明 + 手动脏标记。
 * 子类声明实际属性并在修改状态后调用 $this->dirty = true。
 */
abstract class ReactiveComponent
{
    /** 全局变更队列 */
    protected ?ChangeQueue $queue = null;

    /** 当前组件标识 */
    protected string $componentId = '';

    /** 脏标记: 是否需要重绘 */
    public bool $dirty = false;

    /** 模板文件路径(可选) */
    public string $template = '';

    /** v5 M4: group 级 dirty 追踪 */
    protected array $dirtyGroups = [];

    /** v5 M4: 是否需要全量重绘 (首帧/强制刷新) */
    protected bool $fullDirty = true;

    public function __construct(?string $componentId = null)
    {
        $this->componentId = $componentId ?? get_class($this);
    }

    public function initShared(int $tableSize = 10240): void
    {
        $this->queue = new ChangeQueue();
    }

    /** v5 M4: 标记特定 group 为 dirty */
    public function markGroupDirty(string $groupId): void
    {
        $this->dirtyGroups[$groupId] = true;
        $this->dirty = true;
    }

    /** v5 M4: 标记全量 dirty (全部 group 重绘) */
    public function markFullDirty(): void
    {
        $this->fullDirty = true;
        $this->dirty = true;
    }

    /** v5 M4: 消费 dirty 状态 (渲染器调用后重置) */
    public function consumeDirty(): array
    {
        $groups = $this->dirtyGroups;
        $full = $this->fullDirty;
        $this->dirtyGroups = [];
        $this->fullDirty = false;
        return ['full' => $full, 'groups' => $groups];
    }

    /** v6 M1: 组件布局挂载回调 (子类可覆写做资源初始化) */
    public function onAttach(string $layoutName): void
    {
    }

    /** v6 M1: 组件布局卸载回调 (子类可覆写做资源释放) */
    public function onDetach(string $layoutName): void
    {
    }
}
