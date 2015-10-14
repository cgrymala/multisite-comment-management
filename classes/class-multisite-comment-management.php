<?php
/**
 * Multisite Comment Management Class definition
 * @package multisite-comment-management
 * @version 0.1a
 */
class Multisite_Comment_Management {
	public $version = '0.1a';
	
	function __construct() {
		if ( ! is_multisite() ) {
			add_action( 'admin_notice', array( $this, 'warn_multisite' ) );
		}
		if ( ! is_network_admin() ) {
			return;
		}
		add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
	}
	
	function network_admin_menu() {
		return;
	}
}