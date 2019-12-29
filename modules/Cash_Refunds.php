<?php
function getRefundReceipt($orderID) {
	global $wpdb;
	$result=$wpdb->get_var( "SELECT receiptID FROM pbc2.pbc_paymentDetails WHERE orderID='$orderID' AND checkAmount<0" );
	return $result;
}
if(!isset($_GET['endDate']) || !isset($_GET['startDate'])) {
		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('#startDate').datepicker({
        dateFormat : 'mm/dd/yy'
    });
    jQuery('#endDate').datepicker({
        dateFormat : 'mm/dd/yy'
    });
});

</script>
		<div>
			<form method='get' action='".site_url()."/finance/finance-reports/cash_refunds/' >
				<h4>Please choose a date range</h4>
				<div class=\"form-group\">
					<label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
					<label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\"/>
				</div>
				<div class=\"form-group\">
					<input type='submit' value='SEARCH' />
				</div>
			</form>
		</div>
		";
}else {
	global $wpdb;
	$startDate=date('Y-m-d',strtotime($_GET['startDate']));
	$endDate=date('Y-m-d',strtotime($_GET['endDate']));
	$result=$wpdb->get_results("SELECT * FROM pbc2.pbc_paymentDetails LEFT JOIN pbc2.pbc_pbrestaurants ON pbc2.pbc_paymentDetails.restaurantID=pbc2.pbc_pbrestaurants.restaurantID
WHERE pbc2.pbc_paymentDetails.restaurantID!=0 AND paymentType!='Cash' AND paidDate BETWEEN '".$startDate." 00:00:00' AND '".$endDate." 23:59:59' AND checkID IN
(select checkID FROM pbc2.pbc_paymentDetails WHERE paymentType='Cash'  AND checkAmount < 0) ORDER by pbc_paymentDetails.restaurantID, paidDate");
	$file = fopen(temp_dl_folder.'cash_refunds_'.$startDate.'_'.$endDate.'.csv', 'w');
	$ret.="<table class=\"table table-striped table-hover\">";
	fputcsv($file, array("Restaurant","Check Number","Order Date","Payment Type","Check Amount"));
	$ret.="
		<thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
			<tr>
			<th>Restaurant</th>
			<th>Check Number</th>
			<th>Order Date</th>
			<th>Payment Type</th>
			<th>Check Amount</th>
			</tr>
		</thead>
	";
	foreach($result as $r){
		$ret.="\n<tr><td>".$r->restaurantName."</td>
		<td>".$r->orderNumber."</td>
		<td>".$r->orderDate."</td><td>".$r->paymentType."</td><td>".$r->checkAmount."</td></tr>";
		fputcsv($file, array($r->restaurantName,$r->orderNumber,$r->orderDate,$r->paymentType,$r->checkAmount));
	}
	fclose($file);
	$ret.="</table>";
	if(file_exists(temp_dl_folder.'cash_refunds_'.$startDate.'_'.$endDate.'.csv')) {
		$ret.="<p><a href='".temp_dl_addy."cash_refunds_".$startDate."_".$endDate.".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
	/*
print "<pre>";
	print_r($result);
print "</pre>";
*/
}

?>
