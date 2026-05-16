<?php

/**
 * BaseRenderer - 泛化数据驱动渲染器 (v5 M1: 从 main.php CalcRenderer 提取并泛化)
 * 
 * 接受任意 ReactiveComponent 子类，遍历 LAYOUT 数据，从组件 state 读取数据驱动 C++ GDI 绘制。
 * 不绑定特定组件类型，支持框架复用。
 */
class BaseRenderer
{
    private int $hWnd;
    private ReactiveComponent $component;

    public function __construct(int $hWnd, ReactiveComponent $component)
    {
        $this->hWnd = $hWnd;
        $this->component = $component;
    }

    /** 从组件属性获取绑定值 (委托给生成的 getBindValue 方法) */
    protected function getBindValue(string $bindKey): string
    {
        return $this->component->getBindValue($bindKey);
    }

    /** 渲染文本元素（支持对齐和动态字号） */
    protected function renderTextElement(int $hdc, array $el): void
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

        // 居中对齐：根据容器宽度计算 x 坐标
        if ($align === 'center' && isset($el['containerW'])) {
            $containerW = $el['containerW'];
            $containerX = $el['containerX'] ?? 0;
            $charWidth  = (int)($fontSize * 0.6);
            $textWidth  = $textLen * $charWidth;
            $x = $containerX + (int)(($containerW - $textWidth) / 2);
            if ($x < $containerX) {
                $x = $containerX;
            }
        }

        vue_draw_text($hdc, $x, $y, $text, $fontSize, $color, $bold);
    }

    /**
     * 数据驱动渲染: 两阶段分层渲染 (v5 M3)
     */
    public function render(): void
    {
        $hdc = vue_begin_paint($this->hWnd);
        $layout   = getLayout();
        $elements = $layout['elements'];
        $buttons  = $layout['buttons'];

        // ====== Phase 1: 确定最高活跃层 ======
        $maxLayer = 0;
        foreach ($elements as $el) {
            if (isset($el['condition']) && !$this->component->evalCondition($el['condition'])) continue;
            $layer = $el['layer'] ?? 0;
            if ($layer > $maxLayer) $maxLayer = $layer;
        }
        foreach ($buttons as $btn) {
            if (isset($btn['condition']) && !$this->component->evalCondition($btn['condition'])) continue;
            $layer = $btn['layer'] ?? 0;
            if ($layer > $maxLayer) $maxLayer = $layer;
        }

        // ====== Phase 2: 分层渲染 ======
        // 每层内先画元素再画按钮，确保高层完整覆盖低层
        for ($l = 0; $l <= $maxLayer; $l++) {
            // 本层元素
            foreach ($elements as $el) {
                if (($el['layer'] ?? 0) !== $l) continue;
                if (isset($el['condition']) && !$this->component->evalCondition($el['condition'])) continue;
                $type = $el['type'];
                if ($type === 'rect') {
                    vue_fill_rect($hdc, $el['x'], $el['y'], $el['w'], $el['h'], $el['color']);
                } elseif ($type === 'text') {
                    $this->renderTextElement($hdc, $el);
                }
            }
            // 本层按钮: layer < maxLayer 且有 condition 的跳过 (被高层屏蔽)
            foreach ($buttons as $btn) {
                $btnLayer = $btn['layer'] ?? 0;
                if ($btnLayer !== $l) continue;
                // 低层有条件的按钮被高层屏蔽 (非 chrome)
                if ($btnLayer < $maxLayer && isset($btn['condition'])) continue;
                // condition 不满足则跳过
                if (isset($btn['condition']) && !$this->component->evalCondition($btn['condition'])) continue;
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
        }

        vue_end_paint($this->hWnd, $hdc);
    }
}
