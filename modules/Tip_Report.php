<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$toast = new ToastReport();
$rests=$toast->getAvailableRestaurants();
$cu = wp_get_current_user();
if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
	$toast->isAboveStore=1;
	if(isset($_GET['rid'])){$_REQUEST['rid']=$_GET['rid'];}
}else {
	$_REQUEST['rid']=$rests[0]->restaurantID;
}

if(isset($_GET['startDate']) && isset($_GET['endDate'])) {
	$toast->setStartTime($_GET['startDate']);
  $toast->setEndTime($_GET['endDate']);
  $toast->setRestaurantID($_REQUEST['rid']);
	$restaurantInfo=$toast->getRestaurantIinfo();
  $orders=$toast->getTippedOrdersbyRange();
	foreach($orders as $o){
		$check=$toast->getCheckInfo($o->ToastCheckID);
    $order=$toast->getCheckInfo($o->ToastCheckID);
      $payment=$toast->getPaymentInfo($o->ToastCheckID);
    	$toast->setBusinessDate($order->modifiedDate);
    	$employees=$toast->getClockedInEmployees("Team");
    	$tips=$toast->getAssignedTips($o->ToastCheckID);
    	$dis=0;
			if ($_GET['consolidate']=='yes') {
				foreach($employees as $e){
					$consolidatedPay[$e->employeeName]+=$tips[$e->GUID]->tipAmount;
				}
			}else {
      	$ret.="
      	<div>
      	<h4>Check #".$order->checkNumber;
      	if(isset($order->tabName) && $order->tabName!="") {$ret.=": ".$order->tabName;}
      	$ret.="</h4>";
				if (count($tips)==0) {
					$ret.="<div style='color:#e51818;'><strong>Tips have not been assigned for this check, yet.</strong></div>";
				}
      	$ret.="<div>Opened: ".date("m/d/Y g:i a",strtotime($order->openedDate))." || Paid: ".date("m/d/Y g:i a",strtotime($payment->paidDate))." || Closed: ".date("m/d/Y g:i a",strtotime($order->closedDate))."</div>";
      	$ret.="<div><strong>Payment Method: ".$payment->paymentType." || Tip Amount: ".money_format("%.2n", $payment->tipAmount)." || Order Total: ".money_format("%.2n", $payment->totalAmount)."</strong></div>";
      	$ret.="<div><form method='POST' action='".$page."' onsubmit=\"return validateTipAmounts()\" >
      	<table>
        	<tr>
        	<td><strong>Employee</strong></td>
        	<td><strong>Assigned Portion</strong></td>
      	</tr>
      	<tr>
        	<td>3rd Party/No One</td>
        	<td>".money_format("%.2n", $tips['a0']->tipAmount)."</td>
      	</tr>
      	";
      	foreach($employees as $e){
    			$ret.="
    			<tr>
    				<td><span style='text-transform:capitalize;'>".$e->employeeName."</span></td>
    				<td>".money_format('%.2n',$tips[$e->GUID]->tipAmount)."</td>
    			</tr>";
    		}
    		$ret.="</table><br />";
			}
    }
		if ($_GET['consolidate']=='yes') {
			$ret.="
			<div><h4>Total Tips assigned at ".$restaurantInfo->restaurantName." for ".date("m/d/Y",strtotime($_GET['startDate']))." - ".date("m/d/Y",strtotime($_GET['endDate']))."</h4></div>
			<table>
				<tr>
				<td><strong>Employee</strong></td>
				<td><strong>Assigned Portion</strong></td>
			</tr>
			";
			foreach ($consolidatedPay as $employeeName => $assignedTip) {
				$ret.="
				<tr>
					<td><span style='text-transform:capitalize;'>".$employeeName."</span></td>
					<td>".money_format('%.2n',$assignedTip)."</td>
				</tr>";
			}
			$ret.="</table><br />";
		}
}
$ret.="
<script type=\"text/javascript\">

jQuery(document).ready(function() {
jQuery('#startDate').datepicker({
    dateFormat : 'dd-mm-yy'
});
jQuery('#endDate').datepicker({
    dateFormat : 'dd-mm-yy'
});
});

</script>
<div>
  <form method='get' action='$page' >";
  if ($toast->isAboveStore==1) {
    $ret.="
    <h4>Please Select a Restaurant</h4>
    <div>
      <select name='rid'>
        <option value=''>Choose One</option>
        ";
        foreach($rests as $r){
          $checked="";
          if(isset($_REQUEST['rid']) && $_REQUEST['rid']==$r->restaurantID) {$checked="selected";}
          $ret.="
        <option value='".$r->restaurantID."' $checked>".$r->restaurantName."</option>";
        }
        $ret.="
      </select>
    </div>";
  }
	 $ret.="
    <h4>Please choose a date range</h4>
    <div>
      <label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
      <label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\"/>
    </div>
		<h4>Consolidated Records?</h4>
		<div>
			Yes <input type='checkbox' name='consolidate' value='yes' />
		</div>
    <div>
      <input type='submit' value='SEARCH' />
    </div>
  </form>
</div>


";
