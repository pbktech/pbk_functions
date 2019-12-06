<?php
if ( file_exists( ABSPATH . 'wp-config.php') ) {
require_once( ABSPATH . 'wp-config.php' );
}

require_once( ABSPATH . 'wp-admin/includes/screen.php' );
    add_action('admin_menu', 'pbr_setup_menu');
    add_action( 'admin_post_pbr_save_restaurant_option', 'pbr_update_restaurant' );
    add_action( 'admin_post_pbr_save_nho', 'pbr_update_nho' );
    add_action('admin_post_pbr_nho_attendance_update','pbr_nho_attendance');
    function pbr_setup_menu(){
            add_menu_page( 'Manage PB Restaurants', 'PBR Restaurants', 'manage_options', 'Manage-PB-Restaurants', 'pbr_edit_restaurant');
            add_submenu_page( 'Manage-PB-Restaurants', 'Edit a Restaurant', 'Edit a Restaurant', 'manage_options', 'pbr-edit-restaurant', 'pbr_edit_restaurant' );
            add_submenu_page( 'Manage-PB-Restaurants', 'Add a Restaurant', 'Add a Restaurant', 'manage_options', 'pbr-add-restaurant', 'pbr_add_restaurant' );
            add_submenu_page( 'Manage-PB-Restaurants', 'Manage NHO Events', 'Manage NHO Events', 'manage_options', 'pbr-nho', 'pbr_nho_setup' );
            add_submenu_page( 'Manage-PB-Restaurants', 'NHO Archive', 'NHO Archive', 'manage_options', 'pbr-nho-archive', 'pbr_nho_history' );
    }

    function pbr_admin_init(){
    }
    function pbr_add_restaurant(){
      echo "<h2>Add a Restaurant</h2>";
   	$restaurant = new Restaurant();
   	echo $restaurant->restaurantEditBox();
   	/* TEST*/
    }
function pbr_edit_restaurant(){
	if(!class_exists('WP_List_Table')){
	   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
   echo "<div class=\"wrap\"><div id=\"icon-users\" class=\"icon32\"></div><h2>Edit an Existing Restaurant <a href=\"?page=pbr-add-restaurant\" class=\"add-new-h2\">Add New</a>
            </h2>
            </div>";
	if(!isset($_GET['restaurant']) && !is_numeric()) {
		require_once( 'admin/testlisttable.php' );
	   $myListTable = new My_Example_List_Table();
		$myListTable->prepare_items();
		$myListTable->display();
//		echo '</div>';
	}else {
   	$restaurant = new Restaurant($_GET['restaurant']);
   	echo $restaurant->restaurantEditBox();
	}
//	echo '</div>';
}
function pbr_update_restaurant() {
	print_r($_POST);
   	$restaurant = new Restaurant();
   	$restaurant->setRestaurantInfo($_POST);
//   	print_r($restaurant->rinfo);
//   	die();
   	if($restaurant->insertUpdateRestaurantInfo()) {
   		$m=1;
   	}else {
   		$m=2;
   	}
   	wp_redirect(  admin_url( 'admin.php?page=pbr-edit-restaurant&m='.$m ) );
   	exit;
   	//$restaurant->restaurantEditBox();
}
function pbr_nho_setup(){
  echo "<div class=\"wrap\"><div id=\"icon-users\" class=\"icon32\"></div><h2>NHO Events <a href=\"?page=pbr-nho&amp;nhoDate=_new\" class=\"add-new-h2\">Add New</a>
           </h2>
           </div>";
  $restaurant = new Restaurant();
  if(isset($_GET['nhoDate']) && $_GET['nhoDate']=="_new"){
    echo $restaurant->nho_sign_up_manage();
  }elseif(isset($_GET['nhoDate']) && isset($_GET['nhoLocation'])) {
    echo $restaurant->nho_sign_up_manage($_GET);
  }else{
    require_once( 'admin/nhoList.php' );
    $myListTable = new nhoList();
    $myListTable->prepare_items();
    $myListTable->display();
  }
}
function pbr_update_nho() {
  $restaurant = new Restaurant();
  $restaurant->updateNHO($_POST);
}
function pbr_nho_attendance(){
  $restaurant = new Restaurant();
  $restaurant->updateNHOAttendance($_POST);
  wp_redirect(  admin_url( 'admin.php?page=pbr-nho&nhoDate='.date("y-m-d",strtotime($_POST['nhoDate'])).'&nhoLocation='.$_POST['nhoLocation'].'&r=0' ) );
  exit;
}
function pbr_nho_history(){
  $restaurant = new Restaurant();
  echo $restaurant->nhoHistory($_GET);
}
