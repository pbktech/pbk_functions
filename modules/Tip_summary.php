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
  $ret="
  <script>
  jQuery(document).ready(function() {
    jQuery(\"#submit\").click(function(event){
      var error_free=true;
      if(jQuery(\"#startDate\").val()!='' || jQuery(\"#endDate\").val()!=''){
        if(jQuery(\"#startDate\").val()!='' && jQuery(\"#endDate\").val()==''){
          jQuery(\"#endDateLabel\").after('<span class=\"text-danger\">Required</span>');error_free=false;
        }
        if(jQuery(\"#startDate\").val()=='' && jQuery(\"#endDate\").val()!=''){
          jQuery(\"#startDateLabel\").after('<span class=\"text-danger\">Required</span>');error_free=false;
        }
        if(!jQuery(\"#payroll\").is(':checked') && !jQuery(\"#assigned\").is(':checked') && !jQuery(\"#order\").is(':checked')){
          jQuery(\"#dateTypes\").after('<span class=\"text-danger\">Required</span>');error_free=false;
        }
      }
      if(jQuery(\"#payroll\").is(':checked') || jQuery(\"#assigned\").is(':checked') || jQuery(\"#order\").is(':checked')
      && (jQuery(\"#startDate\").val()!='' || jQuery(\"#endDate\").val()!='')){
        jQuery(\"#chooseDates\").after('<span class=\"text-danger\">Required</span>');error_free=false;
      }
      if (!error_free){
      		event.preventDefault();
      }else{
        window.scrollTo(0,0);
        jQuery(\"#incidentForm\").hide();
        jQuery(\"#processingGif\").show();
      }
    });
  });
  </script>
<div class='container-fluid' id='queryResults'>
  <form method='get' action='".home_url( add_query_arg( array(), $wp->request ) )."'>
      <div class='row'>
        <div class='col'>
          <label for='id'>Please Choose Employees to Search <i>(You can type to search and choose multiple employees)</i></label>
            ".$toast->buildSelectBox($data)."
        </div>
      </div>
      <div class='row'>
        <div class='col'>
          <label id='chooseDates'>Choose your dates:</label>
        </div>
      </div>
      <div class='row'>
        <div class='col'>
            ".$toast->buildDateSelector('startDate',"Starting Date")."
        </div>
        <div class='col'>
            ".$toast->buildDateSelector('endDate',"Ending Date")."
        </div>
      </div>
      <div class='row'>
        <div class='col'>
          <label id='dateTypes'>Choose which date to search:</label>
        </div>
      </div>
      <div class='row'>
        <div class='col'>
          <div class=\"form-check form-check-inline\">
            <input class=\"form-check-input\" type=\"radio\" name=\"dateType\" id=\"payroll\" value=\"payroll\"> <label for='payroll' class=\"form-check-label\">Sent to Payroll</label>
          </div>
          <div class=\"form-check form-check-inline\">
            <input class=\"form-check-input\" type=\"radio\" name=\"dateType\" id=\"assigned\" value=\"assigned\"> <label for='assigned' class=\"form-check-label\">Assigned</label>
          </div>
          <div class=\"form-check form-check-inline\">
            <input class=\"form-check-input\" type=\"radio\" name=\"dateType\" id=\"order\" value=\"order\"> <label for='order' class=\"form-check-label\">Order Date</label>
          </div>
          </div>
        </div>
      </div>
      <div class='row'>
        <div class='col'>
          <input type='submit' id='submit' class=\"btn btn-primary\" value='Search' />
        </div>
      </div>
  </form>";
  if(isset($_GET['id'])){
    foreach($_GET['id'] as $id){
      $ids[]="externalEmployeeID=".$id;
    }
    if(isset($_GET['dateType']) && $_GET['dateType']!=''){
      switch($_GET['dateType']){
        case "payroll":
          $dateRestrict=" AND CAST(json_unquote(JSON_EXTRACT(userID ,'$.SentToPayroll.Date')) as DATETIME) BETWEEN '".date('Y-m-d',strtotime($_GET['startDate']))." 00:00:00' AND '".date('Y-m-d',strtotime($_GET['endDate']))." 23:59:59' ";
          break;
        case "assigned":
          $dateRestrict=" AND CAST(json_unquote(JSON_EXTRACT(userID ,'$.Initial.Date')) as DATETIME) BETWEEN '".date('Y-m-d',strtotime($_GET['startDate']))." 00:00:00' AND '".date('Y-m-d',strtotime($_GET['endDate']))." 23:59:59' ";
          break;
        case "order":
          $dateRestrict=" AND businessDate BETWEEN '".date('Y-m-d',strtotime($_GET['startDate']))." 00:00:00' AND '".date('Y-m-d',strtotime($_GET['endDate']))." 23:59:59' ";
          break;
        default:
          $dateRestrict="";
      }
    }else{
      $dateRestrict="";
    }
    $q="SELECT * FROM pbc_sum_AssignedTips,pbc_ToastEmployeeInfo WHERE employeeGUID = guid AND (".implode(' OR ',$ids).") $dateRestrict ORDER BY employeeName,businessDate";
    $results=$wpdb->get_results($q);
    if($results){
      $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
      $D['Options'][]="\"order\": [ 5, 'asc' ]";
			$D['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
      $D['Headers']=array("Employee Name","Employee ID","Restaurant","Date","Check","Tip","Assigned","Payroll");
      foreach ($results as $r) {
        if($r->tip==0){continue;}
        $info=json_decode($r->userID);
        if(isset($info->SentToPayroll)){
          $payroll=date("m/d/Y",strtotime($info->SentToPayroll->Date)) . " by " . $info->SentToPayroll->User;
        }else{
          $payroll="";
        }
        $D['Results'][]=array(
          $r->employeeName,
          $r->externalEmployeeId,
          $r->restaurantName,
          date("m/d/Y",strtotime($r->businessDate)),
          $r->checkNumber,
          $fmt->formatCurrency($r->tip,"USD"),
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
