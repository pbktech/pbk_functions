<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$dBASE=DB_NAME;
date_default_timezone_set('America/Chicago');
global $wpdb;
$files=$wpdb->get_results("SELECT restaurantID,restaurantName FROM $dBASE .pbc_pbrestaurants WHERE isOpen=1");
foreach($files as $file){
	$rests[$file->restaurantName]=$file->restaurantID;
}
$rests['SSC']=0;
$mysqli = new mysqli("10.80.0.3", "pbconnect", 'KS4DV42pYJ2eNSYB', "pbc2");
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
function reArrayFiles(&$file_post) {

    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$key] = $file_post[$key];
        }
    }

    return $file_ary;
}

$rev=1;
$records=0;
$orderTypes["To Go - Yes Bag"]=3;
$orderTypes["To Go - No Bag"]=3;
$orderTypes["To Go - Kiosk"]=3;
$orderTypes["To Go"]=3;
$orderTypes["Delivery"]=4;
$orderTypes["Dine In - Kiosk"]=5;
$orderTypes["Dine In"]=5;
$orderTypes["Pickup"]=5;
function checkDateTime($data) {
	if(strpos($data, "/") && strpos($data, ":") && strpos($data, " ")) {
//    if (date('Y-m-d G:i:s', strtotime($data)) == $data) {
        return true;
    } else {
        return false;
    }
}
function writeLog($mysqli,$start,$end,$file,$restaurant,$dob) {
	/*
	$stmt=$mysqli->prepare("REPLACE INTO ".DB_NAME.".pbc_tst_Imports (restaurantID, importedFile, dateOfBusiness, start, end)VALUES(?,?,?,?,?)");
	$stmt->bind_param("issii",
								$restaurant,
								$file,
								$dob,
								$start,
								$end);
	$stmt->execute();
	if($stmt->error!='') {echo ("Error message: ". $stmt->error . "\n"); }
	*/
}
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	print "<pre>";
	print_r($_FILES['userfile']);
	print "</pre> - ";
	$dir    = '/var/www/html/c2.theproteinbar.com/wp-content/uploads/';
	$rev=1;
	$uploadfile = $uploaddir . str_replace(" ", "_",basename($_FILES['userfile']['name']));
	move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);
	if(file_exists($uploadfile)) {
		$name=explode(".",basename($_FILES['userfile']['name']));
//		$name[0]=explode("_",$name[0]);
//		print_r($name);
		if($name[1]=='csv') {
			$name=explode("_",$name[0]);
			$csvData = file_get_contents($uploadfile);
			$lines = explode(PHP_EOL, $csvData);
			$array = array();
			$start=time();
			if($name[0]=="TimeEntries"){
				foreach ($lines as $line) {
					$a = str_getcsv($line);
					if(isset($a[1]) && is_numeric($a[1])) {
						$records++;
						$restaurant=$rests[$a[0]];
						$empName=explode(",", $a[7]);
						$stmt3 = $mysqli->prepare("REPLACE INTO $dBASE .emp_def (restaurantID,emp_seq, obj_num, payroll_id, last_name, first_name)
						VALUES (?,?,?,?,?,?)");
						$stmt3->bind_param("iiiiss",
						$restaurant,
						$a[4],
						$a[2],
						$a[6],
						$empName[0],
						$empName[1]);
						$stmt3->execute();
						if($stmt3->error!='') {echo ("Error message: ". $stmt3->error . "\n"); exit;}
						$CheckOpenDateTime=date("Y-m-d G:i:s", strtotime($a[12]));
						$CheckCloseDateTime=date("Y-m-d G:i:s", strtotime($a[13]));
						$stmt4 = $mysqli->prepare("REPLACE INTO $dBASE .time_card_dtl (restaurantID,emp_seq, tm_card_seq, job_seq, rvc_seq, clk_in_date_tm, clk_out_date_tm, reg_hrs, reg_ttl, ovt_hrs, ovt_ttl,tip_decl_amt)
						VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
						if($a[23]=='') {$a[23]='0.00';}
						if($a[24]=='') {$a[24]='0.00';}
						if($a[25]=='') {$a[25]='0.00';}
						if($a[26]=='') {$a[26]='0.00';}
						if($a[18]=='') {$a[18]='0.00';}
						$stmt4->bind_param("iiiiisssssss",
						$restaurant,
						$a[4],
						$a[2],
						$a[10],
						$rev,
						$CheckOpenDateTime,
						$CheckCloseDateTime,
						$a[23],
						$a[25],
						$a[24],
						$a[26],
						$a[18]);
						$stmt4->execute();
						if($stmt4->error!='') {$ret.= "Error message: ". $stmt4->error . "\n"; exit;}
					}
				}
				$ret.= "Imported ".$_FILES['userfile']['name']. " with $records successful records.<br /><br />";
			}
			if($name[0]=="OrderDetails"){
				foreach ($lines as $line) {
					$a = str_getcsv($line);
					if(isset($a[1]) && is_numeric($a[1]) && $a[0]!="Location") {
						$restaurant=$rests[$a[0]];
						$records++;
						$CheckOpenDateTime=date("y-m-d G:i:s", strtotime($a[4]));
						$CheckCloseDateTime=date("y-m-d G:i:s", strtotime($a[21]));
						$stmt3 = $mysqli->prepare("REPLACE INTO $dBASE .chk_detail (restaurantID,chk_seq,emp_seq, order_type_seq, chk_name, chk_num, rvc_seq, chk_open_date_time, chk_clsd_date_time, sub_ttl, tax_ttl, pymnt_ttl, tipTotal, orderSource)
						VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
						$ordTotal=$a[14]+$a[13];
						if(!$stmt3) {echo "Error message: ". $mysqli->error . "\n";}
						$stmt3->bind_param("iiiisiisssssss",
						$restaurant,
						$a[1],
						$a[7],
						$orderTypes[$a[12]],
						$a[6],
						$a[2],
						$rev,
						$CheckOpenDateTime,
						$CheckCloseDateTime,
						$ordTotal,
						$a[15],
						$a[18],
						$a[16],
						$a[23]);
						if(isset($a[7])) {
							$stmt3->execute();
						}
						if($stmt3->error!='') {$ret.= "Error message: ". $stmt3->error . "\n";}
					}
				}
				$ret.= "Imported ".$_FILES['userfile']['name']. " with $records successful records.<br /><br />";
			}
			if($name[0]=="PaymentDetails"){
				$paymentAmount=array(
							"Amex"=>0.00,"Mastercard"=>0.00,"Discover"=>0.00,"Cash"=>0.00,"Visa"=>0.00,"Zuppler"=>0.00,
							"LevelUp"=>0.00,"Ritual"=>0.00,"Gift Card"=>0.00,"Micros Gift Card"=>0.00, "Unknown"=>0.00,
							"MM House Account"=>0.00, "MM Amex"=>0.00, "MM Visa"=>0.00, "MM JCB"=>0.00, "MM Mastercard"=>0.00,
							"MM V/MC/D/JCB/DC"=>0.00, "Seemless"=>0.00, "GrubHub"=>0.00, "Fooda"=>0.00, "EzCater"=>0.00, "Uber Eats"=>0.00, "UberEats"=>0.00, "DoorDash"=>0.00, "Hungry Buffs"=>0.00, "Flatiron"=>0.00, "Foodsby"=>0.00, "Diners"=>0.00,
							"LevelUp Order Ahead"=>0.00, "Uncomped Tax"=>0.00,"FreedomPay"=>0.00,"MA‐ DNU"=>0.00
				);
				$paymentCount=array(
							"Amex"=>0,"Mastercard"=>0,"Discover"=>0,"Cash"=>0,"Visa"=>0,"Zuppler"=>0,
							"LevelUp"=>0,"Ritual"=>0,"Gift Card"=>0,"Micros Gift Card"=>0, "Unknown"=>0,
							"MM House Account"=>0, "MM Amex"=>0, "MM Visa"=>0, "MM JCB"=>0, "MM Mastercard"=>0,
							"MM V/MC/D/JCB/DC"=>0, "Seemless"=>0, "GrubHub"=>0, "Fooda"=>0, "EzCater"=>0, "Uber Eats"=>0, "UberEats"=>0, "DoorDash"=>0, "Hungry Buffs"=>0, "Flatiron"=>0, "Foodsby"=>0,"Diners"=>0,
							"LevelUp Order Ahead"=>0, "Uncomped Tax"=>0,"FreedomPay"=>0,"MA‐ DNU"=>0
				);
				foreach ($lines as $line) {
					$a = str_getcsv($line);
					if(isset($a[1]) && is_numeric($a[1])) {
						$dob=date("Y-m-d", strtotime($a[4]));
						$dobs[$dob]++;
						$restaurant=$rests[$a[0]];
						$paymentType='';
						if(isset($a[30]) && $a[30]=="Credit") {
							$paymentType=$a[32];
							$paymentAmount[$a[32]]+=$a[18];
							$paymentCount[$a[32]]++;
						}elseif(isset($a[30]) && $a[30]=="Other") {
							if($a[33]=="MA? DNU") {$a[33]="MA- DNU";}
							$paymentType=$a[33];
							$paymentAmount[$a[33]]+=$a[18];
							$paymentCount[$a[33]]++;
						}elseif(isset($a[30]) && $a[30]!='') {
							$paymentType=$a[30];
							$paymentAmount[$a[30]]+=$a[18];
							$paymentCount[$a[30]]++;
						}
					}
			//	echo $dob . " :: " . $a[30]." - ".$paymentType."\n";
					if(isset($a[30]) && $a[30]!='') {
						if(isset($a[20]) && $a[20]!=0) {$swiped=0;}else {$swiped=1;}
						if(isset($a[4])){$paidDate=date("Y-m-d G:i:s", strtotime($a[4]));}
						if(isset($a[5])){$orderDate=date("Y-m-d G:i:s", strtotime($a[5]));}
						if(!isset($a[36]) || $a[36]=='') {$a[36]=0;}
						if(!isset($a[37]) || $a[37]=='') {$a[37]=0.00;}
						$stmt = $mysqli->prepare("REPLACE INTO $dBASE.pbc_paymentDetails
									(restaurantID, paymentID,orderID,orderNumber,paidDate,orderDate,checkID,service,diningOption, checkAmount, tipAmout,gratuityAmount,isSwiped,paymentType,lastFour,authFee,receiptID)
									VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

						$stmt->bind_param("iiiississsssisiss",
						$restaurant,
						$a[1],
						$a[2],
						$a[3],
						$paidDate,
						$orderDate,
						$a[6],
						$a[12],
						$a[13],
						$a[15],
						$a[16],
						$a[17],
						$swiped,
						$paymentType,
						$a[36],
						$a[37],
						$a[38]);
						if(is_numeric($a[1]) && $a[1]!=0) {
							$stmt->execute();
						if($stmt->error!='') {echo "Error message: ". $stmt->error . " -- ".$a[37]."<br/>";}
						}
					}
				}
				foreach($dobs as $dob => $noneed){
					$files=$wpdb->get_results("SELECT restaurantID,SUM(checkAmount) as totalAmount,Count(*) as numberChecks, paymentType,DATE(orderDate) as DOB
					FROM $dBASE.pbc_paymentDetails WHERE orderDate LIKE '".$dob."%' GROUP BY DATE(orderDate),restaurantID,paymentType");
					foreach($files as $f){
						$stmt1 = $mysqli->prepare("REPLACE INTO $dBASE.v_R_TendersFromArchive (restaurantID,Total,Count,Tender,BusinessDate)
						VALUES (?,?,?,?,?)");
						$stmt1->bind_param("isiss",
						$f->restaurantID,
						$f->totalAmount,
						$f->numberChecks,
						$f->paymentType,
						$f->DOB);
						$stmt1->execute();
						if($stmt1->error!='') {echo "Error message: ". $stmt1->error . "\n"; exit;}
					}
				}
				$ret.= "Imported ".$name[0]. " with the following dates and record counts<br /><br />";
				foreach($dobs as $dob => $noneed){
					$ret.=$dob . " :: " . $noneed . "<br />";
				}
			}
			if($name[0]=="cash-mgmt"){
				foreach ($lines as $line) {
					$a = str_getcsv($line);
					if(isset($a[1]) && is_numeric($a[1]) && $a[1]!=0) {
						$createdDate=date("y-m-d G:i:s", strtotime($a[2]));
						$restaurant=$rests[$a[0]];
						$records++;
						$stmt = $mysqli->prepare("REPLACE INTO $dBASE.pbc_CashEntries (restaurantID,cashEntryID,createdDate,action,amount,cashDrawer,comment,employee,employee2,payoutReason)
						VALUES (?,?,?,?,?,?,?,?,?,?)");
						$stmt->bind_param("iissssssss",
						$restaurant,
						$a[1],
						$createdDate,
						$a[3],
						$a[4],
						$a[5],
						$a[8],
						$a[9],
						$a[10],
						$a[6]);
						$stmt->execute();
						if($stmt->error!='') {$ret.= "Error message: ". $stmt->error . "\n"; exit;}
					}
				}
				$ret.= "Imported ".$_FILES['userfile']['name']. " with $records successful records.<br /><br />";
			}
			if($name[0]=="ItemSelectionDetails"){
				foreach ($lines as $line) {
					$a = str_getcsv($line);
					if(isset($a[2]) && is_numeric($a[2]) && $a[2]!=0) {
						$restaurant=$rests[$a[0]];
						$dataImport[$restaurant][$day][$a[1]]["orderNumber"]=$a[2];
						$dataImport[$restaurant][$day][$a[1]]["sentDate"]=date("y-m-d G:i:s", strtotime($a[3]));
						$dataImport[$restaurant][$day][$a[1]]["orderDate"]=date("y-m-d G:i:s", strtotime($a[4]));
						$dataImport[$restaurant][$day][$a[1]]["employee"]=$a[6];
						$dataImport[$restaurant][$day][$a[1]]["diningService"]=$a[9];
						$dataImport[$restaurant][$day][$a[1]]["Items"][]=array($a[11],$a[12],$a[13],$a[15],$a[16],$a[17],$a[18],$a[19],$a[20],$a[21],$a[22],$a[23],$a[24],$a[25]);
					}
				}
				foreach($dataImport as $restaurantid => $i){
					foreach($i as $dob => $order){
						foreach($order as $on => $d){
							$dob=date("y-m-d", strtotime($d['sentDate']));
							$result = $mysqli->query("SELECT idpbc_itemCheckHeaders FROM pbc_itemCheckHeaders WHERE restaurantID='".$restaurant."' AND dateOfBusiness='".$dob."' AND orderID='".$on."'");
							$row=$result->fetch_assoc();
							if($row['idpbc_itemCheckHeaders']=='' || !isset($row['idpbc_itemCheckHeaders']) || is_null($row['idpbc_itemCheckHeaders'])) {
								$Hrecords++;
								$orderID=$on;
								$orderNumber=$d['orderNumber'];
								$sentDate=$d['sentDate'];
								$orderDate=$d['orderDate'];
								$employee=$d['employee'];
								$diningService=$d['diningService'];
								$stmt = $mysqli->prepare("INSERT IGNORE INTO $dBASE.pbc_itemCheckHeaders (restaurantID,orderID,orderNumber,sentDate,orderDate,employee,diningService,dateOfBusiness)
								VALUES (?,?,?,?,?,?,?,?)");
								$stmt->bind_param("iiisssss",
								$restaurant,
								$orderID,
								$orderNumber,
								$sentDate,
								$orderDate,
								$employee,
								$diningService,
								$dob);
								$stmt->execute();
								if($stmt->error!='') {$ret.= "Error message: ". $stmt->error . "\n"; exit;}
								$idItemCheckHeaders=$stmt->insert_id;
								foreach($d['Items'] as $item){
									$Irecords++;
									if($item[13]=='FALSE') {$isVoided=0;}else {$isVoided=1;}
										$stmt = $mysqli->prepare("INSERT IGNORE INTO $dBASE.pbc_itemSelectionDetails
										(idItemCheckHeaders,itemSelectID,itemID,masterID,menuItem,menuSubGroup,menuGroup,menu,salesCategory,grossPrice,discount,netPrice,quantity,tax,isVoided)
											VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
										$stmt->bind_param("iiiissssssssssi",
										$idItemCheckHeaders,
										$item[0],
										$item[1],
										$item[2],
										$item[3],
										$item[4],
										$item[5],
										$item[6],
										$item[7],
										$item[8],
										$item[9],
										$item[10],
										$item[11],
										$item[12],
										$isVoided);
										$stmt->execute();
										if($stmt->error!='') {$ret.= "Error message: ". $stmt->error . "\n"; exit;}
									}
								}
							}
						}
					}
					$ret.= "Imported ".implode("_", $name). " with $Hrecords successful check headers and $Irecords successful items.<br /><br />";
				}
				if($name[0]=="ModifiersSelectionDetails"){
					$dataImport = array();
					$updateArray=array();
					foreach ($lines as $line) {
						$a = str_getcsv($line);
						if(isset($a[2]) && is_numeric($a[2]) && $a[2]!=0) {
							$dob=date("y-m-d", strtotime($a[3]));
							$result = $mysqli->query("SELECT idpbc_itemCheckHeaders, modImportDate FROM pbc_itemCheckHeaders WHERE restaurantID='".$restaurant."' AND dateOfBusiness='".$dob."' AND orderID='".$a[1]."' LIMIT 1");
							$row=$result->fetch_assoc();
							if($row['idpbc_itemCheckHeaders']!=0 && isset($row['idpbc_itemCheckHeaders']) && is_numeric($row['idpbc_itemCheckHeaders']) && $row['modImportDate']=='') {
								$itemCheckHeaders=$row['idpbc_itemCheckHeaders'];
								$records++;
								if($a[25]=='FALSE') {$isVoided=0;}else {$isVoided=1;}
								$import = $mysqli->prepare("INSERT IGNORE INTO $dBASE.pbc_itemModifierDetails
										(idItemCheckHeaders,itemSelectID,modfierID,menuItem,masterID,optionGroup,parentMenuItem,parentMenuItemID,grossPrice,discount,netPrice,quantity,isVoided)
										VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
								$import->bind_param("iiisisssissii",
								$itemCheckHeaders,
								$a[11],
								$a[12],
								$a[15],
								$a[13],
								$a[17],
								$a[19],
								$a[18],
								$a[21],
								$a[22],
								$a[23],
								$a[24],
								$isVoided);
								$import->execute();
								if($import->error!='') {$ret.= "Error message: ". $import->error . "\n"; exit;}
								$updateArray[]=$row['idpbc_itemCheckHeaders'];
							}
						}
					}
					foreach($updateArray as $update){
						$import = $mysqli->prepare("UPDATE $dBASE.pbc_itemCheckHeaders SET modImportDate='".date('Y-m-d')."' WHERE idpbc_itemCheckHeaders=?");
						$import->bind_param("i",$update);
						$import->execute();
					}
					$ret.= "Imported ".implode("_", $name). " with $records successful records.<br /><br />";
				}
			$end=time();
			writeLog($mysqli,$start,$end,$name[0],$restaurant,$dob);
		}
		unlink($uploadfile);
	}
}
	$ret.="\n <div style='width:49%;float:left;'><form method='POST' action='".site_url()."/finance/finance-reports/Import_Multi_Toast/' multipart=\"\"  enctype=\"multipart/form-data\">";
	$ret.="\n File Upload<br /><input type=\"file\" name=\"userfile\" id=\"userfile\" ><br /><br /><input type='submit' value='Import' /></form></div>";


?>
