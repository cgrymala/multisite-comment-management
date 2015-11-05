<?php
/**
 * Implement the class to handle listing transient stats
 */
class MS_Transient_Status_List_Table extends WP_List_Table {
	var $doing_network = false;
	
	function get_columns() {
		return apply_filters( 'ms-transient-status-list-table-columns', array(
			'id'      => __( 'ID', 'multisite-comment-management' ), 
			'name'    => __( 'Name', 'multisite-comment-management' ), 
			'expired' => sprintf( '<label class="number"><span class="select-all-button %2$s"><input type="checkbox"/></span>%1$s</label>', __( 'Expired', 'multisite-comment-management' ), 'expired' ), 
			'all'     => sprintf( '<label class="number"><span class="select-all-button %2$s"><input type="checkbox"/></span>%1$s</label>', __( 'All', 'multisite-comment-management' ), 'all' ), 
			'raw'     => __( 'Raw Transient Data', 'multisite-comment-management' ), 
		) );
	}
	
	function get_hidden_columns() {
		return apply_filters( 'ms-transient-status-list-table-hidden', array(
			'raw', 
		) );
	}
	
	function get_sortable_columns() {
		return apply_filters( 'ms-transient-status-list-table-sortable', array(
			'id'   => array( 'id', false ), 
			'name' => array( 'name', false ), 
		) );
	}
	
	function get_column_info() {
		return array(
			$this->get_columns(), 
			$this->get_hidden_columns(), 
			$this->get_sortable_columns(),
		);
	}
	
	function prepare_items( $transients, $is_network=false ) {
		$this->_column_headers = $this->get_column_info();
		usort( $transients, array( &$this, 'usort_reorder' ) );
		$this->items = $transients;
		$this->doing_network = $is_network;
	}
	
	function column_default( $item, $column_name=null ) {
		switch ( $column_name ) {
			case 'raw' : 
				return sprintf( '<pre><code>%s</code></pre>', print_r( $item, true ) );
				break;
			case 'id' : 
				return intval( $item[$column_name] );
				break;
			case 'expired' : 
			case 'all' : 
				return sprintf( '<label class="number"><span class="select-one-button %1$s"><input type="checkbox" name="ms-comment-mgmt[transients]%5$s[%2$d][%1$s]" value="%4$d"/></span>%4$d</label>', $column_name, intval( $item['id'] ), '', intval( $item[$column_name] ), $this->doing_network ? '[networks]' : '' );
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
