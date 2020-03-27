<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$r=new Restaurant;
if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $r->setRestaurantID($_POST['restaurantID']);
  $_POST['orderData']['startDate']=$_POST['startDate'];
  $wpdb->query(
	$wpdb->prepare(
		"INSERT INTO pbc_pbk_orders (orderType,restaurantID,userID,orderData,orderDate,orderStatus)VALUES(%s,%d,%d,%s,%s,%s)",
	        "KeyRelease", $_POST['restaurantID'],get_current_user_id(),json_encode($_POST['orderData']),date("Y-m-d H:i:s"),"Complete"
    )
  );
  if($wpdb->last_error==''){
    $guid=$wpdb->get_var("SELECT guid FROM pbc_pbk_orders WHERE idpbc_pbk_orders = '".$wpdb->insert_id."'");
  }else {
    exit;
  }
  $report=New ToastReport;
  $docFolder=dirname(dirname($report->docSaveLocation)) ."/docs/". $guid;
  require_once dirname(dirname(__FILE__)) . "/classes/signature-to-image.php";
  if (!file_exists($docFolder)) {mkdir($docFolder);}
  $_POST['orderData']['nameSign']=saveSignImage($_POST['orderData']['nameSign'],$docFolder);
  $_POST['orderData']['mgrSign']=saveSignImage($_POST['orderData']['mgrSign'],$docFolder);
  $wpdb->update(
	'pbc_pbk_orders',
	array('orderData' =>json_encode($_POST['orderData'])),
  array( 'idpbc_pbk_orders' => $wpdb->insert_id ),
  array('%s'),
  array('%d')
);
  if($wpdb->last_error==''){
    $html="<div>There has been a new key release issued for ".$r->getRestaurantField("restaurantName").".";
    $current_user = wp_get_current_user();
    $attach=$r->viewKeyRelease($guid,1);
    $report->reportEmail($current_user->user_email.",laura@theproteinbar.com",$html,"New Key Release",$attach['Local']);
    switchpbrMessages(8);
  }
}
$ret.=$r->pbk_form_processing().'
<div class="container-fluid" id="queryResults" >
  <div class="row">
    <div class="col">
      <p>I acknowledge that I have received a copy of the key for my restaurant. I understand that this key is Protein Bar & Kitchen property and that I am responsible for this key as long asI am employed with the company. I will not make copies of this key for any reason.</p>
      <p>At the timemy employment at Protein Bar & Kitchen ends, I will return the key to my manager on or before my last day of work. If I lose this key for any reason, I will immediately report the loss to my manager. I understand that I may be responsible for any and all replacement costs associated with the loss of my key as deemed necessary by Protein Bar & Kitchen.</p>
    </div>
  </div>
</div>
<form method="post" action="'.get_permalink().'"  enctype="multipart/form-data" >
  <div class="container-fluid" >
    <div class="row">
      <div class="col"><label for="restaurantID">Restaurant</label>'.$r->buildRestaurantSelector().'</div>
      <div class="col"><label for="quantity">Key #</label><br><input type="text" class=\'form-control\' name=\'orderData[key]\' id=\'key\' required /></div>
      <div class="col">'.$r->buildDateSelector('startDate',"Date").'</div>
    </div>
    <div class="row">
      <div class="col"><label for="name">Your Name</label><br><input type="text" class=\'form-control\' name=\'orderData[name]\' id=\'name\' required /></div>
      <div class="col">
        <div class="sigPad">
          <p class="drawItDesc">Your Signature</p>
            <ul class="sigNav">
              <li class="drawIt"><a href="#draw-it" >Draw Signature</a></li>
              <li class="clearButton"><a href="#clear">Clear</a></li>
            </ul>
          <div class="sig sigWrapper">
            <div class="typed"></div>
            <canvas class="pad" width="400" height="200"></canvas>
            <input type="hidden" name="orderData[nameSign]" class="output">
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col"><label for="mgrName">Manager Name</label><br><input type="text" class=\'form-control\' name=\'orderData[mgrName]\' id=\'mgrName\' required /></div>
      <div class="col">
        <div class="sigPad">
          <p class="drawItDesc">Manager Signature</p>
            <ul class="sigNav">
              <li class="drawIt"><a href="#draw-it" >Draw Signature</a></li>
              <li class="clearButton"><a href="#clear">Clear</a></li>
            </ul>
          <div class="sig sigWrapper">
            <div class="typed"></div>
            <canvas class="pad" width="400" height="200"></canvas>
            <input type="hidden" name="orderData[mgrSign]" class="output">
          </div>
        </div>
      </div>
    </div>
    <div class=\'row\'>
      <div class=\'col\'><input type="submit" class=\"btn btn-primary\" id="submit" value="Submit"/></div>
      <div class=\'col\'></div>
    </div>
  </div>
</form>
<script>
  jQuery(document).ready(function() {
    jQuery(\'.sigPad\').signaturePad();
  });
</script>
</div>
';
