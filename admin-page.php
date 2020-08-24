<?php
if ( file_exists( ABSPATH . 'wp-config.php') ) {
require_once( ABSPATH . 'wp-config.php' );
}
if(!function_exists('wp_get_current_user')) {
    include(ABSPATH . "wp-includes/pluggable.php");
}
global $wp;
$cu = wp_get_current_user();
add_action('admin_enqueue_scripts', 'pbk_scripts');
require_once( ABSPATH . 'wp-admin/includes/screen.php' );
add_action('admin_menu', 'pbr_setup_menu');
add_action( 'admin_post_pbr_save_restaurant_option', 'pbr_update_restaurant' );
add_action( 'admin_post_pbr_save_nho', 'pbr_update_nho' );
add_action( 'admin_post_pbk-save-devices', 'pbr_edit_devices' );
add_action('admin_post_pbr_nho_attendance_update','pbr_nho_attendance');
add_action('admin_post_pbk_save_minibar','pbk_saveMinibar');
add_action('admin_post_pbk-update-order','pbr_orders');
$pbkAdminPages[]=array("Name"=>"Manage Restaurants","Access"=>"manage_options","Slug"=>"pbr-edit-restaurant","Function"=>"pbr_edit_restaurant");
$pbkAdminPages[]=array("Name"=>"Manage NHO Events","Access"=>"manage_options","Slug"=>"pbr-npbr-edit-restaurantho","Function"=>"pbr_nho_setup");
$pbkAdminPages[]=array("Name"=>"NHO Archive","Access"=>"delete_posts","Slug"=>"pbr-nho-archive","Function"=>"pbr_nho_history");
$pbkAdminPages[]=array("Name"=>"Incident Archive","Access"=>"upload_files","Slug"=>"pbr-incident-history","Function"=>"pbr_search_incident");
$pbkAdminPages[]=array("Name"=>"Manage MiniBar","Access"=>"manage_options","Slug"=>"pbr-edit-minibar","Function"=>"pbr_edit_minibar");
$pbkAdminPages[]=array("Name"=>"Manage Devices","Access"=>"manage_options","Slug"=>"pbr-edit-devices","Function"=>"pbr_edit_devices");
$pbkAdminPages[]=array("Name"=>"Restaurant Orders","Access"=>"upload_files","Slug"=>"pbr-orders","Function"=>"pbr_orders");
$pbkAdminPages[]=array("Name"=>"Health Screen Archive","Access"=>"upload_files","Slug"=>"pbr-hs-archive","Function"=>"pbr_hs_archive");
function pbr_setup_menu(){
  global $pbkAdminPages;
  add_menu_page( 'PBK Functions', 'PBK Functions', 'delete_posts', 'Manage-PBK', 'pbr_show_admin_functions',PBKF_URL . '/assets/images/PBK-Logo-ONLY-LG-2018_White_new.png');
  foreach ($pbkAdminPages as $value) {
    add_submenu_page('Manage-PBK',$value['Name'],$value['Name'],$value['Access'],$value['Slug'],$value['Function']);
  }
}
function wpb_custom_toolbar_link($wp_admin_bar) {
  global $pbkAdminPages;
  $wp_admin_bar->remove_node('wp-logo');
    $args = array(
        'id' => 'pbkfunctions',
        'title' => 'PBK Functions',
        'href' => admin_url( 'admin.php?page=Manage-PBK' ),
        'meta' => array(
            'class' => '',
            'title' => 'Edit PBK Settings'
            )
    );
    $wp_admin_bar->add_node($args);
    foreach ($pbkAdminPages as $value) {
      $args = array(
          'id' => $value['Slug'],
          'title' => $value['Name'],
          'parent' => 'pbkfunctions',
          'href' => admin_url( 'admin.php?page=' . $value['Slug'] ),
          'meta' => array(
              'class' => '',
              'title' => $value['Name']
              )
      );
      $wp_admin_bar->add_node($args);
    }
}
if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
  add_action('admin_bar_menu', 'wpb_custom_toolbar_link', 999);
}
function pbr_admin_init(){
}
function pbr_edit_restaurant(){

	if(!class_exists('WP_List_Table')){
	   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
   echo "<div class=\"wrap\"><div id=\"icon-users\" class=\"icon32\"></div><h2>Manage Restaurants <a href=\"?page=pbr-edit-restaurant&amp;restaurant=_NEW\" class=\"add-new-h2\">Add New</a>
            </h2>
            ";
  if ( isset( $_GET['m'] ) ){
    switchpbrMessages($_GET['m']);
  }
	if(isset($_GET['restaurant']) && is_numeric($_GET['restaurant'])) {
    $restaurant = new Restaurant($_GET['restaurant']);
    echo "<h2>".$restaurant->rinfo->restaurantName."</h2>";
   	echo $restaurant->restaurantEditBox();
	}elseif(isset($_GET['restaurant']) && $_GET['restaurant']=="_NEW") {
    echo "<h2>New Restaurant</h2>";
    $restaurant = new Restaurant();
    echo $restaurant->restaurantEditBox();
  }else {
    require_once( 'classes/testlisttable.php' );
	  $myListTable = new My_Example_List_Table();
		$myListTable->prepare_items();
		$myListTable->display();
	}
  echo "</div>";
}

function pbr_add_restaurant(){

  echo "<div class='wrap'><h2>Add a Restaurant</h2>";
  $restaurant = new Restaurant();
  echo $restaurant->restaurantEditBox();
  echo "</div>";
}
function pbr_orders(){
  echo "<div class='wrap'><h2>PBK Restaurant Supply Orders</h2>
  <div id='ServerResponse'></div>
  ";
  $restaurant = new Restaurant();
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    global $wpdb;
    $wpdb->update(
	'pbc_pbk_orders',
	   array('orderStatus' => $_POST['orderStatus']),
	   array('idpbc_pbk_orders' => $_POST['id']),
	   array('%s'),
	   array('%d')
    );
    $attach=$restaurant->showOrderInfo($_POST['id'],1);
    $report=new ToastReport;
    switch($_POST['orderStatus']){
      case "Shipped":
        $body="The attached order has been shipped to you restaurant.";
        break;
      case "Pickup":
        $body="The attached order is ready to be picked up at the SSC.";
        break;
      case "Cancel":
        $body="The attached order has been canceled.";
        break;
    }
    $email=$wpdb->get_var("SELECT email FROM pbc_pbk_orders,pbc_pbrestaurants WHERE idpbc_pbk_orders = '".$_POST['id']."' AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID");
    $report->reportEmail($email.",laura@theproteinbar.com",$body,"Order Updated",$attach);
    wp_redirect(  admin_url( 'admin.php?page=pbr-orders&type='.$_POST['type'].'&m=7' ) );
  }
  if(isset($_GET['type']) && array_key_exists($_GET['type'],$restaurant->orderTypes)){
    echo "<h3>".$restaurant->orderTypes[$_GET['type']]."</h3>";
    if(isset($_GET['id'])){
      echo  $restaurant->showOrderInfo($_GET['id']);
    }else{
      echo "<div class='container-fluid'>".$restaurant->showRestaurantOrders()."</div>";
    }
  }else{
    echo "<h3>Please Select an Order Type:</h3>
    <div class='container'><form  method='get' action='".admin_url( 'admin.php')."'><input type='hidden' name='page' value='pbr-orders' />" . $restaurant->buildSelectBox(array("Options"=>$restaurant->orderTypes,"Field"=>"type","Multiple"=>"","ID"=>"type","Change"=>"this.form.submit()")) . "</form></div>";
  }
  echo "</div>";
}
function pbr_hs_archive(){
  require "admin_mods/pbr_hs_archive.php";
}
add_action( 'wp_ajax_hs_send', 'pbk_hs_send' );
add_action( 'wp_ajax_nopriv_hs_send', 'pbk_hs_send' );
function pbk_hs_send() {
	global $wpdb;
  exit(implode(",",$_POST));
	wp_die();
}

function pbr_edit_devices(){

  $restaurant = new Restaurant();
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $restaurant->pbkSaveDevice($_POST);
  }
  echo "<div class='wrap'><div id=\"icon-users\" class=\"icon32\"></div><h2>Manage Devices<a href=\"?page=pbr-edit-devices&amp;id=_NEW\" class=\"add-new-h2\">Add New Device</a></h2>";
  if(isset($_GET['id'])){
    echo $restaurant->pbk_device_editor($_GET['id']);
  }else {
    if ( isset( $_GET['m'] ) ){
      switchpbrMessages($_GET['m']);
    }
    $report= new ToastReport;
    echo $report->showResultsTable($restaurant->pbk_listDevices());
  }
  echo "</div>";
}
function pbr_edit_minibar(){

  echo "<div class='wrap'><div id=\"icon-users\" class=\"icon32\"></div><h2>Manage MiniBar <a href=\"?page=pbr-edit-minibar&amp;id=_NEW\" class=\"add-new-h2\">Add New MiniBar Location</a></h2>
  ";
  if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    if ( isset( $_GET['m'] ) ){
      switchpbrMessages($_GET['m']);
    }
    $restaurant = new Restaurant();
    if($mb=$restaurant->getMiniBarInformation($_GET['id'])){
      echo "<h2>".$mb['company']."</h2>";
      echo $restaurant->showMiniBarBuilder($mb);
    }
  }elseif(isset($_GET['id']) && $_GET['id']=="_NEW") {
    echo "<h2>New MiniBar Location</h2>";
    $restaurant = new Restaurant();
    echo $restaurant->showMiniBarBuilder();
  }else {
    $restaurant = new Restaurant();
    $report= new ToastReport;
    echo $report->showResultsTable($restaurant->getMiniBarLocations());
	}
  echo "</div>";
}
    function pbr_show_admin_functions(){
      global $submenu;
      echo "
      <div class='wrap'>
      <h2>".esc_html( get_admin_page_title() )."</h2>
        <div class='container-fluid' style='width:100%;'>
          <div class='row'>
            <div class='col'>
              <ul class='nav flex-column'>";
          foreach($submenu['Manage-PBK'] as $m){
            if($m[2]!='Manage-PBK'){
            echo  "
                <li class='nav-item'><a class='nav-link' href='" . admin_url( 'admin.php?page='.$m[2]) . "'>".$m[0]."</a></li>";
          }}
            echo "
                </ul>
              </div>
            </div>
            <div class='col'></div>
          </div>
        </div>
      ";
    }
  function pbr_incident_pdf(){

    global $wpdb;
    $r=$wpdb->get_row("SELECT * FROM pbc_incident_reports WHERE id_pbc_incident_reports='".$_GET['incident']."'");
    if(!$r){
      echo "<div class='wrap'><div class='alert alert-warning'>Report Not Found</div></div>";
      exit;
    }
    $restaurant = new Restaurant();
    include dirname(__FILE__) . '/modules/forms/incident_header.php';
    include dirname(__FILE__) . '/modules/forms/foodborneIllness.php';
    include dirname(__FILE__) . '/modules/forms/injury.php';
    include dirname(__FILE__) . '/modules/forms/lostStolenProperty.php';
    $ih["reporterName"]=$r->reporterName;
    $ih["startDate"]=$r->dateOfIncident;
    $ih["timeOfIncident"]=$r->dateOfIncident;
    $ih["restaurantID"]=$r->restaurantID;
    $ih['guest']=json_decode($r->guestInfo,true);
    $content['format']='A4-P';
    $content['title']=$restaurant->incidentTypes[$r->incidentType]["Name"] . ' Incident Report ' . $ih['restaurantID'] . "-" . date("Ymd",strtotime($r->dateOfIncident));
    $content['html']=pbk_form_incident_header($ih)."<h3>" . $restaurant->incidentTypes[$r->incidentType]["Name"] . "</h3>";
    switch($r->incidentType){
      case "foodborneIllness":
        $content['html'].=pbk_form_foodborneIllness(json_decode($r->reportInfo,true));
        break;
      case "injury":
        $content['html'].=pbk_form_injury(json_decode($r->reportInfo,true));
        break;
      case "lostStolenProperty":
        $content['html'].=pbk_form_lostStolenProperty(json_decode($r->reportInfo,true));
        break;
    }
    $return= "<div class='wrap'><div class='container'>" . $content['html'];
    if($link=$restaurant->buildHTMLPDF(json_encode($content))){
      $return.= "<div class='row'><div class='col'><a href='".$link['Link']."' target='_blank'>Download PDF</a></div></div>";
    }
    $return.="</div></div>";
    return $return;
  }
  function pbr_search_incident(){
  $restaurant = new Restaurant();
  if(isset($_GET['incident'])){
    if($pdf=pbr_incident_pdf()){
      echo $pdf;
    }else {
      echo "<div class='wrap'><div class='alert alert-danger'>PDF Not Generated</div></div>";
    }
    exit;
  }
  echo "
  <div class='wrap'>
    <h2>Incident Archive</h2>
      <div class='container-fluid' style='width:100%;'>
      <div class='container'>
        <form method='get' action='". admin_url( 'admin.php')."' >
          <input type='hidden' name='page' value='pbr-incident-history' />
          <div class=\"form-group\">
            <div class='row'>
              <div class='col'>
                " . $restaurant->buildDateSelector('startDate',"Starting Date") . "
              </div>
              <div class='col'>
                " . $restaurant->buildDateSelector('endDate',"Ending Date") . "
              </div>
            </div>
          </div>
          <div class=\"form-group\">
            <input id='submit' type='submit' value='SEARCH' />
          </div>
        </form>
        </div>
    " . $restaurant->pbk_form_processing();
  if(isset($_GET['startDate']) && isset($_GET['endDate'])){
    echo "
    <script>
    jQuery(document).ready( function () {
        jQuery('#myTable').DataTable();
        jQuery(\".itemName\").on(\"click\", function(e) {
          jQuery(\"#report_\" + e.target.id).show();
        })
    } );
    </script>
    <div id='queryResults'>
    ";
    if($results=$restaurant->get_incident_reports()){
      $data['File']="Incident_List_";
      $data['Headers']=array("Restaurant","Incident Date","Reported By","Incident Type","Reported Date","");
      foreach($results as $r){
        $download="<a href='" . admin_url( 'admin.php?page=pbr-incident-history&amp;incident=' . $r->id_pbc_incident_reports) . "' target='_blank'>View/Download</a>";
        $data['Results'][]=array(
          $restaurant->getRestaurantName($r->restaurantID),
          date("m/d/Y",strtotime($r->dateOfIncident)),
          $r->reporterName,
          $restaurant->incidentTypes[$r->incidentType]["Name"],
          date("m/d/Y",strtotime($r->reportAdded)),
          $download
        );
      }
      $report= new ToastReport;
      echo $report->showResultsTable($data);
    }else {
      echo "<div class='alert alert-warning'>There were no reports found for " . $_GET['startDate'] . " - " . $_GET['endDate'] . "</div>";
    }
    echo "</div>";
  }
  echo "
    </div>
  </div>";
}
function pbr_update_restaurant() {

   	$restaurant = new Restaurant();
   	$restaurant->setRestaurantInfo($_POST);
   	if($restaurant->insertUpdateRestaurantInfo()) {
   		$m=1;
   	}else {
   		$m=2;
   	}
   	wp_redirect(  admin_url( 'admin.php?page=pbr-edit-restaurant&m='.$m ) );
   	exit;
}
function pbr_nho_setup(){

  echo "<div class=\"wrap\">
          <div id=\"icon-users\" class=\"icon32\"></div>
            <h2>NHO Events <a href=\"?page=pbr-nho&amp;nhoDate=_new\" class=\"add-new-h2\">Add New</a>
           </h2>
           ";
  $restaurant = new Restaurant();
  if(isset($_GET['nhoDate']) && $_GET['nhoDate']=="_new"){
    echo $restaurant->nho_sign_up_manage();
  }elseif(isset($_GET['nhoDate']) && isset($_GET['nhoLocation'])) {
    echo $restaurant->nho_sign_up_manage($_GET);
  }else{
    require_once( 'classes/nhoList.php' );
    $myListTable = new nhoList();
    $myListTable->prepare_items();
    $myListTable->display();
  }
  echo "</div>";
}
function pbr_update_nho() {
  $restaurant = new Restaurant();
  $restaurant->updateNHO($_POST);
}
function pbk_saveMinibar() {
  $restaurant = new Restaurant();
  $restaurant->pbkSaveMinibar($_POST);
}
function pbr_nho_attendance(){
  $restaurant = new Restaurant();
  $restaurant->updateNHOAttendance($_POST);
  wp_redirect(  admin_url( 'admin.php?page=pbr-nho&nhoDate='.date("y-m-d",strtotime($_POST['nhoDate'])).'&nhoLocation='.$_POST['nhoLocation'].'&r=0' ) );
  exit;
}
function pbr_nho_history(){
  echo "<div class='wrap'><h2>View the NHO History for the last 3 months.</h2>";
  $restaurant = new Restaurant();
  echo $restaurant->nhoHistory($_GET);
  echo "</div>";
}
