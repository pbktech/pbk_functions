<?php
if(!isset($_REQUEST['endDate']) || !isset($_REQUEST['startDate'])) {
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
			<form method='get' action='".site_url()."/finance/finance-reports/ticket_times/' >
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
	global $wpdb;
	$file = fopen(temp_dl_folder.'ticket_times_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv', 'w');
	fputcsv($file, array("Restaurant","Order Date","Check Number","Opened","Sent to Kitchen", "Cleared from Expo", "Duration"));
	$result=$wpdb->get_results("SELECT pbc_ToastOrderHeaders.GUID as 'OrderGUID',restaurantName,pbc_ToastOrderHeaders.restaurantID as 'restaurant',businessDate FROM pbc2.pbc_ToastOrderHeaders,pbc2.pbc_pbrestaurants WHERE pbc_ToastOrderHeaders.restaurantID=pbc_pbrestaurants.restaurantID and businessDate
		BETWEEN '".date("Y-m-d",strtotime($_REQUEST['startDate']))."' AND '".date("Y-m-d",strtotime($_REQUEST['endDate']))."' ORDER BY restaurant");
	foreach($result as $r){
		$checkResults=$wpdb->get_results("SELECT checkNumber,openedDate FROM pbc2.pbc_ToastCheckHeaders WHERE ToastOrderID='".$r->OrderGUID."'");
		foreach($checkResults as $cr){
			$kdsRestults=$wpdb->get_results("SELECT sent_time as 'Fired', done_time as 'Bumped', duration FROM pbc2.kds_detail WHERE
				restaurantID='".$r->restaurant."' AND checkNumber='".$cr->checkNumber."' and sent_time LIKE '".$r->businessDate."%' AND station=''");
			foreach($kdsRestults as $kr){
				fputcsv($file, array(
					$r->restaurantName,
					date("m/d/Y",strtotime($r->businessDate)),
					$cr->checkNumber,
					date("m/d/Y G:i:s",strtotime($cr->openedDate)),
					date("m/d/Y G:i:s",strtotime($kr->Fired)),
					date("m/d/Y G:i:s",strtotime($kr->Bumped)),
					$kr->duration
				));
			}
		}
	}
	fclose($file);
	if(file_exists(temp_dl_folder.'ticket_times_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		$ret.="<p><a href='".temp_dl_addy."ticket_times_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
}
?>
