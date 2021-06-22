<?php
global $wp;
global $wpdb;
$current_user = wp_get_current_user();
$userLevel=get_currentuserinfo();
$toast = new ToastReport();
$cu = wp_get_current_user();
$rests=$toast->getAvailableRestaurants();
if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
	$toast->isAboveStore=1;
}else{
  $_REQUEST['rid']=$rests[0]->restaurantID;
}
$products["Shirts"]["T-Shirt XS"]=9.2;
$products["Shirts"]["T-Shirt S"]=9.2;
$products["Shirts"]["T-Shirt M"]=9.2;
$products["Shirts"]["T-Shirt L"]=9.2;
$products["Shirts"]["T-Shirt XL"]=9.2;
$products["Shirts"]["T-Shirt XXL"]=10.9;
$products["Shirts"]["T-Shirt XXXL"]=13.35;
$products["Shirts"]["Polo XS"]=21.4;
$products["Shirts"]["Polo S"]=21.4;
$products["Shirts"]["Polo M"]=21.4;
$products["Shirts"]["Polo L"]=21.4;
$products["Shirts"]["Polo XL"]=21.4;
$products["Shirts"]["Polo XXL"]=22.8;
$products["Shirts"]["Polo XXXL"]=25.7;
$products["Hats"]["Hat"]=9.4;
$products["Hats"]["Visor"]=9.4;
$products["Other"]["Name Tag"]=3.21;
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
  $total=0;
  $count = 0;
  $output="<table>
  <tr><td>Entered By:</td><td>".$_POST['data']['name']."</td></tr>
  <tr><td>Employee:</td><td>".$_POST['data']['employee']."</td></tr>
  <tr><td>Restaurant:</td><td>".$_POST['data']['restaurant']."</td></tr>
  ";
  foreach($products as $category=>$items){
    $output.="<tr><td colspan='2'><strong>$category</strong></td></tr>";
    foreach($items as $item=>$price){
      if(isset($_POST['data']['items'][$item]) && $_POST['data']['items'][$item]>0){
        $output.="<tr><td><label for='item".$count."'>$item</label></td><td>".$_POST['data']['items'][$item]."</td></tr>";
        $total+=($_POST['data']['items'][$item]*$products[$category][$item]);
      }
      $count++;
    }
  }
  $output.="<tr><td><strong>Total Deduction:</strong></td><td>".$toast->switchNegNumber($total,2)."</td></tr>";
  $_POST['data']['total']=$total;
  $json=json_encode($_POST['data']);
  $toast->reportEmail("mcrawford@theproteinbar.com,hrgroup@theproteinbar.com",$output,"New Deduction Request");
  $category="Deductions";
  $stmt = $toast->mysqli->prepare("INSERT INTO pbc2.pbc_miscData (category,jsonData)VALUES(?,?)");
  $stmt->bind_param('ss',$category,$json);
  $stmt->execute();
  if($stmt->error!='') {
    $ret.="There was an error. Please contact the IT department with the following error: ".$stmt->error;
  }else{
    $ret.="Request for ".$_POST['data']['employee']." successfully submitted.";
  }
  }else {
  if(!isset($_REQUEST['rid']) || !is_numeric($_REQUEST['rid'])){
    $ret.="\n
  	<div>
  		<form method='get' action='".home_url( $wp->request )."'  name='restaurantSelector'>
  			<select name='rid' onchange=\"this.form.submit()\"><option value=''>Choose a Restaurant</option>";
  	foreach($rests as $r){
  		$ret.="\n<option value='".$r->restaurantID."'>".$r->restaurantName."</option>";
  	}
  	$ret.="</select></form></div>";

  }else{
    $toast->restaurantID=$_REQUEST['rid'];
    $count=1;
    $employees=$toast->getActiveEmployees();
    $restaurant=$toast->getRestaurantIinfo();
    $ret.="
    <div>
  	 <form method='POST' action='".home_url( $wp->request )."'>
     <div><h3>Your Name</h3><p>";
     if($toast->getManagerType()=="STR"){
       $ret.="<input type='text' name='data[name]' />";
     }else{
       $ret.="<input type='text' value='".$cu->display_name."' disabled /><input type='hidden' name='data[name]' value='".$cu->display_name."' />";
     }
     $ret.="</p></div>
     <div><h3>Employee Name</h3><p>
     <select name='data[employee]' ><option value=''>Choose One</option>
     ";
     foreach($employees as $emp){
       $ret.="<option value='".$emp->employeeName."'>".$emp->employeeName."</option>";
     }
      $ret.="</select></p></div><div><table>";
     foreach($products as $category=>$items){
       $ret.="<tr><td colspan='2'><h3>$category</h3></td></tr>";
       foreach($items as $item=>$price){
         $ret.="<tr><td><label for='item".$count."'>$item</label></td><td><input type='text' name='data[items][$item]' id='item".$count."' size='5'/></td></tr>";
         $count++;
       }
     }
     $ret.="</table></div><input type='hidden' name='rid' value='".$_REQUEST['rid']."' /><input type='hidden' name='data[restaurant]' value='".$restaurant->restaurantName."' /><input type='submit' value='Submit' />
     </form>
    </div>
    ";
  }
}
