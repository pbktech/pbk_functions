<?php
global $wpdb;
$page=site_url()."/pb-digital-menus/?action=ContentEditor";
	$files=$wpdb->get_results("SELECT restaurantID,restaurantName FROM pbconnect.pbc_pbrestaurants WHERE isOpen=1");
	foreach($files as $file){
		$rests[$file->restaurantName]=$file->restaurantID;
	}
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	if(!is_numeric($_POST["restaurantID"]) OR $_POST["restaurantID"]=='' OR $_POST["restaurantID"]==0) {
		echo "You need to select your reastaurant.";
		exit;
	}
	$mysqli = new mysqli("localhost", "pbconnect", 'KS4DV42pYJ2eNSYB', "pbconnect");
	$uploaddir = '/var/www/html/c2.theproteinbar.com/wp-content/uploads/';
	$uploadfile = $uploaddir . str_replace(" ", "_",basename($_FILES['userfile']['name']));	
	move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);
	if(file_exists($uploadfile)) {
		$count=0;
		$csvData = file_get_contents($uploadfile);
		$lines = explode(PHP_EOL, $csvData);
		$array = array();
		foreach ($lines as $line) {
			$a = str_getcsv($line);
			if(is_numeric($a[0]) && $a[0]!="SSC") {
				if(isset($_GET['rid']) && $_GET['rid']!=''){ $restaurantID=$_POST["rid"];}else {$restaurantID=$_POST["restaurantID"];}
				$CheckOpenDateTime=date("Y-m-d H:i:s", strtotime($a[4]));
				$duration=$a[5];
				$CheckCloseDateTime=date("Y-m-d H:i:s", (strtotime($a[4])+$a[5]));
//				echo $CheckOpenDateTime . " - " . $CheckCloseDateTime."<br />";
//				echo strtotime($a[9])-strtotime($a[5])."<br />";
				$kds_trans_seq=$restaurantID.strtotime($a[4]).$a[0];
				$stmt = $mysqli->prepare("REPLACE INTO micros.kds_detail (restaurantID,kds_trans_seq,kds_trans_id,sent_time,done_time,duration,station)
				VALUES (?,?,?,?,?,?,?)");
//				echo $restaurantID." - " . $kds_trans_seq . " - " . $CheckOpenDateTime . " - " . $duration . " - " . $CheckCloseDateTime."<br />";
				$stmt->bind_param("iisssss",
				$restaurantID,
				$kds_trans_seq,
				$kds_trans_seq,
				$CheckOpenDateTime,
				$CheckCloseDateTime,
				$duration,
				$a[3]);
				$stmt->execute();
				if($stmt->error!='') {echo ("Error message: ". $stmt->error . "<br />");}else {$count++;}
			}
		}
		unset($uploadfile);
		$ret.=basename($_FILES['userfile']['name']). "<br />" .$count . " Records imported.<br /><br >";
	}else {
		$ret.="Cannot Find File!";
	}
}
	$ret.="\n <div style='width:49%;float:left;'><form method='POST' action='".site_url()."/finance/finance-reports/import_micros_kds/' enctype=\"multipart/form-data\">";
	$ret.="\n Choose Restaurant<br /><select name='restaurantID'><option value=''>Choose One</option>";
	foreach($rests as $rest =>$r){
		if(isset($_POST["restaurantID"]) && $_POST["restaurantID"]!='' && $_POST["restaurantID"]==$r) {$selected="selected='selected'";}else {$selected="";}
		$ret.="<option value='".$r."' ".$selected." >".$rest."</option>";
	}
	$ret.="\n </select> <br /> \n File Upload<br /><input type=\"file\" name=\"userfile\" id=\"userfile\"><br /><br /><input type='submit' value='Import' /></form></div>";
?>