<?php

/**
 * VueCalc - 类 Vue 数据驱动的桌面计算器
 * 
 * 架构:
 * - ReactiveComponent: 响应式组件基类 (类似 Vue 的 data + watch)
 * - Calculator: 计算器逻辑组件 (纯 PHP 逻辑)
 * - CalcRenderer: 数据驱动渲染器 (读取组件 state, 驱动 C++ GDI 绘制)
 * - C++ 层: 仅提供 Win32 窗口/GDI 绘制原语
 * 
 * 数据流: 用户点击 -> Calculator.handleButton() -> 响应式属性变更 
 *        -> dirty 标记 -> CalcRenderer.render() 读取最新 state -> C++ 绘制
 */

// ============================================================
// 常量
// ============================================================

const SW_SHOW = 5;
const WM_LBUTTONDOWN = 0x0201;
const WM_QUIT = 0x0012;

// 布局参数
const BTN_COLS = 4;
const BTN_ROWS = 5;
const BTN_WIDTH = 80;
const BTN_HEIGHT = 60;
const BTN_MARGIN = 2;
const DISPLAY_HEIGHT = 80;
const WINDOW_WIDTH = BTN_COLS * BTN_WIDTH + 16;
const WINDOW_HEIGHT = DISPLAY_HEIGHT + BTN_ROWS * BTN_HEIGHT + 40;

// 按钮布局 (5行 x 4列)
const BUTTONS = [
    ['C',  '<-', '/',  '*'],
    ['7',  '8',  '9',  '-'],
    ['4',  '5',  '6',  '+'],
    ['1',  '2',  '3',  '='],
    ['0',  '.',  '',   ''],
];

// 按钮类型
const BTN_TYPE_NUM = 0;
const BTN_TYPE_OP = 1;
const BTN_TYPE_FUNC = 2;
const BTN_TYPE_EQ = 3;

// RGB 颜色辅助函数 (BGR 格式)
function rgb(int $r, int $g, int $b): int
{
    return ($r | ($g << 8) | ($b << 16));
}

// ============================================================
// 数据驱动渲染器
// ============================================================

class CalcRenderer
{
    private int $hWnd;
    private Calculator $component;

    public function __construct(int $hWnd, Calculator $component)
    {
        $this->hWnd = $hWnd;
        $this->component = $component;
    }

    /** 判断按钮类型 */
    private function getButtonType(string $label): int
    {
        if ($label === '=') return BTN_TYPE_EQ;
        if ($label === '+' || $label === '-' || $label === '*' || $label === '/') return BTN_TYPE_OP;
        if ($label === 'C' || $label === '<-') return BTN_TYPE_FUNC;
        return BTN_TYPE_NUM;
    }

    /**
     * 数据驱动渲染: 从组件 state 读取数据, 驱动 C++ 绘制
     * 
     * 这是 "类 Vue" 的核心: render 函数不接收具体参数,
     * 而是从响应式组件读取最新状态, 实现 UI = f(state)
     */
    public function render(): void
    {
        $hdc = vue_begin_paint($this->hWnd);

        $totalW = BTN_COLS * BTN_WIDTH;
        $totalH = DISPLAY_HEIGHT + BTN_ROWS * BTN_HEIGHT;

        // 背景
        vue_fill_rect($hdc, 0, 0, $totalW, $totalH, rgb(30, 30, 30));

        // 显示区域背景
        vue_fill_rect($hdc, 4, 4, $totalW - 8, DISPLAY_HEIGHT - 8, rgb(45, 45, 45));

        // ---- 数据驱动: 从组件读取 display 和 expression ----
        $display = $this->component->display;
        $expression = $this->component->expression;

        // 表达式文本(小号, 右上)
        if ($expression !== '') {
            $exprLen = strlen($expression);
            $exprX = $totalW - 12 - $exprLen * 8;
            if ($exprX < 10) {
                $exprX = 10;
            }
            vue_draw_text($hdc, $exprX, 10, $expression, 16, rgb(150, 150, 150), 0);
        }

        // 显示文本(大号, 右对齐)
        $displayLen = strlen($display);
        $fontSize = 32;
        if ($displayLen > 12) {
            $fontSize = 24;
        }
        if ($displayLen > 16) {
            $fontSize = 18;
        }
        $charWidth = (int)($fontSize * 0.6);
        $textX = $totalW - 12 - $displayLen * $charWidth;
        if ($textX < 10) {
            $textX = 10;
        }
        $textY = DISPLAY_HEIGHT - $fontSize - 14;
        vue_draw_text($hdc, $textX, $textY, $display, $fontSize, rgb(255, 255, 255), 1);

        // 绘制按钮 (布局数据驱动)
        for ($row = 0; $row < BTN_ROWS; $row++) {
            for ($col = 0; $col < BTN_COLS; $col++) {
                $label = BUTTONS[$row][$col];
                if ($label === '') {
                    continue;
                }

                $bx = $col * BTN_WIDTH + BTN_MARGIN;
                $by = DISPLAY_HEIGHT + $row * BTN_HEIGHT + BTN_MARGIN;
                $bw = BTN_WIDTH - BTN_MARGIN * 2;
                $bh = BTN_HEIGHT - BTN_MARGIN * 2;

                $type = $this->getButtonType($label);

                // 按钮颜色
                $bgColor = rgb(50, 50, 50);
                $textColor = rgb(255, 255, 255);

                if ($type === BTN_TYPE_OP) {
                    $bgColor = rgb(255, 149, 0);
                } elseif ($type === BTN_TYPE_EQ) {
                    $bgColor = rgb(0, 122, 255);
                } elseif ($type === BTN_TYPE_FUNC) {
                    $bgColor = rgb(80, 80, 80);
                }

                $borderColor = rgb(
                    min(255, (($bgColor >> 16) & 0xFF) + 20),
                    min(255, (($bgColor >> 8) & 0xFF) + 20),
                    min(255, ($bgColor & 0xFF) + 20)
                );

                // 绘制按钮背景和边框
                vue_draw_button($hdc, $bx, $by, $bw, $bh, $bgColor, $borderColor);

                // 绘制按钮文字(居中)
                $labelLen = strlen($label);
                $labelFontSize = 22;
                $labelCharW = (int)($labelFontSize * 0.6);
                $labelX = $bx + (int)(($bw - $labelLen * $labelCharW) / 2);
                $labelY = $by + (int)(($bh - $labelFontSize) / 2);
                vue_draw_text($hdc, $labelX, $labelY, $label, $labelFontSize, $textColor, 1);
            }
        }

        vue_end_paint($this->hWnd, $hdc);
    }
}

// ============================================================
// 主应用控制器
// ============================================================

class CalcApp
{
    private Calculator $calc;
    private int $hWnd;
    private CalcRenderer $renderer;

    public function __construct(Calculator $calc)
    {
        $this->calc = $calc;
        $this->hWnd = 0;
    }

    /** 初始化窗口 */
    public function initWindow(): bool
    {
        $this->hWnd = vue_window_create(
            'VueCalc - Reactive Data-Driven Calculator',
            WINDOW_WIDTH,
            WINDOW_HEIGHT
        );

        if ($this->hWnd == 0) {
            echo "Error: window creation failed!\n";
            return false;
        }

        vue_window_show($this->hWnd, SW_SHOW);
        $this->renderer = new CalcRenderer($this->hWnd, $this->calc);
        echo "VueCalc window created (Data-Driven Mode)\n";
        return true;
    }

    /** 主事件循环 */
    public function run(): void
    {
        $running = true;
        $this->renderer->render(); // 首帧
        echo "VueCalc started!\n";

        while ($running) {
            // 处理所有待处理的 Windows 消息
            while (true) {
                $msg = vue_peek_message();
                if (count($msg) == 0) {
                    break;
                }

                $msgType = $msg[1] ?? 0;

                if ($msgType == WM_LBUTTONDOWN) {
                    $lParam = $msg[3] ?? 0;
                    $mx = $lParam & 0xFFFF;
                    $my = ($lParam >> 16) & 0xFFFF;
                    try {
                        $this->handleClick($mx, $my);
                    } catch (\Throwable $e) {
                        echo "ERROR in handleClick: " . $e->getMessage() . "\n";
                        echo $e->getTraceAsString() . "\n";
                    }
                }

                if ($msgType == WM_QUIT) {
                    $running = false;
                    break;
                }
            }

            if (vue_quit_requested()) {
                $running = false;
            }
            if (!$running) {
                break;
            }

            // ---- 数据驱动渲染: 消息处理完后一次性重绘 ----
            if ($this->calc->dirty) {
                try {
                    $this->renderer->render();
                } catch (\Throwable $e) {
                    echo "RENDER ERROR: " . $e->getMessage() . "\n";
                }
                $this->calc->dirty = false;
            }

            usleep(16000); // ~60 FPS
        }

        echo "VueCalc closed\n";
    }

    /** 处理鼠标点击, 路由到计算器组件 */
    private function handleClick(int $x, int $y): void
    {
        $by = $y - DISPLAY_HEIGHT;
        if ($by < 0) {
            return;
        }

        $col = intdiv($x, BTN_WIDTH);
        $row = intdiv($by, BTN_HEIGHT);

        if ($row < 0 || $row >= BTN_ROWS || $col < 0 || $col >= BTN_COLS) {
            return;
        }

        $label = BUTTONS[$row][$col];
        if ($label === '') {
            return;
        }

        // 数据驱动: 修改组件状态, 自动触发下次渲染
        try {
            $this->calc->handleButton($label);
        } catch (\Throwable $e) {
            echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        }
    }
}

// ============================================================
// 入口函数 (AOT 编译器要求所有代码在函数内)
// ============================================================

function main(): int
{
    date_default_timezone_set('Asia/Shanghai');

    echo "========================================\n";
    echo "  VueCalc - Reactive Data-Driven Demo\n";
    echo "  Framework: Class-Vue Reactive Model\n";
    echo "  (100% PHP Logic + C++ Rendering)\n";
    echo "========================================\n\n";

    // 1. 初始化响应式框架的共享内存和变更队列
    ReactiveComponent::initShared(10240);

    // 2. 创建计算器组件(响应式)
    $calc = new Calculator('MainCalculator');

    // 3. 创建应用并启动
    $app = new CalcApp($calc);
    if (!$app->initWindow()) {
        return 1;
    }
    $app->run();

    echo "\nVueCalc closed. Goodbye!\n";
    return 0;
}
