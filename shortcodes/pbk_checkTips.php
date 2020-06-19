<?php
function pbk_CheckTips() {
  $toast = new ToastReport();
  $rests=$toast->getAvailableRestaurants();
  $cu = wp_get_current_user();
  if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles) || in_array("author", $cu->roles)) {

  }else {
    $_REQUEST['rid']=$rests[0]->restaurantID;
    $bot="2019-01-07 00:00:00";
    $latest=date("Y-m-d",time() - 60 * 60 * 24)." 23:59:59";
    $toast = new ToastReport($_REQUEST['rid']);
  	$toast ->setStartTime(date("Y-m-d G:i:s",strtotime($bot)));
  	$toast ->setEndTime(date("Y-m-d G:i:s",strtotime($latest)));
  	$orders=$toast->getTippedOrders();
    if(is_array($orders) && count($orders)!=0){
      return "
      <script>
        jQuery( document ).ready(function() {
          jQuery('#tipsRequired').trigger('focus')
        });
      </script>
      <div class=\"modal fade\" id=\"tipsRequired\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"tipsRequired\" aria-hidden=\"true\">
        <div class=\"modal-dialog\" role=\"document\">
          <div class=\"modal-content\">
            <div class=\"modal-body\">
              There are ".count($orders)." requiring tip assignment. <br><br>Please <a href='". home_url("/operations/tips/tip-distribution/")."'>assign</a> the tips.
            </div>
            <div class=\"modal-footer\">
              <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>
            </div>
          </div>
        </div>
      </div>
      ";
    }
  }
}
