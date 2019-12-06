<?php
/*
Plugin Name: Test List Table Example
*/

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class nhoList extends WP_List_Table {
    function __construct(){
    global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'nhoevent', 'nhotablelist' ),     //singular name of the listed records
            'plural'    => __( 'nhoevents', 'nhotablelist' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
    ) );
    }

  function column_default( $item, $column_name ) {
    switch( $column_name ) {
        case 'nhoDate':
        case 'nhoHost':
        case 'nhoLocation':
        case 'nhoAttendees':
            return $item[ $column_name ];
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    }
  }

function get_columns(){
        $columns = array(
            'nhoDate' => __( 'Date', 'nhotablelist' ),
            'nhoHost'    => __( 'Host', 'nhotablelist' ),
            'nhoLocation'    => __( 'Location', 'nhotablelist' ),
            'nhoAttendees'    => __( 'Attendees', 'nhotablelist' ),
        );
         return $columns;
    }
function no_items() {
  _e( 'No NHO events found, dude.' );
}
function column_nhoDate($item) {
  $actions['edit']= sprintf('<a href="?page=%s&amp;nhoDate=%s&amp;nhoLocation=%s&amp;r=0">Edit</a>','pbr-nho',$item['nhoDate'],$item['nhoLocation']);
  $actions['roster']=sprintf('<a href="?page=%s&amp;nhoDate=%s&amp;nhoLocation=%s&amp;r=1">Roster</a>','pbr-nho',$item['nhoDate'],$item['nhoLocation']);
  return sprintf('%1$s %2$s', date("m/d/Y",strtotime($item['nhoDate'])), $this->row_actions($actions) );
}
function column_nhoHost($item) {
  global $wpdb;
  return $wpdb->get_var( 'SELECT display_name FROM pbc_users WHERE ID = "'.$item['nhoHost'].'"');
}
function column_nhoAttendees($item) {
  global $wpdb;
  return $wpdb->get_var( 'SELECT COUNT(*) FROM pbc_nhoAttenndess WHERE nhoID = "'.$item['nhoID'].'"');
}
function column_nhoLocation($item) {
  global $wpdb;
  return $wpdb->get_var( 'SELECT restaurantName FROM pbc_pbrestaurants WHERE restaurantID = "'.$item['nhoLocation'].'"');
}
function prepare_items() {
  $columns  = $this->get_columns();
  $hidden   = array();
  $sortable = array();
  $this->_column_headers = array( $columns, $hidden, $sortable );
  require_once("Restaurant.php");
  $restaurant=new Restaurant();
  $this->items = $restaurant->getNHOEvents();
}

} //class

function my_nholist_list_page(){
  $myListTable = new nhoList();
  $myListTable->prepare_items();
  $myListTable->display();
}
