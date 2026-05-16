#include <phpx.h>
#include <phpx_helper.h>
#include <phpx_func.h>
#include <php_func_decl.h>
#include <php_global_var_decl.h>
#include <php_aot_helper.h>



void php_changequeue____construct(php::Object &this_) {

    
        // Stmt_Expression(Expr_Assign) [20:20]
    this_.attr(php_get_prop(16, _literal_strings[125], 5, _literal_strings[49]), true) = php::Array{};
}

void php_changequeue__push(php::Object &this_, php::Str key, php::Int version, php::Var value) {
    php::Int idx = 0;
    php::Int &_object_prop_this___head = Z_LVAL_P(this_.attr(php_get_prop(17, _literal_strings[126], 5, _literal_strings[49]), false).unwrap_ptr());
    php::Int &_object_prop_this___maxSize = Z_LVAL_P(this_.attr(php_get_prop(18, _literal_strings[127], 5, _literal_strings[49]), false).unwrap_ptr());

    
        // Stmt_Expression(Expr_Assign) [26:26]
    idx = php::toInt((_object_prop_this___head) % (php::toInt(_object_prop_this___maxSize)));
    
        // Stmt_Expression(Expr_Assign) [27:31]
    // Func Call: is_string()
    this_.attr(php_get_prop(16, _literal_strings[125], 5, _literal_strings[49]), true).item(idx, true) = php::Array{
        { _literal_strings[128].str(), php::Var(key) }, 
        { _literal_strings[129].str(), php::Var(version) }, 
        { _literal_strings[90].str(), php::Var((php::call(php_get_func(8, _literal_strings[130]), php::ArgList{value})) ? (value) : (php::toString(value))) }
    };
    
        // Stmt_Expression(Expr_PostInc) [32:32]
    _object_prop_this___head++;
}

php::Var php_changequeue__pop(php::Object &this_) {
    php::Var tmp_var_0;
    php::Int idx = 0;
    php::Var row;
    php::Var tmp_var_1;
    php::Int &_object_prop_this___tail = Z_LVAL_P(this_.attr(php_get_prop(19, _literal_strings[131], 5, _literal_strings[49]), false).unwrap_ptr());
    php::Int &_object_prop_this___head = Z_LVAL_P(this_.attr(php_get_prop(17, _literal_strings[126], 5, _literal_strings[49]), false).unwrap_ptr());
    php::Int &_object_prop_this___maxSize = Z_LVAL_P(this_.attr(php_get_prop(18, _literal_strings[127], 5, _literal_strings[49]), false).unwrap_ptr());

    
        // Stmt_If [38:40]
    
if (php::toBool((_object_prop_this___tail) >= (php::toInt(_object_prop_this___head)))) {
        
                // Stmt_Return [39:39]
        tmp_var_0 = php::null;

                return tmp_var_0;
    }

    
        // Stmt_Expression(Expr_Assign) [41:41]
    idx = php::toInt((_object_prop_this___tail) % (php::toInt(_object_prop_this___maxSize)));
    
        // Stmt_Expression(Expr_Assign) [42:42]
    row = this_.attr(php_get_prop(16, _literal_strings[125], 5, _literal_strings[49]), false).item(idx, false);
    
        // Stmt_Expression(Expr_PostInc) [43:43]
    _object_prop_this___tail++;
    
        // Stmt_Return [44:48]
    tmp_var_1 = php::Array{
        { _literal_strings[128].str(), php::Var(row.item(_literal_strings[128], false)) }, 
        { _literal_strings[129].str(), php::Var(row.item(_literal_strings[129], false)) }, 
        { _literal_strings[90].str(), php::Var(row.item(_literal_strings[90], false)) }
    };

        return tmp_var_1;
}

php::Bool php_changequeue__isempty(php::Object &this_) {
    php::Bool tmp_var_0 = 0;
    php::Int &_object_prop_this___tail = Z_LVAL_P(this_.attr(php_get_prop(19, _literal_strings[131], 5, _literal_strings[49]), false).unwrap_ptr());
    php::Int &_object_prop_this___head = Z_LVAL_P(this_.attr(php_get_prop(17, _literal_strings[126], 5, _literal_strings[49]), false).unwrap_ptr());

    
        // Stmt_Return [54:54]
    tmp_var_0 = php::toBool((_object_prop_this___tail) >= (php::toInt(_object_prop_this___head)));

        return tmp_var_0;
}


ZEND_METHOD(ChangeQueue, __construct){
php::Object this_(&execute_data->This);
php_changequeue____construct(this_);
}

ZEND_METHOD(ChangeQueue, push){
php::Object this_(&execute_data->This);
php::Str arg_key = php::getCallArg(0);
php::Int arg_version = php::toInt(php::getCallArg(1));
php::Var arg_value = php::getCallArg(2);
php_changequeue__push(this_, arg_key,arg_version,arg_value);
}

ZEND_METHOD(ChangeQueue, pop){
php::Object this_(&execute_data->This);
auto retval = php_changequeue__pop(this_);
php::move(retval, return_value);
php::deref(return_value);
}

ZEND_METHOD(ChangeQueue, isEmpty){
php::Object this_(&execute_data->This);
auto retval = php_changequeue__isempty(this_);
php::move(retval, return_value);
php::deref(return_value);
}

