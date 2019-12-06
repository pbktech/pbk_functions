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
			<form method='get' action='".site_url()."/finance/finance-reports/payroll_export/' >
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
	$result=$wpdb->get_results("select CONCAT(first_name,' ',last_name) as Name, payroll_id, SUM(reg_hrs) as Hours, SUM(ovt_hrs) as OT, SUM(tip_decl_amt) as Tips 
FROM time_card_dtl, emp_def 
WHERE time_card_dtl.emp_seq=emp_def.emp_seq AND time_card_dtl.restaurantID=emp_def.restaurantID AND clk_in_date_tm BETWEEN '".$_REQUEST['startDate']."' AND '".$_REQUEST['endDate']."' and payroll_id!=0 GROUP BY payroll_id ORDER by last_name
");
	$file = fopen('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Payroll_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv', 'w');
	$ret.="<table>";
	foreach($result as $r){
		$ret.="\n<tr><td>".$r->Name."</td><td>".$r->payroll_id."</td><td>".$r->Hours."</td><td>".$r->OT."</td><td>".$r->Tips."</td></tr>";
		fputcsv($file, array($r->Name,$r->payroll_id,$r->Hours,$r->OT,$r->Tips));
	}
	fclose($file);
	$ret.="</table>";
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Payroll_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		$ret.="<p><a href='https://c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Payroll_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
	/*
print "<pre>";
	print_r($result);
print "</pre>";
*/
}

?>