<?php
/**
 * Multisite Comment Management Class definition
 * @package multisite-comment-management
 * @version 0.1a
 */
class Multisite_Comment_Management {
	public $version = '0.1a';
	public $plugin_name = '';
	public $pagehooks = array();
	
	/**
	 * Instantiate the object
	 * @uses is_multisite() to determine whether this is a multisite install or not
	 * @uses is_network_admin() to bail out if we're not in the network admin area
	 * @uses add_action() to hook into network_admin_menu to register the options page
	 */
	function __construct() {
		$this->plugin_name = __( 'Multisite Management Tools', 'multisite-comment-management' );
		
		if ( ! is_multisite() ) {
			add_action( 'admin_notice', array( $this, 'warn_multisite' ) );
		}
		if ( ! is_network_admin() ) {
			return;
		}
		add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
		
		$this->did_pruning_message = array();
	}
	
	/**
	 * Register the plugin options/action pages
	 */
	function network_admin_menu() {
		/* Intro Page */
		$this->pagehooks['top'] = add_menu_page(
			/* page_title */ $this->plugin_name, 
			/* menu_title */ __( 'Management Tools', 'multsite-comment-management' ), 
			/* capability */ 'manage_sites', 
			/* menu_slug  */ 'ms-mgmt-tools', 
			/* callback   */ array( $this, 'options_page' ), 
			/* icon_url   */ 'dashicons-hammer'
		);
		/* Comments Management */
		$this->pagehooks['comments'] = add_submenu_page(
			/* parent_slug */'ms-mgmt-tools', 
			/* page_title */ __( 'Comment Management', 'multisite-comment-management' ), 
			/* menu_title */ __( 'Comment Management', 'multisite-comment-management' ), 
			/* capability */ 'manage_sites', 
			/* menu_slug  */ 'ms-comment-mgmt', 
			/* callback   */ array( $this, 'comment_management_page' )
		);
		/* Transients Management */
		$this->pagehooks['transients'] = add_submenu_page( 
			/* parent_slug */'ms-mgmt-tools', 
			/* page_title */ __( 'Transient Management', 'multisite-comment-management' ), 
			/* menu_title */ __( 'Transient Management', 'multisite-comment-management' ), 
			/* capability */ 'manage_sites', 
			/* menu_slug  */ 'ms-transient-mgmt', 
			/* callback   */ array( $this, 'transient_management_page' )
		);
		/* Database/Table Management */
		$this->pagehooks['database'] = add_submenu_page( 
			/* parent_slug */'ms-mgmt-tools', 
			/* page_title */ __( 'Database Management', 'multisite-comment-management' ), 
			/* menu_title */ __( 'Database Management', 'multisite-comment-management' ), 
			/* capability */ 'manage_sites', 
			/* menu_slug  */ 'ms-database-mgmt', 
			/* callback   */ array( $this, 'database_management_page' )
		);
		/* Large Files Management */
		$this->pagehooks['files'] = add_submenu_page( 
			/* parent_slug */'ms-mgmt-tools', 
			/* page_title */ __( 'Large Files Management', 'multisite-comment-management' ), 
			/* menu_title */ __( 'Large Files Management', 'multisite-comment-management' ), 
			/* capability */ 'manage_sites', 
			/* menu_slug  */ 'ms-files-mgmt', 
			/* callback   */ array( $this, 'files_management_page' )
		);
		
		add_action( 'started-ms-comment-mgmt-page', array( $this, 'do_admin_styles' ) );
		foreach ( $this->pagehooks as $p ) {
			add_action( 'load-' . $p, array( $this, 'add_meta_boxes' ) );
		}
	}
	
	/**
	 * Register the meta boxes used on the options page
	 */
	function add_meta_boxes() {
		add_meta_box(
			/* id       */ 'ms-comment-mgmt-status', 
			/* title    */ __( 'Comment Status', 'multisite-comment-management' ), 
			/* callback */ array( $this, 'comment_status_metabox' ), 
			/* screen   */ $this->pagehooks['comments'], 
			/* context  */ 'normal', 
			/* priority */ 'default'
		);
		add_meta_box(
			/* id       */ 'ms-comment-mgmt-transients-network', 
			/* title    */ __( 'Network Transient Management', 'multisite-comment-management' ), 
			/* callback */ array( $this, 'network_transient_status_metabox' ), 
			/* screen   */ $this->pagehooks['transients'], 
			/* context  */ 'normal', 
			/* priority */ 'default'
		);
		add_meta_box(
			/* id       */ 'ms-comment-mgmt-transients', 
			/* title    */ __( 'Transient Management', 'multisite-comment-management' ), 
			/* callback */ array( $this, 'transient_status_metabox' ), 
			/* screen   */ $this->pagehooks['transients'], 
			/* context  */ 'normal', 
			/* priority */ 'default'
		);
		add_meta_box(
			/* id       */ 'ms-comment-mgmt-database', 
			/* title    */ __( 'Database Management', 'multisite-comment-management' ), 
			/* callback */ array( $this, 'database_status_metabox' ), 
			/* screen   */ $this->pagehooks['database'], 
			/* context  */ 'normal', 
			/* priority */ 'default'
		);
		add_meta_box(
			/* id       */ 'ms-comment-mgmt-files', 
			/* title    */ __( 'Large Files Management', 'multisite-comment-management' ), 
			/* callback */ array( $this, 'files_status_metabox' ), 
			/* screen   */ $this->pagehooks['files'], 
			/* context  */ 'normal', 
			/* priority */ 'default'
		);
	}
	
	/**
	 * Output any leading text/information on the options/action page
	 */
	function options_page( $page='' ) {
		$this->do_management();
		echo '<div class="wrap" id="ms-comment-mgmt-page-wrapper"><div id="icon-tools" class="icon32"></div>';
		printf( '<h2>%s</h2>', $this->plugin_name );
		$this->do_option_page_content( $page );
		echo '</div>';
		return;
	}
	
	/**
	 * Output the Comment Management page
	 */
	function comment_management_page() {
		$this->options_page( 'ms-comment-mgmt' );
	}
	
	/**
	 * Output the Transient Management page
	 */
	function transient_management_page() {
		$this->options_page( 'ms-transient-mgmt' );
	}
	
	/**
	 * Output the Database Management page
	 */
	function database_management_page() {
		$this->options_page( 'ms-database-mgmt' );
	}
	
	/**
	 * Output the Large Files Management page
	 */
	function files_management_page() {
		$this->options_page( 'ms-files-mgmt' );
	}
	
	/**
	 * Output the main body of the options page
	 */
	function do_option_page_content( $page='' ) {
		global $screen_layout_columns;
		do_action( 'started-ms-comment-mgmt-page' );
		
		if ( empty( $page ) ) {
			$this->do_intro_page_content();
			return;
		}
		
		printf( '<form method="post" action="%s">', network_admin_url( 'admin.php?page=' . $page ) );
		wp_nonce_field( 'ms-comment-mgmt', '_mscm_nonce' );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		
		echo '<div id="poststuff" class="metabox-holder' . ( 2 == $screen_layout_columns ? ' has-right-sidebar' : ' has-no-sidebar' ) . '">';
		echo '<div class="postbox-container">';
		switch ( $page ) {
			case 'ms-transient-mgmt' : 
				do_meta_boxes( $this->pagehooks['transients'], 'normal', null );
				break;
			case 'ms-database-mgmt' : 
				do_meta_boxes( $this->pagehooks['database'], 'normal', null );
				break;
			case 'ms-files-mgmt' : 
				do_meta_boxes( $this->pagehooks['files'], 'normal', null );
				break;
			default :
				do_meta_boxes( $this->pagehooks['comments'], 'normal', null );
				break;
		}
		echo '</div>';
		echo '</div>';
		
		echo '</form>';
		
		
		/* Enqueue WordPress' script for handling the meta boxes */
		wp_enqueue_script( 'postbox' );
		
		/* Add screen option: user can choose between 1 or 2 columns (default 2) */
		add_screen_option( 'layout_columns', array( 'max' => 2, 'default' => 2 ) );
		
		add_action( 'admin_print_footer_scripts', array( $this, 'meta_box_scripts' ) );
	}
	
	/**
	 * Output a little bit of intro info about this plugin
	 */
	function do_intro_page_content() {
		_e( '<p>This plugin offers a handful of useful tools for managing large WordPress Multisite installations.</p>' );
		echo '<ul>';
		_e( '<li><strong>Comment Management:</strong> Here, you have the ability to prune spam comments, delete unapproved comments and even delete all comments from multiple sites at once.</li>' );
		_e( '<li><strong>Transients Management:</strong> Here, you have the ability to manage network transients for all of your networks and the ability to manage normal transients on all of your sites from a single location. You can see how many transients are expired, and how many total transients there are, and you can optionally delete them from multiple sites and networks at once.</li>' );
		_e( '<li><strong>Database Management:</strong> This page is intended to give you the opportunity to identify and prune orphaned tables within your installation.</li>' );
		_e( '<li><strong>Large Files Management:</strong> This page is intended to give you the opportunity to identify and optionally delete any large files within your file system (such as large backups that are hiding in various sub-folders, etc.)</li>' );
		echo '</ul>';
	}

	/**
	 * Output the comment status meta box
	 */
	function comment_status_metabox() {
		printf( '<p><input type="submit" name="ms-comment-mgmt[check-comments]" value="%s" class="button button-secondary"/></p>', __( 'Check Comment Status', 'multisite-comment-management' ) );
		_e( '<p>Any items selected below that report as being 0 will be skipped. If you are getting ready to clean out old comments, it is recommended that you check the status before doing so.</p>', 'multisite-comment-management' );
		do_action( 'did-multisite-comments-check' );
		printf( '<p><input type="submit" name="ms-comment-mgmt[delete-comments]" value="%s" class="button button-primary"/></p>', __( 'Delete Selected Comments', 'multisite-comment-management' ) );
		
		do_action( 'did-multisite-comments-prune' );
	}
	
	/**
	 * Output the database status meta box
	 */
	function database_status_metabox() {
		printf( '<p><input type="submit" name="ms-comment-mgmt[check-database]" value="%s" class="button button-secondary"/></p>', __( 'Check Database Status', 'multisite-comment-management' ) );
		do_action( 'did-multisite-database-check' );
		printf( '<p><input type="submit" name="ms-comment-mgmt[delete-tables]" value="%s" class="button button-primary"/></p>', __( 'Delete Selected Tables', 'multisite-comment-management' ) );
		
		do_action( 'did-multisite-database-prune' );
	}
	
	/**
	 * Output the large files status meta box
	 */
	function files_status_metabox() {
		printf( '<p><input type="submit" name="ms-comment-mgmt[check-files]" value="%s" class="button button-secondary"/></p>', __( 'Check Large Files Status', 'multisite-comment-management' ) );
		do_action( 'did-multisite-files-check' );
		printf( '<p><input type="submit" name="ms-comment-mgmt[delete-files]" value="%s" class="button button-primary"/></p>', __( 'Delete Selected Files', 'multisite-comment-management' ) );
		
		do_action( 'did-multisite-files-prune' );
	}
	
	/**
	 * Perform the comment moderation actions
	 */
	function do_management() {
		add_action( 'started-ms-comment-mgmt-page', array( $this, 'welcome_message' ) );
		add_action( 'did-multisite-comments-check', array( $this, 'old_comment_status' ) );
		add_action( 'did-multisite-transients-check', array( $this, 'old_transient_status' ) );
		add_action( 'did-multisite-database-check', array( $this, 'old_database_status' ) );
		add_action( 'did-multisite-files-check', array( $this, 'old_files_status' ) );
		
		if ( ! isset( $_POST['ms-comment-mgmt'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['_mscm_nonce'], 'ms-comment-mgmt' ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'nonce_not_verified' ) );
			return;
		}
		
		if ( isset( $_POST['ms-comment-mgmt']['check-comments'] ) ) {
			add_action( 'did-multisite-comments-check', array( $this, 'check_comment_status' ) );
			remove_action( 'did-multisite-comments-check', array( $this, 'old_comment_status' ) );
		} else if ( isset( $_POST['ms-comment-mgmt']['delete-comments'] ) ) {
			$this->delete_comments();
		} else if ( isset( $_POST['ms-comment-mgmt']['check-transients'] ) ) {
			add_action( 'did-multisite-transients-check', array( $this, 'check_transient_status' ) );
			remove_action( 'did-multisite-transients-check', array( $this, 'old_transient_status' ) );
		} else if ( isset( $_POST['ms-comment-mgmt']['delete-transients'] ) ) {
			$this->delete_transients();
		} else if ( isset( $_POST['ms-comment-mgmt']['check-database'] ) ) {
			add_action( 'did-multisite-database-check', array( $this, 'check_database_status' ) );
			remove_action( 'did-multisite-database-check', array( $this, 'old_database_status' ) );
		} else if ( isset( $_POST['ms-comment-mgmt']['delete-tables'] ) ) {
			$this->delete_tables();
		} else if ( isset( $_POST['ms-comment-mgmt']['check-files'] ) ) {
			add_action( 'did-multisite-files-check', array( $this, 'check_files_status' ) );
			remove_action( 'did-multisite-files-check', array( $this, 'old_files_status' ) );
		} else if ( isset( $_POST['ms-comment-mgmt']['delete-files'] ) ) {
			$this->delete_files();
		}
	}
	
	/**
	 * Check the status/numbers of all unapproved/spam comments across the install
	 */
	function check_comment_status() {
		$sites = $this->gather_sites();
		if ( is_wp_error( $sites ) || empty( $sites ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'no_sites_notice' ) );
			return;
		}
		$comments = array();
		global $wpdb;
		
		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$comments[$site] = array(
				'id'         => intval( $site ), 
				'name'       => get_bloginfo( 'name', 'display' ),
				'spam'       => 0, 
				'unapproved' => 0,
				'approved'   => 0, 
				'checked'    => current_time( 'mysql' ), 
			);
			$q = $wpdb->prepare( "SELECT COUNT(*) total, SUM(CASE WHEN comment_approved=%s THEN 1 ELSE 0 END) spam, SUM(CASE WHEN comment_approved='%d' THEN 1 ELSE 0 END) unapproved, SUM(CASE WHEN comment_approved='%d' THEN 1 ELSE 0 END) approved FROM {$wpdb->comments}", 'spam', 0, 1 );
			$vars = $wpdb->get_row( $q );
			if ( ! is_wp_error( $vars ) && is_object( $vars ) ) {
				$comments[$site]['spam'] = $vars->spam;
				$comments[$site]['unapproved'] = $vars->unapproved;
				$comments[$site]['approved'] = $vars->approved;
			}
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
	 * Prune any comments that were selected for deletion
	 */
	function delete_comments() {
		if ( ! isset( $_POST['_mscm_nonce'] ) || ! wp_verify_nonce( $_POST['_mscm_nonce'], 'ms-comment-mgmt' ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'nonce_not_verified' ) );
			return;
		}
		if ( ! current_user_can( 'manage_sites' ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'no_user_permissions' ) );
			return;
		}
		
		global $wpdb;
		
		foreach ( $_POST['ms-comment-mgmt']['comments'] as $k => $v ) {
			$k = intval( $k );
			
			$this->did_pruning_message[] = sprintf( '<p>Preparing to review comments on the site with an ID of %d</p>', $k );
			
			$status = array();
			$status_placeholders = array();
			
			if ( isset( $v['spam'] ) && intval( $v['spam'] ) > 0 ) {
				$status[] = 'spam';
				$status_placeholders[] = '%s';
			}
			if ( isset( $v['unapproved'] ) && intval( $v['unapproved'] ) > 0 ) {
				$status[] = 0;
				$status_placeholders[] = '%s';
			}
			if ( isset( $v['approved'] ) && intval( $v['approved'] ) > 0 ) {
				$status[] = 1;
				$status_placeholders[] = '%s';
			}
			if ( ! empty( $status ) ) {
				switch_to_blog( $k );
				
				$status_placeholders = implode( ', ', $status_placeholders );
				/**
				 * Retrieve the IDs of all comments being deleted so we can remove them from the commentmeta table
				 */
				$query = "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved IN ( {$status_placeholders} )";
				$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $wpdb->prepare( $query, $status ) );
				$comment_ids = $wpdb->get_col( $wpdb->prepare( $query, $status ) );
				if ( ! is_wp_error( $comment_ids ) && ! empty( $comment_ids ) ) {
					$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', implode( "\r", $comment_ids ) );
					
					$comment_placeholders = array_fill( 0, count( $comment_ids ), '%d' );
					$comment_placeholders = implode( ', ', $comment_placeholders );
					$query = "DELETE FROM {$wpdb->commentmeta} WHERE comment_id IN ( {$comment_placeholders} )";
					$q2 = "DELETE FROM {$wpdb->comments} WHERE comment_ID IN ( {$comment_placeholders} )";
					
					/**
					 * Delete comment meta data
					 */
					$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $wpdb->prepare( $query, $comment_ids ) );
					$msg = $wpdb->query( $wpdb->prepare( $query, $comment_ids ) );
					/*$msg = $wpdb->delete( $wpdb->commentmeta, array( 'comment_id' => $comment_ids ), array( '%d' ) );*/
					if ( is_wp_error( $msg ) ) {
						$msg = $msg->get_error_message();
					}
					$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', print_r( $msg, true ) );
					
					/**
					 * Delete comments
					 */
					$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $wpdb->prepare( $q2, $comment_ids ) );
					$msg = $wpdb->query( $wpdb->prepare( $q2, $comment_ids ) );
					/*$msg = $wpdb->delete( $wpdb->comments, array( 'comment_ID' => $comment_ids ), array( '%d' ) );*/
					if ( is_wp_error( $msg ) ) {
						$msg = $msg->get_error_message();
					}
					$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', print_r( $msg, true ) );
				} else if ( is_wp_error( $comment_ids ) ) {
					$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $comment_ids->get_error_message() );
				}
				
				restore_current_blog();
			}
		}
		
		delete_site_option( 'ms-comment-management-status' );
		
		add_action( 'did-multisite-comments-prune', array( $this, 'did_multisite_comments_prune_message' ) );
	}
	
	function did_multisite_comments_prune_message() {
		if ( empty( $this->did_pruning_message ) )
			return;
		
		printf( '<div class="warn">%s</div>', implode( '', $this->did_pruning_message ) );
	}
	
	/**
	 * Output the table of comment status data
	 * @param array $comments the array of comment status data
	 */
	function output_status_table( $comments=array() ) {
		if ( empty( $comments ) )
			return;
		
		printf( '<p><strong>%1$s</strong></p>', __( 'List of All Comments Found In This Installation', 'multisite-comment-management' ) );
		
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		
		require_once( plugin_dir_path( __FILE__ ) . '/_inc/class-ms-comment-status-list-table.php' );
		
		$table = new MS_Comment_Status_List_Table();
		$table->prepare_items( $comments );
		$table->display();
		
		return;
		
		printf( '<table id="ms-comment-management-status-data">
		<caption><h4>%6$s</h4></caption>
		<thead>
			<tr>
				<th scope="col">%5$s</th>
				<th scope="col">%1$s</th>
				<th scope="col"><span class="select-all-button spam"><input type="checkbox"/></span>%2$s</th>
				<th scope="col"><span class="select-all-button unapproved"><input type="checkbox"/></span>%3$s</th>
				<th scope="col"><span class="select-all-button approved"><input type="checkbox"/></span>%4$s</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col">%5$s</th>
				<th scope="col">%1$s</th>
				<th scope="col"><span class="select-all-button spam"><input type="checkbox"/></span>%2$s</th>
				<th scope="col"><span class="select-all-button unapproved"><input type="checkbox"/></span>%3$s</th>
				<th scope="col"><span class="select-all-button approved"><input type="checkbox"/></span>%4$s</th>
			</tr>
		</tfoot>
		<tbody>', 
			__( 'Name', 'multisite-comment-management' ), 
			__( 'Spam', 'multisite-comment-management' ), 
			__( 'Unapproved', 'multisite-comment-management' ), 
			__( 'Approved', 'multisite-comment-management' ), 
			__( 'ID', 'multisite-comment-management' ) , 
			__( 'List of All Comments Found In This Installation', 'multisite-comment-management' ) 
		);
		
		$i = 0;
		foreach ( $comments as $k=>$c ) {
			printf( '<tr class="%1$s status-row">
				<th scope="row" class="site-id">%2$d</th>
				<td class="site-name">%3$s</td>
				<td class="spam-status number"><span class="select-one-button spam"><input type="checkbox" name="ms-comment-mgmt[comments][%2$d][spam]" value="%4$d"/></span>%4$d</td>
				<td class="unapproved-status number"><span class="select-one-button unapproved"><input type="checkbox" name="ms-comment-mgmt[comments][%2$d][unapproved]" value="%5$d"/></span>%5$d</td>
				<td class="approved-status number"><span class="select-one-button approved"><input type="checkbox" name="ms-comment-mgmt[comments][%2$d][approved]" value="%6$d"/></span>%6$d</td>
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
	 * Output the network transient status meta box
	 */
	function network_transient_status_metabox() {
		$this->doing_network = true;
		
		printf( '<p><input type="submit" name="ms-comment-mgmt[check-transients]" value="%s" class="button button-secondary"/></p>', __( 'Check Transient Status', 'multisite-comment-management' ) );
		_e( '<p>Any items selected below that report as being 0 will be skipped. If you are getting ready to clean out transients, it is recommended that you check the status before doing so.</p>', 'multisite-comment-management' );
		do_action( 'did-multisite-transients-check' );
		printf( '<p><input type="submit" name="ms-comment-mgmt[delete-transients]" value="%s" class="button button-primary"/></p>', __( 'Delete Selected Transients', 'multisite-comment-management' ) );
		
		do_action( 'did-multisite-transients-prune' );
		return;
	}
	
	/** 
	 * Output the transient status meta box
	 */
	function transient_status_metabox() {
		$this->doing_network = false;
		
		_e( '<p>Any items selected below that report as being 0 will be skipped. If you are getting ready to clean out transients, it is recommended that you check the status before doing so.</p>', 'multisite-comment-management' );
		do_action( 'did-multisite-transients-check' );
		printf( '<p><input type="submit" name="ms-comment-mgmt[delete-transients]" value="%s" class="button button-primary"/></p>', __( 'Delete Selected Transients', 'multisite-comment-management' ) );
		
		do_action( 'did-multisite-transients-prune' );
	}
	
	/**
	 * Check for any transients in the various options tables
	 */
	function check_transient_status() {
		$sites = $this->gather_sites();
		if ( is_wp_error( $sites ) || empty( $sites ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'no_sites_notice' ) );
			return;
		}
		$transients = array();
		global $wpdb;
		
		$current_time = current_time( 'timestamp' );
		$current_mysql = current_time( 'mysql' );
		
		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$transients[$site] = array(
				'id'      => intval( $site ), 
				'name'    => get_bloginfo( 'name', 'display' ),
				'expired' => 0, 
				'all'     => 0,
				'checked' => $current_mysql
			);
			$transients[$site]['expired'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $current_time ) );
			$transients[$site]['all'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s", '_transient_%', '_transient_timeout_%' ) );
			restore_current_blog();
		}
		
		$sites = $wpdb->get_col( "SELECT id FROM {$wpdb->site}" );
		if ( ! is_wp_error( $sites ) && ! empty( $sites ) ) {
			foreach( $sites as $site ) {
				$transients['networks'][$site] = array(
					'id'      => intval( $site ), 
					'name'    => $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->sitemeta} WHERE site_id=%d AND meta_key=%s", $site, 'site_name' ) ), 
					'expired' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d AND site_id=%d", '_site_transient_timeout_%', $current_time, $site ) ), 
					'all'     => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_key NOT LIKE %s AND site_id=%d", '_site_transient_%', '_site_transient_timeout_%', $site ) ), 
					'checked' => $current_mysql
				);
			}
		}
		
		update_site_option( 'ms-comment-management-transient-status', $transients );
		$this->output_transient_status_table( $transients );
	}
	
	/**
	 * Retrieve the most recent transient status data
	 */
	function old_transient_status() {
		$transients = get_site_option( 'ms-comment-management-transient-status', false );
		if ( empty( $transients ) )
			return;
		
		$tmp = $transients;
		if ( array_key_exists( 'networks', $tmp ) ) {
			unset( $tmp['networks'] );
		}
		$tmp = array_pop( $tmp );
		printf( __( '<p>The transient status information below was last generated on <strong>%s</strong></p>', 'multisite-comment-management' ), $tmp['checked'] );
		$this->output_transient_status_table( $transients );
	}
	
	/**
	 * Prune any comments that were selected for deletion
	 */
	function delete_transients() {
		if ( ! wp_verify_nonce( $_POST['_mscm_nonce'], 'ms-comment-mgmt' ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'nonce_not_verified' ) );
			return;
		}
		if ( ! isset( $_POST['ms-comment-mgmt']['transients'] ) || empty( $_POST['ms-comment-mgmt']['transients'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_sites' ) ) {
			add_action( 'started-ms-comment-mgmt-page', array( $this, 'no_user_permissions' ) );
			return;
		}
		
		global $wpdb;
		
		$transients = $_POST['ms-comment-mgmt']['transients'];
		
		$nettrans = isset( $transients['networks'] ) ? $transients['networks'] : false;
		unset( $transients['networks'] );
		
		if ( ! empty( $nettrans ) )
			$this->delete_site_transients( $nettrans );
		
		if ( empty( $transients ) ) {
			delete_site_option( 'ms-comment-management-transient-status' );
			add_action( 'did-multisite-transients-prune', array( $this, 'did_multisite_comments_prune_message' ) );
			return;
		}
		
		foreach ( $transients as $k => $v ) {
			$k = intval( $k );
			
			$this->did_pruning_message[] = sprintf( '<p>Preparing to review transients on the site with an ID of %d</p>', $k );
			
			$status = null;
			
			if ( isset( $v['all'] ) && intval( $v['all'] ) > 0 ) {
				$status = 'all';
			} else if ( isset( $v['expired'] ) && intval( $v['expired'] ) > 0 ) {
				$status = 'expired';
			}
			
			if ( ! empty( $status ) ) {
				switch_to_blog( $k );
				
				if ( 'all' == $status ) {
					$query = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'";
				} else {
					$q = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', current_time( 'timestamp' ) );
					$opts = array();
					foreach ( $wpdb->get_col( $q ) as $o ) {
						$opts[] = $o;
						$opts[] = str_replace( '_timeout', '', $o );
					}
					if ( ! empty( $opts ) ) {
						$placeholders = array_fill( 0, count( $opts ), '%s' );
						$placeholders = implode( ', ', $placeholders );
						$query = "SELECT tbl2.option_id FROM {$wpdb->options} tbl2 WHERE tbl2.option_name IN ( {$placeholders} )";
						$query = $wpdb->prepare( $query, $opts );
						$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $query );
						
						$query = "DELETE FROM {$wpdb->options} WHERE option_name IN ( {$placeholders} )";
						$query = $wpdb->prepare( $query, $opts );
						$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $query );
					} else {
						$this->did_pruning_message[] = __( '<p>For some reason, no transients were found to be deleted.</p>', 'multisite-comment-management' );
					}
				}
				
				$msg = $wpdb->query( $query );
				if ( is_wp_error( $msg ) ) {
					$msg = $msg->get_error_message();
				}
				$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', print_r( $msg, true ) );
				
				restore_current_blog();
			}
		}
		
		delete_site_option( 'ms-comment-management-transient-status' );
		
		add_action( 'did-multisite-transients-prune', array( $this, 'did_multisite_comments_prune_message' ) );
	}
	
	/**
	 * Remove any necessary site transients
	 */
	function delete_site_transients( $transients=array() ) {
		if ( empty( $transients ) )
			return;
		
		global $wpdb;
		
		foreach ( $transients as $network_id=>$options ) {
			$network_id = intval( $network_id );
			$this->did_pruning_message[] = sprintf( '<p>Preparing to review site transients on the network with an ID of %d</p>', $network_id );
			
			if ( isset( $options['all'] ) && intval( $options['all'] ) > 0 ) {
				$query = "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND site_id=%d";
				$query = $wpdb->prepare( $query, '_site_transient_%', $network_id );
			} else if ( isset( $options['expired'] ) && intval( $options['expired'] ) > 0 ) {
				$query = "SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d AND site_id=%d";
				$query = $wpdb->prepare( $query, '_site_transient_timeout_%', current_time( 'timestamp' ), $network_id );
				$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $query );
				
				$option_names = $wpdb->get_col( $query );
				$delete = array();
				foreach ( $option_names as $o ) {
					$delete[] = $o;
					$delete[] = str_replace( '_timeout', '', $o );
				}
				$placeholders = array_fill( 0, count( $delete ), '%s' );
				$placeholders = implode( ', ', $placeholders );
				
				$delete[] = $network_id;
				
				$query = "DELETE FROM {$wpdb->sitemeta} WHERE meta_key IN ( {$placeholders} ) AND site_id=%d";
				$query = $wpdb->prepare( $query, $delete );
				$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $query );
			}
			
			$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', $query );
			
			$msg = $wpdb->query( $query );
			if ( is_wp_error( $msg ) ) {
				$msg = $msg->get_error_message();
			}
			$this->did_pruning_message[] = sprintf( '<pre><code>%s</code></pre>', print_r( $msg, true ) );
			
		}
	}
	
	/**
	 * Output a table showing the current transient status
	 */
	function output_transient_status_table( $transients=array(), $blogs=true ) {
		if ( empty( $transients ) ) 
			return;
		
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		
		require_once( plugin_dir_path( __FILE__ ) . '/_inc/class-ms-transient-status-list-table.php' );
		
		$tmp = $transients;
		$tmp = array_shift( $tmp );
		$checked_time = $tmp['checked'];
		unset( $tmp );
		
		if ( array_key_exists( 'networks', $transients ) && isset( $this->doing_network ) && $this->doing_network ) {
			printf( '<h4>%1$s</h4><p><em>%2$s</em></p>', __( 'Site/Network Transients', 'multisite-comment-management' ), sprintf( __( '*Expired transients were considered expired as of %s', 'multisite-comment-management' ), $checked_time ) );
			$table = new MS_Transient_Status_List_Table();
			$table->prepare_items( $transients['networks'], true );
			$table->display();
			unset( $transients['networks'] );
			return;
		}
		
		if ( array_key_exists( 'networks', $transients ) )
			unset( $transients['networks'] );
		
		printf( '<h4>%1$s</h4><p><em>%2$s</em></p>', __( 'Normal Transients', 'multisite-comment-management' ), sprintf( __( '*Expired transients were considered expired as of %s', 'multisite-comment-management' ), $checked_time ) );
		$table = new MS_Transient_Status_List_Table();
		$table->prepare_items( $transients );
		$table->display();
		
		return;
	}
	
	/**
	 * Check the status of orphaned tables
	 */
	function check_database_status() {
		global $wpdb;
		$tables = $wpdb->get_results( "SHOW TABLES", ARRAY_N );
		
		$sites = $this->gather_sites();
		foreach ( $sites as $k=>$s ) {
			$sites[$k] = $wpdb->base_prefix . $s . '_';
		}
		
		foreach ( $tables as $t ) {
			if ( is_array( $t ) )
				$table = array_pop( $t );
			else
				$table = $t;
			
			$tablename = str_replace( $wpdb->base_prefix, '', $table );
		}
		
		update_site_option( 'ms-comment-management-database-status', $tables );
		
		$this->output_database_status_table( $tables );
	}
	
	/**
	 * Output the cached table information
	 */
	function old_database_status() {
		$tables = get_site_option( 'ms-comment-management-database-status', false );
		if ( empty( $tables ) )
			return;
		
		$tmp = $tables;
		$tmp = array_pop( $tmp );
		printf( __( '<p>The database status information below was last generated on <strong>%s</strong></p>', 'multisite-comment-management' ), $tmp['checked'] );
		$this->output_database_status_table( $tables );
	}
	
	/**
	 * Delete selected tables from the database
	 */
	function delete_tables() {
		return;
	}
	
	/**
	 * Output the table showing table/database status information
	 */
	function output_database_status_table( $tables=array() ) {
		print( '<pre><code>' );
		var_dump( $tables );
		print( '</code></pre>' );
		return;
	}
	
	/**
	 * Check the status of large files
	 */
	function check_files_status() {
	}
	
	/**
	 * Output the cached files information
	 */
	function old_files_status() {
		$files = get_site_option( 'ms-comment-management-files-status', false );
		if ( empty( $files ) )
			return;
		
		$tmp = $files;
		$tmp = array_pop( $tmp );
		printf( __( '<p>The large files status information below was last generated on <strong>%s</strong> and shows files that were larger than %dmegabyts</p>', 'multisite-comment-management' ), $tmp['checked'], intval( $tmp['check-size'] / 1024 ) );
		$this->output_files_status_table( $tables );
	}
	
	/**
	 * Delete selected files from the file system
	 */
	function delete_files() {
		return;
	}
	
	/**
	 * Output the table showing table/database status information
	 */
	function output_files_status_table( $files=array() ) {
		print( '<pre><code>' );
		var_dump( $files );
		print( '</code></pre>' );
		return;
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
	 * Prints script in footer. This 'initialises' the meta boxes
	 * @see http://code.tutsplus.com/articles/integrating-with-wordpress-ui-meta-boxes-on-custom-pages--wp-26843
	 */
	function meta_box_scripts() {
?>
		<script>jQuery( document ).ready( function(){ 
			var MSCMavoidUncheckingAll = false;
			
			postboxes.add_postbox_toggles( '<?php echo $this->pagehook ?>' ); 
			
			jQuery( '.select-all-button input[type="checkbox"]' ).on( 'change', function() {
				if ( true === MSCMavoidUncheckingAll )
					return false;
				
				if ( jQuery( this ).parent().hasClass( 'spam' ) ) {
					jQuery( '.select-one-button.spam input[type="checkbox"], .select-all-button.spam input[type="checkbox"]' ).prop( 'checked', jQuery( this ).is( ':checked' ) );
				} else if ( jQuery( this ).parent().hasClass( 'unapproved' ) ) {
					jQuery( '.select-one-button.unapproved input[type="checkbox"], .select-all-button.unapproved input[type="checkbox"]' ).prop( 'checked', jQuery( this ).is( ':checked' ) );
				} else if ( jQuery( this ).parent().hasClass( 'approved' ) ) {
					jQuery( '.select-one-button.approved input[type="checkbox"], .select-all-button.approved input[type="checkbox"]' ).prop( 'checked', jQuery( this ).is( ':checked' ) );
					if ( jQuery( 'thead .select-all-button.approved input[type="checkbox"]' ).first().is( ':checked' ) ) {
						var confirmSelect = confirm( 'Are you sure you want to delete all approved comments?' );
						if ( confirmSelect == false ) {
							jQuery( '.select-all-button.approved, .select-one-button.approved' ).find( 'input[type="checkbox"]' ).prop( 'checked', false );
							return;
						}
					}
				} else if ( jQuery( this ).parent().hasClass( 'expired' ) ) {
					jQuery(this).closest( 'table' ).find( '.select-one-button.expired input[type="checkbox"], .select-all-button.expired input[type="checkbox"]' ).prop( 'checked', jQuery( this ).is( ':checked' ) );
				} else if ( jQuery( this ).parent().hasClass( 'all' ) ) {
					jQuery(this).closest( 'table' ).find( '.select-one-button.all input[type="checkbox"], .select-all-button.all input[type="checkbox"]' ).prop( 'checked', jQuery( this ).is( ':checked' ) );
					if ( jQuery(this).closest( 'table' ).find( 'thead .select-all-button.all input[type="checkbox"]' ).first().is( ':checked' ) ) {
						var confirmSelect = confirm( 'Are you sure you want to delete all transients, even those that have not yet expired?' );
						if ( confirmSelect == false ) {
							jQuery(this).closest( 'table' ).find( '.select-all-button.all, .select-one-button.all' ).find( 'input[type="checkbox"]' ).prop( 'checked', false );
							return;
						}
					}
				}
			} );
			
			jQuery( '.select-one-button input[type="checkbox"]' ).on( 'change', function() {
				var s = null;
				if ( jQuery( this ).parent().hasClass( 'spam' ) ) {
					s = 'spam';
				} else if ( jQuery( this ).parent().hasClass( 'unapproved' ) ) {
					s = 'unapproved';
				} else if ( jQuery( this ).parent().hasClass( 'approved' ) ) {
					s = 'approved';
				} else if ( jQuery( this ).parent().hasClass( 'expired' ) ) {
					s = 'expired';
				} else if ( jQuery( this ).parent().hasClass( 'all' ) ) {
					s = 'all';
				}
				
				if ( null === s ) {
					return false;
				}
				
				if ( jQuery( this ).closest( 'table' ).find( 'thead .select-all-button.' + s + ' input[type="checkbox"]' ).is( ':checked' ) ) {
					MSCMavoidUncheckingAll = true;
					jQuery( this ).closest( 'table' ).find( '.select-all-button.' + s + ' input[type="checkbox"]' ).prop( 'checked', false );
					MSCMavoidUncheckingAll = false;
					return false;
				}
			} );
		});</script>
<?php
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

/*#ms-comment-mgmt-page-wrapper table {
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
}*/

#ms-comment-mgmt-page-wrapper .number {
	display: block;
	text-align: right;
	margin: 0;
	padding: 0;
}

#ms-comment-mgmt-page-wrapper .sortable .number {
	padding-right: 8px;
}

#ms-comment-mgmt-page-wrapper .column-expired span, 
#ms-comment-mgmt-page-wrapper .column-all span, 
#ms-comment-mgmt-page-wrapper .column-spam span, 
#ms-comment-mgmt-page-wrapper .column-unapproved span, 
#ms-comment-mgmt-page-wrapper .column-approved span {
	float: right;
}

#ms-comment-mgmt-page-wrapper td.column-spam:hover, 
#ms-comment-mgmt-page-wrapper td.column-unapproved:hover, 
#ms-comment-mgmt-page-wrapper td.column-approved:hover {
	background: #ccc;
}

#ms-comment-mgmt-page-wrapper .select-all-button, 
#ms-comment-mgmt-page-wrapper .select-one-button {
	float: right;
	margin: 0 0 0 10px;
	padding: 0;
}
</style>
<?php
	}
	
}