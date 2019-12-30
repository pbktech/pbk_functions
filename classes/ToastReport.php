<?php
use PHPMailer\PHPMailer\PHPMailer;
use Twilio\Rest\Client;
use Twilio\Twiml\MessagingResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require dirname(__DIR__) . '/vendor/autoload.php';

class ToastReport{
	private $config;
	private $localDB;
	private $MSDS_PASSWORD;
	private $MSDS_DB;
	private $MSDS_USER;
	private $MSDS_HOST;
	var $restaurantID=0;
	var $mysqli=null;
	var $businessDate=null;
	var $startTime=null;
	var $endTime=null;
	var $isAboveStore=0;
	var $tipsBot="2018-10-15 00:00:00";
	var $docSaveLocation="";
	var $docDownloadLocation="";
	var $otThreshold=32;
	var $weekDays=null;
	var $quarters=array(1=>1,2=>1,3=>1,4=>2,5=>2,6=>2,7=>3,8=>3,9=>3,10=>4,11=>4,12=>4);

	function __construct($b=null) {
		$this->setConfig();
		$this->connectDB();
		if(isset($b) ) {
			$this->setRestaurantID($b);
			$this->checkAuthority();
		}
	}
	public function setConfig(){
		if(!defined('ABSPATH')){
		  if (file_exists('/var/www/html/c2.theproteinbar.com')) {
		    define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
		  }else {
		    define('ABSPATH', '/var/www/html/c2dev.theproteinbar.com/');
		  }
		}
		$default = dirname(ABSPATH) . '/config.json';
		$this->config=json_decode(file_get_contents($default));
		$this->localDB=$this->config->dBase;
		$this->docSaveLocation=$this->config->docSaveLocation;
		$this->docDownloadLocation=$this->config->docDownloadLocation;
		$this->MSDS_DB=$this->config->MSDS_DB;
		$this->MSDS_PASSWORD=$this->config->MSDS_PASSWORD;
		$this->MSDS_USER=$this->config->MSDS_USER;
		$this->MSDS_HOST=$this->config->MSDS_HOST;
	}
	function connectDB() {
		$this->mysqli = new mysqli($this->config->host, $this->config->username, $this->config->password, $this->config->dBase);
		$this->mysqli->set_charset('utf8mb4');
	}
	function getFiscalPeriod($date) {
		$stmt = $this->mysqli->prepare("SELECT * FROM pbc2.pbc_FiscalYears where startDate <=? AND endDate >=?");
		$stmt->bind_param('ss',$date,$date);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getQuarterStartDate($quarter,$year) {
		$stmt = $this->mysqli->prepare("SELECT MIN(startDate) as 'qStartDate' FROM pbc2.pbc_FiscalYears where quarter =? AND year =?");
		$stmt->bind_param('ss',$quarter,$year);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->qStartDate;
	}
	function getScheduleByDate($start,$end) {
		$stmt = $this->mysqli->prepare("SELECT * FROM pbc2.pbc_ToastScheduledShifts,pbc_ToastEmployeeInfo WHERE inDate Between ? AND ? AND pbc2.pbc_ToastScheduledShifts.employeeGUID=pbc_ToastEmployeeInfo.guid AND pbc2.pbc_ToastScheduledShifts.deletedDate LIKE '1969%'
		ORDER BY pbc_ToastScheduledShifts.restaurantID");
		$stmt->bind_param('ss',$start,$end);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){
			$return[]=array("Name"=>$row->employeeName,"Restaurant"=>$row->restaurantID,"Job"=>$row->jobCode,"Date"=>date("m/d/Y",strtotime($row->inDate)),"Start"=>date("g:i a",strtotime($row->inDate)),"End"=>date("g:i a",strtotime($row->outDate)));
		}
		return $return;
	}
	function getPlanInfo() {
		$stmt = $this->mysqli->prepare("SELECT planNumber,laborPlan,(planNumber*laborPlan) as 'laborDollar' FROM pbc2.pbc_salesPlan where salesDate= ? AND restaurantID =?");
		$stmt->bind_param('ss',$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getPlanInfoDateRange($start,$end) {
		$stmt = $this->mysqli->prepare("SELECT * FROM pbc2.pbc_salesPlan where salesDate BETWEEN  ? AND ? AND restaurantID =?");
		$stmt->bind_param('sss',$start,$end,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getPlanInfoSums() {
		$stmt = $this->mysqli->prepare("SELECT SUM(planNumber) as 'planNumber', AVG(laborPlan) as 'laborPlan' FROM pbc2.pbc_salesPlan where salesDate BETWEEN  ? AND ? AND restaurantID =?");
		$stmt->bind_param('sss',$this->startTime,$this->endTime,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getFundraisingResults() {
		$stmt = $this->mysqli->prepare("SELECT SUM(amount) as 'Amount' FROM pbc2.pbc_ToastCheckHeaders WHERE
GUID IN (SELECT ToastCheckID FROM pbc2.pbc_ToastCheckItems WHERE displayName='FUNDRAISER')
AND ToastOrderID IN (SELECT GUID FROM pbc_ToastOrderHeaders WHERE restaurantID=? AND businessDate=?)");
		$stmt->bind_param('ss',$this->restaurantID,$this->startTime);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getCashInfo($type) {
		$stmt = $this->mysqli->prepare("SELECT SUM(amount) as 'Total' FROM pbc2.pbc_ToastCashEntries WHERE type= ? AND date = ? AND restaurantID =?");
		$stmt->bind_param('sss',$type,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getSumSalesBySource() {
		$stmt = $this->mysqli->prepare("SELECT salesBySource FROM pbc2.pbc_sum_DailySales where dateOfBusiness BETWEEN  ? AND ? AND restaurantID =?");
		$stmt->bind_param('sss',$this->startTime,$this->endTime,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrderCountsbySource($source) {
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) as 'Count',SUM(amount) as 'Total' FROM pbc_ToastCheckHeaders WHERE ToastOrderID IN(SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE source=? AND businessDate=? AND restaurantID=?)");
		$stmt->bind_param('sss',$source,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrderCountsbySourceNo($source) {
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) as 'Count',SUM(amount) as 'Total' FROM pbc_ToastCheckHeaders
		WHERE ToastOrderID IN(SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE source=? AND businessDate=? AND restaurantID=?) AND checkNumber <10000");
		$stmt->bind_param('sss',$source,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrderCountsbyPaymentType($source) {
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) as 'Count',SUM(pbc_ToastCheckHeaders.amount) as 'Total' FROM pbc_ToastCheckHeaders,pbc_ToastOrderPayment WHERE pbc_ToastCheckHeaders.GUID=pbc_ToastOrderPayment.ToastCheckID AND
 paymentType=? AND businessDate=? AND restaurantID=?");
		$stmt->bind_param('sss',$source,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrderCountsbyEntryType($source) {
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) as 'Count',SUM(pbc_ToastCheckHeaders.amount) as 'Total' FROM pbc_ToastCheckHeaders,pbc_ToastOrderPayment WHERE pbc_ToastCheckHeaders.GUID=pbc_ToastOrderPayment.ToastCheckID AND
 cardEntryMode=? AND businessDate=? AND restaurantID=?");
		$stmt->bind_param('sss',$source,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrderCountsbyEntryTypeNo($source) {
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) as 'Count',SUM(pbc_ToastCheckHeaders.amount) as 'Total' FROM pbc_ToastCheckHeaders,pbc_ToastOrderPayment
		WHERE pbc_ToastCheckHeaders.GUID=pbc_ToastOrderPayment.ToastCheckID AND paymentType!='MADNU' AND paymentType!='Ritual' AND paymentType!='MMAmex' AND paymentType!='MMHouseAccount' AND paymentType!='MMVMCDJCBDC' AND paymentType!='Fooda' AND
 cardEntryMode=? AND businessDate=? AND restaurantID=?");
		$stmt->bind_param('sss',$source,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrdersByDay() {
		$stmt = $this->mysqli->prepare('SELECT * FROM pbc_ToastCheckHeaders WHERE ToastOrderID IN (SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE restaurantID=? AND businessDate between ? AND ? )');
		$stmt->bind_param('sss',$this->restaurantID,$this->startDate,$this->endDate);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_object();
	}
	function switchNegNumber($num,$dec=0){
		if(is_numeric($num)){
			switch($num){
				case 0:
					return "-";
					break;
					case $num < 0:
					return "(".abs($num).")";
					break;
					case $num > 0:
					return number_format($num,$dec);
					break;
				}
			}
			return "-";
	}
	function buildDSRLine($dsrData){
		/*
		array $dsrData defined as
		$dsrData["bgcolor"]=;
		$dsrData["rowTitle"]=;
		$dsrData["shortTitle"]=;
		$dsrData["dateTitle"]=;
		$dsrData["Sales"]=;
		$dsrData["salesPlan"]=;
		$dsrData["lySales"]=;
		$dsrData["salesPlanDiff"]=;
		$dsrData["tySalesvlySales"]=;
		$dsrData["Checks"]=;
		$dsrData["lyChecks"]=;
		$dsrData["tyChecksvlyChecks"]=;
		$dsrData["tyAveCheck"]=;
		$dsrData["lyAveCheck"]=;
		$dsrData["checkAveDiff"]=;
		$dsrData["monkeyToday"]=;
		$dsrData["lyCatering"]=;
		$dsrData["cateringDiff"]=;
		$dsrData["laborPercent"]=;
		$dsrData["laborPlan"]=;
		$dsrData["lylaborPercent"]=;
		$dsrData["actualLabor"]=;
		$dsrData["scheduledLabor"]=;
		$dsrData["throughput"]=;
		*/
		$return="";
		$return.=  "  <tr style=\"border-bottom:1px solid #e3e6ea;background:".$dsrData['bgcolor'].";\">";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;width:15%;\">".$dsrData['rowTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["Sales"],0)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["salesPlan"],0)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["lySales"],0)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['salesPlanDiff'],1)."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['tySalesvlySales'],1)."</td>";
		$return.=  "  <td style='text-transform: uppercase;'>".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["Checks"])."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["lyChecks"])."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['tyChecksvlyChecks'],1)."</td>";
		$return.=  "  <td style='text-transform: uppercase;'>".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['tyAveCheck'],2)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['lyAveCheck'],2)."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 0 5px 0 5px;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['checkAveDiff'],1)."</td>";
		$return.=  "  <td style='text-transform: uppercase;'>".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['monkeyToday'])."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["lyCatering"])."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['cateringDiff'],1)."</td>";
		$return.=  "  <td style=\"text-transform: uppercase;\">".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['laborPercent'],1)."%</td>";
		$return.=  "  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber(round($dsrData['laborPlan']*100,1),1)."%</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData['lylaborPercent'],1)."%</td>";
		  if($dsrData['dateTitle']=="YESTERDAY"){
		  $return.=  "
		  <td style=\"text-transform: uppercase;padding: 0 5px 0 5px;\">".$dsrData['shortTitle']."</td>
		  <td style='text-transform: uppercase;padding: 0 5px 0 5px;'>".$dsrData['actualLabor']."</td>
		  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 0 5px 0 5px;\">".$dsrData['scheduledLabor']."</td>";
		}
		    $return.="
		  <td style=\"text-align:right;padding: 0 5px 0 5px;\">".$this->switchNegNumber($dsrData["throughput"])."</td>
		</tr>
		";
		return $return;
	}
	function buildSBSLine($data){
		$return="
				<tr style=\"border:1px solid #000000;background:".$data['bgcolor'].";\">
				<td style=\"border-right:1px solid #e3e6ea;width:10%;\">".$data['restaurantName']."</td>

			  <td style=\"text-align:center;\">".$data['inStoreOrders']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['inStoreOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyinStoreOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lyinStoreOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['onlineOrders']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['onlineOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyonlineOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lyonlineOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['appOrders']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['appOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyappOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lyappOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['ritualOrders']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['ritualOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyritualOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lyritualOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['levelUpOrders']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['levelUpOrders']['Total'],0)."</td>
			  <td style=\"text-align:right;\">".trim($data['lylevelUpOrders']['Count'])."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lylevelUpOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['monkeyCount']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['monkeyTotal']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lymonkeyCount']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lymonkeyTotal']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['thirdParty']['Count']."</td>
			  <td style=\"text-align:center;\">$".$this->switchNegNumber($data['thirdParty']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lythirdParty']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">$".$this->switchNegNumber($data['lythirdParty']['Total'],0)."</td>

			  <td style=\"text-align:center;padding:3px;\">".$data['totalChecks']."</td>
			  <td style=\"text-align:center;padding:3px;\">$".$this->switchNegNumber($data['totalSales'],0)."</td>
			  <td style=\"text-align:center;padding:3px;\">".$data['lytotalChecks']."</td>
			  <td style=\"text-align:center;padding:3px;\">$".$this->switchNegNumber($data['lytotalSales'],0)."</td>
				</tr>
				";
				return $return;
	}
	function getPlanSUMInfoDateRange($start,$end) {
		$stmt = $this->mysqli->prepare("SELECT SUM(planNumber) as 'totalPlan' FROM pbc2.pbc_salesPlan where salesDate BETWEEN  ? AND ? ");
		$stmt->bind_param('ss',$start,$end);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->totalPlan;
	}
	function getDiscountbyChekcGUID($guidArray){
		$return=0;
		$stmt = $this->mysqli->prepare("SELECT SUM(discountAmount) as 'Amount' FROM pbc2.pbc_ToastAppliedDiscounts WHERE ToastCheckID =?");
		foreach($guidArray as $guid){
			$stmt->bind_param('s',$guid);
			$stmt->execute();
			$result = $stmt->get_result();
			$row=$result->fetch_object();
			$return+=$row->Amount;
		}
		return $return;
	}
	function getToastCateringTotals(){
		$stmt = $this->mysqli->prepare("SELECT GUID FROM pbc2.pbc_ToastCheckHeaders WHERE GUID IN (SELECT ToastCheckID FROM pbc2.pbc_ToastCheckItems WHERE salesCategory='Catering'  GROUP BY ToastCheckID) AND ToastOrderID IN (SELECT GUID FROM pbc_ToastOrderHeaders WHERE restaurantID=? AND businessDate  BETWEEN ? AND ?)");
		$stmt->bind_param('sss',$this->restaurantID,$this->startTime,$this->endTime);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){
			$guids[]=$row->GUID;
		}
		if(isset($guids)){
			$discounts=$this->getDiscountbyChekcGUID($guids);
			$stmt = $this->mysqli->prepare("SELECT SUM(preDiscountPrice) as 'Total' FROM pbc2.pbc_ToastCheckItems WHERE salesCategory='Catering' AND ToastCheckID IN(SELECT GUID FROM pbc2.pbc_ToastCheckHeaders WHERE  ToastOrderID IN (SELECT GUID FROM pbc_ToastOrderHeaders WHERE restaurantID=? AND businessDate BETWEEN ? AND ?))");
			$stmt->bind_param('sss',$this->restaurantID,$this->startTime,$this->endTime);
			$stmt->execute();
			$result = $stmt->get_result();
			$row=$result->fetch_object();
			return $row->Total-$discounts;
		}else {
			return 0;
		}
	}
	function getTotalGCSales($store=null) {
		$q="SELECT SUM(amount*quantity) as Total FROM ".$this->localDB.".pbc_ToastGiftCardSold where voidBusinessDate='1969-12-31' AND createdDate BETWEEN '".$this->startTime."' AND  '".$this->endTime."'";
		if(isset($store) && is_numeric($store)){$q.=" AND restaurantID='$store'";}
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getThroughput() {
		$q="SELECT COUNT(*) as 'Total' FROM pbc2.pbc_ToastOrderHeaders WHERE openedDate between '".$this->businessDate." 12:00:00' AND '".$this->businessDate." 13:00:00' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getNetSalesbyHour() {
		$q="SELECT SUM(amount) FROM pbc2.pbc_ToastCheckHeaders where pbc2.pbc_ToastCheckHeaders.closedDate between '".$this->startTime."' AND  '".$this->endTime."' ";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_array(MYSQLI_NUM);
		return $row[0]-$this->getTotalGCSales();
	}
	function k_to_f($temp) {
	    if ( !is_numeric($temp) ) { return false; }
	    return round((($temp - 273.15) * 1.8) + 32);
	}
	function getWeather($array) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,"https://api.darksky.net/".$array['Method']."/900976ec40ea82641b15a774f2c3adf0/".$array['Lat'].",".$array['Long']."");
		$result=curl_exec($ch);
		$weather=json_decode($result);
		return array("Temp"=>$weather->daily->data[0]->temperatureHigh,"Summary"=>$weather->daily->data[0]->summary);
	}
	function getNetSales() {
		$q="SELECT (SUM(checkAmount)-SUM(taxAmount)-SUM(serviceCharges)-SUM(gcSold)) as 'Amount' FROM pbc2.pbc_sum_CheckSales WHERE businessDate=? ";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param('s',$this->businessDate);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Amount;
	}
	function getNetSalesByRestaurant() {
		$q="SELECT (SUM(checkAmount)-SUM(taxAmount)-SUM(serviceCharges)-SUM(gcSold)) as 'S',COUNT(*) as 'C' FROM pbc2.pbc_sum_CheckSales WHERE businessDate='".$this->businessDate."' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		$row->S-=$this->getTotalGCSales($this->restaurantID);
		return array("Sales"=>$row->S,"Checks"=>$row->C);
	}
	function getNetSalesByMarket($mkt) {
		$q="SELECT SUM(amount) as 'S',COUNT(*) as 'C' FROM pbc_ToastCheckHeaders,pbc_ToastOrderHeaders
where pbc_ToastOrderHeaders.GUID=pbc_ToastCheckHeaders.ToastOrderID AND
 pbc_ToastOrderHeaders.businessDate='".$this->businessDate."' AND restaurantID IN (SELECT restaurantID FROM pbc_pbrestaurants WHERE isOpen=1 AND market='".$mkt."')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		$row->S-=$this->getTotalGCSales($this->restaurantID);
		return array("Sales"=>$row->S,"Checks"=>$row->C);
	}
	function getNetSalesByRestaurantDateRange($start,$end) {
		$q="SELECT SUM(amount) as 'S',COUNT(*) as 'C' FROM pbc_ToastCheckHeaders,pbc_ToastOrderHeaders
where pbc_ToastOrderHeaders.GUID=pbc_ToastCheckHeaders.ToastOrderID AND
 pbc_ToastOrderHeaders.businessDate BETWEEN '".$start."' AND '".$end."' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		$row->S-=$this->getTotalGCSales($this->restaurantID);
		return array("Sales"=>$row->S,"Checks"=>$row->C);
	}
	function sendText($n,$m) {
		$client = new Client($this->account_sid, $this->auth_token);
		$client->messages->create(
		$n,
		array(
		'from' => $this->twilio_number,
		 'body' => $m
		));
	}
	function getCheckInfo($guid) {
		$q="SELECT * FROM pbc2.pbc_ToastCheckHeaders WHERE pbc2.pbc_ToastCheckHeaders.GUID='$guid'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getOrderInfo($guid) {
		$q="SELECT * FROM pbc2.pbc_ToastOrderHeaders,pbc2.pbc_ToastCheckHeaders WHERE pbc2.pbc_ToastCheckHeaders.GUID='$guid' AND
pbc2.pbc_ToastCheckHeaders.ToastOrderID=pbc2.pbc_ToastOrderHeaders.GUID";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getClockedInEmployees($jc=null) {
		if(isset($jc)) {$add="AND jobCode LIKE '".$jc."%'";}else {$add="";}
		$q="SELECT GUID,employeeName FROM pbc2.pbc_ToastEmployeeInfo WHERE GUID IN (SELECT employeeGUID FROM pbc2.pbc_ToastTimeEntries WHERE inDate BETWEEN '".$this->startTime."' AND '".$this->endTime."' AND restaurantID='".$this->restaurantID."' ".$add.")";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getAssignedTips($guid) {
		$q="SELECT * FROM pbc2.pbc_TipDistribution WHERE orderGUID='$guid'";
		$stmt = $this->mysqli->prepare($q);      echo $compStatus. " " . $d['Start'] . " " . $d['End'] . "\n";
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->employeeGUID]=$row;}
		return $r;
	}
	function getEmployeeInfo($guid) {
		$q="SELECT * FROM pbc2.pbc_ToastEmployeeInfo WHERE guid='$guid' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r[0];
	}
	function getTipsForPayroll() {
		$q="SELECT SUM(tipAmount) as Total,employeeGUID,orderGUID FROM pbc2.pbc_TipDistribution where sentToPayroll='0' GROUP BY employeeGUID,orderGUID";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getTotalLaborDollars() {
		$q="SELECT SUM((regularHours*hourlyWage)+(overtimeHours*(hourlyWage*1.5))) as 'Total' FROM pbc2.pbc_ToastTimeEntries WHERE inDate BETWEEN '".$this->startTime."' AND '".$this->endTime."' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getTippedOrders() {
		$q="SELECT * FROM pbc2.pbc_ToastOrderPayment where restaurantID is not null and tipAmount!=0 AND  restaurantID=".$this->restaurantID." AND businessDate BETWEEN '".$this->startTime."' AND '".$this->endTime."'
		AND tipsAssigned = 0";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getCountTippedOrders() {
		$q="SELECT count(*) as Total FROM pbc2.pbc_ToastOrderPayment where restaurantID is not null and tipAmount!=0 AND  restaurantID=".$this->restaurantID." AND businessDate BETWEEN '".$this->startTime."' AND '".$this->endTime."'
		AND pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT orderGUID FROM pbc2.pbc_TipDistribution)";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getTippedOrdersbyRange() {
		$q="SELECT * FROM pbc2.pbc_ToastOrderPayment where restaurantID is not null and tipAmount!=0 AND  restaurantID=".$this->restaurantID." AND businessDate BETWEEN '".$this->startTime."' AND '".$this->endTime."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getEnteredTipsbyRange() {
		$q="SELECT * FROM pbc2.pbc_TipDistribution where dateOfBusiness BETWEEN '".$this->startTime."' AND '".$this->endTime."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->orderGUID][]=$row;}
		return $r;
	}
	function getPaymentInfo($guid) {
		$q="SELECT closedDate,paymentType,openedDate,totalAmount,tipAmount,paidDate,checkNumber,tabName FROM pbc2.pbc_ToastOrderPayment,pbc2.pbc_ToastCheckHeaders
		WHERE pbc2.pbc_ToastOrderPayment.ToastCheckID=pbc_ToastCheckHeaders.GUID AND pbc_ToastCheckHeaders.GUID='".$guid."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getEmployeeInfoGUID($guid) {
		$q="SELECT * FROM pbc_ToastEmployeeInfo WHERE GUID='".$guid."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getEmployeeAllExternalIDs() {
		$q="SELECT externalEmployeeId FROM pbc_ToastEmployeeInfo";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->externalEmployeeId]=0;}
		return $r;
	}
	function getScheduledEmployeeguids($start,$end) {
		$r=array();
		$q="SELECT employeeGUID FROM pbc_ToastScheduledShifts WHERE inDate BETWEEN '".$start." 00:00:00' AND '".$end." 23:59:59' AND restaurantID='".$this->restaurantID."' GROUP BY employeeGUID";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->employeeGUID]=$row->employeeGUID;}
		return $r;
	}
	function getEmployeeApproachingOvertime($start,$end) {
		$r=array();
		$q="SELECT externalEmployeeID,SUM(regularHours+overtimeHours)as 'totalHours' FROM pbc2.pbc_ToastTimeEntries,pbc_ToastEmployeeInfo
 		WHERE businessDate BETWEEN '".$start."' AND '".$end."' AND pbc2.pbc_ToastTimeEntries.employeeGUID=pbc_ToastEmployeeInfo.guid GROUP BY externalEmployeeID HAVING SUM(regularHours)>=".$this->otThreshold."";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){
				$sql="SELECT guid,employeeName FROM pbc_ToastEmployeeInfo where externalEmployeeId='".$row->externalEmployeeID."'";
				$stmt1 = $this->mysqli->prepare($sql);
				$stmt1->execute();
				$result1 = $stmt1->get_result();
				while($row1=$result1->fetch_object()){
					if($row1->employeeName!=""){
						$r[$row1->guid]=$row1->employeeName . " (".$row->totalHours.")";
					}
				}
		}
		return $r;
	}
	function getShifts($db) {
		$r=array();
		$q="SELECT restaurantID, SEC_TO_TIME(SUM(time_to_sec((TIMEDIFF(outDate,inDate))))) as 'Total' FROM pbc2.$db WHERE inDate LIKE '".$this->businessDate."%' AND jobCode!='GM/AGM' GROUP BY restaurantID";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->restaurantID]=$row->Total;}
		return $r;
	}
	function autClokedOut($restaurantID) {
		$r=array();
		$q="SELECT employeeName FROM pbc_ToastEmployeeInfo WHERE guid IN(SELECT employeeGUID FROM pbc2.pbc_ToastTimeEntries WHERE businessDate='".$this->businessDate."' AND outDate LIKE '%00:00:00' AND jobCode!='KDS' AND jobCode!='GM/AGM' AND restaurantID='$restaurantID')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row->employeeName;}
		return $r;
	}
	function checkOverTime($restaurantID){
		$return=array();
		$q="SELECT externalEmployeeId,restaurantID,guid FROM pbc2.pbc_ToastEmployeeInfo WHERE deleted='0' AND externalEmployeeId!=''";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->externalEmployeeId][$row->restaurantID]=$row->guid;}
		$q="SELECT externalEmployeeId,SUM(regularHours+overtimeHours) as 'Hours',employeeName FROM pbc2.pbc_ToastTimeEntries,pbc_ToastEmployeeInfo
WHERE businessdate BETWEEN '".$this->weekDays[1]."' AND '".$this->businessDate."' AND employeeGUID=pbc_ToastEmployeeInfo.guid AND deleted='0' AND externalEmployeeId!=''
group by externalEmployeeId,employeeName";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){
			if (in_array($restaurantID,$r[$row->externalEmployeeId]) && $row->Hours > $this->otThreshold) {
				$return[] = $row->employeeName;
			}
		}
		return $return;
	}
	function markTipSentToPayroll($guid) {
		date_default_timezone_set("America/Chicago");
		$getstmt = $this->mysqli->prepare("SELECT userID,employeeGUID from pbc_TipDistribution WHERE orderGUID='".$guid."'");
		$getstmt->execute();
		$result = $getstmt->get_result();
		while($row=$result->fetch_object()){
			$log=json_decode($row->userID,true);
			$log["SentToPayroll"]=array("Date"=>date("Y-m-d G:i:s"),"User"=>"SYSTEM");
			$q="UPDATE pbc_TipDistribution SET sentToPayroll ='1', userID='".json_encode($log)."' WHERE orderGUID='".$guid."' AND employeeGUID='".$row->employeeGUID."'";
			$stmt = $this->mysqli->prepare($q);
			$stmt->execute();
		}
	}
	function checkAuthority() {
		$cu = wp_get_current_user();
		if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
			$this->isAboveStore=1;
		}else {
			$this->isAboveStore=0;
			$q="SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."'";
			$stmt = $this->mysqli->prepare($q);
			$stmt->execute();
			$result = $stmt->get_result();
			while($row=$result->fetch_array(MYSQLI_NUM)){$r[]=$row[0];}
			if(!in_array($this->restaurantID,$r)) {echo "You are not authorized for this restaurant.";die();}
		}
	}
	function getAvailableRestaurants() {
		$cu = wp_get_current_user();
		if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
			$q="SELECT restaurantID,restaurantName FROM  pbc_pbrestaurants WHERE isOpen='1'";
		}else {
			$q="SELECT restaurantID,restaurantName FROM  pbc_pbrestaurants WHERE restaurantID IN (SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."')";
		}
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getActiveEmployees() {
		$q="SELECT * FROM pbc2.pbc_ToastEmployeeInfo WHERE restaurantID='".$this->restaurantID."' AND deleted=0 AND externalEmployeeId!='' ORDER BY employeeName";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getManagerType() {
		$cu = wp_get_current_user();
		$q="SELECT mgrType  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->mgrType;
	}
	function getTotalDiscounts() {
		$q="SELECT SUM(discountAmount) as 'Total' FROM pbc2.pbc_ToastAppliedDiscounts,pbc2.pbc_ToastCheckHeaders WHERE ToastCheckID =pbc_ToastCheckHeaders.GUID AND ToastOrderID IN (
SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE restaurantID=? AND businessDate BETWEEN ? AND ?)";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param('sss',$this->restaurantID,$this->startTime,$this->endTime);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getTotalTaxes() {
		$q="SELECT SUM(pbc_ToastAppliedTaxes.taxAmount) as 'Total' FROM pbc2.pbc_ToastAppliedTaxes,pbc2.pbc_ToastCheckHeaders WHERE ToastCheckID =pbc_ToastCheckHeaders.GUID AND ToastOrderID IN (
SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE restaurantID=? AND businessDate BETWEEN ? AND ?)";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param('sss',$this->restaurantID,$this->startTime,$this->endTime);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getAvailableDiscounts() {
		$q="SELECT discount FROM pbc2.pbc_ToastAppliedDiscounts GROUP BY discount;";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getDiscountActivity($d) {
		$q="SELECT ToastCheckID,approver,discountAmount,checkNumber,openedDate,closedDate,tabName,amount,appliedPromoCode,discount FROM pbc2.pbc_ToastAppliedDiscounts,pbc_ToastCheckHeaders WHERE discount='$d' and ToastCheckID =pbc_ToastCheckHeaders.GUID AND ToastOrderID IN (
SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE restaurantID='".$this->restaurantID."' AND businessDate BETWEEN '".$this->startTime."' AND '".$this->endTime."')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getAllManagerEmails() {
		$q="SELECT user_email FROM pbc_users WHERE ID in (SELECT managerID FROM pbc_pbr_managers WHERE restaurantID='".$this->restaurantID."')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getRestaurantIinfo() {
		$q="SELECT * FROM `pbc_pbrestaurants` WHERE restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getRestaurantDailySummary() {
		$q="SELECT SUM(totalSales) as 'Sales',SUM(totalDiscounts) as 'Dicounts', SUM(totalTaxes) as 'Taxes', SUM(totalLabor) as 'Labor', SUM(totalChecks) as 'Checks',SUM(totalCatering) as 'Catering', SUM(noonThroughput) as 'Throughput' FROM pbc2.pbc_sum_DailySales WHERE restaurantID=? AND dateOfBusiness BETWEEN ? AND ?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("sss",$this->restaurantID,$this->startTime,$this->endTime);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_object();
	}
	function getRestaurantIinfoGUID($guid) {
		$q="SELECT * FROM `pbc_pbrestaurants` WHERE GUID='".$guid."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function buildSCV($data,$filename) {
		if(file_exists($this->docSaveLocation.$filename.'.csv')) {unlink($this->docSaveLocation.$filename.'csv');}
		$file = fopen($this->docSaveLocation.$filename.'.csv', 'w');
		foreach($data as $d){
			fputcsv($file,$d);
		}
		fclose($file);
		if(file_exists($this->docSaveLocation.$filename.'.csv')) {
			return $this->docDownloadLocation.$filename.'.csv';
		}else {
			return false;
		}
	}
	function getMonkeyPromoSalesDateRange($restaurantID=null){
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {print_r( sqlsrv_errors(), true);}
		if(isset($restaurantID) && is_numeric($restaurantID)){
			$sql = "SELECT msr.accounts.company_name,store_id,date_reqd,msr.orders.entered_by as 'SalesPerson',subtotal,msr.orders.client_id as 'client' from msr.orders,msr.accounts,msr.clients_and_leads where is_promo=1 AND msr.clients_and_leads.account_id=msr.accounts.account_id
AND msr.orders.client_id=msr.clients_and_leads.client_id AND msr.accounts.account_id>10
AND billing_id!=429 AND billing_id!=744 AND billing_id!=679 AND msr.orders.client_id!=5459 AND msr.orders.client_id!=1384 AND date_reqd BETWEEN ? AND ? AND  store_id=?
ORDER BY msr.orders.entered_by,date_reqd ";
			$params=array($this->startTime,$this->endTime,$restaurantID);
		}else{
			$sql = "SELECT msr.accounts.company_name,store_id,date_reqd,msr.orders.entered_by as 'SalesPerson',subtotal,msr.orders.client_id as 'client' from msr.orders,msr.accounts,msr.clients_and_leads where is_promo=1 AND msr.clients_and_leads.account_id=msr.accounts.account_id
AND msr.orders.client_id=msr.clients_and_leads.client_id AND msr.accounts.account_id>10
AND billing_id!=429 AND billing_id!=744 AND billing_id!=679 AND msr.orders.client_id!=5459 AND msr.orders.client_id!=1384 AND date_reqd BETWEEN ? AND ?
ORDER BY msr.orders.entered_by,date_reqd ";
			$params=array($this->startTime,$this->endTime);
		}
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		while ($result=sqlsrv_fetch_object( $stmt)){
			$return[]=$result;
		}
		return $return;
	}
	function getMonkeySales($restaurantID){
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {print_r( sqlsrv_errors(), true);}
		$sql = "SELECT SUM(subtotal) as 'Sales',COUNT(*) as 'Total' FROM msr.orders WHERE date_reqd BETWEEN ? AND ? AND status='confirmed' AND store_id=? AND deleted!=1 AND order_type='order'";
		$params=array($this->startTime,$this->endTime,$restaurantID);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		return sqlsrv_fetch_object( $stmt);
	}
	function getMonkeyRewardsSales(){
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {echo "<pre>"; print_r( sqlsrv_errors()); echo "</pre>";}
		$sql = "SELECT client_name,client_id,SUM(subtotal) as Total,COUNT(*) as Orders from msr.orders where is_promo=0 AND msr.orders.client_id!=0 AND locked=1
		AND billing_id!=429 AND billing_id!=744 AND billing_id!=679 AND msr.orders.client_id!=5459 AND date_reqd BETWEEN ? AND ? AND deleted!=1 group by client_id,client_name order by Total DESC";
		$params=array($this->startTime,$this->endTime);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {echo "<pre>"; print_r( sqlsrv_errors()); echo "</pre>";}
		while ($result=sqlsrv_fetch_object( $stmt)){
			$return[]=$result;
		}
		return $return;
	}
	function getMonkeyClientSales($restaurantID){
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {print_r( sqlsrv_errors(), true);}
		$sql = "SELECT SUM(subtotal) as 'Sales',COUNT(*) as 'Total' FROM msr.orders WHERE date_reqd BETWEEN ? AND ? AND status='confirmed' AND client_id=? AND deleted!=1 AND is_promo=0";
		$params=array($this->startTime,$this->endTime,$restaurantID);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		return sqlsrv_fetch_object( $stmt);
	}
	function getUnpostedMonkey($rid){
		$return=array();
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {print_r( sqlsrv_errors(), true);}
		$sql = "SELECT order_id FROM msr.orders WHERE date_reqd BETWEEN ? AND ? AND status='confirmed' AND store_id=? AND pos_order_id is null AND deleted!=1 AND order_id!='19868' AND order_type!='quote'";
		$params=array($this->startTime,$this->endTime,$rid);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		while ($result=sqlsrv_fetch_object( $stmt)){
			$return[]=$result->order_id;
		}
		return $return;
	}
	function getUnpostedMonkeyTotal(){
		$return=array();
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {print_r( sqlsrv_errors(), true);}
		$sql = "SELECT COUNT(*) as 'Orders',SUM(subtotal-discount) as 'Total' FROM msr.orders WHERE date_reqd BETWEEN ? AND ? AND status='confirmed' AND pos_order_id is null AND deleted!=1 AND order_id!='19868' AND order_type!='quote'";
		$params=array($this->startTime,$this->endTime,$rid);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		$result=sqlsrv_fetch_object( $stmt);
		return $result;
	}
	function getPromoName($promo) {
		$q="SELECT name FROM pbc2.pbc_DiscountCardCodes WHERE code='".$promo."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->name;
	}
	function getCheckItems($d) {
		$q="SELECT * FROM pbc2.pbc_ToastCheckItems WHERE ToastCheckID='$d'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getCheckItemsFromNumber($d) {
		$q="SELECT * FROM pbc2.pbc_ToastCheckItems WHERE ToastCheckID IN (SELECT GUID FROM pbc_ToastCheckHeaders WHERE checkNumber='$d' AND
		ToastOrderID IN (SELECT GUID FROM pbc_ToastOrderHeaders WHERE restaurantID='".$this->restaurantID."' AND businessDate='".$this->businessDate."'))";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		if($stmt->error!='') {echo $stmt->error."\n\n";}
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getMonkeyRestaurants() {
		$q="SELECT mnkyID,restaurantName FROM pbc2.pbc_pbrestaurants WHERE restaurantID!=0 AND isOpen=1 AND mnkyID is not null";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[$row->mnkyID]=$row->restaurantName;}
		return $r;
	}
	function getOpenOrders() {
		$r=array();
		$q="SELECT checkNumber FROM pbc2.pbc_ToastCheckHeaders WHERE closedDate LIKE '1969-12-31%' AND paymentStatus!='Closed' AND ToastOrderID IN (SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE businessDate = ? AND restaurantID=?)";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("ss",$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row->checkNumber;}
		return $r;
	}
	function sameDayLastYear($d) {
		$today = new \DateTime($d);
		$year  = (int) $today->format('Y');
		$week  = (int) $today->format('W'); // Week of the year
		$day   = (int) $today->format('w'); // Day of the week (0 = sunday)
		if($day==0){$week++;}
		$sameDayLastYear = new \DateTime();
		$sameDayLastYear->setISODate($year - 1, $week, $day);
		if(date('Y-m-d')=="2019-12-30"){return "2018-12-31";}
		return $sameDayLastYear->format('Y-m-d');
	}
	function buildPDF($htmlPages,$title){
		require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Protein Bar & Kitchen');
		$pdf->SetTitle($title);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// set auto page breaks
		$pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);
		foreach($htmlPages as $html){
			$pdf->AddPage();
			$pdf->writeHTML($html, true, false, true, false, '');
			$pdf->lastPage();
		}
		$pdf->Output($this->docSaveLocation.'/'.str_replace(" ","_",$title).".pdf", 'I');
		if (file_exists($this->docSaveLocation.'/'.str_replace(" ","_",$title).".pdf")) {
			return $this->docSaveLocation.'/'.str_replace(" ","_",$title).".pdf";
		}
		return FALSE;
	}
	function reportEmail($to,$body,$subject,$attach=null) {
		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->SMTPDebug = 0;
		$mail->Host = $this->config->SMTP_HOST;
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;
		$mail->Username = $this->config->SMTP_USERNAME;
		$mail->Password = $this->config->SMTP_PASSWORD;
		$mail->setFrom('otrs@theproteinbar.com', 'PBK SYSTEM NOTIFY');
		$addresses=explode(",",$to);
		foreach($addresses as $address){
		  $mail->addAddress($address);
		}
		$mail->Subject = $subject;
		$mail->msgHTML($body, __DIR__);
		if (isset($attach) && is_array($attach)) {
		  foreach($attach as $at){
		    $mail->addAttachment($at);
		  }
		} else {
		  if(isset($attach)) {
		    $mail->addAttachment($attach);
		  }
		}
		if (!$mail->send()) {
		    echo "Mailer Error: " . $mail->ErrorInfo;
				die();
		}
	}
	function showRawArray($a) {
		echo "<pre>";
		print_r($a);
		echo "</pre>";
	}
	function setRestaurantID($id){
		$this->restaurantID=$id;
	}
	function setStartTime($t) {
		$this->startTime=date("Y-m-d H:i:s",strtotime($t));
	}
	function setBusinessDate($t) {
		$this->businessDate=date("Y-m-d",strtotime($t));
		$this->startTime=date("Y-m-d",strtotime($t))." 00:00:00";
		$this->endTime=date("Y-m-d",strtotime($t))." 23:59:59";
		$numDayOfWeek=strtotime($t)-(date("N",strtotime($t))*86400);
//		$numDayOfWeek+=86400;
		for ($i=1; $i < 8; $i++) {
			$this->weekDays[$i]=date("Y-m-d",$numDayOfWeek+($i*86400));
		}
	}
	function setEndTime($t) {
		$this->endTime=date("Y-m-d H:i:s",strtotime($t));
	}
}
