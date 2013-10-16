<?php
/**
 * Plugin Name: Smarterer Connect
 * Plugin URI: http://x-team.com
 * Description:
 * Version: 0.1.0
 * Author: X-Team, Shady Sharaf
 * Author URI: http://x-team.com/wordpress/
 * License: GPLv2+
 * Text Domain: smarterer-connect
 * Domain Path: /smarterer-connect
 */

/**
 * Copyright (c) 2013 X-Team (http://x-team.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

class Smarterer_Connect {

	function __construct() {
		// Initialize admin functions
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) require_once dirname( __FILE__ ) . '/inc/class.smarterer-connect-admin.php';
	}

	/**
	 * Get smarterer username for specified/current user
	 * @param  mixed $user If null, gets the current logged-in user
	 * @return mixed       Smarterer username, or FALSE
	 */
	static function get_username( $user = null ) {
		if ( is_null( $user ) && is_user_logged_in() )
			$user = wp_get_current_user();
		elseif ( ! empty( $user ) )
			$user = new WP_User( $user );

		if ( ! isset( $user ) || empty( $user->ID ) )
			return false;

		$meta = get_user_meta( $user->ID, 'smarterer_badges', true );
		if ( $meta && is_object( $meta ) ) {
			if ( isset( $meta->username ) ) {
				return $meta->username;
			}
		}

		return false;
	}

}

$GLOBALS['smarterer_connect'] = new Smarterer_Connect;
