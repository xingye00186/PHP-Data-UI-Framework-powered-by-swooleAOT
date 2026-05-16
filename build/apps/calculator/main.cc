#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>


php::Int php_main() {
    php::Object tmp_var_0;
    php::Object component;
    php::Object app;

    
        // Stmt_Expression(Expr_FuncCall) [23:23]
    // Func Call: date_default_timezone_set()
    php::call(php_get_func(0, _literal_strings[6]), php::ArgList{_literal_strings[7]});
    
        // Stmt_Echo [25:25]
    php::echo(_literal_strings[8]);
    
        // Stmt_Echo [26:26]
    php::echo(_literal_strings[9]);
    
        // Stmt_Echo [27:27]
    php::echo(_literal_strings[10]);
    
        // Stmt_Echo [28:28]
    php::echo(_literal_strings[11]);
    
        // Stmt_Expression(Expr_StaticCall) [31:31]
    // Static Method Call: ReactiveComponent::initShared()
    Z_PTR_P(tmp_var_0.ptr()) = php_get_class(0, _literal_strings[12]);
    php_reactivecomponent__initshared(tmp_var_0, php::toInt(10240LL));
    
        // Stmt_Expression(Expr_Assign) [34:34]
    component = php::newObject(php_get_class(1, _literal_strings[5]), php::ArgList{_literal_strings[13]});
    
        // Stmt_Expression(Expr_Assign) [37:37]
    app = php::newObject(php_get_class(2, _literal_strings[14]), php::ArgList{component});
    
        // Stmt_If [38:40]
    // Method Call: app->initWindow()

if (!(php_application__initwindow(app))) {
        
                // Stmt_Return [39:39]
        return php::toInt(1LL);
    }

    
        // Stmt_Expression(Expr_MethodCall) [41:41]
    // Method Call: app->run()
    php_application__run(app);
    
        // Stmt_Echo [43:43]
    php::echo(_literal_strings[17]);
    
        // Stmt_Return [44:44]
    return php::toInt(0LL);
}

ZEND_FUNCTION(main){
auto retval = php_main();
php::move(retval, return_value);
php::deref(return_value);
}

