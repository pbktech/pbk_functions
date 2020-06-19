<?php
function pbk_CheckTips() {
  global $wpdb;
  global $wp;
  $cu = wp_get_current_user();
  $results=$wpdb->get_results( "SELECT restaurantName,count(*) as 'Total' FROM pbc2.pbc_ToastOrderPayment,pbc2.pbc_pbrestaurants WHERE pbc2.pbc_ToastOrderPayment.restaurantID=pbc2.pbc_pbrestaurants.restaurantID AND businessDate > '2019-01-07 00:00:00' AND pbc2.pbc_ToastOrderPayment.restaurantID IN (SELECT restaurantID FROM pbc2.pbc_pbr_managers WHERE managerID='".$cu->ID."') AND pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT orderGUID FROM pbc2.pbc_TipDistribution) and tipAmount!=0 GROUP BY restaurantName");
  if($results){
    foreach($results as $r){
      $tips[]=array("T"=>$r->Total,"R"=>$r->restaurantName);
    }
    if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles) || in_array("author", $cu->roles)) {
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
            <span style='color:#B1050C;'>".$tip['T']."</span>
          </div>
        </div>
        ";
      }
      $message.="</div>";
    }else {
      $message="There are <span style='color:#B1050C;'>".$tips[0]['T']."</span> orders requiring tip assignments. Please assign tips.";
    }
    pbk_show_modal($message,"<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location.href='". home_url("/operations/tips/tip-distribution/")."'\">Tip Distribution</button>");
  }
  if(in_array("administrator", $cu->roles)){
  return  pbk_show_modal("Test Modal","<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location.href='". home_url("/operations/tips/tip-distribution/")."'\">Tip Distribution</button>");
  }
}
