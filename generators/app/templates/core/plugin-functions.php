<?php
/**
 * Provides helper functions.
 *
 * @since	  {{VERSION}}
 *
 * @package	<%- pkgName %>
 * @subpackage <%- pkgName -%>/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		{{VERSION}}
 *
 * @return		<%- pkgName %>
 */
function <%- instanceName -%>() {
	return <%- pkgName -%>::instance();
}