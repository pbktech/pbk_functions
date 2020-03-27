<?php
/*
Plugin Name: Test List Table Example
*/

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class mbList extends WP_List_Table {
    function __construct(){
    global $status, $page;
        parent::__construct( array(
            'singular'  => __( 'MiniBar Location', 'mbtablelist' ),     //singular name of the listed records
            'plural'    => __( 'MiniBar Locations', 'mbtablelist' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
    ) );
    }

  function column_default( $item, $column_name ) {
    switch( $column_name ) {
        case 'restaurantName':
        case 'company':
          return $item[ $column_name ];
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    }
  }

function get_columns(){
        $columns = array(
          'restaurantName' => __( 'Restaurant Name', 'mbtablelist' ),
          'company' => __( 'MiniBar Name', 'mbtablelist' )
        );
         return $columns;
    }
function no_items() {
  _e( 'No MiniBar Locations found, dude.' );
}
function column_restaurantName($item) {
  $actions['edit']= sprintf('<a href="?page=%s&amp;id=%s">Edit</a>','pbr-edit-minibar',$item['idpbc_minibar']);
  return sprintf('%1$s %2$s', $item['restaurantName'], $this->row_actions($actions) );
}
function column_company($item) {
  return $item['company'];
}
function prepare_items() {
  $columns  = $this->get_columns();
  $hidden   = array();
  $sortable = array("restaurantName","company");
  $this->_column_headers = array( $columns, $hidden, $sortable );
  require_once("Restaurant.php");
  $restaurant=new Restaurant();
  $this->items = $restaurant->getMiniBarLocations();
}

} //class

function my_mblist_list_page(){
  $myListTable = new mbList();
  $myListTable->prepare_items();
  $myListTable->display();
}
