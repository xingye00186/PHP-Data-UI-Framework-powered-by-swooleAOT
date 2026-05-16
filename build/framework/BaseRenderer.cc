#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>


void php_baserenderer____construct(php::Object &this_, php::Int hWnd, php::Object component) {

    
        // Stmt_Expression(Expr_Assign) [16:16]
    this_.attr(php_get_prop(20, _literal_strings[19], 3, _literal_strings[23]), true) = php::toInt(hWnd);
    
        // Stmt_Expression(Expr_Assign) [17:17]
    this_.attr(php_get_prop(21, _literal_strings[18], 3, _literal_strings[23]), true) = component;
}

php::Str php_baserenderer__getbindvalue(php::Object &this_, php::Str bindKey) {
    php::Str tmp_var_0;

    
        // Stmt_Return [23:23]
    tmp_var_0 = this_.attr(php_get_prop(21, _literal_strings[18], 3, _literal_strings[23]), false).call(_literal_strings[132], php::ArgList{bindKey});

        return tmp_var_0;
}

void php_baserenderer__rendertextelement(php::Object &this_, php::Int hdc, php::Array el) {
    php::Var bindKey;
    php::Var tmp_var_0;
    php::Var tmp_var_1;
    php::Str text;
    php::Var fontSize;
    php::Var tmp_var_2;
    php::Var tmp_var_3;
    php::Var color;
    php::Var tmp_var_4;
    php::Var tmp_var_5;
    php::Var bold;
    php::Var tmp_var_6;
    php::Var tmp_var_7;
    php::Var align;
    php::Var tmp_var_8;
    php::Var tmp_var_9;
    php::Var x;
    php::Var tmp_var_10;
    php::Var tmp_var_11;
    php::Var y;
    php::Var tmp_var_12;
    php::Var tmp_var_13;
    php::Int textLen = 0;
    php::Var containerW;
    php::Var containerX;
    php::Var tmp_var_14;
    php::Var tmp_var_15;
    php::Var charWidth;
    php::Int textWidth = 0;
    php::Var rightEdge;

    
        // Stmt_Expression(Expr_Assign) [29:29]
    // Expr: $el['bind'] ?? ''
tmp_var_1 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[99])}}, tmp_var_0) ? tmp_var_0 : _literal_strings[1];
    bindKey = tmp_var_1;
    
        // Stmt_If [32:39]
    
if (!(php::same(bindKey, _literal_strings[1]))) {
        
                // Stmt_Expression(Expr_Assign) [33:33]
        // Method Call: this_->getBindValue()
        text = php_baserenderer__getbindvalue(this_, bindKey);
        
                // Stmt_If [34:36]
        
if (php::same(text, _literal_strings[1])) {
            
                        // Stmt_Return [35:35]
            return;
        }

    } else {
        
                // Stmt_Return [38:38]
        return;
    }

    
        // Stmt_Expression(Expr_Assign) [41:41]
    // Expr: $el['fontSize'] ?? 16
tmp_var_3 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[102])}}, tmp_var_2) ? tmp_var_2 : 16LL;
    fontSize = tmp_var_3;
    
        // Stmt_Expression(Expr_Assign) [42:42]
    // Expr: $el['color'] ?? 0xffffff
tmp_var_5 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[97])}}, tmp_var_4) ? tmp_var_4 : 16777215LL;
    color = tmp_var_5;
    
        // Stmt_Expression(Expr_Assign) [43:43]
    // Expr: $el['bold'] ?? 0
tmp_var_7 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[103])}}, tmp_var_6) ? tmp_var_6 : 0LL;
    bold = tmp_var_7;
    
        // Stmt_Expression(Expr_Assign) [44:44]
    // Expr: $el['align'] ?? 'left'
tmp_var_9 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[101])}}, tmp_var_8) ? tmp_var_8 : _literal_strings[106];
    align = tmp_var_9;
    
        // Stmt_Expression(Expr_Assign) [45:45]
    // Expr: $el['x'] ?? 0
tmp_var_11 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[42])}}, tmp_var_10) ? tmp_var_10 : 0LL;
    x = tmp_var_11;
    
        // Stmt_Expression(Expr_Assign) [46:46]
    // Expr: $el['y'] ?? 0
tmp_var_13 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[44])}}, tmp_var_12) ? tmp_var_12 : 0LL;
    y = tmp_var_13;
    
        // Stmt_Expression(Expr_Assign) [49:49]
    textLen = php::toInt(php::fn::strlen(text));
    
        // Stmt_If [50:52]
    
if (php::toBool((php::toBool((textLen) > (php::toInt(12LL)))) && (php::toBool((fontSize) > (24LL))))) {
        
                // Stmt_Expression(Expr_Assign) [51:51]
        fontSize = 24LL;
    }

    
        // Stmt_If [53:55]
    
if (php::toBool((php::toBool((textLen) > (php::toInt(16LL)))) && (php::toBool((fontSize) > (18LL))))) {
        
                // Stmt_Expression(Expr_Assign) [54:54]
        fontSize = 18LL;
    }

    
        // Stmt_If [58:68]
    
if (php::toBool((php::same(align, _literal_strings[100])) && (php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[104])}})))) {
        
                // Stmt_Expression(Expr_Assign) [59:59]
        containerW = el.item(_literal_strings[104], false);
        
                // Stmt_Expression(Expr_Assign) [60:60]
        // Expr: $el['containerX'] ?? 0
tmp_var_15 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[105])}}, tmp_var_14) ? tmp_var_14 : 0LL;
        containerX = tmp_var_15;
        
                // Stmt_Expression(Expr_Assign) [61:61]
        charWidth = php::toInt((fontSize) * (0.59999999999999998));
        
                // Stmt_Expression(Expr_Assign) [62:62]
        textWidth = php::toInt((textLen) * (php::toInt(charWidth)));
        
                // Stmt_Expression(Expr_Assign) [63:63]
        rightEdge = ((containerX) + (containerW));
        
                // Stmt_Expression(Expr_Assign) [64:64]
        x = php::toInt((php::toInt((rightEdge) - (12LL))) - (textWidth));
        
                // Stmt_If [65:67]
        
if (php::toBool((x) < (((containerX) + (4LL))))) {
            
                        // Stmt_Expression(Expr_Assign) [66:66]
            x = ((containerX) + (4LL));
        }

    }

    
        // Stmt_Expression(Expr_FuncCall) [70:70]
    php_vue_draw_text(php::toInt(hdc), php::toInt(x), php::toInt(y), text, php::toInt(fontSize), php::toInt(color), php::toInt(bold));
}

void php_baserenderer__render(php::Object &this_) {
    php::Int hdc = 0;
    php::Array layout;
    php::Var elements;
    php::Var buttons;
    php::Var maxLayer;
    php::Var el;
    php::Var layer;
    php::Var tmp_var_2;
    php::Var tmp_var_3;
    php::Var btn;
    php::Var tmp_var_6;
    php::Var tmp_var_7;
    php::Var l;
    php::Var tmp_var_10;
    php::Var tmp_var_11;
    php::Var type;
    php::Var btnLayer;
    php::Var tmp_var_14;
    php::Var tmp_var_15;
    php::Var label;
    php::Int labelLen = 0;
    php::Var labelFontSize;
    php::Var labelCharW;
    php::Var labelX;
    php::Var labelY;

    
        // Stmt_Expression(Expr_Assign) [78:78]
    hdc = php::toInt(php_vue_begin_paint(php::toInt(this_.attr(php_get_prop(20, _literal_strings[19], 3, _literal_strings[23]), false))));
    
        // Stmt_Expression(Expr_Assign) [79:79]
    layout = php_getlayout();
    
        // Stmt_Expression(Expr_Assign) [80:80]
    elements = layout.item(_literal_strings[108], false);
    
        // Stmt_Expression(Expr_Assign) [81:81]
    buttons = layout.item(_literal_strings[38], false);
    
        // Stmt_Expression(Expr_Assign) [84:84]
    maxLayer = 0LL;
    
        // Stmt_Foreach [85:89]
    
php::Array tmp_var_0 = elements;
for (auto tmp_var_1 = tmp_var_0.begin(); tmp_var_1 != tmp_var_0.end(); ++tmp_var_1) {
         el = tmp_var_1.value();

        
                // Stmt_If [86:86]
        
if (php::toBool((php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})) && (!(this_.attr(php_get_prop(21, _literal_strings[18], 3, _literal_strings[23]), false).call(_literal_strings[40], php::ArgList{el.item(_literal_strings[39], false)}))))) {
            
                        // Stmt_Continue [86:86]
            continue;
        }

        
                // Stmt_Expression(Expr_Assign) [87:87]
        // Expr: $el['layer'] ?? 0
tmp_var_3 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[41])}}, tmp_var_2) ? tmp_var_2 : 0LL;
        layer = tmp_var_3;
        
                // Stmt_If [88:88]
        
if (php::toBool((layer) > (maxLayer))) {
            
                        // Stmt_Expression(Expr_Assign) [88:88]
            maxLayer = layer;
        }


    }
    
        // Stmt_Foreach [90:94]
    
php::Array tmp_var_4 = buttons;
for (auto tmp_var_5 = tmp_var_4.begin(); tmp_var_5 != tmp_var_4.end(); ++tmp_var_5) {
         btn = tmp_var_5.value();

        
                // Stmt_If [91:91]
        
if (php::toBool((php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})) && (!(this_.attr(php_get_prop(21, _literal_strings[18], 3, _literal_strings[23]), false).call(_literal_strings[40], php::ArgList{btn.item(_literal_strings[39], false)}))))) {
            
                        // Stmt_Continue [91:91]
            continue;
        }

        
                // Stmt_Expression(Expr_Assign) [92:92]
        // Expr: $btn['layer'] ?? 0
tmp_var_7 = php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[41])}}, tmp_var_6) ? tmp_var_6 : 0LL;
        layer = tmp_var_7;
        
                // Stmt_If [93:93]
        
if (php::toBool((layer) > (maxLayer))) {
            
                        // Stmt_Expression(Expr_Assign) [93:93]
            maxLayer = layer;
        }


    }
    
        // Stmt_For [98:109]
    l = 0LL;
    
for (;php::toBool((l) <= (maxLayer)); l++) {
        
                // Stmt_Foreach [99:108]
        
php::Array tmp_var_8 = elements;
for (auto tmp_var_9 = tmp_var_8.begin(); tmp_var_9 != tmp_var_8.end(); ++tmp_var_9) {
             el = tmp_var_9.value();

            
                        // Stmt_If [100:100]
            // Expr: $el['layer'] ?? 0
tmp_var_11 = php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[41])}}, tmp_var_10) ? tmp_var_10 : 0LL;

if (!(php::same(tmp_var_11, l))) {
                
                                // Stmt_Continue [100:100]
                continue;
            }

            
                        // Stmt_If [101:101]
            
if (php::toBool((php::exists(el, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})) && (!(this_.attr(php_get_prop(21, _literal_strings[18], 3, _literal_strings[23]), false).call(_literal_strings[40], php::ArgList{el.item(_literal_strings[39], false)}))))) {
                
                                // Stmt_Continue [101:101]
                continue;
            }

            
                        // Stmt_Expression(Expr_Assign) [102:102]
            type = el.item(_literal_strings[96], false);
            
                        // Stmt_If [103:107]
            
if (php::same(type, _literal_strings[95])) {
                
                                // Stmt_Expression(Expr_FuncCall) [104:104]
                php_vue_fill_rect(php::toInt(hdc), php::toInt(el.item(_literal_strings[42], false)), php::toInt(el.item(_literal_strings[44], false)), php::toInt(el.item(_literal_strings[43], false)), php::toInt(el.item(_literal_strings[45], false)), php::toInt(el.item(_literal_strings[97], false)));
            } else if (php::same(type, _literal_strings[98])) {
                
                                // Stmt_Expression(Expr_MethodCall) [106:106]
                // Method Call: this_->renderTextElement()
                php_baserenderer__rendertextelement(this_, php::toInt(hdc), el);
            }


        }
    }

    
        // Stmt_For [112:131]
    l = 0LL;
    
for (;php::toBool((l) <= (maxLayer)); l++) {
        
                // Stmt_Foreach [113:130]
        
php::Array tmp_var_12 = buttons;
for (auto tmp_var_13 = tmp_var_12.begin(); tmp_var_13 != tmp_var_12.end(); ++tmp_var_13) {
             btn = tmp_var_13.value();

            
                        // Stmt_Expression(Expr_Assign) [114:114]
            // Expr: $btn['layer'] ?? 0
tmp_var_15 = php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[41])}}, tmp_var_14) ? tmp_var_14 : 0LL;
            btnLayer = tmp_var_15;
            
                        // Stmt_If [115:115]
            
if (!(php::same(btnLayer, l))) {
                
                                // Stmt_Continue [115:115]
                continue;
            }

            
                        // Stmt_If [117:117]
            
if (php::toBool((php::toBool((btnLayer) < (maxLayer))) && (php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})))) {
                
                                // Stmt_Continue [117:117]
                continue;
            }

            
                        // Stmt_If [119:119]
            
if (php::toBool((php::exists(btn, {{php::ArrayDimFetch, php::Var(_literal_strings[39])}})) && (!(this_.attr(php_get_prop(21, _literal_strings[18], 3, _literal_strings[23]), false).call(_literal_strings[40], php::ArgList{btn.item(_literal_strings[39], false)}))))) {
                
                                // Stmt_Continue [119:119]
                continue;
            }

            
                        // Stmt_Expression(Expr_FuncCall) [121:121]
            php_vue_draw_button(php::toInt(hdc), php::toInt(btn.item(_literal_strings[42], false)), php::toInt(btn.item(_literal_strings[44], false)), php::toInt(btn.item(_literal_strings[43], false)), php::toInt(btn.item(_literal_strings[45], false)), php::toInt(btn.item(_literal_strings[110], false)), php::toInt(btn.item(_literal_strings[112], false)));
            
                        // Stmt_Expression(Expr_Assign) [123:123]
            label = btn.item(_literal_strings[109], false);
            
                        // Stmt_Expression(Expr_Assign) [124:124]
            labelLen = php::toInt(php::fn::strlen(label));
            
                        // Stmt_Expression(Expr_Assign) [125:125]
            labelFontSize = 22LL;
            
                        // Stmt_Expression(Expr_Assign) [126:126]
            labelCharW = php::toInt((labelFontSize) * (0.59999999999999998));
            
                        // Stmt_Expression(Expr_Assign) [127:127]
            labelX = ((btn.item(_literal_strings[42], false)) + (php::toInt(((php::toInt(btn.item(_literal_strings[43], false))) - (((labelLen) * (php::toInt(labelCharW))))) / (2LL))));
            
                        // Stmt_Expression(Expr_Assign) [128:128]
            labelY = ((btn.item(_literal_strings[44], false)) + (php::toInt(((btn.item(_literal_strings[45], false)) - (labelFontSize)) / (2LL))));
            
                        // Stmt_Expression(Expr_FuncCall) [129:129]
            php_vue_draw_text(php::toInt(hdc), php::toInt(labelX), php::toInt(labelY), label, php::toInt(labelFontSize), php::toInt(btn.item(_literal_strings[111], false)), php::toInt(1LL));

        }
    }

    
        // Stmt_Expression(Expr_FuncCall) [133:133]
    php_vue_end_paint(php::toInt(this_.attr(php_get_prop(20, _literal_strings[19], 3, _literal_strings[23]), false)), php::toInt(hdc));
}


ZEND_METHOD(BaseRenderer, __construct){
php::Object this_(&execute_data->This);
php::Int arg_hWnd = php::toInt(php::getCallArg(0));
php::Object arg_component = php::getCallArg(1);
php_baserenderer____construct(this_, arg_hWnd,arg_component);
}

ZEND_METHOD(BaseRenderer, getBindValue){
php::Object this_(&execute_data->This);
php::Str arg_bindKey = php::getCallArg(0);
auto retval = php_baserenderer__getbindvalue(this_, arg_bindKey);
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_METHOD(BaseRenderer, renderTextElement){
php::Object this_(&execute_data->This);
php::Int arg_hdc = php::toInt(php::getCallArg(0));
php::Array arg_el = php::getCallArg(1);
php_baserenderer__rendertextelement(this_, arg_hdc,arg_el);
}

ZEND_METHOD(BaseRenderer, render){
php::Object this_(&execute_data->This);
php_baserenderer__render(this_);
}

