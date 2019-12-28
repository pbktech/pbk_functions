<?php
function getRefundReceipt($orderID) {
	global $wpdb;
	$result=$wpdb->get_var( "SELECT receiptID FROM pbc2.pbc_paymentDetails WHERE orderID='$orderID' AND checkAmount<0" );
	return $result;
}
if(!isset($_REQUEST['endDate']) || !isset($_REQUEST['startDate'])) {
		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('.datePickker').datepicker({
        dateFormat : 'yy-mm-dd'
    });
});

</script>
		<div>
			<form method='get' action='".site_url()."/finance/finance-reports/cash_refunds/' >
				<h4>Please choose a date range</h4>
				<div>
					<label for='startDate'>Start Date</label><br /><input type=\"text\" class=\" datePicker\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
					<label for='endDate'>End Date</label><br /><input type=\"text\" class=\" datePicker\" id=\"endDate\" name=\"endDate\" value=\"\"/>
				</div>
				<div>
					<input type='submit' value='SEARCH' />
				</div>
			</form>
		</div>


		";
}else {
	global $wpdb;
	$result=$wpdb->get_results("SELECT * FROM pbc2.pbc_paymentDetails LEFT JOIN pbc2.pbc_pbrestaurants ON pbc2.pbc_paymentDetails.restaurantID=pbc2.pbc_pbrestaurants.restaurantID
WHERE pbc2.pbc_paymentDetails.restaurantID!=0 AND paymentType!='Cash' AND paidDate BETWEEN '".$_REQUEST['startDate']." 00:00:00' AND '".$_REQUEST['endDate']." 23:59:59' AND checkID IN
(select checkID FROM pbc2.pbc_paymentDetails WHERE paymentType='Cash'  AND checkAmount < 0) ORDER by pbc_paymentDetails.restaurantID, paidDate");
	$file = fopen(temp_dl_folder.'cash_refunds_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv', 'w');
	$ret.="<table class='table '>";
	fputcsv($file, array("Restaurant","Check Number","Order Date","Payment Type","Check Amount"));
	foreach($result as $r){
		$ret.="\n<tr><td>".$r->restaurantName."</td>
		<td>
		<a href='https://www.toasttab.com/receipts/".$r->toastExportId."/".$r->receiptID."' target='_blank'>".$r->orderNumber."</a><br />
		<a href='https://www.toasttab.com/receipts/".$r->toastExportId."/".getRefundReceipt($r->orderID)."' target='_blank'>Refund</a>
		</td>
		<td>".$r->orderDate."</td><td>".$r->paymentType."</td><td>".$r->checkAmount."</td></tr>";
		fputcsv($file, array($r->restaurantName,$r->orderNumber,$r->orderDate,$r->paymentType,$r->checkAmount));
	}
	fclose($file);
	$ret.="</table>";
	if(file_exists(temp_dl_folder.'cash_refunds_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		$ret.="<p><a href='".temp_dl_addy."cash_refunds_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
	/*
print "<pre>";
	print_r($result);
print "</pre>";
*/
}

?>
