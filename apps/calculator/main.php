<?php

/**
 * VueCalc v6 — Application Entry Point (v6 M1: RenderContext + 分段布局)
 * 
 * AOT 编译由此文件开始。project.yml sources 引用此文件。
 */

// Windows 消息常量
const SW_SHOW = 5;
const WM_LBUTTONDOWN = 0x0201;
const WM_QUIT = 0x0012;

function main(): int
{
    date_default_timezone_set('Asia/Shanghai');

    echo "========================================\n";
    echo "  VueCalc v6 — SFC Data-Driven Application\n";
    echo "  Pipeline: .vue → SFC Compiler → .gen.php → AOT → .exe\n";
    echo "========================================\n\n";

    // 1. 创建应用组件
    $component = new App('MainApp');
    $component->initShared(10240);

    // 2. 创建渲染上下文 (v6 M1)
    $ctx = new GdiRenderContext();

    // 3. 创建应用控制器并启动事件循环
    $app = new Application($component, $ctx);
    if (!$app->initWindow()) {
        return 1;
    }
    $app->run();

    echo "\nApplication closed.\n";
    return 0;
}
