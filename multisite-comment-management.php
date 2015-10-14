<?php
/*
Plugin Name: Multisite Comment Management
Description: Allows the deletion of spam/unapproved comments from an entire WordPress multisite network
Version:     0.1a
Author:      Curtiss Grymala
Author URI:  http://www.umw.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: multisite-comment-management
Network:     true
*/

if ( ! class_exists( 'Multisite_Comment_Management' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/classes/class-multisite-comment-management.php' );
	
	add_action( 'plugins_loaded', 'inst_multisite_comment_management_obj' );
	
	function inst_multisite_comment_management_obj() {
		global $multisite_comment_management_obj;
		$multisite_comment_management_obj = new Multisite_Comment_Management;
	}
}