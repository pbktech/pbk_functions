<?php
// $reports=[N][[Title][SQL][Column Headers (SQL))][File Name]]
$reports[1]=array("Title"=>"Ticket Times","SQL"=>"SELECT restaurantName as 'Name', date_format(sent_time,'%c/%e/%Y') as Weekday,time_format(sent_time,'%h %p') as Sales_Hour , SEC_TO_TIME(avg(duration)) as Average_Wait,
SEC_TO_TIME(MAX(duration)) as Max_Wait, COUNT(*) as Customer_Count FROM `kds_detail`,`pbc_pbrestaurants`
WHERE `kds_detail`.`restaurantID` = `pbc_pbrestaurants`.`restaurantID` AND sent_time BETWEEN '".$_REQUEST['startDate']." 00:00:00' AND '".$_REQUEST['endDate']." 23:59:59' GROUP BY `kds_detail`.restaurantID,date_format(sent_time,'%c%e%Y'),hour(sent_time) ORDER BY `kds_detail`.restaurantID,date_format(sent_time,'%c%e%Y'),hour(sent_time) ",
"Headers"=>array("Name","Weekday","Sales_Hour","Average_Wait","Max_Wait","Customer_Count"),"FN"=>"tt"
);
$reports[2]=array("Title"=>"Tenders","SQL"=>"SELECT restaurantName as 'Name', BusinessDate as 'Date_of_Business', Tender, Count, Total FROM v_R_TendersFromArchive,pbc_pbrestaurants WHERE v_R_TendersFromArchive.restaurantID=pbc_pbrestaurants.restaurantID
AND BusinessDate BETWEEN '".$_REQUEST['startDate']."' AND '".$_REQUEST['endDate']."';",
"Headers"=>array("Name","Date_of_Business","Tender","Count","Total"),"FN"=>"tenders"
);

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
			<form method='get' action='".site_url()."/finance/finance-reports/multi_reports/' >
				<h4>Please Select a Report</h4>
				<div>
					<select name='rpt'>
						<option value=''>Choose One</option>
						";

						foreach($reports as $r=>$n){
							$checked="";
							if(isset($_REQUEST['rpt']) && $_REQUEST['rpt']==$r) {$checked="selected";}
							$ret.="
						<option value='".$r."' $checked>".$n['Title']."</option>";
						}
						$ret.="
					</select>
				</div>
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
if(isset($_REQUEST['endDate']) && isset($_REQUEST['startDate']) && (isset($_REQUEST['rpt']) && is_numeric($_REQUEST['rpt']))) {
	global $wpdb;
	$ret.="<hr /><h4>".$reports[$_REQUEST['rpt']]['Title']."</h4><br />";
	if($_REQUEST['rpt']==1) {
		$lastImport = $wpdb->get_var( "SELECT MAX(sent_time) FROM pbc2.kds_detail" );
		$ret.="<div >The last imported date is ".date("m/d/Y", strtotime($lastImport)).".</div>";
	}
	$ret.="<table><tr>";
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/'.$reports[$_REQUEST["rpt"]]["FN"].'_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {unlink('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/'.$reports[$_REQUEST["rpt"]]["FN"].'_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv');}
	$file = fopen('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/'.$reports[$_REQUEST["rpt"]]["FN"].'_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv', 'w');
	fputcsv($file,$reports[$_REQUEST['rpt']]['Headers']);
	foreach($reports[$_REQUEST['rpt']]['Headers'] as $h){
		$ret.="<td>".$h."</td>";
	}
	$ret.="</tr>";
	$result = $wpdb->get_results($reports[$_REQUEST['rpt']]['SQL']);
	foreach($result as $row){
		$csvArray=array();
		$ret.="<tr>";
			foreach($reports[$_REQUEST['rpt']]['Headers'] as $h){
				$ret.="<td>".$row->$h."</td>";
				$csvArray[]=$row->$h;
			}
		$ret.="</tr>";
		fputcsv($file,$csvArray);
	}

	$ret.="</table>";
	fclose($file);
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/'.$reports[$_REQUEST["rpt"]]["FN"].'_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		echo "<p><a href='https://c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/".$reports[$_REQUEST['rpt']]['FN']."_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
}
