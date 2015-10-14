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
		
		add_action( 'started-ms-comment-mgmt-page', array( $this, 'do_admin_styles' ) );
	}
	
	/**
	 * Output any leading text/information on the options/action page
	 */
	function options_page() {
		$this->do_management();
		echo '<div class="wrap" id="ms-comment-mgmt-page-wrapper"><div id="icon-tools" class="icon32"></div>';
		printf( '<h2>%s</h2>', $this->plugin_name );
		$this->do_option_page_content();
		echo '</div>';
		return;
	}
	
	/**
	 * Output the main body of the options page
	 */
	function do_option_page_content() {
		do_action( 'started-ms-comment-mgmt-page' );
		printf( '<form method="post" action="%s">', network_admin_url( 'sites.php?page=ms-comment-mgmt' ) );
		wp_nonce_field( 'ms-comment-mgmt', '_mscm_nonce' );
		printf( '<p><input type="submit" name="ms-comment-mgmt[check-comments]" value="%s" class="button button-secondary"/></p>', __( 'Check Comment Status', 'multisite-comment-management' ) );
		do_action( 'did-multisite-comments-check' );
		printf( '<fieldset><legend>%1$s</legend><h3>%1$s</h3>', __( 'Comment Management', 'multisite-comment-management' ) );
		do_action( 'did-multisite-comments-prune' );
		printf( '<fieldset><legend>%1$s</legend><h4>%1$s</h4>', __( 'What to Prune', 'multisite-comment-management' ) );
		printf( '<p><label><input type="checkbox" name="ms-comment-mgmt[prune][spam]" value="1"/> %s</label></p>', __( 'Delete All Spam Comments', 'multisite-comment-management' ) );
		printf( '<p><label><input type="checkbox" name="ms-comment-mgmt[prune][unapproved]" value="1"/> %s</label></p>', __( 'Delete All Unapproved Comments', 'multisite-comment-management' ) );
		echo '</fieldset>';
		printf( '<p><input type="submit" name="ms-comment-mgmt[delete-comments]" value="%s" class="button button-primary"/></p>', __( 'Delete Comments', 'multisite-comment-management' ) );
		echo '</fieldset>';
		echo '</form>';
	}
	
	/**
	 * Perform the comment moderation actions
	 */
	function do_management() {
		if ( ! isset( $_POST['ms-comment-mgmt'] ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'welcome_message' ) );
			add_action( 'did-multisite-comments-check', array( $this, 'old_comment_status' ) );
			return;
		}
		if ( ! wp_verify_nonce( $_POST['_mscm_nonce'], 'ms-comment-mgmt' ) ) {
			add_action( 'did-multisite-comments-check', array( $this, 'nonce_not_verified' ) );
			return;
		}
		
		if ( isset( $_POST['ms-comment-mgmt']['check-comments'] ) ) {
			add_action( 'did-multisite-comments-check', array( $this, 'check_comment_status' ) );
		} else if ( isset( $_POST['ms-comment-mgmt']['delete-comments'] ) ) {
			add_action( 'did-multisite-comments-prune', array( $this, 'delete_comments' ) );
		} else {
			add_action( 'did-multisite-comments-check', array( $this, 'old_comment_status' ) );
		}
	}
	
	/**
	 * Check the status/numbers of all unapproved/spam comments across the install
	 */
	function check_comment_status() {
		$sites = $this->gather_sites();
		if ( is_wp_error( $sites ) || empty( $sites ) ) {
			add_action( 'did-multisite-comments-check', array( $this, 'no_sites_notice' ) );
			return;
		}
		$comments = array();
		global $wpdb;
		
		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$comments[$site] = array(
				'name' => get_bloginfo( 'name', 'display' ),
				'spam' => 0, 
				'unapproved' => 0,
				'approved' => 0, 
				'checked' => current_time( 'mysql' ), 
			);
			$comments[$site]['spam'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved=%s", 'spam' ) );
			$comments[$site]['unapproved'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved=%d", 0 ) );
			$comments[$site]['approved'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved=%d", 1 ) );
			restore_current_blog();
		}
		
		update_site_option( 'ms-comment-management-status', $comments );
		$this->output_status_table( $comments );
	}
	
	/**
	 * Retrieve the most recent comment status data
	 */
	function old_comment_status() {
		$comments = get_site_option( 'ms-comment-management-status', false );
		if ( empty( $comments ) )
			return;
		
		$tmp = $comments;
		$tmp = array_pop( $tmp );
		printf( __( '<p>The comment status information below was last generated on <strong>%s</strong></p>', 'multisite-comment-management' ), $tmp['checked'] );
		$this->output_status_table( $comments );
	}
	
	/**
	 * Retrieve an array of all blog IDs in this install
	 */
	function gather_sites() {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE public<%d", 2 ) );
	}
	
	/**
	 * Output the table of comment status data
	 * @param array $comments the array of comment status data
	 */
	function output_status_table( $comments=array() ) {
		if ( empty( $comments ) )
			return;
		
		printf( '<table id="ms-comment-management-status-data">
		<thead>
			<tr>
				<th scope="col">%5$s</th>
				<th scope="col">%1$s</th>
				<th scope="col">%2$s</th>
				<th scope="col">%3$s</th>
				<th scope="col">%4$s</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>%5$s</th>
				<th>%1$s</th>
				<th>%2$s</th>
				<th>%3$s</th>
				<th>%4$s</th>
			</tr>
		</tfoot>
		<tbody>', 
			__( 'Name', 'multisite-comment-management' ), 
			__( 'Spam', 'multisite-comment-management' ), 
			__( 'Unapproved', 'multisite-comment-management' ), 
			__( 'Approved', 'multisite-comment-management' ), 
			__( 'ID', 'multisite-comment-management' ) 
		);
		
		$i = 0;
		foreach ( $comments as $k=>$c ) {
			printf( '<tr class="%1$s status-row">
				<th scope="row" class="site-id">%2$d</th>
				<td class="site-name">%3$s</td>
				<td class="spam-status number">%4$d</td>
				<td class="unapproved-status number">%5$d</td>
				<td class="approved-status number">%6$d</td>
			</tr>', 
				$i%2 ? 'odd-row' : 'even-row', 
				intval( $k ), 
				$c['name'], 
				intval( $c['spam'] ), 
				intval( $c['unapproved'] ), 
				intval( $c['approved'] )
			);
			$i++;
		}
		echo '</tbody></table>';
	}
	
	/**
	 * Output a welcome message on the options page when no actions have been taken
	 */
	function welcome_message() {
		printf( __( '<p class="warn">Welcome to the %s plugin management page. On this page, you can use the %s button to check the status/number of comments on each site within your installation, or you can use the form below to prune all spam and/or unapproved comments within this install.</p>', 'multisite-comment-management' ), $this->plugin_name, __( 'Check Comment Status', 'multisite-comment-management' ) );
	}
	
	/**
	 * Output an error message when the nonce cannot be verified
	 */
	function nonce_not_verified() {
		_e( '<p class="error">It appears that you attempted to perform an action, but the nonce could not be verified. Please try again.</p>', 'multisite-comment-management' );
	}
	
	/**
	 * Output a warning explaining that the list of blog IDs could not be retrieved
	 */
	function no_sites_notice() {
		_e( '<p class="error">There was an error retrieving the list of sites within this installation, so we could not retrieve the comment status.</p>', 'multisite-comment-management' );
	}
	
	/**
	 * Output a warning explaining that this plugin should not be used
	 * 		on non-multisite installations
	 */
	function warn_multisite() {
		echo __( '<p class="error">The Multisite Comment Management plugin is intended for use only on multisite installations. This does not appear to be a multisite install, so the plugin will not do anything.</p>', 'multisite-comment-management' );
	}
	
	/**
	 * Output any CSS specific to our admin page
	 */
	function do_admin_styles() {
?>
<style title="ms-comment-mgmt-admin-styles" type="text/css">
#ms-comment-mgmt-page-wrapper .error {
	padding: 1rem;
	background: #FF7676;
	color: #360000;
	border: 1px solid #360000;
	border-radius: 1rem;
}

#ms-comment-mgmt-page-wrapper .warn {
	background: #FFFF9E;
	color: #575701;
	border: 1px solid #575701;
	border-radius: 1rem;
	padding: 1rem;
}

#ms-comment-mgmt-page-wrapper fieldset fieldset {
	padding: 1rem;
	border: none;
	background: #eee;
}

#ms-comment-mgmt-page-wrapper fieldset fieldset p {
	margin: 0 0 1rem;
}

#ms-comment-mgmt-page-wrapper legend {
	width: 0;
	height: 0;
	text-indent: -99999em;
	font-size: 0;
	line-height: 0;
	margin: 0;
	padding: 0;
	border: none;
	overflow: hidden;
}

#ms-comment-mgmt-page-wrapper table {
	width: 100%;
	max-width: 100%;
}

#ms-comment-mgmt-page-wrapper tr {
	margin: 0;
	padding: 0;
}

#ms-comment-mgmt-page-wrapper th, 
#ms-comment-mgmt-page-wrapper td {
	border: 1px solid #666;
	padding: .5rem;
	margin: 0;
}

#ms-comment-mgmt-page-wrapper td.number {
	text-align: right;
}

#ms-comment-mgmt-page-wrapper th {
	background: #e2e2e2;
	color: #010957;
	font-weight: 700;
}

#ms-comment-mgmt-page-wrapper .odd-row td {
	background: #e9e9e9;
	color: #000;
}

#ms-comment-mgmt-page-wrapper .even-row td {
	background: #fff;
	color: #666;
}
</style>
<?php
	}
	
}