<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	<%= pkgName %>
 * @subpackage <%= pkgName %>/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		<%= pkgName %>
 */
function <%= instanceName %>() {
	return <%= pkgName %>::instance();
}