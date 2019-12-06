<?php
$thisYear=date("Y");
$lastYear=$thisYear-1;
if(!isset($_REQUEST['endDate']) || !isset($_REQUEST['startDate'])) {
		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('#startDate').datepicker({
        dateFormat : 'yy-mm-dd'
    });
    jQuery('#endDate').datepicker({
         defaultDate: -365,
        dateFormat : 'yy-mm-dd'
    });
});

</script>
		<div>
			<form method='get' action='".site_url()."/finance/finance-reports/throughput_export/' >
				<h4>Please choose a date range</h4>
				<div>
					<label for='startDate'>" . $thisYear . " Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
					<label for='endDate'>" . $lastYear . " Start Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\"/>
				</div>
				<div>
					<input type='submit' value='SEARCH' />
				</div>
			</form>		
		</div>		
			
		
		";
}else {
	global $wpdb;
	$date1=explode("-",$_REQUEST['startDate']);
	$date2=explode("-",$_REQUEST['endDate']);
	for($i=0;$i<7;$i++) {
		$d1= date("Y-m-d", mktime(0, 0, 0, $date1[1] , $date1[2] +$i, $date1[0]))."\n";
		$d2= date("Y-m-d", mktime(0, 0, 0, $date2[1] , $date2[2] +$i, $date2[0]))."\n";
		$tyorInsert[]=" chk_clsd_date_time BETWEEN '".$d1." 12:00:00' AND '".$d1." 12:59:59' ";
		$lyorInsert[]=" chk_clsd_date_time BETWEEN '".$d2." 12:00:00' AND '".$d2." 12:59:59' ";
	}
	$q1="SELECT pbc_pbrestaurants.restaurantID as Restaurant,restaurantName,count(chk_seq) as TotalChecks FROM pbc_pbrestaurants, chk_detail WHERE
		pbc_pbrestaurants.restaurantID = chk_detail.restaurantID /*AND sub_ttl>0  AND pymnt_ttl>0*/
		AND (".implode("OR",$tyorInsert)." ) GROUP BY chk_detail.restaurantID;";
//		echo $q1;
//		exit;

	$q2="SELECT pbc_pbrestaurants.restaurantID as Restaurant,restaurantName,count(chk_seq) as TotalChecks FROM pbc_pbrestaurants, chk_detail WHERE
		pbc_pbrestaurants.restaurantID = chk_detail.restaurantID /*AND sub_ttl>0  AND pymnt_ttl>0*/
		AND (".implode("OR",$lyorInsert)." ) GROUP BY chk_detail.restaurantID;";
	$result1=$wpdb->get_results($q1);
	$result2=$wpdb->get_results($q2);
//	print_r($result1);
	foreach($result1 as $r){
		$total[$date1[0]][$r->Restaurant]=array("Name" => $r->restaurantName, "Checks" => $r->TotalChecks );
	}
	foreach($result2 as $r){
		$total[$date2[0]][$r->Restaurant]=array("Name" => $r->restaurantName, "Checks" => $r->TotalChecks );
	}
	$objPHPExcel = new PHPExcel();	
	$objPHPExcel->getProperties()->setCreator("Jon Arbitman")
							 ->setLastModifiedBy("PBConnect")
							 ->setTitle("")
							 ->setSubject("")
							 ->setDescription("")
							 ->setKeywords("")
							 ->setCategory("");
	$ret.="\n<table><tr><td colspan='2'>$thisYear</td><td>&nbsp;&nbsp;&nbsp;</td><td colspan='2'>$lastYear</td></tr>
	<tr><td>Restaurant</td><td>Checks</td><td>&nbsp;&nbsp;&nbsp;</td><td>Restaurant</td><td>Checks</td>";
	$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', $date1[0])
            ->setCellValue('D1', $date2[0])
            ->setCellValue('A2', "Restaurant")
            ->setCellValue('B2', "Checks")
            ->setCellValue('D2', "Restaurant")
            ->setCellValue('E2', "Checks");
   $objPHPExcel->getActiveSheet()->mergeCells('A1:B1');
   $objPHPExcel->getActiveSheet()->mergeCells('D1:E1');
   $count=3;
   $result=$wpdb->get_results("SELECT restaurantID FROM pbc_pbrestaurants WHERE isOpen = 1");
	foreach($result as $r){
		$ret.="\n<tr><td>" . $total[$date1[0]][$r->restaurantID]["Name"] . "</td><td>" . $total[$date1[0]][$r->restaurantID]["Checks"] .  "</td><td>&nbsp;&nbsp;&nbsp;</td><td>" . $total[$date2[0]][$r->restaurantID]["Name"] . "</td><td>" . $total[$date2[0]][$r->restaurantID]["Checks"] .  "</td>";
		$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A'.$count, $total[$date1[0]][$r->restaurantID]["Name"])
            ->setCellValue('B'.$count, $total[$date1[0]][$r->restaurantID]["Checks"])
            ->setCellValue('D'.$count, $total[$date2[0]][$r->restaurantID]["Name"])
            ->setCellValue('E'.$count, $total[$date2[0]][$r->restaurantID]["Checks"]);
		$count++;
	}
	$objPHPExcel->setActiveSheetIndex(0);
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Throughput.xlsx');
	if(file_exists('/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Throughput.xlsx')) {
		$ret.="<p><a href='https://c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/Throughput.xlsx' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";
	}
/*
	$stmt = $mysqli->stmt_init();
	$stmt->prepare($q);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_array(MYSQLI_NUM)){
		$total[][$date1[1]][$row[1]]=$row[2];
	}	
	$email="<table><tr><td><strong>Restaurant</strong></td><td><strong>Checks</strong></td></tr>";
	foreach($total as $restaurant => $checks){
		$email.="<tr><td>".$restaurant."</td><td>".$checks."</td></tr>";
	}
	*/
$ret.="</table>";
}
?>