<?php
$time = microtime();
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$latest=date("Y-m-d",time() - 60 * 60 * 24)." 23:59:59";
$toast = new ToastReport();
$rests=$toast->getAvailableRestaurants();
$cu = wp_get_current_user();
if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
	$toast->isAboveStore=1;
}
if($toast->isAboveStore==0) {
	$_REQUEST['rid']=$rests[0]->restaurantID;
}
if (isset($_REQUEST['rid']) && $_REQUEST['rid']==4) {
	$bot="2018-11-26 00:00:00";
}else {
	$bot="2019-01-07 00:00:00";
}
if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['restaurant']=='') {
	$toast->setRestaurantID($_POST['rid']);
	$workerPercent=1/count($_POST['worked']);
	$driverPercent=1/count($_POST['driver']);
	$order=$toast->getPaymentInfo($_POST['chkID']);
	$dateOfBusiness=date("Y-m-d",strtotime($order->closedDate));
	$share=round(($order->tipAmount/2),2);
	foreach($_POST['worked'] as $e){
		$tipShare[$e]+=round($share*$workerPercent,3);
	}
	foreach($_POST['driver'] as $e){
		$tipShare[$e]+=round($share*$driverPercent,3);
		if ($e=="a0") {
			$tipShare[$e]+=round($share*$driverPercent,3);
		}
	}
	foreach($tipShare as $e=>$t){
		$cu = wp_get_current_user();
		$userID=json_encode(array("Initial"=>array("Date"=>date("Y-m-d G:i:s"),"User"=>$cu->user_firstname." ".$cu->user_lastname)));
		$wpdb->query( $wpdb->prepare("INSERT INTO pbc_TipDistribution(employeeGUID,orderGUID,dateOfBusiness,tipAmount,userID)values(%s,%s,%s,%s,%s)",array($e,$_POST['chkID'],$dateOfBusiness,$t,$userID)));
	}
	$wpdb->update(
		'pbc_ToastOrderPayment',
		array(
			'tipsAssigned' => '1'
		),
		array( 'ToastCheckID' => $_POST['chkID'] )
	);
	if($toast->isAboveStore==1) {
		echo "<script>window.location.replace(\"".$page."/?rid=".$_POST['rid']."\");</script>";
	}else {
		echo "<script>window.location.replace(\"".$page."\");</script>";
	}
}
if(!isset($_REQUEST['rid'])) {
	$ret.="\n
	<div>
		<form method='get' action='".$page."'  name='restaurantSelector'>
			<select name='rid' onchange=\"this.form.submit()\"><option value=''>Choose a Restaurant</option>";
	foreach($rests as $r){
		$ret.="\n<option value='".$r->restaurantID."'>".$r->restaurantName."</option>";
	}
	$ret.="</select></form></div>";
}else {
	//$toast->showRawArray($_REQUEST);
	$toast = new ToastReport($_REQUEST['rid']);
	$toast ->setStartTime(date("Y-m-d G:i:s",strtotime($bot)));
	$toast ->setEndTime(date("Y-m-d G:i:s",strtotime($latest)));
	$orders=$toast->getTippedOrders();
	$o=$orders[0];
	$order=$toast->getPaymentInfo($orders[0]->ToastCheckID);
	if(count($orders)!=0){
		$toast ->setStartTime(date("Y-m-d 00:00:00",strtotime($order->openedDate)));
		$toast ->setEndTime(date("Y-m-d 23:59:59",strtotime($order->openedDate)));
		$employees=$toast->getClockedInEmployees("Team Member");
		$ret.="<div><strong>There are ".count($orders)." order(s) that require tip assignments.</strong></div>";
		$ret.="<div>
		<h4>Check #".$order->checkNumber;
		if(isset($order->tabName) && $order->tabName!="") {$ret.=": ".$order->tabName;}
		$ret.="</h4>";
		$ret.="<div>Opened: ".date("m/d/Y g:i a",strtotime($order->openedDate))." || Paid: ".date("m/d/Y g:i a",strtotime($order->paidDate))." || Closed: ".date("m/d/Y g:i a",strtotime($order->closedDate))."</div>";
		$ret.="<div><strong>Payment Method: ".$order->paymentType." || Tip Amount: ".money_format("%.2n", $order->tipAmount)." || Order Total: ".money_format("%.2n", $order->totalAmount)."</strong></div>
		<script>
			function disableOtherChecks(){
		";
		foreach($employees as $e){
			$ret.="
			if (document.getElementById(\"d-".$e->GUID."\").disabled == true) {
				document.getElementById(\"d-".$e->GUID."\").disabled = false;
			}else if (document.getElementById(\"d-".$e->GUID."\").disabled == false) {
				document.getElementById(\"d-".$e->GUID."\").checked = false;
				document.getElementById(\"d-".$e->GUID."\").disabled = true;
			}
			if (document.getElementById(\"w-".$e->GUID."\").disabled == true) {
				document.getElementById(\"w-".$e->GUID."\").disabled = false;
			}else if (document.getElementById(\"w-".$e->GUID."\").disabled == false) {
				document.getElementById(\"w-".$e->GUID."\").checked = false;
				document.getElementById(\"w-".$e->GUID."\").disabled = true;
			}
			";
		}
		$ret.="
			}
		</script>
		<div><form method='POST' action='".$page."' >
		<table>
			<tr>
				<td>3rd Party/No One</td>
				<td><label for='d-a0'>Driver?</label> <input type='checkbox' name='driver[]' onclick='disableOtherChecks()' value='a0' id='d-a0'/></td>
				<td></td>
			</tr>";
		foreach($employees as $e){
			$ret.="
			<tr>
				<td><span style='text-transform:capitalize;'>".$e->employeeName."</span></td>
				<td><label for='d-".$e->GUID."'>Driver?</label> <input type='checkbox' name='driver[]' value='".$e->GUID."' id='d-".$e->GUID."'/> </td>
				<td><label for='w-".$e->GUID."'>Worked On?</label> <input type='checkbox' name='worked[]' value='".$e->GUID."' id='w-".$e->GUID."'/><td>
			</tr>";
		}
		$ret.="</table><br />
		<input type='hidden' name='chkID' value='".$o->ToastCheckID."' />
		<input type='hidden' name='rid' value='".$_REQUEST['rid']."' />
		<input type='submit' value='Save Check #".$order->checkNumber."' /></form></div></div>";
	} else {
		$ret.="There are not any orders that need their tips assigned to employees.";
	}
}
