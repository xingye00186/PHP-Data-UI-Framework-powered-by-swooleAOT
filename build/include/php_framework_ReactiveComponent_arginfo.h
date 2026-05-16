/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 7c8add61578a2dd5f0eb4fea555b119a25ed6444 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_ReactiveComponent___construct, 0, 0, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, componentId, IS_STRING, 1, "null")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ReactiveComponent_initShared, 0, 0, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, tableSize, IS_LONG, 0, "10240")
ZEND_END_ARG_INFO()

ZEND_METHOD(ReactiveComponent, __construct);
ZEND_METHOD(ReactiveComponent, initShared);

static const zend_function_entry class_ReactiveComponent_methods[] = {
	ZEND_ME(ReactiveComponent, __construct, arginfo_class_ReactiveComponent___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(ReactiveComponent, initShared, arginfo_class_ReactiveComponent_initShared, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *php_register_class_ReactiveComponent(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "ReactiveComponent", class_ReactiveComponent_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_ABSTRACT);

	zval property_queue_default_value;
	ZVAL_NULL(&property_queue_default_value);
	zend_string *property_queue_name = zend_string_init("queue", sizeof("queue") - 1, true);
	zend_string *property_queue_class_ChangeQueue = zend_string_init("ChangeQueue", sizeof("ChangeQueue")-1, 1);
	zend_declare_typed_property(class_entry, property_queue_name, &property_queue_default_value, ZEND_ACC_PROTECTED|ZEND_ACC_STATIC, NULL, (zend_type) ZEND_TYPE_INIT_CLASS(property_queue_class_ChangeQueue, 0, MAY_BE_NULL));
	zend_string_release_ex(property_queue_name, true);

	zval property_componentId_default_value;
	ZVAL_EMPTY_STRING(&property_componentId_default_value);
	zend_string *property_componentId_name = zend_string_init("componentId", sizeof("componentId") - 1, true);
	zend_declare_typed_property(class_entry, property_componentId_name, &property_componentId_default_value, ZEND_ACC_PROTECTED|ZEND_ACC_STATIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_componentId_name, true);

	zval property_dirty_default_value;
	ZVAL_FALSE(&property_dirty_default_value);
	zend_string *property_dirty_name = zend_string_init("dirty", sizeof("dirty") - 1, true);
	zend_declare_typed_property(class_entry, property_dirty_name, &property_dirty_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_BOOL));
	zend_string_release_ex(property_dirty_name, true);

	zval property_template_default_value;
	ZVAL_EMPTY_STRING(&property_template_default_value);
	zend_string *property_template_name = zend_string_init("template", sizeof("template") - 1, true);
	zend_declare_typed_property(class_entry, property_template_name, &property_template_default_value, ZEND_ACC_PUBLIC, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_STRING));
	zend_string_release_ex(property_template_name, true);

	return class_entry;
}
