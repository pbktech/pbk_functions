<?php
function pbk_CheckTips() {
  global $wpdb;
  global $wp;
  $cu = wp_get_current_user();
  $results=$wpdb->get_results( "SELECT restaurantName,count(*) as 'Total' FROM pbc2.pbc_ToastOrderPayment,pbc2.pbc_pbrestaurants WHERE pbc2.pbc_ToastOrderPayment.restaurantID=pbc2.pbc_pbrestaurants.restaurantID AND businessDate > '2020-05-01 00:00:00' AND pbc2.pbc_ToastOrderPayment.restaurantID IN (SELECT restaurantID FROM pbc2.pbc_pbr_managers WHERE managerID='".$cu->ID."') AND pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT orderGUID FROM pbc2.pbc_TipDistribution) and tipAmount!=0 AND pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT GUID FROM pbc_ToastCheckHeaders WHERE tabName LIKE '%Grubhub Delivery%') GROUP BY restaurantName");
  if($results){
    foreach($results as $r){
      $tips[]=array("T"=>$r->Total,"R"=>$r->restaurantName);
    }
    if(count($tips)>1) {
      $message="
      <div class='container'>
        <div class='row'>
          <div class='col'>
            The following restaurants have orders that require tip assignments.
          </div>
        </div>
            ";
      foreach($tips as $tip){
        $message.="
        <div class='row'>
          <div class='col'>
            ".$tip['R']."
          </div>
          <div class='col'>
            <strong class='text-danger'>".$tip['T']."</strong>
          </div>
        </div>
        ";
      }
      $message.="</div>";
    }elseif(count($tips)==1) {
      $message="There are <strong class='text-danger'>".$tips[0]['T']."</strong> orders requiring tip assignments. Please assign tips.";
    }
    return  pbk_show_modal($message,"<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location.href='". home_url("/operations/tips/tip-distribution/")."'\">Tip Distribution</button>");
  }
}
