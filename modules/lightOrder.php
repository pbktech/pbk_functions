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
    $html="<div>There has been a new supply order placed for ".$r->getRestaurantField("restaurantName").".";
    $current_user = wp_get_current_user();
    $attach=$r->showOrderInfo($wpdb->insert_id,1);
    $report->reportEmail($current_user->user_email.",supplies@theproteinbar.com",$attach['html'],"Supply Order",$attach['pdf']);
    switchpbrMessages(6);
  }
}
$ret.=$r->pbk_form_processing()."
<div class='container' id='queryResults'>
  <form method='post' action='".$page."' id='' enctype='multipart/form-data' >
    <div class='row'>
      <div class='col'><label for='reporterName'>Your Name</label>".$r->buildLoggedInName()."</div>
      <div class='col'><label for='restaurantID'>Restaurant</label>".$r->buildRestaurantSelector()."</div>
    </div>";
    foreach($r->getBulbs() as $header =>$items){
        $ret.="<h3>" . $header . "</h3>
<div class='row'>";
        foreach($items as $id =>$name) {
            $ret .= "
      <div class='col-auto'>
        <label class=\"sr-only\" for=\"bulb" . $id . "\">" . $name . "</label>
            <div class=\"input-group mb-2\">
                <div class=\"input-group-prepend\">
                    <div class=\"input-group-text\">" . $name . "</div>
                </div>
                <input type=\"number\" class=\"form-control\" style='width: 100px;' id=\"bulb" . $id . "\" name='orderData[items][$name]' />
            </div>
      </div>
        ";
        }
        $ret.="</div>";
    }
$ret.="
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
