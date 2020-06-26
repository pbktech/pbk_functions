<?php
global $ret;
global $wp;
global $wpdb;
$fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
$cu = wp_get_current_user();
if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
	$sql="SELECT GUID,restaurantName FROM pbc_pbrestaurants WHERE isOpen=1";
}else {
	$sql="SELECT GUID,restaurantName FROM pbc_pbrestaurants WHERE isOpen=1 AND pbc_pbrestaurants.GUID IN
(SELECT GUID FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='" . $current_user->ID . "')";
}
$files=$wpdb->get_results($sql);
foreach($files as $file){
	$rests[$file->restaurantName]=$file->GUID;
}
if(isset($_GET['cguid']) && isset($_GET['sguid'])) {
	$toast = new Toast(trim($_GET['sguid']));
	date_default_timezone_set($toast->getTimeZone());
	$c=$toast->getCustomer($_GET['cguid']);
	$transactions=$toast->getCustomerTransactions($c->guid);
	$ret.="<h3>Customer Credit Transaction History</h3><div>
			<p><strong>".ucfirst($c->firstName)." ".ucfirst($c->lastName)."</strong></p>";
	foreach($transactions as $t){
		$ret.="<p>". date("m/d/Y",strtotime($t->transactionDate)) . " " .$t->transactionType . " " . $fmt->formatCurrency($t->amount,"USD") . " <i>Expires: " . date("m/d/Y",strtotime($t->expirationDate)) ."</i> ".$t->note . "</p>";
	}
	$ret.="</div>
	";
}
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$toast = new Toast(trim($_POST['GUID']));
	date_default_timezone_set($toast->getTimeZone());
	$toast->setOrderGUID($_POST['orderGUID']);
	$json=$toast->getOrderInfo(trim($_POST['orderGUID']));
	echo "<pre>";
	print_r($json);
	echo "</pre>";
}
if(!isset($_POST['orderGUID'])){$_POST['orderGUID']='';}
if(!isset($_POST['GUID'])){$_POST['GUID']='';}
$restaurant=new Restaurant();
$ret.="
<div>
	<h3>Search for an Order</h3>
	<form method='POST' name='phoneSearch' action='".home_url( $wp->request )."' onsubmit='return validateForm()'>
		<div class=\"form-group\">
			<input type='text' class=\"form-control\" name='orderGUID' id='orderGUID' value='".$_POST['orderGUID']."' placeholder='Order GUID'/>
		</div>
		<div class=\"form-group\">
		";
$ret.=$restaurant->buildRestaurantSelector(0,'GUID');
$ret.="
</div>
<div class=\"form-group\">
		<input type='hidden' name='part' value='1' />
		<input type='submit' value='Search' />
		</div>
	</form>
</div>";
