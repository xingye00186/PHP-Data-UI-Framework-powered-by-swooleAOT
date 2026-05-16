<?php

/**
 * Application — 通用 SFC 应用控制器 (v5 M2)
 * 
 * 负责窗口初始化、事件循环、点击分发、脏标记驱动的渲染调度。
 * 接受任意 ReactiveComponent 子类，不绑定具体组件类型。
 * 框架可复用：任何 .vue 编译产物均可作为组件传入。
 */
class Application
{
    private ReactiveComponent $component;
    private int $hWnd;
    private BaseRenderer $renderer;

    public function __construct(ReactiveComponent $component)
    {
        $this->component = $component;
        $this->hWnd = 0;
    }

    /** 初始化窗口 (使用 SFC 生成 WINDOW_WIDTH/WINDOW_HEIGHT 常量) */
    public function initWindow(): bool
    {
        $this->hWnd = vue_window_create(
            'VueCalc - SFC Data-Driven App',
            WINDOW_WIDTH,
            WINDOW_HEIGHT
        );

        if ($this->hWnd == 0) {
            echo "Error: window creation failed!\n";
            return false;
        }

        vue_window_show($this->hWnd, SW_SHOW);
        $this->renderer = new BaseRenderer($this->hWnd, $this->component);
        echo "Window created (SFC Data-Driven Mode)\n";
        return true;
    }

    /** 主事件循环 */
    public function run(): void
    {
        $running = true;
        $this->renderer->render(); // 首帧
        echo "App started!\n";

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
            if ($this->component->dirty) {
                try {
                    $this->renderer->render();
                } catch (\Throwable $e) {
                    echo "RENDER ERROR: " . $e->getMessage() . "\n";
                }
                $this->component->dirty = false;
            }

            usleep(16000); // ~60 FPS
        }

        echo "App closed\n";
    }

    /** 处理鼠标点击: 分层命中测试 (v5 M3) */
    private function handleClick(int $x, int $y): void
    {
        $buttons = getLayout()['buttons'];

        // Phase 1: 确定最高活跃层
        $maxLayer = 0;
        foreach ($buttons as $btn) {
            if (isset($btn['condition']) && !$this->component->evalCondition($btn['condition'])) continue;
            $layer = $btn['layer'] ?? 0;
            if ($layer > $maxLayer) $maxLayer = $layer;
        }

        // Phase 2: 从最高层向下逆序命中测试
        for ($l = $maxLayer; $l >= 0; $l--) {
            for ($i = count($buttons) - 1; $i >= 0; $i--) {
                $btn = $buttons[$i];
                $btnLayer = $btn['layer'] ?? 0;
                if ($btnLayer !== $l) continue;
                // 低层有条件的非 chrome 按钮被屏蔽
                if ($btnLayer < $maxLayer && isset($btn['condition'])) continue;
                // condition 不满足则跳过
                if (isset($btn['condition']) && !$this->component->evalCondition($btn['condition'])) continue;

                if ($x >= $btn['x'] && $x < $btn['x'] + $btn['w'] &&
                    $y >= $btn['y'] && $y < $btn['y'] + $btn['h']) {
                    $this->dispatchClick($btn);
                    return;
                }
            }
        }
    }

    /** 分发按钮点击到组件方法 (委托给生成的 dispatchClick 方法) */
    private function dispatchClick(array $btn): void
    {
        $this->component->dispatchClick($btn);
    }
}
