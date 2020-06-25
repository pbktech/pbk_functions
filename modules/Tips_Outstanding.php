<?php
$time = microtime();
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$latest=date("Y-m-d",time() - 60 * 60 * 24)." 23:59:59";
$toast = new ToastReport();
$rests=$toast->getAvailableRestaurants();
foreach($rests as $r){
	$orStmt[]="pbc_ToastOrderPayment.restaurantID=".$r->restaurantID;
}
$store=" AND (".implode(' OR ',$orStmt).")";
echo $store;
$bot="2020-05-01 00:00:00";
$q="SELECT restaurantName,businessDate ,checkNumber,tipAmount FROM pbc2.pbc_ToastOrderPayment,pbc2.pbc_pbrestaurants,pbc_ToastCheckHeaders
    WHERE pbc2.pbc_ToastOrderPayment.restaurantID=pbc2.pbc_pbrestaurants.restaurantID AND businessDate > '2020-05-01 00:00:00' AND
    pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT orderGUID FROM pbc2.pbc_TipDistribution) AND pbc_ToastOrderPayment.ToastCheckID = pbc_ToastCheckHeaders.GUID and
    tipAmount!=0 $store ORDER BY pbc_pbrestaurants.restaurantID,restaurantName";
$results=$wpdb->get_results($q);
if($results){
	$fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
	$D['Options'][]="\"order\": [ 5, 'asc' ]";
	$D['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
	$D['Headers']=array("Restaurant","Date","Check","Tip Amount");
	foreach ($results as $r) {
		$D['Results'][]=array(
			$r->restaurantName,
			date("m/d/Y",strtotime($r->dateOfBusiness)),
			$r->checkNumber,
			$fmt->formatCurrency($r->tipAmount,"USD")
		);
	}
		echo $toast->showResultsTable($D);
	}else{
?>
<div class="alert alert-secondary" role="alert">
	There were no records found.
</div>
<?php
}
