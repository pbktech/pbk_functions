<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$toast = new ToastReport($_REQUEST['rid']);
	$payment=$toast->getPaymentInfo($_REQUEST['order']);
	$submittedTips=array_sum($_POST['emp']);
	if($payment->tipAmount < $submittedTips) {
		$ret.="<div>
			Assigned Tips ($".round($submittedTips,2).") cannot be larger than Total Tips  ($".$payment->tipAmount.")<br />
			Please <a href='".$page."?order=".$_REQUEST['order']."&amp;rid=".$_REQUEST['rid']."'>Try Again</a>.
		</div>";
	}else {
		$order=$toast->getCheckInfo($_REQUEST['order']);
		$payment=$toast->getPaymentInfo($_REQUEST['order']);
		$toast->setBusinessDate($order->modifiedDate);
		$employees=$toast->getClockedInEmployees("Team");
		$tips=$toast->getAssignedTips($_REQUEST['order']);
		$dob=date("Y-m-d",strtotime($order->modifiedDate));
		$employees[] = (object) array("GUID"=>"a0","employeeName"=>"3rd Party/No One");
		foreach($employees as $e){
			if(trim($tips[$e->GUID]->tipAmount)!=$_POST[$e->GUID] && $tips[$e->GUID]->sentToPayroll!=1) {
				$json=json_decode($tips->userID,true);
				$cu = wp_get_current_user();
				$json["Update"][]=array("Date"=>date("Y-m-d G:i:s"),"User"=>$cu->user_firstname." ".$cu->user_lastname);
				$json=json_encode($json);
				$wpdb->replace(
					'pbc_TipDistribution',
						array(
							'employeeGUID' => $e->GUID,
							'orderGUID' => $_REQUEST['order'],
							'tipAmount' => $_POST[$e->GUID],
							'dateOfBusiness'=>$dob,
							'userID' => $json
						),
						array(
							'%s',
							'%s',
							'%s',
							'%s',
							'%s'
						)
					);
			}
		}
		$wpdb->print_error();
		echo "<script>window.location.replace(\"".$page."?order=".$_REQUEST['order']."&rid=".$_REQUEST['rid']."\");</script>";
	}
}
if(isset($_REQUEST['order']) && $_REQUEST['order']!='') {
	$toast = new ToastReport($_REQUEST['rid']);
	$order=$toast->getCheckInfo($_REQUEST['order']);
	$payment=$toast->getPaymentInfo($_REQUEST['order']);
	$toast->setBusinessDate($order->modifiedDate);
	$employees=$toast->getClockedInEmployees("Team Member");
	$tips=$toast->getAssignedTips($_REQUEST['order']);
	$disabled="";
	foreach($tips as $tip){
			if($tip->sentToPayroll==1) {$disabled=" disabled ";break;}
	}
	$ret.="
	<script type=\"text/javascript\">
	function stripCharacters(num){
		return num.replace(/\D/g,'');
	}
	function sumArray(a){
		var arrayLength = a.length;
		var totalTips=0;
		for (var i = 0; i < arrayLength; i++) {
	    totalTips+=parseFloat(a[i]);
		}
		return totalTips;
	}
	function validateTipAmounts(){
		var emp = [];
	 emp.push(document.getElementById('a0').value);
		";
	foreach($employees as $e){
		$ret.="\n	emp.push(document.getElementById('".$e->GUID."').value);";
	}
	$ret.="
		var totalAssignedTips=sumArray(emp);
		var totalTips=".$payment->tipAmount.";
		if (totalAssignedTips > totalTips) {
			alert('Assigned Tips ($' + totalAssignedTips + ') cannot be larger than Total Tips  ($' + totalTips + ')');
			return false;
		}
	}
	</script>
	<div>
	<h4>Check #".$order->checkNumber;
	if(isset($order->tabName) && $order->tabName!="") {$ret.=": ".$order->tabName;}
	$ret.="</h4>";
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
			<td><input type='text' name='a0' value='".round($tips['a0']->tipAmount,3)."' id='a0'".$disabled."/></td>
		</tr>
		";
	foreach($employees as $e){
		$ret.="
		<tr>
			<td><span style='text-transform:capitalize;'>".$e->employeeName."</span></td>
			<td><input type='input' name='".trim($e->GUID)."' value='".round($tips[$e->GUID]->tipAmount,3)."' id='".$e->GUID."'".$disabled."/> </td>
		</tr>";
	}
	$ret.="</table><br />
	<input type='hidden' name='order' value='".$_REQUEST['order']."' />
	<input type='hidden' name='rid' value='".$_REQUEST['rid']."' />";
	if($disabled=='') {$ret.="
	<input type='submit' value='Save Check #".$order->checkNumber."'/></form></div></div>";
	}
}

if(!isset($_REQUEST['startDate']) || $_REQUEST['startDate']=='' || !isset($_REQUEST['endDate']) || $_REQUEST['endDate']=='' || !isset($_REQUEST['rid']) || $_REQUEST['rid']=='') {
	$toast = new ToastReport();
	$rests=$toast -> getAvailableRestaurants();
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
			<form method='get' action='$page' >
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
				</div>
				<h4>Please choose a date range</h4>
				<div>
					<label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
					<label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\"/>
				</div>
				<div>
					<input type='submit' value='SEARCH' />
				</div>
			</form>
		</div>


		";

}else {
	$toast = new ToastReport($_REQUEST['rid']);
	$toast ->setStartTime(date($_REQUEST['startDate'],strtotime($bot))." 00:00:00");
	$toast ->setEndTime(date($_REQUEST['endDate'],strtotime($latest))." 23:59:59");
	$orders=$toast->getTippedOrdersbyRange();
	foreach($orders as $o){
		$check=$toast->getCheckInfo($o->ToastCheckID);
		$ret.="
		<p>
			<a href='".$page."?order=".$o->ToastCheckID."&amp;rid=".$_REQUEST['rid']."'>Check #".$check->checkNumber." from ".date("m/d/Y",strtotime($check->modifiedDate))."</a>
		</p>";
	}
}
