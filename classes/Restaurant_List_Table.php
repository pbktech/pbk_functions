<?php
class Restaurant_List_Table extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct() {
       parent::__construct( array(
      'singular'=> 'wp_list_text_link', //Singular label
      'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
      'ajax'   => false //We won't support Ajax for this table
      ) );
    }
function extra_tablenav( $which ) {
   if ( $which == "top" ){
      //The code that goes before the table is here
//      echo"Hello, I'm before the table";
   }
   if ( $which == "bottom" ){
      //The code that goes after the table is there
//      echo"Hi, I'm after the table";
   }
}
function get_columns() {
   return $columns= array(
      'col_restaurant_ID'=>__('ID'),
      'col_restaurant_Code'=>__('Code'),
      'col_restaurant_Name'=>__('Name'),
      'col_city'=>__('City'),
      'col_GUID'=>__('GUID'),
      'col_timeZone'=>__('Time Zone'),
      'col_isOpen'=>__('Is Open')
   );
}
public function get_sortable_columns() {
   return $sortable = array(
      'col_restaurant_ID'=>'restaurantID',
      'col_restaurant_Name'=>'restaurantName'
   );
}
function prepare_items() {
   global $wpdb, $_wp_column_headers;
   $screen = get_current_screen();
   /* -- Preparing your query -- */
        $query = "SELECT * FROM pbc_pbrestaurants";
   /* -- Ordering parameters -- */
       //Parameters that are going to be used to order the result
       $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
       $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
       if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
   /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 20;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
       if(!empty($paged) && !empty($perpage)){
          $offset=($paged-1)*$perpage;
         $query.=' LIMIT '.(int)$offset.','.(int)$perpage;
       }

   /* -- Register the pagination -- */
      $this->set_pagination_args( array(
         "total_items" => $totalitems,
         "total_pages" => $totalpages,
         "per_page" => $perpage,
      ) );
      //The pagination links are automatically built according to those parameters

   /* -- Register the Columns -- */
	$columns = $this->get_columns();
	$hidden = array();
	$sortable = $this->get_sortable_columns();
	$this->_column_headers = array($columns, $hidden, $sortable);
   /* -- Fetch the items -- */
   $this->items = $wpdb->get_results($query);
}
function switchisOpen($var) {
	switch($var) {
		case 1: return "Yes"; break;
		case 0: return "No"; break;
	}
}
function column_default( $item, $column_name ) {
  switch( $column_name ) {
    case 'col_restaurant_ID':
    case 'col_restaurant_Code':
    case 'col_restaurant_Name':
    case 'col_city':
    case 'col_timeZone':
    case 'col_GUID':
    case 'col_isOpen':
     return $item[ $column_name ];
    default:
      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
  }
}

function display_rows() {

   //Get the records registered in the prepare_items method
   $records = $this->items;

   //Get the columns registered in the get_columns and get_sortable_columns methods
   list( $columns, $hidden ) = $this->get_column_info();

   //Loop for each record
   if(!empty($records)){foreach($records as $rec){
/*
		echo "<pre>";
		print_r($rec);
		echo "</pre>";
		*/
      //Open the line
        echo '< tr id="record_'.$rec->restaurantID.'">';
      foreach ( $columns as $column_name => $column_display_name ) {

         //Style attributes for each col
         $class = "class='$column_name column-$column_name'";
         $style = "";
         if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
         $attributes = $class . $style;

         //edit link
         $editlink  = '/wp-admin.php?page=pbr-edit-restaurant&link_id='.(int)$rec->restaurantID;

         //Display the cell
         switch ( $column_name ) {
            case "col_restaurant_ID":  echo '< td '.$attributes.'>'.stripslashes($rec->restaurantID).'< /td>';   break;
            case "col_restaurant_Code": echo '< td '.$attributes.'>'.stripslashes($rec->restaurantCode).'< /td>'; break;
            case "col_restaurant_Name": echo '< td '.$attributes.'>'.stripslashes($rec->restaurantName).'< /td>'; break;
            case "col_city": echo '< td '.$attributes.'>'.$rec->city.'< /td>'; break;
            case "col_timeZone": echo '< td '.$attributes.'>'.$rec->timeZone.'< /td>'; break;
            case "col_GUID": echo '< td '.$attributes.'>'.$rec->GUID.'< /td>'; break;
            case "col_isOpen": echo '< td '.$attributes.'>'.$this->switchisOpen($rec->isOpen).'< /td>'; break;
         }
      }

      //Close the line
      echo'< /tr>';
   }}
}
}
