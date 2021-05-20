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
	function getFiscalYearStart($date) {
		$stmt = $this->mysqli->prepare("SELECT startDate FROM pbc2.pbc_FiscalYears where period =1 AND year=?");
		$stmt->bind_param('s',$date);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->startDate;
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
	function getOutpostOrders() {
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
	function getTodayPlanInfoSums() {
		$stmt = $this->mysqli->prepare("SELECT SUM(planNumber) as 'planNumber', AVG(laborPlan) as 'laborPlan' FROM pbc2.pbc_salesPlan where salesDate BETWEEN  ? AND ? ");
		$stmt->bind_param('ss',$this->startTime,$this->endTime);
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
	final public function getSumSalesBySource(): ?object{
		$stmt = $this->mysqli->prepare("SELECT salesBySource FROM pbc2.pbc_sum_DailySales where dateOfBusiness BETWEEN  ? AND ? AND restaurantID =?");
		$stmt->bind_param('sss',$this->startTime,$this->endTime,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_object();
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
					return " - ";
					break;
					case $num < 0:
					return "(".abs($num).")";
					break;
					case $num > 0:
					return round($num,$dec);
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
        $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
        $return="";
		$return.=  "  <tr style=\"border-bottom:1px solid #e3e6ea;background:".$dsrData['bgcolor'].";\">";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;width:15%;\">".$dsrData['rowTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["Sales"],0)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["salesPlan"],0)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["lySales"],0)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['salesPlanDiff'],1)."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['tySalesvlySales'],1)."</td>";
		$return.=  "  <td style='text-transform: uppercase;'>".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["Checks"])."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["lyChecks"])."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['tyChecksvlyChecks'],1)."</td>";
		$return.=  "  <td style='text-transform: uppercase;'>".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;padding: 8px;\">".$this->switchNegNumber($dsrData['tyAveCheck'],2)."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;padding: 8px;\">".$this->switchNegNumber($dsrData['lyAveCheck'],2)."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 8px;padding: 8px;\">".$this->switchNegNumber($dsrData['checkAveDiff'],1)."</td>";
		$return.=  "  <td style='text-transform: uppercase;'>".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['monkeyToday'])."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["lyCatering"])."</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['cateringDiff'],1)."</td>";
		$return.=  "  <td style=\"text-transform: uppercase;\">".$dsrData['shortTitle']."</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['laborPercent'],1)."%</td>";
		$return.=  "  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber(round($dsrData['laborPlan']*100,1),1)."%</td>";
		$return.=  "  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData['lylaborPercent'],1)."%</td>";
		  if($dsrData['dateTitle']=="YESTERDAY"){
		  $return.=  "
		  <td style=\"text-transform: uppercase;padding: 8px;\">".$dsrData['shortTitle']."</td>
		  <td style='text-transform: uppercase;padding: 8px;'>".$dsrData['actualLabor']."</td>
		  <td style=\"border-right:1px solid #e3e6ea;text-align:right;padding: 8px;\">".$dsrData['scheduledLabor']."</td>";
		}
		    $return.="
		  <td style=\"text-align:right;padding: 8px;\">".$this->switchNegNumber($dsrData["throughput"])."</td>
		</tr>
		";
		return $return;
	}
	function buildSBSLine($data){
		$return="
				<tr style=\"border:1px solid #000000;background:".$data['bgcolor'].";\">
				<td style=\"border-right:1px solid #e3e6ea;width:10%;\">".$data['restaurantName']."</td>

			  <td style=\"text-align:center;\">".$data['inStoreOrders']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['inStoreOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyinStoreOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lyinStoreOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['onlineOrders']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['onlineOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyonlineOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lyonlineOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['appOrders']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['appOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyappOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lyappOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['ritualOrders']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['ritualOrders']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lyritualOrders']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lyritualOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['levelUpOrders']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['levelUpOrders']['Total'],0)."</td>
			  <td style=\"text-align:right;\">".trim($data['lylevelUpOrders']['Count'])."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lylevelUpOrders']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['monkeyCount']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['monkeyTotal']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lymonkeyCount']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lymonkeyTotal']['Total'],0)."</td>

			  <td style=\"text-align:center;\">".$data['thirdParty']['Count']."</td>
			  <td style=\"text-align:center;\">".$this->switchNegNumber($data['thirdParty']['Total'],0)."</td>
			  <td style=\"text-align:center;\">".$data['lythirdParty']['Count']."</td>
			  <td style=\"border-right:1px solid #e3e6ea;text-align:center;\">".$this->switchNegNumber($data['lythirdParty']['Total'],0)."</td>

			  <td style=\"text-align:center;padding:3px;\">".$data['totalChecks']."</td>
			  <td style=\"text-align:center;padding:3px;\">".$this->switchNegNumber($data['totalSales'],0)."</td>
			  <td style=\"text-align:center;padding:3px;\">".$data['lytotalChecks']."</td>
			  <td style=\"text-align:center;padding:3px;\">".$this->switchNegNumber($data['lytotalSales'],0)."</td>
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
	function getHeaderInformationFromCheck($guid) {
		$q="SELECT * FROM pbc2.pbc_ToastOrderHeaders,pbc2.pbc_ToastCheckHeaders,pbc_pbrestaurants WHERE
pbc_ToastCheckHeaders.GUID ='$guid' AND pbc_ToastOrderHeaders.GUID=pbc_ToastCheckHeaders.ToastOrderID
AND pbc_ToastOrderHeaders.restaurantID = pbc_pbrestaurants.restaurantID ";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		if($stmt->error!='') {echo $stmt->error;}
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row;
	}
	function getHeaderInformationFromOrder($guid) {
		$q="SELECT * FROM pbc2.pbc_ToastOrderHeaders,pbc2.pbc_ToastCheckHeaders,pbc_pbrestaurants WHERE
pbc_ToastOrderHeaders.GUID ='$guid' AND pbc_ToastOrderHeaders.GUID=pbc_ToastCheckHeaders.ToastOrderID
AND pbc_ToastOrderHeaders.restaurantID = pbc_pbrestaurants.restaurantID ";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
	function getNetSalesbyHour() {
		$q="SELECT (SUM(checkAmount)-SUM(gcSold)) as 'Amount' FROM pbc2.pbc_sum_CheckSales WHERE guid IN (SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE openedDate between '".$this->startTime."' AND  '".$this->endTime."')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_array(MYSQLI_NUM);
		return $row[0]-$this->getTotalGCSales();
	}
	function getNetSalesbyHourStore() {
		$q="SELECT (SUM(checkAmount)-SUM(gcSold)) as 'Amount',COUNT(*) as 'Checks' FROM pbc2.pbc_sum_CheckSales WHERE guid IN (SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE openedDate between '".$this->startTime."' AND  '".$this->endTime."' AND restaurantID='".$this->restaurantID."')";
//		echo $q."\n";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		return $row=$result->fetch_object();
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
		$q="SELECT (SUM(checkAmount)-SUM(gcSold)) as 'Amount' FROM pbc2.pbc_sum_CheckSales WHERE businessDate BETWEEN ? AND ?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("ss",$this->startTime,$this->endTime);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Amount;
	}
	function getCateringNetSales() {
		$q="SELECT (SUM(checkAmount)-SUM(gcSold)) as 'Amount' FROM pbc2.pbc_sum_CheckSales WHERE restaurantID=? AND businessDate BETWEEN ? AND ? AND isCatering='1'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("sss",$this->restaurantID,$this->startTime,$this->endTime);
		$stmt->execute();
		if($this->mysqli->error!='') {echo $this->mysqli->error."\n";}
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		if($row->Amount==0 || $row->Amount==''){return 0;}
		return $row->Amount;
	}
	function getNetSalesByRestaurant() {
		$q="SELECT (SUM(checkAmount)-SUM(gcSold)) as 'Sales',COUNT(*) as 'Checks' FROM pbc2.pbc_sum_CheckSales WHERE restaurantID=? AND businessDate BETWEEN ? AND ?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("sss",$this->restaurantID,$this->startTime,$this->endTime);
		$stmt->execute();
		if($this->mysqli->error!='') {echo $this->mysqli->error."\n";}
		$result = $stmt->get_result();
		return $result->fetch_array();
//		return array("Sales"=>$row->S,"Checks"=>$row->C);
	}
	function getNetSalesByMarket($mkt) {
		$q="SELECT (SUM(checkAmount)-SUM(gcSold)) as 'S',COUNT(*) as 'C' FROM pbc2.pbc_sum_CheckSales WHERE businessDate='".$this->businessDate."' AND restaurantID IN (SELECT restaurantID FROM pbc_pbrestaurants WHERE isOpen=1 AND market='".$mkt."')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return array("Sales"=>$row->S,"Checks"=>$row->C);
	}
	function getNetSalesByRestaurantDateRange($start,$end) {
		$q="SELECT (SUM(checkAmount)-SUM(serviceCharges)-SUM(gcSold)) as 'S',COUNT(*) as 'C'  FROM
		pbc2.pbc_sum_CheckSales WHERE businessDate BETWEEN '".$start."' AND '".$end."' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
//		$row->S-=$this->getTotalGCSales($this->restaurantID);
		return array("Sales"=>$row->S,"Checks"=>$row->C);
	}
	function getNetSalesSubtraction($isCatering=0){
		$q="SELECT (SUM(taxAmount)-SUM(serviceCharges)-SUM(gcSold)) as 'Amount' FROM pbc2.pbc_sum_CheckSales  WHERE restaurantID=? AND businessDate BETWEEN ? AND ? AND isCatering=?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("ssss",$this->restaurantID,$this->startTime,$this->endTime,$isCatering);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Amount;
	}
	function sendText(string $n,string $m): object{
		$client = new Client($this->config->account_sid, $this->config->auth_token);
		$msg = $client->messages->create(
		$n,
		array(
		'from' => $this->config->twilio_number,
		 'body' => $m . "\n\nReply STOP to STOP. Msg&Data Rates May Apply."
		));
		return $msg;
	}
	final public function updateCurbsideText(string $m, int $id): void{
        $update = $this->mysqli->prepare("UPDATE pbc_curbside_link SET messageID = ? WHERE linkID = ?");
        $update->bind_param('ss', $m, $id);
        $update->execute();
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
	final public function getClockedInEmployees(string $jc=null): ?array {
		if(isset($jc)) {$add="AND jobCode LIKE '".$jc."%'";}else {$add="";}
		$q="SELECT GUID,employeeName FROM pbc2.pbc_ToastEmployeeInfo WHERE GUID IN (SELECT employeeGUID FROM pbc2.pbc_ToastTimeEntries WHERE inDate BETWEEN '".$this->startTime."' AND '".$this->endTime."' AND restaurantID='".$this->restaurantID."' ".$add.")";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows===0){return null;}
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
    final public function getAssignedTips(string $guid): array {
	    $r=array();
		$q="SELECT * FROM pbc2.pbc_TipDistribution WHERE orderGUID='$guid'";
		$stmt = $this->mysqli->prepare($q);
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
		$q="SELECT SUM(tipAmount) as Total,employeeGUID,orderGUID FROM pbc2.pbc_TipDistribution where sentToPayroll='0' AND tipAmount !=0 AND employeeGUID!='a0' GROUP BY employeeGUID,orderGUID";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
	function getTotalLaborDollars() {
		$q="SELECT SUM((regularHours*hourlyWage)+(overtimeHours*(hourlyWage*1.5))) as 'Total' FROM pbc2.pbc_ToastTimeEntries WHERE businessDate BETWEEN '".$this->startTime."' AND '".$this->endTime."' AND restaurantID='".$this->restaurantID."'";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_object();
		return $row->Total;
	}
    final public function getTippedOrders(): array {
	    $r = array();
		$q="SELECT * FROM pbc2.pbc_ToastOrderPayment where restaurantID is not null and tipAmount!=0 AND  restaurantID=".$this->restaurantID." AND businessDate BETWEEN '".$this->startTime."' AND '".$this->endTime."'
		AND pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT orderGUID FROM pbc2.pbc_TipDistribution) AND pbc_ToastOrderPayment.GUID NOT IN (SELECT guid FROM pbc_DeliveryRequests) AND pbc_ToastOrderPayment.ToastCheckID NOT IN (SELECT GUID FROM pbc_ToastCheckHeaders WHERE tabName LIKE '%Grubhub Delivery%')";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row;}
		return $r;
	}
    final public function getCountTippedOrders() {
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
		$q="SELECT restaurantID, SEC_TO_TIME(SUM(time_to_sec((TIMEDIFF(outDate,inDate))))) as 'Total' FROM pbc2.$db WHERE inDate LIKE '".$this->businessDate."%' GROUP BY restaurantID";
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
	private function monkeyConnectObject(){
		$connectionInfo = array( "Database"=>$this->MSDS_DB, "UID"=>$this->MSDS_USER, "PWD"=>$this->MSDS_PASSWORD );
		$conn = sqlsrv_connect( $this->MSDS_HOST, $connectionInfo);
		if( !$conn ) {print_r( sqlsrv_errors(), true);die();}else{return $conn;}
	}
	function getMonkeyPromoSalesDateRange($restaurantID=null){
		$conn=$this->monkeyConnectObject();
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
		$conn=$this->monkeyConnectObject();
		$sql = "SELECT SUM(subtotal) as 'Sales',COUNT(*) as 'Total' FROM msr.orders WHERE date_reqd BETWEEN ? AND ? AND status='confirmed' AND store_id=? AND deleted!=1 AND order_type='order'";
		$params=array($this->startTime,$this->endTime,$restaurantID);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		return sqlsrv_fetch_object( $stmt);
	}
	function getMonkeyActiveItems(){
		$return=array();
		$conn=$this->monkeyConnectObject();
		$sql = "SELECT item_id,item_group,item_category,item_name FROM msr.items where is_production_item=0 AND status='Active' order by item_category;";
		$params=array();
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		while ($result=sqlsrv_fetch_object( $stmt)){
			$return[$result->item_category][$result->item_group][$result->item_id]=$result->item_name;
		}
		return $return;
	}
	function getMonkeyRewardsSales(){
		$return=array();
		$conn=$this->monkeyConnectObject();
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
	function getMonkeySalesFromItems($data){
		foreach($data['items'] as $item){$i[]="menu_item_id=".$item;}
		$data['startDate']=date("Y-m-d",strtotime($data['startDate']));
		$data['endDate']=date("Y-m-d",strtotime($data['endDate']));
		$return=array();
		$conn=$this->monkeyConnectObject();
		$sql = "SELECT * from msr.orders where order_id IN
		(SELECT order_id from msr.order_items WHERE (" . implode(' OR ',$i) . "))
		AND date_reqd BETWEEN ? AND ? AND status='confirmed' AND deleted!=1 order by store_id";
		$params=array($data['startDate'],$data['endDate']);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {echo "<pre>"; print_r( sqlsrv_errors()); echo "</pre>";}
		while ($result=sqlsrv_fetch_object( $stmt)){
			$return[]=$result;
		}
		return $return;
	}
	function getMonkeyClientSales($restaurantID){
		$conn=$this->monkeyConnectObject();
		$sql = "SELECT SUM(subtotal) as 'Sales',COUNT(*) as 'Total' FROM msr.orders WHERE date_reqd BETWEEN ? AND ? AND status='confirmed' AND client_id=? AND deleted!=1 AND is_promo=0";
		$params=array($this->startTime,$this->endTime,$restaurantID);
		$stmt = sqlsrv_query( $conn, $sql,$params);
		if( $stmt === false ) {print_r( sqlsrv_errors(), true);}
		return sqlsrv_fetch_object( $stmt);
	}
	function getUnpostedMonkey($rid){
		$return=array();
		$conn=$this->monkeyConnectObject();
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
		$conn=$this->monkeyConnectObject();
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
		$q="SELECT checkNumber,GUID,ToastOrderID FROM pbc2.pbc_ToastCheckHeaders WHERE closedDate LIKE '1969-12-31%' AND paymentStatus!='Closed' AND ToastOrderID IN (SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE isVoided!=1 AND businessDate = ? AND restaurantID=?)";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("ss",$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=array($row->checkNumber,$row->GUID,$row->ToastOrderID);}
		return $r;
	}
	function getMedianOrderTime($date=array()) {
		$this->mysqli->query("set @rowid=0;");
		$this->mysqli->query("set @cnt=(select count(*) from pbc2.kds_detail WHERE sent_time BETWEEN '".date("Y-m-d H:i:s",strtotime($date['Start']))."' AND '".date("Y-m-d H:i:s",strtotime($date['End']))."' AND station='' and restaurantID='".$this->restaurantID."' ORDER BY sent_time);");
		$this->mysqli->query("set @middle_no=ceil(@cnt/2);");
		$this->mysqli->query("set @odd_even=null;");
		$q="
select sec_to_time(AVG(duration)) as 'Median' from
(select duration,@rowid:=@rowid+1 as rid, (CASE WHEN(mod(@cnt,2)=0) THEN @odd_even:=1 ELSE @odd_even:=0 END) as odd_even_status
from pbc2.kds_detail WHERE sent_time BETWEEN  ? AND ? AND station='' and restaurantID=? ORDER BY sent_time)
 as tbl where tbl.rid=@middle_no or tbl.rid=(@middle_no+@odd_even);
		";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("sss",$date['Start'],$date['End'],$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$r=$result->fetch_object();
		return $r->Median;
	}
	function getAverageOrderTime($date=array()) {
		$q="SELECT sec_to_time(AVG(duration)) as 'Average' FROM pbc2.kds_detail WHERE sent_time BETWEEN  ? AND ? AND station='' AND restaurantID=?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("sss",$date['Start'],$date['End'],$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		$r=$result->fetch_object();
		return $r->Average;
	}
	function getLastImportTime() {
		$q="SELECT MAX(timeStamp) as 'Average' FROM pbc2.pbc_ToastAPIImportStatus WHERE importDate=?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("s",$this->businessDate);
		$stmt->execute();
		$result = $stmt->get_result();
		$r=$result->fetch_object();
		return $r->Average;
	}
	function getOrderTip($guid) {
		$q="SELECT tipAmount FROM pbc2.pbc_ToastOrderPayment WHERE ToastCheckID=?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("s",$guid);
		$stmt->execute();
		$result = $stmt->get_result();
		$r=$result->fetch_object();
		if($r->tipAmount==0){
			return false;
		}else{
			return $r->tipAmount;
		}
	}

	function getMiniBarOrders($outpost) {
		$r=array();
		$q="SELECT GUID FROM pbc2.pbc_ToastOrderHeaders WHERE pbc_ToastOrderHeaders.diningOption IN (SELECT outpostIdentifier FROM pbc_minibar WHERE idpbc_minibar=?)
  AND businessDate=? AND pbc_ToastOrderHeaders.restaurantID=?";
		$stmt = $this->mysqli->prepare($q);
		$stmt->bind_param("sss",$outpost,$this->businessDate,$this->restaurantID);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$r[]=$row->GUID;}
		return $r;
	}

    final public function getManagerEmails(): array{
        $r=array();
        $q="SELECT user_email FROM pbc_users WHERE ID in (SELECT managerID FROM pbc_pbr_managers WHERE restaurantID= ? )";
        $stmt = $this->mysqli->prepare($q);
        $stmt->bind_param("s", $this->restaurantID);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row=$result->fetch_object()){$r[]=$row->user_email;}
        return $r;
    }

    function sameDayLastYear($d) {
        /*
        $d=date('Y-m-d', strtotime('-1 year', strtotime($d)));
        if(date("L")==1 && (date("n")>2 || (date("n")==2 && date("d")==29))){$addDays=2;}else{$addDays=1;}
        if(date("L")==1 && date("n",strtotime($d))<=2 && date("L",strtotime($d))==0){$addDays=1;}
        return date('Y-m-d', strtotime('+' . $addDays . ' days', strtotime($d)));
        */
        $today = new \DateTime($d);
        $year  = (int) $today->format('Y');
        $week  = (int) $today->format('W'); // Week of the year
        $day   = (int) $today->format('w'); // Day of the week (0 = sunday)
        if($day==0){$week++;}
        $sameDayLastYear = new \DateTime();
        $sameDayLastYear->setISODate($year - 1, $week, $day);
        if($d=="2019-12-30"){return "2018-12-31";}
        return $sameDayLastYear->format('Y-m-d');
    }
    function sameDayLastWeek($d) {
        $today = new \DateTime($d);
        $year  = (int) $today->format('Y');
        $week  = (int) $today->format('W'); // Week of the year
        $day   = (int) $today->format('w'); // Day of the week (0 = sunday)
        if($day==0){$week++;}
        $sameDayLastYear = new \DateTime();
        $sameDayLastYear->setISODate($year, $week - 1, $day + 1);
        if($d=="2019-12-30"){return "2018-12-31";}
        return $sameDayLastYear->format('Y-m-d');
    }
	function buildPDF($content, $save=1){
        $content=json_decode($content);
        if(isset($content->Save) && $content->Save!=''){$docSaveLocation=$content->Save;}else{$docSaveLocation=$this->docSaveLocation;}
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'c',
            'format' => $content->format,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'margin_header' => 0,
            'margin_footer' => 0,
            'CSSselectMedia' => 'Screen'
        ]);
        $stylesheet=file_get_contents(dirname(dirname(__FILE__)) . "/assets/css/mpdf-bootstrap.css");
        $mpdf->SetTitle($content->title);
        $mpdf->SetAuthor("Protein Bar & Kitchen");
        if(isset($content->watermark)){
            $mpdf->SetWatermarkText($content->watermark);
            $mpdf->showWatermarkText = true;
        }
        $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML(utf8_encode($content->html),\Mpdf\HTMLParserMode::HTML_BODY);
        if(isset($content->fileName)){
            $filename=$content->fileName . ".pdf";
        }else{
            $filename=str_replace(" ","_",str_replace("/","_",$content->title)).".pdf";
        }
        if(file_exists($docSaveLocation.$filename)){unlink($docSaveLocation.$filename);}
        if($save==0){
            $mpdf->Output();
        }else {
            $mpdf->Output($docSaveLocation.$filename, 'F');
        }
        if(file_exists($docSaveLocation.$filename)){
            return array("Link"=>$this->docDownloadLocation.$filename,"Local"=>$docSaveLocation.$filename);
        }else {
            return false;
        }
	}
	function reportEmail($to,$body,$subject,$attach=null, $bcc=null) {
		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->SMTPDebug = 0;
		$mail->Host = $this->config->SMTP_HOST;
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;
		$mail->Username = $this->config->SMTP_USERNAME;
		$mail->Password = $this->config->SMTP_PASSWORD;
		$mail->setFrom('otrs@theproteinbar.com', 'Protein Bar & Kitchen');
		if(isset($this->config->debug) && (string)$this->config->debug === "true"){
            $to="jon@theproteinbar.com";
        }
		$addresses=explode(",",$to);
		foreach($addresses as $address){
		  $mail->addAddress($address);
		}
		if($bcc) {
            $mail->addBcc($bcc);
        }
		$mail->Subject = $subject;
		$mail->msgHTML($body, __DIR__);
		if (isset($attach) && is_array($attach)) {
		  foreach($attach as $at){
		    $mail->addAttachment($at);
		  }
		} else {
		  if(isset($attach)) {
				if(is_array($attach)){
					foreach($attach as $a){
		    		$mail->addAttachment($a);
					}
				}else {
					$mail->addAttachment($attach);
				}
		  }
		}
		if (!$mail->send()) {
		    echo "Mailer Error: " . $mail->ErrorInfo;
				die();
		}
	}
	public function getGeoCode($address){
		$address=str_replace(" ","+",$address);
		$url="https://maps.googleapis.com/maps/api/geocode/json?address=" . $address . "&key=" . $this->config->geocodeAPI;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL,$url);
		$result=curl_exec($ch);
		curl_close($ch);
		return json_decode($result);
	}
	public function calculateDistance(array $coords): string{
		$url="https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=" . $coords['o'] . "&destinations=" . $coords['d'] . "&key=" . $this->config->geocodeAPI;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL,$url);
		$result=curl_exec($ch);
		curl_close($ch);
		$r = json_decode($result, true);
		return $r['rows'][0]['elements'][0]['distance']['text'];
    }
	function showResultsTable($data=array(),$tableName="myTable"){
		if(isset($data['Results']) && count($data['Results'])!=0){
            $data['Message'] = empty($data['Message']) ? "" : $data['Message'];
            $data['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
            $data['Options'][] = "'dom': \"<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'B><'col-sm-12 col-md-4'f>>\" +
\"<'row'<'col-sm-12'tr>>\" +
\"<'row'<'col-sm-12 col-md-4'i><'col-sm-12 col-md-8'p>>\"";
            $data['Options'][] = "'buttons': [
            'print',
            'excelHtml5',
            'csvHtml5',
            {extend: 'pdfHtml5',
                messageTop: '" . $data['Message'] . "',
                customize: function ( doc ) {
                    doc.content.splice( 0, 1, {
                      margin: [ 0, 0, 0, 12 ],
                      alignment: 'center',
                      image: 'data:image/png;base64," . DOC_IMG . "',
                      fit: [400, 103]
                    } );
                }
            }
            ]";
			if(isset($data['Options']) && is_array($data['Options'])){$options="{\n					".implode(",\n					",$data['Options'])."}\n				";}else{$options='';}
			$return="
		<script>
		jQuery(document).ready( function () {
				jQuery('#".$tableName."').DataTable(".$options.");
		} );
		</script>
		<div id='queryResults'>
	      <table id='myTable' class=\"table table-striped table-hover table-bordered\" style='width:100%;'>
		        <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
		          <tr><th>".implode("</th><th>",$data['Headers']) . "
		          </th></tr>
		        </thead>";
			foreach($data['Results'] as $row=>$col){
				$return.=	"<tr><td>" . implode("</td><td>",$col) . "</td></tr>";
			}
			$return.=	"
			</table>
			</div>";

		}else {
			$return = "<div class='alert alert-warning'>No Results Found</div>";
		}
		return $return;
	}
	function showRawArray($a) {
		echo "<pre>";
		print_r($a);
		echo "</pre>";
	}
	function genGUID($name) {
		$nhex = str_replace(array('-','{','}'), '', $this->restaurantID);
		$nstr = '';
		 for($i = 0; $i < strlen($nhex); $i+=2) {
				$nstr .= chr(hexdec($nhex[$i]));
			}
			$hash = sha1($nstr . $name);
			return sprintf('%08s-%04s-%04x-%04x-%12s',substr($hash, 0, 8),substr($hash, 8, 4),(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,substr($hash, 20, 12));
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
	function hexFileName($email){
		$nhex = str_replace(array('-','{','}','@','.',' '), '', $email);
		$nstr = '';
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i]));
		}
		$hash = sha1($nstr . microtime());
		return $nhex . "-" . sprintf('%08s-%04s-%04x-%04x-%12s',substr($hash, 0, 8),substr($hash, 8, 4),(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,substr($hash, 20, 12));
	}
	public function pbk_form_processing(){
		return "
		<script>
			jQuery(document).ready(function() {
				jQuery(\"#submit\").click(function(){
					window.scrollTo(0,0);
					jQuery(\"#queryResults\").hide();
					jQuery(\"#processingGif\").show();
				});
			});
		</script>
		<div id='processingGif' style=\"display: none;text-align:center;\"><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>
		";
	}
	public function buildSelectBox($data=array()){
		if(isset($data['Change'])){$change=' onchange="'.$data['Change'].'" ';}else{$change='';}
		$return="
		<script>
			jQuery(document).ready(function() {
	    	jQuery('.js-example-basic-single').select2();
			});
		</script>
		<select name='".$data['Field']."' class=\"custom-select js-example-basic-single\" required id='".$data['ID']."' ".$data['Multiple']."$change>
			<option value=''>Choose One</option>
			";
			foreach($data['Options'] as $id=>$option){
				$return.="
			<option value='$id'>$option</option>
				";
			}
		$return.="
		</select>";
		return $return;
	}
	public function buildDateSelector($field='startDate',$label="Starting Date"){
		if(isset($_GET[$field])){$dateValue=$_GET[$field];}else{$dateValue="";}
		return "
		<script>
			jQuery(document).ready(function() {
				jQuery('#".$field."').datepicker({
			      dateFormat : 'mm/dd/yy'
				});
			});
		</script>
		<label for='$field' id='".$field."Label'>$label</label>
		<input class=\"form-control\" type=\"text\" id=\"".$field."\" name=\"".$field."\" value=\"".$dateValue."\"/>
		";
	}
}
