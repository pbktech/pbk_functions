<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
global $wpdb;
$page=site_url()."/pb-digital-menus/?action=ContentEditor";
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$files=$wpdb->get_results("SELECT restaurantID,restaurantName FROM pbc2.pbc_pbrestaurants WHERE isOpen=1");
	foreach($files as $file){
		$rests[$file->restaurantName]=$file->restaurantID;
	}
	$rests['La Salle']=23;
	$rests['State and Lake']=5;
	$rests['Washington']=4;
	$mysqli = new mysqli("35.192.37.122", "pbconnect", 'KS4DV42pYJ2eNSYB', "pbc2");
	$uploaddir = '/var/www/html/c2.theproteinbar.com/wp-content/uploads/';
	for ($i = 0; $i < count($_FILES['userfile']['name']); $i++) {
	$uploadfile = $uploaddir . str_replace(" ", "_",basename($_FILES['userfile']['name'][$i]));
	move_uploaded_file($_FILES['userfile']['tmp_name'][$i], $uploadfile);
	if(file_exists($uploadfile)) {
		$count=0;
		$csvData = file_get_contents($uploadfile);
		$lines = explode(PHP_EOL, $csvData);
		$array = array();
		foreach ($lines as $line) {
			$a = str_getcsv($line);
			if(is_numeric($a[1]) && $a[0]!="SSC") {
				$restaurantID=$rests[$a[0]];
				$CheckOpenDateTime=date("Y-m-d H:i:s", strtotime($a[8]));
				preg_match ("/(\d+) minutes and (\d{1,2}) seconds/", $a[10], $m1);
				preg_match ("/(\d+) minute and (\d{1,2}) seconds/", $a[10], $m2);
				preg_match ("/(\d+) seconds/", $a[10], $m3);
				if(isset($m1[0])) {
					$duration=($m1[1]*60)+$m1[2];
				}elseif(isset($m2[0])) {
					$duration=($m2[1]*60)+$m2[2];
				}else {
					$duration=$m3[1];
				}


//				$duration=strtotime($a[9])-strtotime($a[8]);
				$CheckCloseDateTime=date("Y-m-d H:i:s", strtotime($a[9]));
//				echo $CheckOpenDateTime . " - " . $CheckCloseDateTime."<br />";
//				echo strtotime($a[9])-strtotime($a[5])."<br />";
				if(!is_numeric()){$a[3]=rand();}
				$stmt = $mysqli->prepare("REPLACE INTO ".DB_NAME.".kds_detail (restaurantID,kds_trans_seq,checkNumber,sent_time,done_time,duration,station)
				VALUES (?,?,?,?,?,?,?)");
				$stmt->bind_param("iisssss",
				$restaurantID,
				$a[1],
				$a[3],
				$CheckOpenDateTime,
				$CheckCloseDateTime,
				$duration,
				$a[6]);
				$stmt->execute();
				if($stmt->error!='') {echo ("Error message: ". $stmt->error . " - " . $a[0]. " <br />");}else {$count++;}
			}
		}
		unset($uploadfile);
		$ret.=basename($_FILES['userfile']['name'][$i]). "<br />" .$count . " Records imported.<br /><br >";
	}else {
		$ret.="Cannot Find File!";
	}
}
}
	$ret.="\n <div style='width:49%;float:left;'><form method='POST' action='".site_url()."/finance/finance-reports/Import_Toast_KDS/' enctype=\"multipart/form-data\" multipart=\"\">";
	$ret.="\n File Upload<br /><input type=\"file\" name=\"userfile[]\" id=\"userfile\" multiple><br /><br /><input type='submit' value='Import' /></form></div>";
?>
