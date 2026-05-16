/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 5a0113747f131602520599ee8ed4f3f53b166385 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_BaseRenderer___construct, 0, 0, 2)
	ZEND_ARG_TYPE_INFO(0, hWnd, IS_LONG, 0)
	ZEND_ARG_OBJ_INFO(0, component, ReactiveComponent, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_BaseRenderer_getBindValue, 0, 1, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, bindKey, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_BaseRenderer_renderTextElement, 0, 2, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, hdc, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, el, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_BaseRenderer_render, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(BaseRenderer, __construct);
ZEND_METHOD(BaseRenderer, getBindValue);
ZEND_METHOD(BaseRenderer, renderTextElement);
ZEND_METHOD(BaseRenderer, render);

static const zend_function_entry class_BaseRenderer_methods[] = {
	ZEND_ME(BaseRenderer, __construct, arginfo_class_BaseRenderer___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(BaseRenderer, getBindValue, arginfo_class_BaseRenderer_getBindValue, ZEND_ACC_PROTECTED)
	ZEND_ME(BaseRenderer, renderTextElement, arginfo_class_BaseRenderer_renderTextElement, ZEND_ACC_PROTECTED)
	ZEND_ME(BaseRenderer, render, arginfo_class_BaseRenderer_render, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static zend_class_entry *php_register_class_BaseRenderer(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "BaseRenderer", class_BaseRenderer_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	zval property_hWnd_default_value;
	ZVAL_UNDEF(&property_hWnd_default_value);
	zend_string *property_hWnd_name = zend_string_init("hWnd", sizeof("hWnd") - 1, true);
	zend_declare_typed_property(class_entry, property_hWnd_name, &property_hWnd_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release_ex(property_hWnd_name, true);

	zval property_component_default_value;
	ZVAL_UNDEF(&property_component_default_value);
	zend_string *property_component_name = zend_string_init("component", sizeof("component") - 1, true);
	zend_string *property_component_class_ReactiveComponent = zend_string_init("ReactiveComponent", sizeof("ReactiveComponent")-1, 1);
	zend_declare_typed_property(class_entry, property_component_name, &property_component_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_component_class_ReactiveComponent, 0, 0));
	zend_string_release_ex(property_component_name, true);

	return class_entry;
}
