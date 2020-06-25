<script>
jQuery(function () {
  jQuery('[data-toggle="tooltip"]').tooltip()
})
</script>
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
$bot="2020-05-01 00:00:00";
$q="SELECT restaurantName,businessDate ,checkNumber,tipAmount,pbc_pbrestaurants.restaurantID as 'restaurantID', pbc_ToastOrderPayment.ToastCheckID as 'ToastCheckID' FROM pbc2.pbc_ToastOrderPayment,pbc2.pbc_pbrestaurants,pbc_ToastCheckHeaders
    WHERE pbc2.pbc_ToastOrderPayment.restaurantID=pbc2.pbc_pbrestaurants.restaurantID AND businessDate > '2020-05-01 00:00:00' AND
    pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT orderGUID FROM pbc2.pbc_TipDistribution) AND pbc_ToastOrderPayment.ToastCheckID = pbc_ToastCheckHeaders.GUID and
    tipAmount!=0 $store ORDER BY pbc_pbrestaurants.restaurantID,restaurantName";
$results=$wpdb->get_results($q);
if($results){
	$fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
	$D['Options'][]="\"order\": [ 1, 'asc' ]";
	$D['Options'][]="\"lengthMenu\": [ [10, 20, -1], [10, 20, \"All\"] ]";
	$D['Headers']=array("Restaurant","Date","Check","Tip Amount");
	foreach ($results as $r) {
		$D['Results'][]=array(
			$r->restaurantName,
			date("m/d/Y",strtotime($r->businessDate)),
			"<a href='".home_url( 'operations/tips/tip-distribution/?i='.$r->ToastCheckID )."' target='_blank' title='Assign this order' data-toggle=\"tooltip\" data-placement=\"bottom\">".$r->checkNumber."</a>",
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
