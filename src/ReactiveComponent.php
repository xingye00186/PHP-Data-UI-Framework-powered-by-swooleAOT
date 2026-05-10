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
    protected static ?ChangeQueue $queue = null;

    /** 当前组件标识 */
    protected static string $componentId = '';

    /** 脏标记: 是否需要重绘 */
    public bool $dirty = false;

    /** 模板文件路径(可选) */
    public string $template = '';

    public function __construct(?string $componentId = null)
    {
        self::$componentId = $componentId ?? get_class($this);
    }

    public static function initShared(int $tableSize = 10240): void
    {
        self::$queue = new ChangeQueue();
    }
}
