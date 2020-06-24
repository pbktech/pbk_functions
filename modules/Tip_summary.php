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
WHERE pbc_TipDistribution.employeeGUID = pbc_ToastEmployeeInfo.guid
".$store." GROUP BY externalEmployeeID, employeeName ORDER BY employeeName";
$results=$wpdb->get_results($q);
if($results){
  $data['Field']="id[]";
  $data['ID']="id";
  $data['Multiple']="Multiple";
  foreach($results as $r){
    $data['Options'][$r->externalEmployeeID]=$r->employeeName;
  }
  $ret=$toast->pbk_form_processing() . "
  <script>
  function confirm_proceed() {
    return confirm('This report takes a long time to process, please be patient.');
  }
  </script>
<div class='container' id='queryResults'>
  <form method='get' action='".home_url( add_query_arg( array(), $wp->request ) )."'>
      <div class='row'>
      <label for='id'>Please Choose Employees to Search</label>
        ".$toast->buildSelectBox($data)."
      </div>
      <div class='row'>
        <input type='submit' id='submit' class=\"btn btn-primary\" value='Search' />
      </div>
  </form>";
  if(isset($_GET['id'])){
    foreach($_GET['id'] as $id){
      $ids[]="externalEmployeeID=".$id;
    }
    $q="SELECT * FROM pbc_sum_AssignedTips,pbc_ToastEmployeeInfo WHERE employeeGUID = guid AND (".implode(' OR ',$ids).") ORDER BY employeeName,businessDate";
    $results=$wpdb->get_results($q);
    if($results){
      $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
      $D['Options'][]="\"order\": [ 5, 'asc' ]";
			$D['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
      $D['Headers']=array("Employee Name","Employee ID","Restaurant","Date","Check","Tip","Assigned","Payroll");
      foreach ($results as $r) {
        if($r->Tip==0){continue;}
        $info=json_decode($r->userID);
        if(isset($info->SentToPayroll)){
          $payroll=date("m/d/Y",strtotime($info->SentToPayroll->Date)) . " by " . $info->SentToPayroll->User;
        }else{
          if($r->sentToPayroll==1){
            $payroll="SENT";
          }else{
            $payroll="PENDING";
          }
        }
        $D['Results'][]=array(
          $r->employeeName,
          $r->externalEmployeeId,
          $r->restaurantName,
          date("m/d/Y",strtotime($r->businessDate)),
          $r->checkNumber,
          $fmt->formatCurrency($r->Tip,"USD"),
          date("m/d/Y",strtotime($info->Initial->Date)) . " by " . $info->Initial->User,
          $payroll);
      }
      $ret.=$toast->showResultsTable($D);
    }else{
      $ret.="
      		<div class=\"alert alert-secondary\" role=\"alert\">
      		There were no records found.
      		</div>";
    }
  }
  $ret.="
</div>
  ";
}
