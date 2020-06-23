<?php
global $wp;
global $wpdb;
$cu = wp_get_current_user();
$toast = new ToastReport();
if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
	$toast->isAboveStore=1;
  $store="";
}
if($toast->isAboveStore==0) {
  $rests=$toast->getAvailableRestaurants();
  foreach($rests as $r){
    $orStmt[]="restaurantID=".$r->restaurantID;
  }
  $store=" AND (".implode(' OR ',$orStmt).")";
}
$q="SELECT externalEmployeeID, employeeName FROM pbc_TipDistribution,pbc_ToastEmployeeInfo
WHERE pbc_TipDistribution.employeeGUID = pbc_ToastEmployeeInfo.guid ".$store." GROUP BY externalEmployeeID, employeeName ORDER BY employeeName ";
echo $q
$results=$wpdb->get_results($q);
if($results){
  $data['Field']="id[]";
  $data['ID']="id";
  $data['Multiple']="Multiple";
  foreach($results as $r){
    $data['Options'][$r->externalEmployeeID]=$r->employeeName;
  }
  $ret=$toast->pbk_form_processing() . "
<div class='container'>
  <form method='get' action='".home_url( add_query_arg( array(), $wp->request ) )."'>
    <div class='form-control'>
      <div class='row'>
        ".$toast->buildSelectBox($data)."
      </div>
      <div class='row'>
        <input type='submit' value='Search"' />
      </div>
    </div>
  </form>
</div>
  ";
}
