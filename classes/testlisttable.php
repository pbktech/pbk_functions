<?php
/*
Plugin Name: Test List Table Example
*/

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class My_Example_List_Table extends WP_List_Table {
    function __construct(){
    global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'restaurant', 'restaurantlisttable' ),     //singular name of the listed records
            'plural'    => __( 'restaurants', 'restaurantlisttable' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
    ) );
    }

  function column_default( $item, $column_name ) {
    switch( $column_name ) {
        case 'restaurantID':
        case 'restaurantCode':
        case 'restaurantName':
        case 'isOpen':
        case 'city':
        case 'timeZone':
        case 'GUID':
            return $item[ $column_name ];
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    }
  }

function get_columns(){
        $columns = array(
            'restaurantID' => __( 'Compeat ID', 'restaurantlisttable' ),
            'restaurantCode'    => __( 'Code', 'restaurantlisttable' ),
            'restaurantName'    => __( 'Name', 'restaurantlisttable' ),
            'isOpen'    => __( 'Open', 'restaurantlisttable' ),
            'city'      => __( 'City', 'restaurantlisttable' ),
            'timeZone'      => __( 'Time Zone', 'restaurantlisttable' ),
            'GUID'      => __( 'Toast GUID', 'restaurantlisttable' )
        );
         return $columns;
    }
function no_items() {
  _e( 'No books found, dude.' );
}
function column_restaurantID($item) {
  $actions = array(
            'edit'      => sprintf('<a href="?page=%s&restaurant=%s">Edit</a>','pbr-edit-restaurant',$item['restaurantID'])
        );

  return sprintf('%1$s %2$s', $item['restaurantID'], $this->row_actions($actions) );
}
function column_isOpen($item) {
  switch($item['isOpen']) {
		case 1: return "Yes"; break;
		case 0: return "No"; break;
	}
//  return sprintf('%1$s %2$s', $item['restaurantID'], $this->row_actions($actions) );
}
function prepare_items() {
  $columns  = $this->get_columns();
  $hidden   = array();
  $sortable = array();
  $this->_column_headers = array( $columns, $hidden, $sortable );
  global $wpdb;
  $query = "SELECT * FROM pbc_pbrestaurants WHERE archived=0";
  $this->items = $wpdb->get_results($query, ARRAY_A);;
}

} //class

function my_render_list_page(){
  $myListTable = new My_Example_List_Table();
  $return= '</pre><div class="wrap"><h2>My List Table Test</h2>';
  $myListTable->prepare_items();
  $myListTable->display();
  echo '</div>';
}
