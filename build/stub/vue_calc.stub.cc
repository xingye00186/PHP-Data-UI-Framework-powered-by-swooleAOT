#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>











ZEND_FUNCTION(vue_window_create){
php::Str arg_title = php::getCallArg(0);
php::Int arg_width = php::toInt(php::getCallArg(1));
php::Int arg_height = php::toInt(php::getCallArg(2));
auto retval = php_vue_window_create(arg_title,arg_width,arg_height);
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_FUNCTION(vue_window_show){
php::Int arg_hWnd = php::toInt(php::getCallArg(0));
php::Int arg_cmdShow = php::toInt(php::getCallArg(1));
php_vue_window_show(arg_hWnd,arg_cmdShow);
}

ZEND_FUNCTION(vue_quit_requested){
auto retval = php_vue_quit_requested();
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_FUNCTION(vue_peek_message){
auto retval = php_vue_peek_message();
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_FUNCTION(vue_begin_paint){
php::Int arg_hWnd = php::toInt(php::getCallArg(0));
auto retval = php_vue_begin_paint(arg_hWnd);
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_FUNCTION(vue_end_paint){
php::Int arg_hWnd = php::toInt(php::getCallArg(0));
php::Int arg_hdc = php::toInt(php::getCallArg(1));
php_vue_end_paint(arg_hWnd,arg_hdc);
}

ZEND_FUNCTION(vue_fill_rect){
php::Int arg_hdc = php::toInt(php::getCallArg(0));
php::Int arg_x = php::toInt(php::getCallArg(1));
php::Int arg_y = php::toInt(php::getCallArg(2));
php::Int arg_w = php::toInt(php::getCallArg(3));
php::Int arg_h = php::toInt(php::getCallArg(4));
php::Int arg_rgb = php::toInt(php::getCallArg(5));
php_vue_fill_rect(arg_hdc,arg_x,arg_y,arg_w,arg_h,arg_rgb);
}

ZEND_FUNCTION(vue_draw_text){
php::Int arg_hdc = php::toInt(php::getCallArg(0));
php::Int arg_x = php::toInt(php::getCallArg(1));
php::Int arg_y = php::toInt(php::getCallArg(2));
php::Str arg_text = php::getCallArg(3);
php::Int arg_fontSize = php::toInt(php::getCallArg(4));
php::Int arg_rgb = php::toInt(php::getCallArg(5));
php::Int arg_bold = php::toInt(php::getCallArg(6));
php_vue_draw_text(arg_hdc,arg_x,arg_y,arg_text,arg_fontSize,arg_rgb,arg_bold);
}

ZEND_FUNCTION(vue_draw_button){
php::Int arg_hdc = php::toInt(php::getCallArg(0));
php::Int arg_x = php::toInt(php::getCallArg(1));
php::Int arg_y = php::toInt(php::getCallArg(2));
php::Int arg_w = php::toInt(php::getCallArg(3));
php::Int arg_h = php::toInt(php::getCallArg(4));
php::Int arg_bgColor = php::toInt(php::getCallArg(5));
php::Int arg_borderColor = php::toInt(php::getCallArg(6));
php_vue_draw_button(arg_hdc,arg_x,arg_y,arg_w,arg_h,arg_bgColor,arg_borderColor);
}

