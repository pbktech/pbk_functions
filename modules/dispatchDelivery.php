<?php
global $wpdb;
global $wp;
if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $toast = new Toast();
  $report=new ToastReport();
  foreach($_POST['phone'] as $p=>$restaurant){
    $restaurantID=$restaurant;
    foreach ($restaurant as $company => $phoneNumber) {
      $emails=array("jon@theproteinbar.com");
      $publicGUID=$toast->genGUID(microtime());
      $phone=preg_replace("/[^0-9]/", "",$phoneNumber);
      $cu = wp_get_current_user();
      $wpdb->insert(	"pbc_minibar_deliveries",
		array(
      "publicGUID"=>$publicGUID,
      "restaurantID"=>$restaurantID,
      "userID"=>$cu->ID,
      "deliveryDate"=>date("Y-m-d"),
      "initiated"=>date("Y-m-d H:i:s"),
      "recipients"=>json_encode($emails),
      "outpostID"=>$company
    ),
		array( "%s", "%s", "%s", "%s", "%s", "%s", "%s" )
  );
    if($wpdb->last_error !== ''){
      $thisYear->sendText("+1".$phone,home_url("/deliveryNotify.php?id=".$publicGUID));
      echo "<div class='alert alert-success>Text with link sent</div>'";
    }else {
    echo "<div class='alert alert-danger>Tehre was an error. ". $wpdb->last_error ."</div>'";
    }
    }
  }
}
$r=new Restaurant;
foreach ($r->myRestaurants as $restaurantID => $restaurantName) {
  //Added for testing
  if($restaurantID!=22){continue;}
  $results=$wpdb->get_results("SELECT company,idpbc_minibar FROM pbc2.pbc_ToastOrderHeaders,pbc_minibar WHERE pbc_ToastOrderHeaders.diningOption=pbc_minibar.outpostIdentifier AND businessDate='".date("Y-m-d")."' AND pbc_ToastOrderHeaders.restaurantID='".$restaurantID."'
  AND idpbc_minibar NOT IN (SELECT outpostID FROM pbc_minibar_deliveries WHERE restaurantID='".$restaurantID."' AND deliveryDate='".date("Y-m-d")."') GROUP BY company,idpbc_minibar");
  //Added for testing
  $results = (object) array("r"=>(object) array("company"=>"Bain & Co Chicago","idpbc_minibar"=>1));
  if($results){
    $ret.="<div class='container'><h3>".$restaurantName."</h3>
    <form method='post' action='".home_url( add_query_arg( array(), $wp->request ) )."' id='' >
      ";
      foreach($results as $result){
        $ret.="
        <div class=\"form-group\">
          <label for='PBK".$restaurantID."-".$result->idpbc_minibar."'>".$result->company."</label><input type='text' class='form-control' name='phone[".$restaurantID."][".$result->idpbc_minibar."]' id='PBK".$restaurantID."-".$result->idpbc_minibar."' placeholder='Phone Number' />
        </div>";
      }
    $ret.="
      <div class=\"form-group\">
        <button type=\"submit\" class=\"btn btn-primary\"/>Submit</button>
      </div>
    </form></div>";
  }else {
    $ret.="<div class='alert alert-warning'>There were no orders for ".$restaurantName.".</div>";
  }
}
