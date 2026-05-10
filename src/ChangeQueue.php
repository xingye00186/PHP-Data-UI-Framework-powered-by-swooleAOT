<?php

use native_types;

/**
 * ChangeQueue - 变更通知队列
 * 
 * 环形缓冲实现的变更队列。当 ReactiveValue 发生变化时，
 * 将变更推入队列，渲染循环消费队列。
 */
class ChangeQueue
{
    private array $buffer = [];
    private int $head = 0;
    private int $tail = 0;
    private int $maxSize = 4096;

    public function __construct()
    {
        $this->buffer = [];
    }

    /** 将变更推入队列 */
    public function push(string $key, int $version, $value): void
    {
        $idx = $this->head % $this->maxSize;
        $this->buffer[$idx] = [
            'key'     => $key,
            'version' => $version,
            'value'   => is_string($value) ? $value : (string)$value
        ];
        $this->head++;
    }

    /** 从队列取出一个变更(无变更时返回 null) */
    public function pop(): ?array
    {
        if ($this->tail >= $this->head) {
            return null;
        }
        $idx = $this->tail % $this->maxSize;
        $row = $this->buffer[$idx];
        $this->tail++;
        return [
            'key'     => $row['key'],
            'version' => $row['version'],
            'value'   => $row['value']
        ];
    }

    /** 检查队列是否为空 */
    public function isEmpty(): bool
    {
        return $this->tail >= $this->head;
    }
}
