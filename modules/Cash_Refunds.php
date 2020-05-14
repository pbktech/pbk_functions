<?php
if (isset($_REQUEST['startDate'])) {
    $startDate=$_REQUEST['startDate'];
} else {
    $startDate="";
}
if (isset($_REQUEST['endDate'])) {
    $endDate=$_REQUEST['endDate'];
} else {
    $endDate="";
}
        $ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('#startDate').datepicker({
        dateFormat : 'mm/dd/yy'
    });
    jQuery('#endDate').datepicker({
        dateFormat : 'mm/dd/yy'
    });
		jQuery(\"#submit\").click(function(){
			window.scrollTo(0,0);
			jQuery(\"#queryResults\").hide();
			jQuery(\"#processingGif\").show();
		});
});
</script>
		<div id='dateSearch'>
			<form method='get' action='".site_url()."/finance/finance-reports/cash_refunds/' >
				<h4>Please choose a date range</h4>
				<div class=\"form-group\">
					<label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"".$startDate."\"/><br />
					<label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"".$endDate."\"/>
				</div>
				<div class=\"form-group\">
					<input id='submit' type='submit' value='SEARCH' />
				</div>
			</form>
		</div>
		<div id='processingGif' style=\"display: none;text-align:center;\"><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>
		";
if (isset($_GET['endDate']) && isset($_GET['startDate'])) {
    global $wpdb;
    $startDate=date('Y-m-d', strtotime($_GET['startDate']));
    $endDate=date('Y-m-d', strtotime($_GET['endDate']));
    $result=$wpdb->get_results("SELECT * FROM pbc2.pbc_paymentDetails LEFT JOIN pbc2.pbc_pbrestaurants ON pbc2.pbc_paymentDetails.restaurantID=pbc2.pbc_pbrestaurants.restaurantID
WHERE pbc2.pbc_paymentDetails.restaurantID!=0 AND paymentType!='Cash' AND paidDate BETWEEN '".$startDate." 00:00:00' AND '".$endDate." 23:59:59' AND checkID IN
(select checkID FROM pbc2.pbc_paymentDetails WHERE paymentType='Cash'  AND checkAmount < 0) ORDER by pbc_paymentDetails.restaurantID, paidDate");
    if ($result) {
        $file = fopen(temp_dl_folder.'cash_refunds_'.$startDate.'_'.$endDate.'.csv', 'w');
        $ret.="
<script>
jQuery(document).ready( function () {
    jQuery('#myTable').DataTable();
} );
</script>
<div id='queryResults'>
	<table id='myTable' class=\"table table-striped table-hover\" style='width:100%;'>";
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
        foreach ($result as $r) {
            $ret.="\n<tr><td>".$r->restaurantName."</td>
		<td>".$r->orderNumber."</td>
		<td>".date("m/d/Y g:i a", strtotime($r->orderDate))."</td><td>".$r->paymentType."</td><td>".$r->checkAmount."</td></tr>";
            fputcsv($file, array($r->restaurantName,$r->orderNumber,$r->orderDate,$r->paymentType,$r->checkAmount));
        }
        fclose($file);
        $ret.="
		<tfoot style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
			<tr>
			<th>Restaurant</th>
			<th>Check Number</th>
			<th>Order Date</th>
			<th>Payment Type</th>
			<th>Check Amount</th>
			</tr>
		</tfoot>
		</table>";
        if (file_exists(temp_dl_folder.'cash_refunds_'.$startDate.'_'.$endDate.'.csv')) {
            $ret.="<div><a href='".temp_dl_addy."cash_refunds_".$startDate."_".$endDate.".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</div>";
        }
        $ret.="
		</div>";
    } else {
        $ret.="<div class='alert alert-warning'>There were no cash refunds found</div>";
    }
}
