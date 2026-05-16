/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 426f55acb0029fbed3e27bfa3098a2bbe29182ff */

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_reset, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_inputDigit, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, digit, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_App_inputDecimal arginfo_class_App_reset

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_inputOperator, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, op, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_App_calculate arginfo_class_App_reset

#define arginfo_class_App_backspace arginfo_class_App_reset

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_handleButton, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, label, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_App_toggleAboutDialog arginfo_class_App_reset

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_getBindValue, 0, 1, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, bindKey, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_dispatchClick, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, btn, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_App_evalCondition, 0, 1, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, cond, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_App___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, componentId, IS_STRING, 0, "\'App\'")
ZEND_END_ARG_INFO()

ZEND_METHOD(App, reset);
ZEND_METHOD(App, inputDigit);
ZEND_METHOD(App, inputDecimal);
ZEND_METHOD(App, inputOperator);
ZEND_METHOD(App, calculate);
ZEND_METHOD(App, backspace);
ZEND_METHOD(App, handleButton);
ZEND_METHOD(App, toggleAboutDialog);
ZEND_METHOD(App, getBindValue);
ZEND_METHOD(App, dispatchClick);
ZEND_METHOD(App, evalCondition);
ZEND_METHOD(App, __construct);

static const zend_function_entry class_App_methods[] = {
	ZEND_ME(App, reset, arginfo_class_App_reset, ZEND_ACC_PUBLIC)
	ZEND_ME(App, inputDigit, arginfo_class_App_inputDigit, ZEND_ACC_PUBLIC)
	ZEND_ME(App, inputDecimal, arginfo_class_App_inputDecimal, ZEND_ACC_PUBLIC)
	ZEND_ME(App, inputOperator, arginfo_class_App_inputOperator, ZEND_ACC_PUBLIC)
	ZEND_ME(App, calculate, arginfo_class_App_calculate, ZEND_ACC_PUBLIC)
	ZEND_ME(App, backspace, arginfo_class_App_backspace, ZEND_ACC_PUBLIC)
	ZEND_ME(App, handleButton, arginfo_class_App_handleButton, ZEND_ACC_PUBLIC)
	ZEND_ME(App, toggleAboutDialog, arginfo_class_App_toggleAboutDialog, ZEND_ACC_PUBLIC)
	ZEND_ME(App, getBindValue, arginfo_class_App_getBindValue, ZEND_ACC_PUBLIC)
	ZEND_ME(App, dispatchClick, arginfo_class_App_dispatchClick, ZEND_ACC_PUBLIC)
	ZEND_ME(App, evalCondition, arginfo_class_App_evalCondition, ZEND_ACC_PUBLIC)
	ZEND_ME(App, __construct, arginfo_class_App___construct, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static zend_class_entry *php_register_class_App(zend_class_entry *class_entry_ReactiveComponent)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "App", class_App_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, class_entry_ReactiveComponent, 0);

	zval property_display_default_value;
	zend_string *property_display_default_value_str = zend_string_init("0", strlen("0"), 1);
	ZVAL_STR(&property_display_default_value, property_display_default_value_str);
	zend_string *property_display_name = zend_string_init("display", sizeof("display") - 1, true);
	zend_declare_typed_property(class_entry, property_display_name, &property_display_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_display_name, true);

	zval property_expression_default_value;
	ZVAL_EMPTY_STRING(&property_expression_default_value);
	zend_string *property_expression_name = zend_string_init("expression", sizeof("expression") - 1, true);
	zend_declare_typed_property(class_entry, property_expression_name, &property_expression_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_expression_name, true);

	zval property_operand1_default_value;
	ZVAL_EMPTY_STRING(&property_operand1_default_value);
	zend_string *property_operand1_name = zend_string_init("operand1", sizeof("operand1") - 1, true);
	zend_declare_typed_property(class_entry, property_operand1_name, &property_operand1_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_operand1_name, true);

	zval property_operator_default_value;
	ZVAL_EMPTY_STRING(&property_operator_default_value);
	zend_string *property_operator_name = zend_string_init("operator", sizeof("operator") - 1, true);
	zend_declare_typed_property(class_entry, property_operator_name, &property_operator_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_operator_name, true);

	zval property_newInput_default_value;
	ZVAL_TRUE(&property_newInput_default_value);
	zend_string *property_newInput_name = zend_string_init("newInput", sizeof("newInput") - 1, true);
	zend_declare_typed_property(class_entry, property_newInput_name, &property_newInput_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release_ex(property_newInput_name, true);

	zval property_hasDecimal_default_value;
	ZVAL_FALSE(&property_hasDecimal_default_value);
	zend_string *property_hasDecimal_name = zend_string_init("hasDecimal", sizeof("hasDecimal") - 1, true);
	zend_declare_typed_property(class_entry, property_hasDecimal_name, &property_hasDecimal_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release_ex(property_hasDecimal_name, true);

	zval property_showDialog_default_value;
	ZVAL_FALSE(&property_showDialog_default_value);
	zend_string *property_showDialog_name = zend_string_init("showDialog", sizeof("showDialog") - 1, true);
	zend_declare_typed_property(class_entry, property_showDialog_name, &property_showDialog_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release_ex(property_showDialog_name, true);

	zval property_dialogTitle_default_value;
	zend_string *property_dialogTitle_default_value_str = zend_string_init("About VueCalc", strlen("About VueCalc"), 1);
	ZVAL_STR(&property_dialogTitle_default_value, property_dialogTitle_default_value_str);
	zend_string *property_dialogTitle_name = zend_string_init("dialogTitle", sizeof("dialogTitle") - 1, true);
	zend_declare_typed_property(class_entry, property_dialogTitle_name, &property_dialogTitle_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_dialogTitle_name, true);

	zval property_dialogContent_default_value;
	zend_string *property_dialogContent_default_value_str = zend_string_init("SFC Data-Driven Calculator", strlen("SFC Data-Driven Calculator"), 1);
	ZVAL_STR(&property_dialogContent_default_value, property_dialogContent_default_value_str);
	zend_string *property_dialogContent_name = zend_string_init("dialogContent", sizeof("dialogContent") - 1, true);
	zend_declare_typed_property(class_entry, property_dialogContent_name, &property_dialogContent_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_dialogContent_name, true);

	zval property_dialogVersion_default_value;
	zend_string *property_dialogVersion_default_value_str = zend_string_init("Version 5.0 (M2)", strlen("Version 5.0 (M2)"), 1);
	ZVAL_STR(&property_dialogVersion_default_value, property_dialogVersion_default_value_str);
	zend_string *property_dialogVersion_name = zend_string_init("dialogVersion", sizeof("dialogVersion") - 1, true);
	zend_declare_typed_property(class_entry, property_dialogVersion_name, &property_dialogVersion_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_dialogVersion_name, true);

	zval property_closeHint_default_value;
	ZVAL_EMPTY_STRING(&property_closeHint_default_value);
	zend_string *property_closeHint_name = zend_string_init("closeHint", sizeof("closeHint") - 1, true);
	zend_declare_typed_property(class_entry, property_closeHint_name, &property_closeHint_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_closeHint_name, true);

	return class_entry;
}
