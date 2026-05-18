<?php

use native_types;

/**
 * GdiRenderContext - Win32 GDI 后端实现 (v6 M1)
 * 
 * 直接委托给 C++ phpx 扩展提供的 vue_* stub 函数。
 * 当前封装 5 个基础原语，未来 C++ 层扩展后可逐步添加新方法。
 */
class GdiRenderContext extends RenderContext
{
    public function beginFrame(int $hWnd): int
    {
        return vue_begin_paint($hWnd);
    }

    public function endFrame(int $hWnd, int $hdc): void
    {
        vue_end_paint($hWnd, $hdc);
    }

    public function fillRect(int $hdc, int $x, int $y, int $w, int $h, int $color): void
    {
        vue_fill_rect($hdc, $x, $y, $w, $h, $color);
    }

    public function drawText(int $hdc, int $x, int $y, string $text, int $fontSize, int $color, int $bold): void
    {
        vue_draw_text($hdc, $x, $y, $text, $fontSize, $color, $bold);
    }

    public function drawButton(int $hdc, int $x, int $y, int $w, int $h, int $bg, int $border): void
    {
        vue_draw_button($hdc, $x, $y, $w, $h, $bg, $border);
    }
}
