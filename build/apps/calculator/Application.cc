#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>


void php_application____construct(php::Object &this_, php::Object component) {

    
        // Stmt_Expression(Expr_Assign) [18:18]
    this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), true) = component;
    
        // Stmt_Expression(Expr_Assign) [19:19]
    this_.attr(php_get_prop(1, _literal_strings[19], 2, _literal_strings[14]), true) = 0LL;
}

php::Bool php_application__initwindow(php::Object &this_) {
    php::Bool tmp_var_0 = 0;
    php::Bool tmp_var_1 = 0;

    
        // Stmt_Expression(Expr_Assign) [25:29]
    this_.attr(php_get_prop(1, _literal_strings[19], 2, _literal_strings[14]), true) = php::toInt(php_vue_window_create(_literal_strings[20], php::toInt(window_width), php::toInt(window_height)));
    
        // Stmt_If [31:34]
    
if (php::equals(this_.attr(php_get_prop(1, _literal_strings[19], 2, _literal_strings[14]), false), 0LL)) {
        
                // Stmt_Echo [32:32]
        php::echo(_literal_strings[21]);
        
                // Stmt_Return [33:33]
        tmp_var_0 = php::toBool(false);

                return tmp_var_0;
    }

    
        // Stmt_Expression(Expr_FuncCall) [36:36]
    php_vue_window_show(php::toInt(this_.attr(php_get_prop(1, _literal_strings[19], 2, _literal_strings[14]), false)), php::toInt(sw_show));
    
        // Stmt_Expression(Expr_Assign) [37:37]
    this_.attr(php_get_prop(2, _literal_strings[22], 2, _literal_strings[14]), true) = php::newObject(php_get_class(3, _literal_strings[23]), php::ArgList{this_.attr(php_get_prop(1, _literal_strings[19], 2, _literal_strings[14]), false), this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), false)});
    
        // Stmt_Echo [38:38]
    php::echo(_literal_strings[24]);
    
        // Stmt_Return [39:39]
    tmp_var_1 = php::toBool(true);

        return tmp_var_1;
}

void php_application__run(php::Object &this_) {
    php::Var running;
    php::Array msg;
    php::Var msgType;
    php::Var tmp_var_0;
    php::Var tmp_var_1;
    php::Var lParam;
    php::Var tmp_var_2;
    php::Var tmp_var_3;
    php::Var mx;
    php::Var my;
    php::Object tmp_var_4;
    php::Object e;
    php::Object tmp_var_5;

    
        // Stmt_Expression(Expr_Assign) [45:45]
    running = true;
    
        // Stmt_Expression(Expr_MethodCall) [46:46]
    this_.attr(php_get_prop(2, _literal_strings[22], 2, _literal_strings[14]), false).call(_literal_strings[25]);
    
        // Stmt_Echo [47:47]
    php::echo(_literal_strings[26]);
    
        // Stmt_While [49:95]
    
while (running) {
        
                // Stmt_While [51:75]
        
while (true) {
            
                        // Stmt_Expression(Expr_Assign) [52:52]
            msg = php_vue_peek_message();
            
                        // Stmt_If [53:55]
            // Func Call: count()

if (php::equals(php::call(php_get_func(1, _literal_strings[27]), php::ArgList{msg}), 0LL)) {
                
                                // Stmt_Break [54:54]
                break;
            }

            
                        // Stmt_Expression(Expr_Assign) [57:57]
            // Expr: $msg[1] ?? 0
tmp_var_1 = php::exists(msg, {{php::ArrayDimFetch, php::Var(1LL)}}, tmp_var_0) ? tmp_var_0 : 0LL;
            msgType = tmp_var_1;
            
                        // Stmt_If [59:69]
            
if (php::equals(msgType, wm_lbuttondown)) {
                
                                // Stmt_Expression(Expr_Assign) [60:60]
                // Expr: $msg[3] ?? 0
tmp_var_3 = php::exists(msg, {{php::ArrayDimFetch, php::Var(3LL)}}, tmp_var_2) ? tmp_var_2 : 0LL;
                lParam = tmp_var_3;
                
                                // Stmt_Expression(Expr_Assign) [61:61]
                mx = ((lParam) & (65535LL));
                
                                // Stmt_Expression(Expr_Assign) [62:62]
                my = ((((lParam) >> (16LL))) & (65535LL));
                
                                // Stmt_TryCatch [63:68]
                
try {
                    
                                        // Stmt_Expression(Expr_MethodCall) [64:64]
                    // Method Call: this_->handleClick()
                    php_application__handleclick(this_, php::toInt(mx), php::toInt(my));
                }
catch(zend_object *_ex) {
                tmp_var_4 = php::catchException();
                    e = tmp_var_4;

                    if (e && php::instanceOf(e, php_get_class(4, _literal_strings[29]))) {
                        
                                                // Stmt_Echo [66:66]
                        // Method Call: e->getMessage()
                        php::echo(php::concat(php::toString(php::concat(php::toString(_literal_strings[30]), php::toString(e.call(_literal_strings[31])))), php::toString(_literal_strings[32])));
                        
                                                // Stmt_Echo [67:67]
                        // Method Call: e->getTraceAsString()
                        php::echo(php::concat(php::toString(e.call(_literal_strings[33])), php::toString(_literal_strings[32])));
                        tmp_var_4.unset();
                    }}
if (tmp_var_4) {
                php::throwException(tmp_var_4);
                }
            }

            
                        // Stmt_If [71:74]
            
if (php::equals(msgType, wm_quit)) {
                
                                // Stmt_Expression(Expr_Assign) [72:72]
                running = false;
                
                                // Stmt_Break [73:73]
                break;
            }

        }

        
                // Stmt_If [77:79]
        
if (php_vue_quit_requested()) {
            
                        // Stmt_Expression(Expr_Assign) [78:78]
            running = false;
        }

        
                // Stmt_If [80:82]
        
if (!(running)) {
            
                        // Stmt_Break [81:81]
            break;
        }

        
                // Stmt_If [85:92]
        
if (this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), false).attr(_literal_strings[34], false)) {
            
                        // Stmt_TryCatch [86:90]
            
try {
                
                                // Stmt_Expression(Expr_MethodCall) [87:87]
                this_.attr(php_get_prop(2, _literal_strings[22], 2, _literal_strings[14]), false).call(_literal_strings[25]);
            }
catch(zend_object *_ex) {
            tmp_var_5 = php::catchException();
                e = tmp_var_5;

                if (e && php::instanceOf(e, php_get_class(4, _literal_strings[29]))) {
                    
                                        // Stmt_Echo [89:89]
                    // Method Call: e->getMessage()
                    php::echo(php::concat(php::toString(php::concat(php::toString(_literal_strings[35]), php::toString(e.call(_literal_strings[31])))), php::toString(_literal_strings[32])));
                    tmp_var_5.unset();
                }}
if (tmp_var_5) {
            php::throwException(tmp_var_5);
            }
            
                        // Stmt_Expression(Expr_Assign) [91:91]
            this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), false).setProperty(_literal_strings[34], false);
        }

        
                // Stmt_Expression(Expr_FuncCall) [94:94]
        // Func Call: usleep()
        php::call(php_get_func(2, _literal_strings[36]), php::ArgList{16000LL});
        
                // Stmt_Nop [94:94]
    }

    
        // Stmt_Echo [97:97]
    php::echo(_literal_strings[37]);
}

void php_application__handleclick(php::Object &this_, php::Int x, php::Int y) {
    php::Var buttons;
    php::Var maxLayer;
    php::Var btn;
    php::Var layer;
    php::Var tmp_var_2;
    php::Var tmp_var_3;
    php::Var l;
    php::Int i = 0;
    php::Var btnLayer;
    php::Var tmp_var_4;
    php::Var tmp_var_5;

    
        // Stmt_Expression(Expr_Assign) [103:103]
    buttons = php_getlayout().item(_literal_strings[38], false);
    
        // Stmt_Expression(Expr_Assign) [106:106]
    maxLayer = 0LL;
    
        // Stmt_Foreach [107:111]
    
php::Array tmp_var_0 = buttons;
for (auto tmp_var_1 = tmp_var_0.begin(); tmp_var_1 != tmp_var_0.end(); ++tmp_var_1) {
         btn = tmp_var_1.value();

        
                // Stmt_If [108:108]
        
if (php::toBool((php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})) && (!(this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), false).call(_literal_strings[40], php::ArgList{btn.item(_literal_strings[39], false)}))))) {
            
                        // Stmt_Continue [108:108]
            continue;
        }

        
                // Stmt_Expression(Expr_Assign) [109:109]
        // Expr: $btn['layer'] ?? 0
tmp_var_3 = php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[41])}}, tmp_var_2) ? tmp_var_2 : 0LL;
        layer = tmp_var_3;
        
                // Stmt_If [110:110]
        
if (php::toBool((layer) > (maxLayer))) {
            
                        // Stmt_Expression(Expr_Assign) [110:110]
            maxLayer = layer;
        }


    }
    
        // Stmt_For [114:130]
    l = maxLayer;
    
for (;php::toBool((l) >= (0LL)); l--) {
        
                // Stmt_For [115:129]
        i = php::toInt((php::call(php_get_func(1, _literal_strings[27]), php::ArgList{buttons})) - (php::toInt(1LL)));
        // Func Call: count()

for (;php::toBool((i) >= (php::toInt(0LL))); i--) {
            
                        // Stmt_Expression(Expr_Assign) [116:116]
            btn = buttons.item(i, false);
            
                        // Stmt_Expression(Expr_Assign) [117:117]
            // Expr: $btn['layer'] ?? 0
tmp_var_5 = php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[41])}}, tmp_var_4) ? tmp_var_4 : 0LL;
            btnLayer = tmp_var_5;
            
                        // Stmt_If [118:118]
            
if (!(php::same(btnLayer, l))) {
                
                                // Stmt_Continue [118:118]
                continue;
            }

            
                        // Stmt_If [120:120]
            
if (php::toBool((php::toBool((btnLayer) < (maxLayer))) && (php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})))) {
                
                                // Stmt_Continue [120:120]
                continue;
            }

            
                        // Stmt_If [122:122]
            
if (php::toBool((php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})) && (!(this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), false).call(_literal_strings[40], php::ArgList{btn.item(_literal_strings[39], false)}))))) {
                
                                // Stmt_Continue [122:122]
                continue;
            }

            
                        // Stmt_If [124:128]
            
if (php::toBool((php::toBool((php::toBool((php::toBool((x) >= (php::toInt(btn.item(_literal_strings[42], false))))) && (php::toBool((x) < (php::toInt((btn.item(_literal_strings[42], false)) + (btn.item(_literal_strings[43], false)))))))) && (php::toBool((y) >= (php::toInt(btn.item(_literal_strings[44], false))))))) && (php::toBool((y) < (php::toInt((btn.item(_literal_strings[44], false)) + (btn.item(_literal_strings[45], false)))))))) {
                
                                // Stmt_Expression(Expr_MethodCall) [126:126]
                // Method Call: this_->dispatchClick()
                php_application__dispatchclick(this_, btn);
                
                                // Stmt_Return [127:127]
                return;
            }

        }

    }

}

void php_application__dispatchclick(php::Object &this_, php::Array btn) {

    
        // Stmt_Expression(Expr_MethodCall) [136:136]
    this_.attr(php_get_prop(0, _literal_strings[18], 2, _literal_strings[14]), false).call(_literal_strings[46], php::ArgList{btn});
}


ZEND_METHOD(Application, __construct){
php::Object this_(&execute_data->This);
php::Object arg_component = php::getCallArg(0);
php_application____construct(this_, arg_component);
}

ZEND_METHOD(Application, initWindow){
php::Object this_(&execute_data->This);
auto retval = php_application__initwindow(this_);
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_METHOD(Application, run){
php::Object this_(&execute_data->This);
php_application__run(this_);
}

ZEND_METHOD(Application, handleClick){
php::Object this_(&execute_data->This);
php::Int arg_x = php::toInt(php::getCallArg(0));
php::Int arg_y = php::toInt(php::getCallArg(1));
php_application__handleclick(this_, arg_x,arg_y);
}

ZEND_METHOD(Application, dispatchClick){
php::Object this_(&execute_data->This);
php::Array arg_btn = php::getCallArg(0);
php_application__dispatchclick(this_, arg_btn);
}

