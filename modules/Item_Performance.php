<?php
		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('#startDate').datepicker({
        dateFormat : 'yy-mm-dd'
    });
    jQuery('#endDate').datepicker({
        dateFormat : 'yy-mm-dd'
    });
});

</script>
		<div>
			<form method='get' action='".site_url()."/finance/finance-reports/item_performance/' >
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
if(isset($_REQUEST['endDate']) && isset($_REQUEST['startDate']) ) {
	$rptHeaders=array("Restaurant","Date of Business","Sent to Kitchen","Menu Group","Item","Unique ID","Check Number","Price","Tender Type");
	global $wpdb;
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/item_performance_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {unlink('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/item_performance_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv');}
	$file = fopen('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/item_performance_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv', 'w');
	fputcsv($file,$rptHeaders);
	$result = $wpdb->get_results("SELECT restaurantID from pbc_pbrestaurants WHERE isOpen=1");
	foreach($result as $row){
		$result1 = $wpdb->get_results("SELECT restaurantName as 'Restaurant', DATE_FORMAT(pbc_itemCheckHeaders.dateOfBusiness,'%m/%d/%Y') as 'Date of Business',DATE_FORMAT(pbc_itemCheckHeaders.sentDate,'%r') as 'Sent to Kitchen',menu as 'Menu Group', menuItem as 'Item', pbc_itemCheckHeaders.orderID as 'Unique ID',  pbc_itemCheckHeaders.orderNumber as 'Check Number',
grossPrice as 'Price',paymentType as 'Tender Type'
FROM pbc2.pbc_itemCheckHeaders,pbc_itemSelectionDetails, pbc2.pbc_paymentDetails,pbc_pbrestaurants
WHERE dateOfBusiness BETWEEN '".$_REQUEST['startDate']."' AND '".$_REQUEST['endDate']."' AND idpbc_itemCheckHeaders=idItemCheckHeaders AND pbc_itemCheckHeaders.orderID=pbc_paymentDetails.orderID
AND pbc_itemCheckHeaders.restaurantID=pbc_pbrestaurants.restaurantID AND pbc_itemCheckHeaders.restaurantID='".$row->restaurantID."'");
		foreach($result1 as $row1){
			$csvArray=array();
				foreach($rptHeaders as $h){
					$csvArray[]=$row1->$h;
				}
			fputcsv($file,$csvArray);
		}

	}
	fclose($file);
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/item_performance_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		echo "<p><a href='https://c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/item_performance_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
}
