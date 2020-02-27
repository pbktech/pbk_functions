<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$r=new Restaurant;
if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $r->setRestaurantID($_POST['restaurantID']);
  $_POST['orderData']['files']=array();
  $_POST['orderData']['reporterName']=$_POST['reporterName'];
  if ( $_FILES ) {
    $pid = get_post();
    $files = $_FILES["orderDataFiles"];
    foreach ($files['name'] as $key => $value) {
      if ($files['name'][$key]) {
        $file = array(
          'name' => $files['name'][$key],
          'type' => $files['type'][$key],
          'tmp_name' => $files['tmp_name'][$key],
          'error' => $files['error'][$key],
          'size' => $files['size'][$key]
        );
        $_FILES = array ("orderDataFiles" => $file);
        foreach ($_FILES as $file => $array) {
          $postKey = $r->pbk_foh_attachment($file,$pid);
          $_POST['orderData']['files'][]=get_post_meta($postKey, '_wp_attached_file', true);
        }
      }
    }
  }
  $wpdb->query(
	$wpdb->prepare(
		"INSERT INTO pbc_pbk_orders (orderType,restaurantID,userID,orderData,orderDate,orderStatus)VALUES(%s,%d,%d,%s,%s,%s)",
	        "LightBulb", $_POST['restaurantID'],get_current_user_id(),json_encode($_POST['orderData']),date("Y-m-d H:i:s"),"Pending"
    )
  );
  if($wpdb->last_error==''){
    $report=New ToastReport;
    $html="<div>There has been a new light bulb order placed for ".$r->getRestaurantField("restaurantName").".<br>

    <a href='".admin_url( 'admin.php?page=pbr-orders&type=LightBulb&id='.$wpdb->insert_id)."'>View</a> the full order.";
    $report->reportEmail("laura@theproteinbar.com,jon@theproteinbar.com",$html,"Light Bulb Order");
    switchpbrMessages(6);
  }
}
$ret.=$r->pbk_form_processing()."
<script>
function addInput(divName,htmlToAdd){
  var newdiv = document.createElement(\"div\");
  newdiv.innerHTML = htmlToAdd;
  document.getElementById(divName).appendChild(newdiv);
}
</script>
<div class='container' id='queryResults'>
  <form method='post' action='".$page."' id='' enctype='multipart/form-data' >
    <div class='row'>
      <div class='col'><label for='reporterName'>Your Name</label>".$r->buildLoggedInName()."</div>
      <div class='col'><label for='restaurantID'>Restaurant</label>".$r->buildRestaurantSelector()."</div>
    </div>
    <div class='row'>
      <div class='col'><label for='bulbs'>Bulb Type</label>".$r->buildSelectBox(array("Options"=>$r->getBulbs(),"Field"=>"orderData[bulbs]","Multiple"=>"","ID"=>"bulbs"))."</div>
      <div class='col'><label for='quantity'>Quantity</label><br><input type='text' class='form-control' name='orderData[quantity]' id='quantity' required /></div>
    </div>
    <div class='row'>
      <div class='col'><label for='other'>Additional Comments</label><textarea class=\"form-control\" rows=\"5\" id=\"other\" name='orderData[other]' placeholder='Please include any other necessary detail, such as specs on current light bulb, location of lighting, etc'></textarea></div>
      <div class='col' id='file_area'><label for='pictures'>Images</label><br><input type='file' name='orderDataFiles[]' id='files' multiple /></div>
    </div>
    <div class='row'>
      <div class='col'><input type=\"submit\" class=\"btn btn-primary\" id='submit' value='Submit'/></div>
      <div class='col'></div>
    </div>
  </form>
</div>
  ";
