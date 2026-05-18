<?php

use native_types;

/**
 * RenderContext - 后端无关渲染抽象基类 (v6 M1)
 * 
 * 提供与渲染后端无关的绘制接口抽象。当前封装已有 5 个 GDI 原语，
 * 未来可扩展至 26+ 方法支持多后端 (Skia/OpenGL/Web Canvas)。
 * 
 * AOT 兼容: abstract class + extends 模式已验证通过 Swoole Compiler。
 */
abstract class RenderContext
{
    /** 开始一帧渲染 (创建双缓冲) */
    abstract public function beginFrame(int $hWnd): int;

    /** 结束一帧渲染 (提交双缓冲) */
    abstract public function endFrame(int $hWnd, int $hdc): void;

    /** 填充矩形 */
    abstract public function fillRect(int $hdc, int $x, int $y, int $w, int $h, int $color): void;

    /** 绘制文本 */
    abstract public function drawText(int $hdc, int $x, int $y, string $text, int $fontSize, int $color, int $bold): void;

    /** 绘制按钮 (背景 + 边框) */
    abstract public function drawButton(int $hdc, int $x, int $y, int $w, int $h, int $bg, int $border): void;
}
