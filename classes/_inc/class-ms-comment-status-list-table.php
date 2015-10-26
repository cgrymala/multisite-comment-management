<?php
/**
 * Implement the class to handle listing comment stats
 */
class MS_Comment_Status_List_Table extends WP_List_Table {
	public $comments = array();
	
	function get_columns() {
		return apply_filters( 'ms-comment-status-list-table-columns', array(
			'id'         => __( 'ID', 'multisite-comment-management' ), 
			'name'       => __( 'Name', 'multisite-comment-management' ), 
			'spam'       => sprintf( '<label class="number"><span class="select-all-button %2$s"><input type="checkbox"/></span>%1$s</label>', __( 'Spam', 'multisite-comment-management' ), 'spam' ), 
			'unapproved' => sprintf( '<label class="number"><span class="select-all-button %2$s"><input type="checkbox"/></span>%1$s</label>', __( 'Unapproved', 'multisite-comment-management' ), 'unapproved' ), 
			'approved'   => sprintf( '<label class="number"><span class="select-all-button %2$s"><input type="checkbox"/></span>%1$s</label>', __( 'Approved', 'multisite-comment-management' ), 'approved' ), 
			/*'raw'        => __( 'Raw Data', 'multisite-comment-management' ), */
		) );
	}
	
	function get_sortable_columns() {
		return apply_filters( 'ms-comment-status-list-table-sortable', array(
			'id' => array( 'id', false ), 
			'name' => array( 'name', false )
		) );
	}
	
	function get_column_info() {
		return array(
			$this->get_columns(), 
			array(), 
			$this->get_sortable_columns(),
		);
	}
	
	function prepare_items( $comments=array() ) {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = $this->get_column_info();
		usort( $comments, array( &$this, 'usort_reorder' ) );
		$this->items = $comments;
	}
	
	function column_default( $item, $column_name=null ) {
		switch ( $column_name ) {
			case 'raw' : 
				return sprintf( '<pre><code>%s</code></pre>', print_r( $item, true ) );
				break;
			case 'id' : 
				return intval( $item[$column_name] );
				break;
			case 'spam' : 
			case 'unapproved' : 
			case 'approved' : 
				return sprintf( '<label class="number"><span class="select-one-button %1$s"><input type="checkbox" name="ms-comment-mgmt[comments][%2$d][%1$s]" value="%4$d"/></span>%4$d</label>', $column_name, intval( $item['id'] ), '', intval( $item[$column_name] ) );
				break;
			default : 
				return esc_attr( $item[$column_name] );
				break;
		}
	}
	
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'id';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		switch( $orderby ) {
			case 'id' :
				$result = intval( $a[$orderby] ) < intval( $b[$orderby] ) ? -1 : ( intval( $a[$orderby] ) > intval( $b[$orderby] ) ? 1 : 0 );
				break;
			default :
				$result = strcmp( $a[$orderby], $b[$orderby] );
				break;
		}
		
		return 'asc' == $order ? $result : ( $result * -1 );
	}
}