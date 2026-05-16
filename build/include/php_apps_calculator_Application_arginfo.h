/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 2e4435d24e8945fa9aa4ad796b33d3c9dd319303 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_Application___construct, 0, 0, 1)
	ZEND_ARG_OBJ_INFO(0, component, ReactiveComponent, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Application_initWindow, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Application_run, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Application_handleClick, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, x, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, y, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Application_dispatchClick, 0, 1, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, btn, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(Application, __construct);
ZEND_METHOD(Application, initWindow);
ZEND_METHOD(Application, run);
ZEND_METHOD(Application, handleClick);
ZEND_METHOD(Application, dispatchClick);

static const zend_function_entry class_Application_methods[] = {
	ZEND_ME(Application, __construct, arginfo_class_Application___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(Application, initWindow, arginfo_class_Application_initWindow, ZEND_ACC_PUBLIC)
	ZEND_ME(Application, run, arginfo_class_Application_run, ZEND_ACC_PUBLIC)
	ZEND_ME(Application, handleClick, arginfo_class_Application_handleClick, ZEND_ACC_PRIVATE)
	ZEND_ME(Application, dispatchClick, arginfo_class_Application_dispatchClick, ZEND_ACC_PRIVATE)
	ZEND_FE_END
};

static zend_class_entry *php_register_class_Application(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "Application", class_Application_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	zval property_component_default_value;
	ZVAL_UNDEF(&property_component_default_value);
	zend_string *property_component_name = zend_string_init("component", sizeof("component") - 1, true);
	zend_string *property_component_class_ReactiveComponent = zend_string_init("ReactiveComponent", sizeof("ReactiveComponent")-1, 1);
	zend_declare_typed_property(class_entry, property_component_name, &property_component_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_component_class_ReactiveComponent, 0, 0));
	zend_string_release_ex(property_component_name, true);

	zval property_hWnd_default_value;
	ZVAL_UNDEF(&property_hWnd_default_value);
	zend_string *property_hWnd_name = zend_string_init("hWnd", sizeof("hWnd") - 1, true);
	zend_declare_typed_property(class_entry, property_hWnd_name, &property_hWnd_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release_ex(property_hWnd_name, true);

	zval property_renderer_default_value;
	ZVAL_UNDEF(&property_renderer_default_value);
	zend_string *property_renderer_name = zend_string_init("renderer", sizeof("renderer") - 1, true);
	zend_string *property_renderer_class_BaseRenderer = zend_string_init("BaseRenderer", sizeof("BaseRenderer")-1, 1);
	zend_declare_typed_property(class_entry, property_renderer_name, &property_renderer_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_renderer_class_BaseRenderer, 0, 0));
	zend_string_release_ex(property_renderer_name, true);

	return class_entry;
}
