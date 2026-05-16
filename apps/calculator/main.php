<?php

/**
 * VueCalc v5 — Application Entry Point (通用框架)
 * 
 * AOT 编译由此文件开始。project.yml sources 引用此文件。
 * 
 * 架构:
 * - .vue 单文件组件 → SFC 编译器 → .gen.php
 * - ReactiveComponent: 响应式组件基类 (framework/)
 * - App: 应用逻辑组件 (SFC 从 App.vue 编译生成)
 * - BaseRenderer: 数据驱动渲染器 (GDI 绘制)
 * - Application: 通用窗口管理和事件循环
 */

// Windows 消息常量
const SW_SHOW = 5;
const WM_LBUTTONDOWN = 0x0201;
const WM_QUIT = 0x0012;

function main(): int
{
    date_default_timezone_set('Asia/Shanghai');

    echo "========================================\n";
    echo "  VueCalc v5 — SFC Data-Driven Application\n";
    echo "  Pipeline: .vue → SFC Compiler → .gen.php → AOT → .exe\n";
    echo "========================================\n\n";

    // 1. 创建应用组件 (SFC 编译生成的 ReactiveComponent 子类)
    $component = new App('MainApp');

    // 2. 初始化响应式框架共享内存
    $component->initShared(10240);

    // 3. 创建应用控制器并启动事件循环
    $app = new Application($component);
    if (!$app->initWindow()) {
        return 1;
    }
    $app->run();

    echo "\nApplication closed.\n";
    return 0;
}
