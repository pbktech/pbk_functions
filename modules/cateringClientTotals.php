<?php
global $ret;
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
			<form method='get' action='".site_url()."/operations/catering-rewards-totals/' >
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
	$reportArray[]=array("Report Dates",date("m/d/Y",strtotime($_REQUEST['startDate'])),date("m/d/Y",strtotime($_REQUEST['endDate'])));
	$reportArray[]=array("Client","Total $","Number of Orders");
	$report=new ToastReport();
	$report->setStartTime($_REQUEST['startDate']);
	$report->setEndTime($_REQUEST['endDate']);
	$orders=$report->getMonkeyRewardsSales();
	$ret.="
	<div><h4>Catering Total Sales for ".date("m/d/Y",strtotime($_REQUEST['startDate']))." - ".date("m/d/Y",strtotime($_REQUEST['endDate']))."</h4></div>
		<table>
			<thead>
					<tr><th>Client</th><th>Total $</th><th>Number of Orders</th></tr>
			</thead>
			<tbody>
	";
	foreach($orders as $order){
		$ret.="
		<tr><td>".$order->client_name."</td><td>".$report->switchNegNumber($order->Total,2)."</td><td>".$order->Orders."</td></tr>";
		$reportArray[]=array($order->client_name,$report->switchNegNumber($order->Total,2),$order->Total);
	}
	$ret.="</tbody></table>";
	$report->buildSCV($reportArray,"catering_sales_".$_REQUEST['startDate']."_".$_REQUEST['endDate']);
	if(file_exists(temp_dl_folder.'catering_sales_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		$ret.="<p><a href='".temp_dl_addy."catering_sales_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
}
