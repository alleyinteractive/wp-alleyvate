<?php
/**
 * Interface file for REST_Get_Callback
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Rest_Api;

/**
 * Specifies a REST API field that implements a `get_callback()`.
 */
interface REST_Field_Get_Callback {
	/**
	 * The callback function used to retrieve the field value.
	 *
	 * @param array            $object      REST API object.
	 * @param string           $field_name  Field name.
	 * @param \WP_REST_Request $request     REST API request.
	 * @param string           $object_type Object type.
	 * @return mixed Field value.
	 */
	public function get_callback( $object, string $field_name, \WP_REST_Request $request, string $object_type );
}
