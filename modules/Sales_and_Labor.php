<?php
// $retArray=[Restaurant][Date][Hour][Labor_Cost]

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
			<form method='get' action='".site_url()."/finance/finance-reports/sales_and_labor/' >
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
if(isset($_REQUEST['endDate']) && isset($_REQUEST['startDate'])) {
	$ret.="<hr />";
	global $wpdb;
	function getLaborInfoOld($restaurantID,$businessDate,$hour){
		global $wpdb;
		$result = $wpdb->get_results("SELECT inDate,outDate,hourlyWage FROM pbc2.pbc_ToastTimeEntries WHERE inDate < '".$businessDate." ".$hour.":59:59' AND outDate > '".$businessDate." ".$hour.":59:59' AND restaurantID='".$restaurantID."' AND businessDate= '".$businessDate."'");
		return $result;
	}
	function getSalesInfoOld($restaurantID,$businessDate,$hour){
		global $wpdb;
		$result = $wpdb->get_results("SELECT sub_ttl FROM chk_detail WHERE chk_open_date_time BETWEEN '".$businessDate." ".$hour.":00:00' AND '".$businessDate." ".$hour.":59:59' AND restaurantID='".$restaurantID."'");
		return $result;
	}
	$result = $wpdb->get_results("SELECT restaurantName,restaurantID as 'Restaurant' FROM  pbc_pbrestaurants WHERE isOpen=1");
	foreach($result as $row){
		for($i=strtotime($_REQUEST['startDate']);$i<strtotime($_REQUEST['endDate']);$i+=86400){
			for ($SalesHour=6; $SalesHour < 21; $SalesHour++) {
				$salesInfo=getSalesInfoOld($row->Restaurant,date("Y-m-d",$i),$SalesHour);
				$laborInfo=getLaborInfoOld($row->Restaurant,date("Y-m-d",$i),$SalesHour);
				if(count($salesInfo)!=0 || count($laborInfo)!=0){
					foreach($laborInfo as $labor){
						if (date("G",strtotime($labor->inDate))!=$SalesHour && date("G",strtotime($labor->outDate))!=$SalesHour) {
							$retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['labor']+=$labor->hourlyWage;
						}elseif(date("G",strtotime($labor->inDate))==$SalesHour){
							$mins=date("i",strtotime($labor->inDate))/60;
							$retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['labor']+=round($mins*$labor->hourlyWage,2);
						}elseif(date("G",strtotime($labor->outDate))==$SalesHour){
							$mins=date("i",strtotime($labor->outDate))/60;
							$retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['labor']+=round($mins*$labor->hourlyWage,2);
						}
					}
					foreach($salesInfo as $sales){
						$retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['total']+=$sales->sub_ttl;
					}
					$retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['sales']=round(($retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['total']/count($salesInfo)),2);
					$retArray[$row->restaurantName][date("m/d/Y",$i)][$SalesHour]['peeps']=count($laborInfo);
				}
			}
		}
	}
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Sales-and-Labor_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {unlink('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Sales-and-Labor_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv');}
	$file = fopen('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Sales-and-Labor_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv', 'w');

	$ret.="<table>";
	fputcsv($file, array("Restaurant","Date", "Sales Hour", "Total Hourly Sales", "Average Hourly Sales", "Labor Cost", "People Clocked-In"));
	$ret.="\n<tr><td>Restaurant</td><td>Date</td><td>Sales Hour</td><td>Total Hourly Sales</td><td>Average Hourly Sales</td><td>Labor Cost</td><td>People Clocked-In</td></tr>";

	foreach($retArray as $retA =>$r){
		foreach($r as $date =>$hour){
			foreach($hour as $h => $cost){
				$ret.="\n<tr><td>".$retA."</td><td>".$date."</td><td>".$h."</td><td>".money_format('%(#10n',$cost['total'])."</td><td>".money_format('%(#10n',$cost['sales'])."</td><td>".money_format('%(#10n',$cost['labor'])."</td><td>".$cost['peeps']."</td></tr>";
				fputcsv($file, array($retA,$date,$h,$cost['total'],$cost['sales'],$cost['labor'],$cost['peeps']));
			}
		}
	}
	fclose($file);
	$ret.="</table>";
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Sales-and-Labor_'.$_REQUEST['startDate'].'_'.$_REQUEST['endDate'].'.csv')) {
		echo "<p><a href='https://c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Sales-and-Labor_".$_REQUEST['startDate']."_".$_REQUEST['endDate'].".csv' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}

}
/*
echo "<pre>";
print_r($retArray);
echo "</pre>";
*/
