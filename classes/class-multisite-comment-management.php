<?php
/**
 * Multisite Comment Management Class definition
 * @package multisite-comment-management
 * @version 0.1a
 */
class Multisite_Comment_Management {
	public $version = '0.1a';
	public $plugin_name = '';
	
	/**
	 * Instantiate the object
	 * @uses is_multisite() to determine whether this is a multisite install or not
	 * @uses is_network_admin() to bail out if we're not in the network admin area
	 * @uses add_action() to hook into network_admin_menu to register the options page
	 */
	function __construct() {
		$this->plugin_name = __( 'Multisite Comment Management', 'multisite-comment-management' );
		
		if ( ! is_multisite() ) {
			add_action( 'admin_notice', array( $this, 'warn_multisite' ) );
		}
		if ( ! is_network_admin() ) {
			return;
		}
		add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
	}
	
	/**
	 * Register the plugin options/action page
	 */
	function network_admin_menu() {
		add_submenu_page(
			/* parent_slug */'sites.php', 
			/* page_title */ $this->plugin_name, 
			/* menu_title */ $this->plugin_name, 
			/* capability */ 'manage_sites', 
			/* menu_slug  */ 'ms-comment-mgmt', 
			/* callback   */ array( $this, 'options_page' )
		);
	}
	
	/**
	 * Output any leading text/information on the options/action page
	 */
	function options_page() {
		echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
		printf( '<h2>%s</h2>', $this->plugin_name );
		printf( __( '<p>This is the page where the %s plugin can be managed.</p>', 'multisite-comment-management' ), $this->plugin_name );
		echo '</div>';
		return;
	}
	
	/**
	 * Output a warning explaining that this plugin should not be used
	 * 		on non-multisite installations
	 */
	function warn_multisite() {
		echo __( '<p>The Multisite Comment Management plugin is intended for use only on multisite installations. This does not appear to be a multisite install, so the plugin will not do anything.</p>', 'multisite-comment-management' );
	}
}