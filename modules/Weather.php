<?php
use DmitryIvanov\DarkSkyApi\DarkSkyApi;
require_once '/var/www/html/c2.theproteinbar.com/wp-content/plugins/pbr_finance/includes/ToastFunctions/classes/vendor/autoload.php';
require_once '/var/www/html/c2.theproteinbar.com/wp-content/plugins/pbr_finance/includes/ToastFunctions/classes/ToastReport.php';
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
			<form method='get' action='".site_url()."/finance/finance-reports/weather/' >
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
	$report=new ToastReport();
	$file = fopen($report->docSaveLocation.date("Ymd",strtotime($_REQUEST['startDate']))."-".date("Ymd",strtotime($_REQUEST['endDate'])).'-Weather-YoY.csv', 'w');
	fputcsv($file, array("Date","IL","Sales","Summary","DC","Sales","Summary","CO","Sales","Summary"));
	$latLong["Chicago"]=array("Lat"=>41.885858,"Long"=>-87.632561);
	$latLong["District of Columbia"]=array("Lat"=>38.893481,"Long"=>-77.022022);
	$latLong["Colorado"]=array("Lat"=>39.752327,"Long"=>-105.001158);
	$start=strtotime($_REQUEST['startDate']);
	$end=strtotime($_REQUEST['endDate']);
	for ($i=$start; $i <= $end; $i+=86400) {
	  $yesterday=date("Y-m-d",$i);
	  $sdly=$report->sameDayLastYear($yesterday);
	  $il = (new DarkSkyApi('900976ec40ea82641b15a774f2c3adf0'))
	      ->location($latLong["Chicago"]["Lat"],$latLong["Chicago"]["Long"])
	      ->timeMachine($yesterday, ['daily', 'flags']);
	  $sdlyil = (new DarkSkyApi('900976ec40ea82641b15a774f2c3adf0'))
	      ->location($latLong["Chicago"]["Lat"],$latLong["Chicago"]["Long"])
	      ->timeMachine($sdly, ['daily', 'flags']);
	  $dc = (new DarkSkyApi('900976ec40ea82641b15a774f2c3adf0'))
	      ->location($latLong["District of Columbia"]["Lat"],$latLong["District of Columbia"]["Long"])
	      ->timeMachine($yesterday, ['daily', 'flags']);
	  $sdlydc = (new DarkSkyApi('900976ec40ea82641b15a774f2c3adf0'))
	      ->location($latLong["District of Columbia"]["Lat"],$latLong["District of Columbia"]["Long"])
	      ->timeMachine($sdly, ['daily', 'flags']);
	  $co = (new DarkSkyApi('900976ec40ea82641b15a774f2c3adf0'))
	      ->location($latLong["Colorado"]["Lat"],$latLong["Colorado"]["Long"])
	      ->timeMachine($yesterday, ['daily', 'flags']);
	  $sdlyco = (new DarkSkyApi('900976ec40ea82641b15a774f2c3adf0'))
	      ->location($latLong["Colorado"]["Lat"],$latLong["Colorado"]["Long"])
	      ->timeMachine($sdly, ['daily', 'flags']);
	  $report->businessDate=$yesterday;
	  $ilsales=$report->getNetSalesByMarket("Chicago");
	  $dcsales=$report->getNetSalesByMarket("District of Columbia");
	  $cosales=$report->getNetSalesByMarket("Colorado");
	  fputcsv($file, array(date("m/d/Y",$i),
	  round($il->daily()->apparentTemperatureHigh(),0),$ilsales['Sales'],$il->daily()->summary(),
	  round($dc->daily()->apparentTemperatureHigh(),0),$dcsales['Sales'],$dc->daily()->summary(),
	  round($co->daily()->apparentTemperatureHigh(),0),$cosales['Sales'],$co->daily()->summary()
	));
	  $report->businessDate=$sdly;
	  $ilsales=$report->getNetSalesByMarket("Chicago");
	  $dcsales=$report->getNetSalesByMarket("District of Columbia");
	  $cosales=$report->getNetSalesByMarket("Colorado");
	  fputcsv($file, array(date("m/d/Y",strtotime($sdly)),
	  round($sdlyil->daily()->apparentTemperatureHigh(),0),$ilsales['Sales'],$sdlyil->daily()->summary(),
	  round($sdlydc->daily()->apparentTemperatureHigh(),0),$dcsales['Sales'],$sdlydc->daily()->summary(),
	  round($sdlyco->daily()->apparentTemperatureHigh(),0),$cosales['Sales'],$sdlyco->daily()->summary()
	));
	}
	fclose($file);
	if(file_exists($report->docSaveLocation.date("Ymd",strtotime($_REQUEST['startDate']))."-".date("Ymd",strtotime($_REQUEST['endDate'])).'-Weather-YoY.csv')) {
		$ret.="<div><a href='".$report->docDownloadLocation.date("Ymd",strtotime($_REQUEST['startDate']))."-".date("Ymd",strtotime($_REQUEST['endDate'])).'-Weather-YoY.csv'."' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</div>";
	}
}
?>
