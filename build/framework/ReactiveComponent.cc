#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>



void php_reactivecomponent____construct(php::Object &this_, php::Var componentId) {
    php::Var tmp_var_0;

    
        // Stmt_Expression(Expr_Assign) [27:27]
    // Expr: $componentId ?? get_class($this)
tmp_var_0 = php::exists(componentId) ? componentId : php::fn::get_class(this_);
    php::getStaticProperty(php_get_class(0, _literal_strings[12]), php_get_prop(3, _literal_strings[47], 0, _literal_strings[12])) = tmp_var_0;
}

void php_reactivecomponent__initshared(php::Object &this_, php::Int tableSize) {

    
        // Stmt_Expression(Expr_Assign) [32:32]
    php::getStaticProperty(php_get_class(0, _literal_strings[12]), php_get_prop(4, _literal_strings[48], 0, _literal_strings[12])) = php::newObject(php_get_class(5, _literal_strings[49]));
}


ZEND_METHOD(ReactiveComponent, __construct){
php::Object this_(&execute_data->This);
php::Var arg_componentId = php::getCallArg(0, php::null);
php_reactivecomponent____construct(this_, arg_componentId);
}

ZEND_METHOD(ReactiveComponent, initShared){
php::Object this_(&execute_data->This);
php::Int arg_tableSize = php::toInt(php::getCallArg(0, 10240LL));
php_reactivecomponent__initshared(this_, arg_tableSize);
}

