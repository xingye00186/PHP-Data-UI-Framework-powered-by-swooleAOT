#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>
#include <php_apps_calculator_main_arginfo.h>
#include <php_apps_calculator_Application_arginfo.h>
#include <php_framework_ReactiveComponent_arginfo.h>
#include <php_apps_calculator_gen_App.gen_arginfo.h>
#include <php_apps_calculator_gen_AppLayout_gen_arginfo.h>
#include <php_framework_ChangeQueue_arginfo.h>
#include <php_framework_BaseRenderer_arginfo.h>
#include <php_stub_vue_calc_arginfo.h>

extern "C" {
#include "php_cli_process_title.h"
#include "php_cli_process_title_arginfo.h"
}
// global vars 
// class register functions 
zend_class_entry *php_class_entry_Application;
zend_class_entry *php_class_entry_ReactiveComponent;
zend_class_entry *php_class_entry_App;
zend_class_entry *php_class_entry_ChangeQueue;
zend_class_entry *php_class_entry_BaseRenderer;
// class entry 
zend_class_entry *php_class_map[6];
// func 
zend_function *php_func_map[9];
// property 
uint32_t php_property_map[22];
zend_class_entry *php_get_class(int class_id, const php::Str &class_name) {
    if (UNEXPECTED(php_class_map[class_id] == nullptr)) {
        php_class_map[class_id] = php::getClassEntrySafe(class_name);
    }
    return php_class_map[class_id];
}

zend_function *php_get_func(int func_id, const php::Str &func_name) {
    if (UNEXPECTED(php_func_map[func_id] == nullptr)) {
        php_func_map[func_id] = php::getFunction(func_name);
    }
    return php_func_map[func_id];
}

zend_function *php_get_method(int func_id, const php::Str &method_name, int class_id, const php::Str &class_name) {
    if (UNEXPECTED(php_func_map[func_id] == nullptr)) {
        auto ce = php_get_class(class_id, class_name);
        php_func_map[func_id] = php::getMethod(ce, method_name);
    }
    return php_func_map[func_id];
}

uint32_t php_get_prop(int prop_id, const php::Str &prop_name, int class_id, const php::Str &class_name) {
    if (UNEXPECTED(php_property_map[prop_id] == 0)) {
        php_property_map[prop_id] = php::getPropertyOffset(class_name, prop_name) + 1024;
    }
    return php_property_map[prop_id] - 1024;
}

// literal strings 
php::Str _literal_strings[] = {
php::Str{ZEND_STRL("0"), true}, // [0]
php::Str{ZEND_STRL(""), true}, // [1]
php::Str{ZEND_STRL("About VueCalc"), true}, // [2]
php::Str{ZEND_STRL("SFC Data-Driven Calculator"), true}, // [3]
php::Str{ZEND_STRL("Version 5.0 (M2)"), true}, // [4]
php::Str{ZEND_STRL("App"), true}, // [5]
php::Str{ZEND_STRL("date_default_timezone_set"), true}, // [6]
php::Str{ZEND_STRL("Asia/Shanghai"), true}, // [7]
php::Str{ZEND_STRL("========================================\n"), true}, // [8]
php::Str{ZEND_STRL("  VueCalc v5 \342\200\224 SFC Data-Driven Application\n"), true}, // [9]
php::Str{ZEND_STRL("  Pipeline: .vue \342\206\222 SFC Compiler \342\206\222 .gen.php \342\206\222 AOT \342\206\222 .exe\n"), true}, // [10]
php::Str{ZEND_STRL("========================================\n\n"), true}, // [11]
php::Str{ZEND_STRL("ReactiveComponent"), true}, // [12]
php::Str{ZEND_STRL("MainApp"), true}, // [13]
php::Str{ZEND_STRL("Application"), true}, // [14]
php::Str{ZEND_STRL("initWindow"), true}, // [15]
php::Str{ZEND_STRL("run"), true}, // [16]
php::Str{ZEND_STRL("\nApplication closed.\n"), true}, // [17]
php::Str{ZEND_STRL("component"), true}, // [18]
php::Str{ZEND_STRL("hWnd"), true}, // [19]
php::Str{ZEND_STRL("VueCalc - SFC Data-Driven App"), true}, // [20]
php::Str{ZEND_STRL("Error: window creation failed!\n"), true}, // [21]
php::Str{ZEND_STRL("renderer"), true}, // [22]
php::Str{ZEND_STRL("BaseRenderer"), true}, // [23]
php::Str{ZEND_STRL("Window created (SFC Data-Driven Mode)\n"), true}, // [24]
php::Str{ZEND_STRL("render"), true}, // [25]
php::Str{ZEND_STRL("App started!\n"), true}, // [26]
php::Str{ZEND_STRL("count"), true}, // [27]
php::Str{ZEND_STRL("handleClick"), true}, // [28]
php::Str{ZEND_STRL("Throwable"), true}, // [29]
php::Str{ZEND_STRL("ERROR in handleClick: "), true}, // [30]
php::Str{ZEND_STRL("getMessage"), true}, // [31]
php::Str{ZEND_STRL("\n"), true}, // [32]
php::Str{ZEND_STRL("getTraceAsString"), true}, // [33]
php::Str{ZEND_STRL("dirty"), true}, // [34]
php::Str{ZEND_STRL("RENDER ERROR: "), true}, // [35]
php::Str{ZEND_STRL("usleep"), true}, // [36]
php::Str{ZEND_STRL("App closed\n"), true}, // [37]
php::Str{ZEND_STRL("buttons"), true}, // [38]
php::Str{ZEND_STRL("condition"), true}, // [39]
php::Str{ZEND_STRL("evalCondition"), true}, // [40]
php::Str{ZEND_STRL("layer"), true}, // [41]
php::Str{ZEND_STRL("x"), true}, // [42]
php::Str{ZEND_STRL("w"), true}, // [43]
php::Str{ZEND_STRL("y"), true}, // [44]
php::Str{ZEND_STRL("h"), true}, // [45]
php::Str{ZEND_STRL("dispatchClick"), true}, // [46]
php::Str{ZEND_STRL("componentId"), true}, // [47]
php::Str{ZEND_STRL("queue"), true}, // [48]
php::Str{ZEND_STRL("ChangeQueue"), true}, // [49]
php::Str{ZEND_STRL("display"), true}, // [50]
php::Str{ZEND_STRL("expression"), true}, // [51]
php::Str{ZEND_STRL("operand1"), true}, // [52]
php::Str{ZEND_STRL("operator"), true}, // [53]
php::Str{ZEND_STRL("newInput"), true}, // [54]
php::Str{ZEND_STRL("hasDecimal"), true}, // [55]
php::Str{ZEND_STRL("."), true}, // [56]
php::Str{ZEND_STRL("calculate"), true}, // [57]
php::Str{ZEND_STRL(" "), true}, // [58]
php::Str{ZEND_STRL("+"), true}, // [59]
php::Str{ZEND_STRL("-"), true}, // [60]
php::Str{ZEND_STRL("*"), true}, // [61]
php::Str{ZEND_STRL("/"), true}, // [62]
php::Str{ZEND_STRL("Error"), true}, // [63]
php::Str{ZEND_STRL("rtrim"), true}, // [64]
php::Str{ZEND_STRL("sprintf"), true}, // [65]
php::Str{ZEND_STRL("%.8f"), true}, // [66]
php::Str{ZEND_STRL("strpos"), true}, // [67]
php::Str{ZEND_STRL("substr"), true}, // [68]
php::Str{ZEND_STRL("C"), true}, // [69]
php::Str{ZEND_STRL("reset"), true}, // [70]
php::Str{ZEND_STRL("<-"), true}, // [71]
php::Str{ZEND_STRL("backspace"), true}, // [72]
php::Str{ZEND_STRL("="), true}, // [73]
php::Str{ZEND_STRL("inputOperator"), true}, // [74]
php::Str{ZEND_STRL("inputDecimal"), true}, // [75]
php::Str{ZEND_STRL("inputDigit"), true}, // [76]
php::Str{ZEND_STRL("showDialog"), true}, // [77]
php::Str{ZEND_STRL("dialogTitle"), true}, // [78]
php::Str{ZEND_STRL("dialogContent"), true}, // [79]
php::Str{ZEND_STRL("dialogVersion"), true}, // [80]
php::Str{ZEND_STRL("handler"), true}, // [81]
php::Str{ZEND_STRL("handleButton"), true}, // [82]
php::Str{ZEND_STRL("arg"), true}, // [83]
php::Str{ZEND_STRL("toggleAboutDialog"), true}, // [84]
php::Str{ZEND_STRL("prop"), true}, // [85]
php::Str{ZEND_STRL("op"), true}, // [86]
php::Str{ZEND_STRL("truthy"), true}, // [87]
php::Str{ZEND_STRL("falsy"), true}, // [88]
php::Str{ZEND_STRL("=="), true}, // [89]
php::Str{ZEND_STRL("value"), true}, // [90]
php::Str{ZEND_STRL("!="), true}, // [91]
php::Str{ZEND_STRL("__construct"), true}, // [92]
php::Str{ZEND_STRL("window_width"), true}, // [93]
php::Str{ZEND_STRL("window_height"), true}, // [94]
php::Str{ZEND_STRL("rect"), true}, // [95]
php::Str{ZEND_STRL("type"), true}, // [96]
php::Str{ZEND_STRL("color"), true}, // [97]
php::Str{ZEND_STRL("text"), true}, // [98]
php::Str{ZEND_STRL("bind"), true}, // [99]
php::Str{ZEND_STRL("right"), true}, // [100]
php::Str{ZEND_STRL("align"), true}, // [101]
php::Str{ZEND_STRL("fontSize"), true}, // [102]
php::Str{ZEND_STRL("bold"), true}, // [103]
php::Str{ZEND_STRL("containerW"), true}, // [104]
php::Str{ZEND_STRL("containerX"), true}, // [105]
php::Str{ZEND_STRL("left"), true}, // [106]
php::Str{ZEND_STRL("center"), true}, // [107]
php::Str{ZEND_STRL("elements"), true}, // [108]
php::Str{ZEND_STRL("label"), true}, // [109]
php::Str{ZEND_STRL("bg"), true}, // [110]
php::Str{ZEND_STRL("fg"), true}, // [111]
php::Str{ZEND_STRL("border"), true}, // [112]
php::Str{ZEND_STRL("NULL"), true}, // [113]
php::Str{ZEND_STRL("7"), true}, // [114]
php::Str{ZEND_STRL("8"), true}, // [115]
php::Str{ZEND_STRL("9"), true}, // [116]
php::Str{ZEND_STRL("4"), true}, // [117]
php::Str{ZEND_STRL("5"), true}, // [118]
php::Str{ZEND_STRL("6"), true}, // [119]
php::Str{ZEND_STRL("1"), true}, // [120]
php::Str{ZEND_STRL("2"), true}, // [121]
php::Str{ZEND_STRL("3"), true}, // [122]
php::Str{ZEND_STRL("?"), true}, // [123]
php::Str{ZEND_STRL("Close"), true}, // [124]
php::Str{ZEND_STRL("buffer"), true}, // [125]
php::Str{ZEND_STRL("head"), true}, // [126]
php::Str{ZEND_STRL("maxSize"), true}, // [127]
php::Str{ZEND_STRL("key"), true}, // [128]
php::Str{ZEND_STRL("version"), true}, // [129]
php::Str{ZEND_STRL("is_string"), true}, // [130]
php::Str{ZEND_STRL("tail"), true}, // [131]
php::Str{ZEND_STRL("getBindValue"), true}, // [132]
php::Str{ZEND_STRL("renderTextElement"), true}, // [133]
};

// constants 
php::Var sw_show;
php::Var wm_lbuttondown;
php::Var wm_quit;
php::Var window_width;
php::Var window_height;
// class 
// clang-format off
static const zend_function_entry ext_functions[] = {
    PHP_FE(cli_set_process_title,        arginfo_cli_set_process_title)
    PHP_FE(cli_get_process_title,        arginfo_cli_get_process_title)
    ZEND_FE(main, arginfo_main)
    ZEND_FE(getlayout, arginfo_getlayout)
    ZEND_FE(vue_window_create, arginfo_vue_window_create)
    ZEND_FE(vue_window_show, arginfo_vue_window_show)
    ZEND_FE(vue_quit_requested, arginfo_vue_quit_requested)
    ZEND_FE(vue_peek_message, arginfo_vue_peek_message)
    ZEND_FE(vue_begin_paint, arginfo_vue_begin_paint)
    ZEND_FE(vue_end_paint, arginfo_vue_end_paint)
    ZEND_FE(vue_fill_rect, arginfo_vue_fill_rect)
    ZEND_FE(vue_draw_text, arginfo_vue_draw_text)
    ZEND_FE(vue_draw_button, arginfo_vue_draw_button)
    ZEND_FE_END
};
// clang-format on

PHP_MINIT_FUNCTION(app_calculator) {
zend_try {
// class/interface class entries
php_class_entry_Application = php_register_class_Application();
php_class_entry_ReactiveComponent = php_register_class_ReactiveComponent();
php_class_entry_App = php_register_class_App(php_class_entry_ReactiveComponent);
php_class_entry_ChangeQueue = php_register_class_ChangeQueue();
php_class_entry_BaseRenderer = php_register_class_BaseRenderer();

// register symbols
} zend_end_try();
return SUCCESS;
}

void php_app_init() {
// register constants
sw_show = 5LL;
php::define("SW_SHOW", sw_show);
wm_lbuttondown = 513LL;
php::define("WM_LBUTTONDOWN", wm_lbuttondown);
wm_quit = 18LL;
php::define("WM_QUIT", wm_quit);
window_width = 328LL;
php::define("WINDOW_WIDTH", window_width);
window_height = 420LL;
php::define("WINDOW_HEIGHT", window_height);
// global vars 
// static property 
// class array constants
}

void php_app_clean() {
sw_show.unset();
wm_lbuttondown.unset();
wm_quit.unset();
window_width.unset();
window_height.unset();
// class array constants
}

PHP_RINIT_FUNCTION(app_calculator) {
php::request_init();
php_app_init();
php::eval("main();");
return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(app_calculator) {
    php_app_clean();
    php::request_shutdown();
    return SUCCESS;
}

zend_module_entry app_calculator_module_entry = {
    STANDARD_MODULE_HEADER,
    "app_calculator",
    ext_functions,
    PHP_MINIT(app_calculator),
    nullptr,
    PHP_RINIT(app_calculator),
    PHP_RSHUTDOWN(app_calculator),
    nullptr,
    nullptr,
    STANDARD_MODULE_PROPERTIES,
};

zend_module_entry *php_embed_get_module() {
    return &app_calculator_module_entry;
}
