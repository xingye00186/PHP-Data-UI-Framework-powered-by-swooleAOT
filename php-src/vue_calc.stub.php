<?php

/**
 * VueCalc Win32 API 声明 (stub)
 * 
 * C++ 层仅提供 Win32 API 的薄封装。
 * 所有计算器逻辑、响应式数据均由 PHP 端实现。
 * 
 * 函数命名规范: 在 PHP 中以 vue_ 开头, C++ 实现中对应 php_vue_ 前缀
 */

// ---- 窗口管理 ----
function vue_window_create(string $title, int $width, int $height): int {}
function vue_window_show(int $hWnd, int $cmdShow): void {}
function vue_quit_requested(): bool {}
function vue_peek_message(): array {}

// ---- GDI 绘制原语 ----
function vue_begin_paint(int $hWnd): int {}
function vue_end_paint(int $hWnd, int $hdc): void {}
function vue_fill_rect(int $hdc, int $x, int $y, int $w, int $h, int $rgb): void {}
function vue_draw_text(int $hdc, int $x, int $y, string $text, int $fontSize, int $rgb, int $bold): void {}
function vue_draw_button(int $hdc, int $x, int $y, int $w, int $h, int $bgColor, int $borderColor): void {}
