<?php
global $wpdb;
global $wp;
$r=new Restaurant;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $report=new ToastReport();
  foreach($_POST['phone'] as $p=>$restaurant){
    $restaurantID=$p;
    foreach ($restaurant as $company => $phoneNumber) {
      $emails=array();
      $orderDetails=array();
//      $emails=array("jon@theproteinbar.com","jcohen@theproteinbar.com","kate@theproteinbar.com");
      $report->setRestaurantID($p);
      $report->setBusinessDate(date("Y-m-d"));
      $orders=$report->getMiniBarOrders($company);
      $r->restaurantID=$restaurantID;
      $toast = new Toast($r->getRestaurantField("GUID"));
      foreach ($orders as $order) {
        $json=$toast->getOrderInfo($order);
        foreach($json->checks as $c){
          $orderDetails[$c->displayNumber]["Name"]=$c->customer->firstName . " " . $c->customer->lastName;
          foreach($c->selections as $s){
            $orderDetails[$c->displayNumber]["Items"][]=$s->displayName;
          }
          $emails[]=$c->customer->email;
        }
      }
      $emails[]="jcohen@theproteinbar.com";
      $packingList['title']="MiniBar Packing List for " . $wpdb->get_var( "SELECT company FROM pbc_minibar WHERE idpbc_minibar='".$company."'") . " " . date("m-d-Y");
      $packingList["html"]="
      <div><h3>" . $packingList['title'] . "</h3>
        <ol>";
      foreach($orderDetails as $orderID => $orderDetail){
        $packingList["html"].="<li><h5># " . $orderID . " for " . $orderDetail['Name'] . "</h5><ul>";
        foreach($orderDetail['Items'] as $item){
          $packingList["html"].="<li>" . $item . "</li>";
        }
        $packingList["html"].="</ul></li>";
      }
      $packingList["html"].="</ol></div>";
      $packingList['format']='A4-P';
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
      if($wpdb->last_error == ''){
        $report->sendText("+1".$phone,"CONFIRM\n".home_url("/deliveryNotify.php?id=".$publicGUID));
        $ret.= "<div class='alert alert-success'>Text with link sent</div>";
      }else {
        $ret.= "<div class='alert alert-danger'>There was an error. ". $wpdb->last_error ."</div>";
      }
      if($pdf=$r->buildHTMLPDF(json_encode($packingList))){
        $current_user = wp_get_current_user();
        $report->reportEmail($current_user->user_email,$packingList["html"],$packingList['title']);
        echo "<div><button class=\"btn btn-primary\" onclick=\"window.open('" . $pdf['Link'] . "', '_blank');\">Download the Packing List</a></div>";
      }
    }
  }
}
foreach ($r->myRestaurants as $restaurantID => $restaurantName) {
  $results=$wpdb->get_results("SELECT company,idpbc_minibar FROM pbc2.pbc_ToastOrderHeaders,pbc_minibar WHERE pbc_ToastOrderHeaders.diningOption=pbc_minibar.outpostIdentifier AND businessDate='".date("Y-m-d")."' AND pbc_ToastOrderHeaders.restaurantID='".$restaurantID."'
  AND idpbc_minibar NOT IN (SELECT outpostID FROM pbc_minibar_deliveries WHERE restaurantID='".$restaurantID."' AND deliveryDate='".date("Y-m-d")."') GROUP BY company,idpbc_minibar");
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
