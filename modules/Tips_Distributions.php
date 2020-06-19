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
	$tipShare=array();
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
	if(isset($orders[0])){
		$o=$orders[0];
		$order=$toast->getPaymentInfo($orders[0]->ToastCheckID);
	}
	if(is_array($orders) && count($orders)!=0){
		$fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
		$toast ->setStartTime(date("Y-m-d 00:00:00",strtotime($order->openedDate)));
		$toast ->setEndTime(date("Y-m-d 23:59:59",strtotime($order->openedDate)));
		$employees=$toast->getClockedInEmployees("Team Member");
		$ret.="<div><strong>There are ".count($orders)." order(s) that require tip assignments.</strong></div>";
		$ret.="<div>
		<h4>Check #".$order->checkNumber;
		if(isset($order->tabName) && $order->tabName!="") {$ret.=": ".$order->tabName;}
		$ret.="</h4>";
		$ret.="<div>Opened: ".date("m/d/Y g:i a",strtotime($order->openedDate))." || Paid: ".date("m/d/Y g:i a",strtotime($order->paidDate))." || Closed: ".date("m/d/Y g:i a",strtotime($order->closedDate))."</div>";
		$ret.="<div><strong>Payment Method: ".$order->paymentType." || Tip Amount: ".$fmt->formatCurrency($order->tipAmount,"USD")." || Order Total: ".$fmt->formatCurrency($order->totalAmount,"USD")."</strong></div>
		<script>
		jQuery(function() {
		  enable_cb();
		  jQuery(\"#d-a0\").click(enable_cb);
		});
		function enable_cb() {
		  if (jQuery(\"#d-a0\").prop(\"checked\")==true) {
				jQuery(\"input.group1\"). prop(\"checked\", false);
				jQuery(\"input.group1\").attr(\"disabled\", true);
		  } else {
				jQuery(\"input.group1\").removeAttr(\"disabled\");
		  }
		}
		</script>
		<div><form method='POST' action='".$page."' >
		<table>
			<tr>
				<td>3rd Party/No One</td>
				<td><label for='d-a0'>Driver?</label> <input type='checkbox' name='driver[]' value='a0' id='d-a0'/></td>
				<td></td>
			</tr>";
		foreach($employees as $e){
			$ret.="
			<tr>
				<td><span style='text-transform:capitalize;'>".$e->employeeName."</span></td>
				<td><label for='d-".$e->GUID."'>Driver?</label> <input class='group1' type='checkbox' name='driver[]' value='".$e->GUID."' id='d-".$e->GUID."'/> </td>
				<td><label for='w-".$e->GUID."'>Worked On?</label> <input class='group1' type='checkbox' name='worked[]' value='".$e->GUID."' id='w-".$e->GUID."'/><td>
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
