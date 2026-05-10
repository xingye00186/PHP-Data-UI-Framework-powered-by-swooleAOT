<?php

/**
 * VueCalc - 类 Vue 数据驱动的桌面计算器 (SFC 模式)
 * 
 * 架构:
 * - .vue 单文件组件 → SFC 编译器 → .gen.php (布局 + 组件类)
 * - ReactiveComponent: 响应式组件基类
 * - Calculator: 计算器逻辑组件 (100% PHP 逻辑)
 * - CalcRenderer: 数据驱动渲染器 (读取 LAYOUT 数据 + 组件 state, 驱动 C++ GDI 绘制)
 * - C++ 层: 仅提供 Win32 窗口/GDI 绘制原语
 * 
 * 数据流: 用户点击 → CalcApp.handleClick() → Calculator.handleButton()
 *        → 响应式属性变更 → dirty → CalcRenderer.render() → C++ 绘制
 */

// Windows 消息常量
const SW_SHOW = 5;
const WM_LBUTTONDOWN = 0x0201;
const WM_QUIT = 0x0012;

// ============================================================
// 数据驱动渲染器 (使用 SFC 生成的 LAYOUT 数据)
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

    /** 从组件属性获取绑定值 (避免 AOT 不支持 $obj->$variable) */
    private function getBindValue(string $bindKey): string
    {
        if ($bindKey === 'expression') {
            return $this->component->expression;
        }
        if ($bindKey === 'display') {
            return $this->component->display;
        }
        return '';
    }

    /** 渲染文本元素（支持对齐和动态字号） */
    private function renderTextElement(int $hdc, array $el): void
    {
        $bindKey = $el['bind'] ?? '';

        // 仅在绑定了属性且有内容时渲染
        if ($bindKey !== '') {
            $text = $this->getBindValue($bindKey);
            if ($text === '') {
                return;
            }
        } else {
            return;
        }

        $fontSize = $el['fontSize'] ?? 16;
        $color    = $el['color'] ?? 0xFFFFFF;
        $bold     = $el['bold'] ?? 0;
        $align    = $el['align'] ?? 'left';
        $x        = $el['x'] ?? 0;
        $y        = $el['y'] ?? 0;

        // 动态字号调整（长数字时缩小）
        $textLen = strlen($text);
        if ($textLen > 12 && $fontSize > 24) {
            $fontSize = 24;
        }
        if ($textLen > 16 && $fontSize > 18) {
            $fontSize = 18;
        }

        // 右对齐：根据容器宽度计算 x 坐标
        if ($align === 'right' && isset($el['containerW'])) {
            $containerW = $el['containerW'];
            $containerX = $el['containerX'] ?? 0;
            $charWidth  = (int)($fontSize * 0.6);
            $textWidth  = $textLen * $charWidth;
            $rightEdge  = $containerX + $containerW;
            $x = $rightEdge - 12 - $textWidth;
            if ($x < $containerX + 4) {
                $x = $containerX + 4;
            }
        }

        vue_draw_text($hdc, $x, $y, $text, $fontSize, $color, $bold);
    }

    /**
     * 数据驱动渲染: 遍历 LAYOUT 数据，从组件 state 读取数据驱动 C++ 绘制
     */
    public function render(): void
    {
        $hdc = vue_begin_paint($this->hWnd);
        $layout   = getLayout();
        $elements = $layout['elements'];
        $buttons  = $layout['buttons'];

        // 渲染元素 (rect 背景 + text 文本)
        foreach ($elements as $el) {
            $type = $el['type'];
            if ($type === 'rect') {
                vue_fill_rect($hdc, $el['x'], $el['y'], $el['w'], $el['h'], $el['color']);
            } elseif ($type === 'text') {
                $this->renderTextElement($hdc, $el);
            }
        }

        // 渲染按钮 (背景 + 边框 + 居中文字)
        foreach ($buttons as $btn) {
            // 按钮背景和边框
            vue_draw_button($hdc, $btn['x'], $btn['y'], $btn['w'], $btn['h'], $btn['bg'], $btn['border']);

            // 按钮文字居中
            $label = $btn['label'];
            $labelLen = strlen($label);
            $labelFontSize = 22;
            $labelCharW = (int)($labelFontSize * 0.6);
            $labelX = $btn['x'] + (int)(($btn['w'] - $labelLen * $labelCharW) / 2);
            $labelY = $btn['y'] + (int)(($btn['h'] - $labelFontSize) / 2);
            vue_draw_text($hdc, $labelX, $labelY, $label, $labelFontSize, $btn['fg'], 1);
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

    /** 初始化窗口 (使用 SFC 生成 WINDOW_WIDTH/WINDOW_HEIGHT 常量) */
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
        echo "VueCalc window created (SFC Data-Driven Mode)\n";
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

            // 数据驱动渲染: 仅在组件状态变更后重绘
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

    /** 处理鼠标点击: 基于 LAYOUT 按钮坐标命中测试 */
    private function handleClick(int $x, int $y): void
    {
        $buttons = getLayout()['buttons'];

        foreach ($buttons as $btn) {
            if ($x >= $btn['x'] && $x < $btn['x'] + $btn['w'] &&
                $y >= $btn['y'] && $y < $btn['y'] + $btn['h']) {
                $this->dispatchClick($btn);
                return;
            }
        }
    }

    /** 分发按钮点击到组件方法 (显式路由，兼容 AOT 编译器) */
    private function dispatchClick(array $btn): void
    {
        $handler = $btn['handler'];
        $arg     = $btn['arg'];

        if ($handler === 'reset') {
            $this->calc->reset();
        } elseif ($handler === 'backspace') {
            $this->calc->backspace();
        } elseif ($handler === 'calculate') {
            $this->calc->calculate();
        } elseif ($handler === 'handleButton') {
            $this->calc->handleButton($arg);
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
    echo "  VueCalc - SFC Data-Driven Calculator\n";
    echo "  Pipeline: .vue → SFC Compiler → .gen.php → AOT → .exe\n";
    echo "  (PHP Logic + C++ GDI Rendering)\n";
    echo "========================================\n\n";

    // 1. 初始化响应式框架的共享内存和变更队列
    ReactiveComponent::initShared(10240);

    // 2. 创建计算器组件 (SFC 编译生成的 ReactiveComponent 子类)
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
