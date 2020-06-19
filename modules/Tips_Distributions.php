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
		$bot="2020-05-01 00:00:00";
	//	$bot="2019-01-07 00:00:00";
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
		 jQuery(document).ready(function(){
			 	jQuery('#d-a0').click(function(){
					if(jQuery(this).prop(\"checked\") == true){
						jQuery(\"input.group1\"). prop(\"checked\", false);
						jQuery(\"input.group1\").attr(\"disabled\", true);
						jQuery(\".toDisable\").hide();
					}else if(jQuery(this).prop(\"checked\") == false){
						jQuery(\"input.group1\").removeAttr(\"disabled\");
						jQuery(\".toDisable\").show();
					}
			});
		});
		</script>
		<div class='container'>
		<form method='POST' action='".$page."' >
			<div class='row'>
				<div class='col'>3rd Party/No One</div>
				<div class='col'><label for='d-a0'>Driver?</label> <input type='checkbox' name='driver[]' value='a0' id='d-a0'/></div>
			</div>";
		foreach($employees as $e){
			$ret.="
			<div class='row toDisable'>
				<div class='col'><span style='text-transform:capitalize;'>".$e->employeeName."</span></div>
				<div class='col'><label for='d-".$e->GUID."'>Driver?</label> <input class='group1' type='checkbox' name='driver[]' value='".$e->GUID."' id='d-".$e->GUID."'/> </div>
				<div class='col'><label for='w-".$e->GUID."'>Worked On?</label> <input class='group1' type='checkbox' name='worked[]' value='".$e->GUID."' id='w-".$e->GUID."'/></div>
			</div>";
		}
		$ret.="<br />
		<input type='hidden' name='chkID' value='".$o->ToastCheckID."' />
		<input type='hidden' name='rid' value='".$_REQUEST['rid']."' />
		<input type='submit' value='Save Check #".$order->checkNumber."' /></form></div>";
	} else {
		$ret.="
		<div class=\"alert alert-secondary\" role=\"alert\">
		There are not any orders that need their tips assigned to employees.
		</div>";
	}
}
