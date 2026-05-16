/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: fe716fa490a25036079352f59e49736397fa6558 */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_ChangeQueue___construct, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ChangeQueue_push, 0, 3, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, key, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, version, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, value, IS_MIXED, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ChangeQueue_pop, 0, 0, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ChangeQueue_isEmpty, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(ChangeQueue, __construct);
ZEND_METHOD(ChangeQueue, push);
ZEND_METHOD(ChangeQueue, pop);
ZEND_METHOD(ChangeQueue, isEmpty);

static const zend_function_entry class_ChangeQueue_methods[] = {
	ZEND_ME(ChangeQueue, __construct, arginfo_class_ChangeQueue___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(ChangeQueue, push, arginfo_class_ChangeQueue_push, ZEND_ACC_PUBLIC)
	ZEND_ME(ChangeQueue, pop, arginfo_class_ChangeQueue_pop, ZEND_ACC_PUBLIC)
	ZEND_ME(ChangeQueue, isEmpty, arginfo_class_ChangeQueue_isEmpty, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};

static zend_class_entry *php_register_class_ChangeQueue(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "ChangeQueue", class_ChangeQueue_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, 0);

	zval property_buffer_default_value;
	ZVAL_EMPTY_ARRAY(&property_buffer_default_value);
	zend_string *property_buffer_name = zend_string_init("buffer", sizeof("buffer") - 1, true);
	zend_declare_typed_property(class_entry, property_buffer_name, &property_buffer_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_ARRAY));
	zend_string_release_ex(property_buffer_name, true);

	zval property_head_default_value;
	ZVAL_LONG(&property_head_default_value, 0);
	zend_string *property_head_name = zend_string_init("head", sizeof("head") - 1, true);
	zend_declare_typed_property(class_entry, property_head_name, &property_head_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release_ex(property_head_name, true);

	zval property_tail_default_value;
	ZVAL_LONG(&property_tail_default_value, 0);
	zend_string *property_tail_name = zend_string_init("tail", sizeof("tail") - 1, true);
	zend_declare_typed_property(class_entry, property_tail_name, &property_tail_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release_ex(property_tail_name, true);

	zval property_maxSize_default_value;
	ZVAL_LONG(&property_maxSize_default_value, 4096);
	zend_string *property_maxSize_name = zend_string_init("maxSize", sizeof("maxSize") - 1, true);
	zend_declare_typed_property(class_entry, property_maxSize_name, &property_maxSize_default_value, ZEND_ACC_PRIVATE, NULL, (zend_type) ZEND_TYPE_INIT_MASK(MAY_BE_LONG));
	zend_string_release_ex(property_maxSize_name, true);

	return class_entry;
}
