<script>
jQuery(document).ready(function($) {
    $('.datePicker').datepicker({
        dateFormat : 'mm/dd/yy'
    });
    $('.submit').click(function() {
      $('.processing').show();
      $('#queryResults').hide();
    });
});
</script>
<div class="container">
    <form method='get' action='<?php echo site_url();?>/finance/finance-reports/sales_and_labor/' >
        <h4>Please choose a date range</h4>
        <div class="row">
            <div class="col"><label for='startDate'>Start Date</label><br /><input type="text" class="form-control datePicker" id="startDate" name="startDate" value=""/></div>
            <div class="col"><label for='endDate'>End Date</label><br /><input type="text" class="form-control datePicker" id="endDate" name="endDate" value=""/></div>
        </div>
        <div class="row">
            <input type='submit' class="submit" value='SEARCH' />
        </div>
    </form>
    <div class="row processing" style="display: none; text-align: center;">
        <img src='<?php echo PBKF_URL; ?>/assets/images/processing.gif' style='height:92px;width:92px;' />
    </div>
</div>
<?php
$fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
if(isset($_REQUEST['endDate'], $_REQUEST['startDate'])) {
    $retArray = array();
	echo "<hr />";
	global $wpdb;
	function getLaborInfoOld($restaurantID,$businessDate,$hour){
		global $wpdb;
        return $wpdb->get_results("SELECT inDate,outDate,hourlyWage FROM pbc2.pbc_ToastTimeEntries WHERE inDate < '".$businessDate." ".$hour.":59:59' AND outDate > '".$businessDate." ".$hour.":59:59' AND restaurantID='".$restaurantID."' AND businessDate= '".$businessDate."'");
	}
	function getSalesInfoOld($restaurantID,$businessDate,$hour){
		global $wpdb;
        return $wpdb->get_results("SELECT checkAmount FROM pbc_sum_CheckSales WHERE guid IN (SELECT GUID FROM pbc_ToastOrderHeaders WHERE openedDate BETWEEN '".$businessDate." ".$hour.":00:00' AND '".$businessDate." ".$hour.":59:59' AND restaurantID='".$restaurantID."')");
	}
	$result = $wpdb->get_results("SELECT restaurantName,restaurantID as 'Restaurant' FROM  pbc_pbrestaurants WHERE isOpen=1");
    if ($result) {

        $D['Message'] = "Sales and Labor : " . $_REQUEST['startDate'] . " - " . $_REQUEST['endDate'];
        $D['Options'][] = "\"order\": [ 1, 'asc' ]";
        $D['Options'][] = "\"lengthMenu\": [ [10, 20, -1], [10, 20, \"All\"] ]";
        $D['Headers'] = array("Restaurant","Date", "Sales Hour", "Total Hourly Sales", "Average Hourly Sales", "Labor Cost", "People Clocked-In");
        foreach($result as $row){
            for($i=strtotime($_REQUEST['startDate']);$i<=strtotime($_REQUEST['endDate']);$i+=86400){
                for ($SalesHour=6; $SalesHour < 21; $SalesHour++) {
                    $salesInfo=getSalesInfoOld($row->Restaurant,date("Y-m-d",$i),$SalesHour);
                    $laborInfo=getLaborInfoOld($row->Restaurant,date("Y-m-d",$i),$SalesHour);
                    if(count($salesInfo)!=0 || count($laborInfo)!=0){
                        $laborTotal = 0;
                        foreach($laborInfo as $labor){
                            if (date("G",strtotime($labor->inDate))!=$SalesHour && date("G",strtotime($labor->outDate))!=$SalesHour) {
                                $laborTotal+=$labor->hourlyWage;
                            }elseif(date("G",strtotime($labor->inDate))==$SalesHour){
                                $mins=date("i",strtotime($labor->inDate))/60;
                                $laborTotal+=round($mins*$labor->hourlyWage,2);
                            }elseif(date("G",strtotime($labor->outDate))==$SalesHour){
                                $mins=date("i",strtotime($labor->outDate))/60;
                                $laborTotal+=round($mins*$labor->hourlyWage,2);
                            }
                        }
                        $total = 0;
                        foreach($salesInfo as $sales){
                            $total+=$sales->checkAmount;
                        }
                        $avgLabor = 0;
                        if(count($salesInfo) !== 0){
                            $avgLabor = round(($total/count($salesInfo)),2);
                        }
                        $D['Results'][] = array(
                            $row->restaurantName,
                            date("m/d/Y",$i),
                            $SalesHour,
                            $fmt->formatCurrency($total, 'USD'),
                            $fmt->formatCurrency($avgLabor, 'USD'),
                            $fmt->formatCurrency($laborTotal, 'USD'),
                            count($laborInfo)
                        );
                    }
                }
            }
        }
        $toast = new ToastReport();
        echo $toast->showResultsTable($D);
    } else {
        echo '
        <div class="alert alert-warning" role="alert">
            There were no records found.
        </div>';
    }
}