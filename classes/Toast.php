<?php
use PHPMailer\PHPMailer\PHPMailer;
use Twilio\Rest\Client;
use Twilio\Twiml\MessagingResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require dirname(__DIR__) . '/vendor/autoload.php';

class Toast{
	private $config;
	private $ToastClient;
	private $ToastSecret;
	private $url;
	private $sbToastClient;
	private $sbToastSecret;
	private $sburl;
	private $localDB;
	var $auth=null;
	public $mysqli=null;
	var $restaurantID=0;
	var $timeZone=null;
	var $guid=null;
	var $json=null;
	var $orderGUID=null;
	var $restOptions=array();
	var $dateOfBusiness=null;

	function __construct($b=null,$sandbox=0) {
		$this->setConfig($sandbox);
		$this->auth=$this->getAuthorized();
		$this->connectDB();
		if(isset($b) ) {
			$this->guid=$b;
			$this->getRestaurantID();
			$this->loadGUIDs();
			date_default_timezone_set($this->getTimeZone());
		}
	}
	public function setConfig($sandbox=0){
		$default = dirname(ABSPATH) . '/config.json';
		$this->config=json_decode(file_get_contents($default));
		if($sandbox==0){
			$this->ToastClient=$this->config->ToastClient;
			$this->ToastSecret=$this->config->ToastSecret;
			$this->url=$this->config->ToastURL;
		}else {
			$this->ToastClient=$this->config->sbToastClient;
			$this->ToastSecret=$this->config->sbToastSecret;
			$this->url=$this->config->sbToastURL;
		}
		$this->localDB=$this->config->dBase;
	}
	function getAuthorized() {
		$json=json_encode(array("clientId"=>$this->ToastClient,"clientSecret"=>$this->ToastSecret,"userAccessType"=>"TOAST_MACHINE_CLIENT"));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/json",'Accept: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_URL,$this->url. "/authentication/v1/authentication/login");
		$result=curl_exec($ch);
		print_r($result);
		die();
		return json_decode($result);

		/*
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/x-www-form-urlencoded"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&client_id=".$this->ToastClient."&client_secret=".$this->ToastSecret."");
		curl_setopt($ch, CURLOPT_URL,$this->url. "/usermgmt/v1/oauth/token");
		$result=curl_exec($ch);
		return json_decode($result);
		*/
	}
	function connectDB() {
		$this->mysqli = new mysqli($this->config->host, $this->config->username, $this->config->password, $this->config->dBase);
		$this->mysqli->set_charset('utf8mb4');
	}
	function loadGUIDs(){
		$q="SELECT * FROM pbc2.pbc_ToastGUIDOptions";
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row=$result->fetch_object()){$this->restOptions[$row->GUID]=$row->optionName;}
	}
	function getRestaurantAllOptions($option,$pageSize) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/config/v2/".$option."?pageSize=".$pageSize."");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getRestaurantOptionsJSON($do=null,$option) {
//		if(trim($do)=="" || !isset($do)) {return "BLANK";echo "NO do\n";}
		if(array_key_exists($do,$this->restOptions)){return $this->restOptions[$do];}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/config/v2/".$option."/".$do);
		$result=curl_exec($ch);
		$options= json_decode($result);
		return $options;
	}
	function getRestaurantOptions($do,$option) {
		if(trim($do)=="" || !isset($do)) {return "BLANK";echo "NO do\n";}
		if(array_key_exists($do,$this->restOptions)){return $this->restOptions[$do];}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/config/v2/".$option."/".$do);
		$result=curl_exec($ch);
		$options= json_decode($result);
		if(isset($options->status) && $options->status!=200){
			$body ="Restaurant: ".$this->restaurantID."<br />";
			$body.="Date: ".$this->dateOfBusiness."<br />";
			$body.="do / option: ".$do.":".$option."<br />";
			$body.="Order GUID: " . $this->orderGUID."<br />";
			$body.="Returned JSON <br /><br />".json_encode($options);
			$this->notifyIT($body,"Toast Pull Error - Restaurant Options");
		}
		if(isset($options->name) && $options->name!=''){return $options->name;}else {print_r($result);echo "\n".$do." - ".$option."\n";return "UNKNOWN";}
	}
	function getRestaurantID() {
		$q="SELECT restaurantID, timeZone FROM `pbc_pbrestaurants` WHERE GUID='".$this->guid."'";
		$stmt=$this->mysqli->prepare($q);
		$stmt->execute();
		$result = $stmt->get_result();
		$row=$result->fetch_array(MYSQLI_NUM);
		$this->restaurantID=$row[0];
		$this->timeZone=$row[1];
	}
	function getOrders($date) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/orders/v2/orders?businessDate=" . $date);
		$result=curl_exec($ch);
		if($result->status!=200 && $result->status!="") {$this->notifyIT(json_decode($result)."\n\n Authorization: Bearer " . $this->auth->access_token."Toast-Restaurant-External-ID: " . $this->guid."\n\n".$this->url. "/orders/v2/orders?businessDate=" . $date,"JSON Error - getOrders");}
		return json_decode($result);
	}
	function getMenuItems($menu) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/config/v2/menus/" . $menu);
		$result=curl_exec($ch);
		if($result->status!=200 && $result->status!="") {$this->notifyIT(json_decode($result)."\n\n Authorization: Bearer " . $this->auth->access_token."Toast-Restaurant-External-ID: " . $this->guid."\n\n".$this->url. "/orders/v2/orders?businessDate=" . $date,"JSON Error - getOrders");}
		return json_decode($result);
	}
	function getOrdersByDate($date) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/orders/v2/orders?businessDate=" . $date);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getOrdersByTime($date,$s,$e,$tz) {
		$startDate=date("Y-m-d",strtotime($date))."T".$s.".000".$tz;
		$endDate=date("Y-m-d",strtotime($date))."T".$e.".000".$tz;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/orders/v2/orders?startDate=" . $startDate."&endDate=". $endDate);
		$result=curl_exec($ch);
		if(isset($result->status) && $result->status!=200 && $result->status!="") {$this->notifyIT(json_decode($result)."\n\n Authorization: Bearer " . $this->auth->access_token."Toast-Restaurant-External-ID: " . $this->guid."\n\n".$this->url. "/orders/v2/orders?startDate=" . $startDate."&endDate=". $endDate,"JSON Error - getOrdersByTime");}
		return json_decode($result);
	}
	function getOrdersByDateRange($sDate,$eDate,$s,$e,$tz) {
		$startDate=date("Y-m-d",strtotime($sDate))."T".$s.".000".$tz;
		$endDate=date("Y-m-d",strtotime($eDate))."T".$e.".000".$tz;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/orders/v2/orders?startDate=" . $startDate."&endDate=". $endDate);
		$result=curl_exec($ch);
		print_r(json_decode($result));
	}
	function getLaborByTime($date,$s,$e,$tz) {
		$startDate=date("Y-m-d",strtotime($date))."T".$s.".000".$tz;
		$endDate=date("Y-m-d",strtotime($date))."T".$e.".000".$tz;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/labor/v1/timeEntries?startDate=" . $startDate."&endDate=". $endDate);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getShiftsByTime($date,$s,$e,$tz) {
		$startDate=date("Y-m-d",strtotime($date))."T".$s.".000".$tz;
		$endDate=date("Y-m-d",strtotime($date))."T".$e.".000".$tz;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/labor/v1/shifts?startDate=" . $startDate."&endDate=". $endDate);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getOrderInfo($order) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/orders/v2/orders/" . $order);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getUserInfo($user) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/labor/v1/employees/" . $user);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getPaymentInfo($p) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/orders/v2/payments/" . $p);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getAllEmployees() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/labor/v1/employees");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getJobInfo($j) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/labor/v1/jobs/".$j);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getCashInfo($j) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/cashmgmt/v1/entries?businessDate=".$j);
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getCustCredits($c) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/crm/v1/customers/" . $c . "/creditSummary");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getCustomer($c) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/crm/v1/customers/" . $c . "");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function getCustomerTransactions($c) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,"Toast-Restaurant-External-ID: " . $this->guid));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/crm/v1/customers/" . $c . "/creditTransactions");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function addCustomerCredit($c,$ph) {
		$ph=json_encode($ph);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,
		"Toast-Restaurant-External-ID: " . $this->guid,
		'Content-Type: application/json',
    'Content-Length: ' . strlen($ph)));
    curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $ph);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/crm/v1/customers/".$c . "/creditTransactions");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function addCustomer($ph) {
		$ph=json_encode($ph);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,
		"Toast-Restaurant-External-ID: " . $this->guid,
		'Content-Type: application/json',
    'Content-Length: ' . strlen($ph)));
    curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $ph);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/crm/v1/customers");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function findCustomerID($ph) {
		echo $ph;
		$ph=array("query" => array("phone"=>$ph));
		$ph=json_encode($ph);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization: Bearer " . $this->auth->access_token,
		"Toast-Restaurant-External-ID: " . $this->guid,
		'Content-Type: application/json',
    'Content-Length: ' . strlen($ph)));
    curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $ph);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL,$this->url. "/crm/v1/customers/search");
		$result=curl_exec($ch);
		return json_decode($result);
	}
	function dateToUTC($date) {
		date_default_timezone_set("UTC");
		return date("Y-d-mTG:i:sz", strtotime($date));
	}
	function getTimeZone() {
		return $this->timeZone;
	}
	function setDateOfBusiness($date) {
		$this->dateOfBusiness=date("Y-m-d",strtotime($date));
	}
	function setOrderGUID($guid) {
		$this->orderGUID=$guid;
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
	function getLatestWage($guid){

		$sql="SELECT wage FROM pbc_ToastEmployeeWages WHERE entryID IN (SELECT MAX(entryID) FROM pbc_ToastEmployeeWages WHERE employeeGUID = '$guid' AND restaurantID='".$this->restaurantID."' )";
		$stmt=$this->mysqli->prepare($sql);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT(" \n\n".$stmt->error."\n\n".$sql,"SQL Import Error");}
		if($result = $stmt->get_result()){
			$row=$result->fetch_array(MYSQLI_NUM);
			return $row[0];
		}else {
			return 0;
		}
	}
	function notifyIT($body,$subject){
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
	  $mail->addAddress("jon@theproteinbar.com","Jon Arbitman");
		$mail->Subject = $subject;
		$mail->msgHTML($body, __DIR__);
		if (!$mail->send()) {
		    echo "Mailer Error: " . $mail->ErrorInfo;
		}
	}
	function storeRawJSON($json, $dob, $stmt,$entityType) {
		/*
		$dateOfBusiness=date("Y-m-d",strtotime($dob));
		$encJSON=json_encode($json);
		$stmt->bind_param('sssss',$this->restaurantID,$json->guid,$dateOfBusiness,$encJSON,$entityType);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeRawJSON \n\n".$stmt->error."\n\ndob: " . $dob."\n\nentity: " . $entityType . "\n\n " . print_r($json,true),"SQL Import Error");}
		*/
	}
	function createImportRecord($json, $dob, $timeToComplete, $stmt) {
		$dateOfBusiness=date("Y-m-d",strtotime($dob));
		$encJSON=json_encode($json);
		$stmt->bind_param('ssss',$timeToComplete,$dateOfBusiness,$encJSON,$this->restaurantID);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("createImportRecord \n\n".$stmt->error."\n\n","SQL Import Error");}
	}
	function storeCheckInfo($c, $ToastOrderID,$stmt) {
		$closedDate=date("Y-m-d G:i:s",strtotime($c->closedDate));
		$openedDate=date("Y-m-d G:i:s",strtotime($c->openedDate));
		$modifiedDate=date("Y-m-d G:i:s",strtotime($c->modifiedDate));
		$tabName=htmlentities($c->tabName);
		$tabName=str_replace("\\"," ",$tabName);
		$stmt->bind_param('sssssssssss', $c->guid, $ToastOrderID, $c->displayNumber, $closedDate, $openedDate, $modifiedDate, $c->paymentStatus, $tabName, $c->taxExempt, $c->amount, $c->totalAmount);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeCheckInfo \n\n".$stmt->error."\n\n".$c->guid."\n".$ToastOrderID."\n".$c->displayNumber."\n".$closedDate."\n".$openedDate."\n".$modifiedDate."\n".$c->paymentStatus."\n".$tabName."\n".$c->taxExempt."\n".$c->amount."\n".$c->totalAmount,"SQL Import Error");}
	}
	function storeSelectionInfo($s, $ToastCheckID,$stmt) {
		$createdDate=date("Y-m-d G:i:s",strtotime($s->createdDate));
		$voidBusinessDate=date("Y-m-d G:i:s",strtotime($s->voidBusinessDate));
		if(isset($s->salesCategory->guid) && $s->salesCategory->guid!="") {$salesCategories=$this->getRestaurantOptions($s->salesCategory->guid,'salesCategories');}else {$salesCategories="None";}
		$displayName=htmlentities(substr($s->displayName,0,254));
		$stmt->bind_param('sssssssss', $s->guid,$ToastCheckID,$displayName,$s->quantity,$s->preDiscountPrice,$salesCategories,$createdDate,$s->tax,$voidBusinessDate);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeSelectionInfo \n\n".$stmt->error."\n\n".$s->guid."\n".$ToastCheckID."\n".$displayName."\n".$s->quantity."\n".$s->preDiscountPrice."\n".$salesCategories."\n".$createdDate."\n".$s->tax."\n".$voidBusinessDate,"SQL Import Error");}
		if ($displayName=='Gift Card') {
			$query=$this->mysqli->prepare("REPLACE INTO pbc_ToastGiftCardSold (GUID,ToastCheckGUID,restaurantID,amount,quantity,createdDate,voidBusinessDate) VALUES (?,?,?,?,?,?,?)");
			$query->bind_param('sssssss',$s->guid,$ToastCheckID,$this->restaurantID,$s->preDiscountPrice,$s->quantity,$createdDate,$voidBusinessDate);
			$query->execute();
			if($query->error!='') {$this->notifyIT("pbc_ToastGiftCardSold \n\n".$stmt->error."\n\n".$s->guid."\n".$ToastCheckID."\n"."\n".$s->quantity."\n".$s->preDiscountPrice."\n"."\n".$createdDate."\n"."\n".$voidBusinessDate,"SQL Import Error");}
		}
	}
	function storeModifierInfo($m, $CheckItemID,$stmt) {
		$createdDate=date("Y-m-d G:i:s",strtotime($m->createdDate));
		$displayName=htmlentities($m->displayName);
		$stmt->bind_param('sssssss', $m->guid,$CheckItemID,$displayName,$m->price,$m->tax,$m->voided,$createdDate);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeModifierInfo \n\n".$stmt->error."\n\n".$m->guid."\n".$CheckItemID."\n".$displayName."\n".$m->price."\n".$m->tax."\n".$m->voided."\n".$createdDate,"SQL Import Error");}
	}
	function storeTaxInfo($t, $CheckItemID,$stmt) {
		if(!isset($t->guid) || $t->guid=='') {$t->guid=$this->genGUID(microtime());}
		$stmt->bind_param('ssss', $t->guid,$CheckItemID,$t->rate,$t->taxAmount);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeTaxInfo \n\n".$stmt->error."\n\n".$t->guid."\n".$CheckItemID."\n".$t->rate."\n".$t->taxAmount,"SQL Import Error");}
	}
	function storePaymentInfo($p, $ToastCheckID,$restaurant,$date,$stmt) {
		$date=date("Y-m-d",strtotime($date));
		$paidBusinessDate=date("Y-m-d G:i:s",strtotime($p->paidBusinessDate));
		$paidDate=date("Y-m-d G:i:s",strtotime($p->paidDate));
		if($p->type=="CREDIT") {
			$paymentType=$p->cardType;
		}elseif ($p->type=="OTHER"){
			$paymentType=preg_replace('/[^A-Za-z0-9\-]/', '',$this->getRestaurantOptions($p->otherPayment->guid,'alternatePaymentTypes'));
			$p->cardEntryMode="3RD_PARTY";
		}else {
			$paymentType=$p->type;
		}
		if(!is_numeric($p->originalProcessingFee)) {$p->originalProcessingFee=0.00;}
		if(!is_numeric($p->last4Digits)) {$p->last4Digits=0;}
		if(isset($p->refund->refundAmount) && $p->refund->refundAmount!=0.00) {$refund=json_encode($p->refund);}else {$refund="None";}
		$stmt->bind_param('ssssssssssssss', $p->guid,$ToastCheckID, $p->amount,$p->tipAmount,$paymentType,$p->originalProcessingFee,$paidBusinessDate,$refund,$p->paymentStatus,$paidDate,$p->last4Digits,$p->cardEntryMode,$restaurant,$date);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storePaymentInfo \n\n".$stmt->error."\n\n".$p->guid."\n".$ToastCheckID."\n". $p->amount."\n".$p->tipAmount."\n".$paymentType."\n".$p->originalProcessingFee."\n".$paidBusinessDate."\n".$p->refund."\n".$p->paymentStatus."\n".$paidDate."\n".$p->last4Digits."\n".$p->cardEntryMode,"SQL Import Error");}
	}
	function storeServiceChargeInfo($d, $ToastOrderID,$stmt) {
		$stmt->bind_param('ssss', $d->guid,$ToastOrderID,$d->name,$d->chargeAmount);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeServiceChargeInfo \n\n".$stmt->error."\n\n<pre>".print_r($d,true)."</pre>\n".$ToastOrderID."\n","SQL Import Error");}
	}
	function storeCheckSum($i,$stmt){
		$stmt->bind_param('sssssssssssss',$i['orderGUID'],$i['restaurantID'],$i['businessDate'],$i['checkIds'],$i['checkAmount'],$i['orderSource'],$i['diningOption'],$i['taxAmount'],$i['serviceCharges'],$i['discounts'],$i['voidBusinessDate'],$i['isCatering'],$i['gcSold']);
		$stmt->execute();
		if($stmt->error!='') {
			$this->notifyIT(
				"checkSum \n\n".$stmt->error."\n\n<pre>".print_r($i,true)."</pre>\n",
				"SQL Import Error");
		}

	}
	function storeDiscountInfo($d, $ToastOrderID,$type,$stmt) {
		$mod="";
		if(isset($d->approver->guid) && $d->approver->guid!="") {
			$u=$this->getUserInfo($d->approver->guid);
			$mod=addslashes($u->firstName). " " .addslashes($u->lastName);
		}
		$stmt->bind_param('sssssss', $d->guid,$ToastOrderID,$mod,$d->name,$d->discountAmount,$d->appliedPromoCode,$type);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeDiscountInfo \n\n".$stmt->error."\n\n".$d->guid."\n".$ToastOrderID."\n".$mod."\n".$d->name."\n".$d->discountAmount."\n".$d->appliedPromoCode."\n".$type,"SQL Import Error");}
	}
	function storeGuestInfo($d, $ToastOrderID,$delivery,$stmt) {
		$delivery=json_encode($delivery);
		$stmt->bind_param('sssssss', $d->guid,$ToastOrderID,$d->firstName,$d->lastName,$d->phone,$d->email,$delivery);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeGuestInfo \n\n".$stmt->error."\n\n".$d->guid."\n".$ToastOrderID."\n".$mod."\n".$d->name."\n".$d->discountAmount."\n".$d->appliedPromoCode."\n".$type,"SQL Import Error");}
	}
	function storeOrderInfo($json, $stmt) {
		$businessDate=date("Y-m-d",strtotime($json->businessDate));
		$openedDate=date("Y-m-d G:i:s",strtotime($json->openedDate));
		$paidDate=date("Y-m-d G:i:s",strtotime($json->paidDate));
		$closedDate=date("Y-m-d G:i:s",strtotime($json->closedDate));
		if(isset($json->diningOption->guid) && $json->diningOption->guid!="") {$diningOptions=$this->getRestaurantOptions($json->diningOption->guid,'diningOptions');}else{$diningOptions="Dine In";}
		$stmt->bind_param('sssssssssss',$this->restaurantID,$json->guid,$json->entityType,$rev,$json->source,$businessDate,$json->voided,$openedDate,$paidDate,$closedDate,$diningOptions);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeOrderInfo\n\n".$stmt->error."\n\n".$this->restaurantID."\n".$json->guid."\n".$json->entityType."\n".$rev."\n".$json->source."\n".$businessDate."\n".$json->voided."\n".$openedDate."\n".$paidDate."\n".$closedDate."\n".$diningOptions,"SQL Import Error");}
	}
	function storeEmployeeInDB($json,$stmt,$stmt1) {
		if($json->deleted=='') {$json->deleted=0;}
		$empName=$json->firstName." ".$json->lastName;
		$createdDate=date("Y-m-d G:i:s",strtotime($json->createdDate));
		$deletedDate=date("Y-m-d G:i:s",strtotime($json->deletedDate));
		$modifiedDate=date("Y-m-d G:i:s",strtotime($json->modifiedDate));
		$stmt->bind_param('sssssssss',$this->restaurantID,$json->guid,$empName,$json->externalEmployeeId,$createdDate,$json->deleted,$deletedDate,$modifiedDate,$json->email);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeEmployeeInDB\n\n".$stmt->error."\n\n".$this->restaurantID."\n".$json->guid."\n".$empName."\n".$json->externalEmployeeId."\n".$createdDate."\n".$json->deleted."\n".$deletedDate."\n".$modifiedDate."\n".$json->email,"SQL Import Error");}
		if(isset($json->wageOverrides[0])) {
			foreach($json->wageOverrides as $w) {
				if($this->getLatestWage($json->guid) != $w->wage) {
					$jc=$this->getJobInfo($w->jobReference->guid);
					$stmt1->bind_param('ssss',$this->restaurantID,$json->guid,$jc->title,$w->wage);
					$stmt1->execute();
					if($stmt1->error!='') {$this->notifyIT("ToastEmployeeWages \n\n".$stmt1->error."\n\n".$this->restaurantID."\n".$json->guid."\n".$jc->title."\n".$w->wage,"SQL Import Error");}
				}
			}
		}
	}
	function storePunchesInDB($json,$stmt) {
		$jc=$this->getJobInfo($json->jobReference->guid);
		$businessDate=date("Y-m-d G:i:s",strtotime($json->businessDate));
		$inDate=date("Y-m-d G:i:s",strtotime($json->inDate));
		$outDate=date("Y-m-d G:i:s",strtotime($json->outDate));
		$createdDate=date("Y-m-d G:i:s",strtotime($json->createdDate));
		$modifiedDate=date("Y-m-d G:i:s",strtotime($json->modifiedDate));
		$deletedDate=date("Y-m-d G:i:s",strtotime($json->deletedDate));
		$regularHours=round($json->regularHours,2);
		$overtimeHours=round($json->overtimeHours,2);
		$hourlyWage=round($json->hourlyWage,2);
		$nonCashTips=round($json->nonCashTips,2);
		$declaredCashTips=round($json->declaredCashTips,2);
		$breaks=json_encode($json->breaks);
		$sql="REPLACE INTO pbc_ToastTimeEntries (restaurantID,guid,employeeGUID,businessDate,inDate,outDate,regularHours,overtimeHours,breaks,hourlyWage,createdDate,modifiedDate,deletedDate,jobCode,nonCashTips,declaredCashTips)VALUES(
		'".$this->restaurantID."','".$json->guid."','".$json->employeeReference->guid."','".date("Y-m-d",strtotime($json->businessDate))."','".date("Y-m-d G:i:s",strtotime($json->inDate))."','".date("Y-m-d G:i:s",strtotime($json->outDate))."',
		'".round($json->regularHours,2)."','".round($json->overtimeHours,2)."', '".addslashes(json_encode($json->breaks))."','".round($json->hourlyWage,2)."','".date("Y-m-d G:i:s",strtotime($json->createdDate))."',
		'".date("Y-m-d G:i:s",strtotime($json->modifiedDate))."','".date("Y-m-d G:i:s",strtotime($json->deletedDate))."','".$jc->title."','".round($json->nonCashTips,2)."','".round($json->declaredCashTips,2)."'
		)";
		$stmt->bind_param('ssssssssssssssss',$this->restaurantID,
		$json->guid,
		$json->employeeReference->guid,$businessDate,$inDate,$outDate,$regularHours,$overtimeHours,$breaks,$hourlyWage,$createdDate,$modifiedDate,$deletedDate,$jc->title,$nonCashTips,$declaredCashTips);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storePunchesInDB \n\n".$stmt->error."\n\n".$sql,"SQL Import Error");}
	}
	function storeShiftsInDB($json,$stmt) {
		$jc=$this->getJobInfo($json->jobReference->guid);
		$inDate=date("Y-m-d G:i:s",strtotime($json->inDate));
		$outDate=date("Y-m-d G:i:s",strtotime($json->outDate));
		$createdDate=date("Y-m-d G:i:s",strtotime($json->createdDate));
		$modifiedDate=date("Y-m-d G:i:s",strtotime($json->modifiedDate));
		$deletedDate=date("Y-m-d G:i:s",strtotime($json->deletedDate));
		$sql="REPLACE INTO pbc_ToastScheduledShifts(restaurantID, guid,employeeGUID,inDate,outDate,createdDate,modifiedDate,deletedDate,jobCode)VALUES(
		'".$this->restaurantID."','".$json->guid."','".$json->employeeReference->guid."','".date("Y-m-d G:i:s",strtotime($json->inDate))."','".date("Y-m-d G:i:s",strtotime($json->outDate))."','".date("Y-m-d G:i:s",strtotime($json->createdDate))."','".date("Y-m-d G:i:s",strtotime($json->modifiedDate))."','".date("Y-m-d G:i:s",strtotime($json->deletedDate))."','".$jc->title."')";
		$stmt->bind_param('sssssssss',$this->restaurantID,$json->guid,$json->employeeReference->guid,$inDate,$outDate,$createdDate,$modifiedDate,$deletedDate,$jc->title);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeShiftsInDB \n\n".$stmt->error."\n\n".$sql,"SQL Import Error");}
	}
	function storeCashInDB($json,$stmt) {
		$date=date("Y-m-d",strtotime($json->date));
		if(isset($json->cashDrawer->guid) && $json->cashDrawer->guid!=''){$cDrawer=$json->cashDrawer->guid;}else {$cDrawer="";}
		if(isset($json->payoutReason->guid) && $json->payoutReason->guid!='') {$payoutReasons=$this->getRestaurantOptions($json->payoutReason->guid,'payoutReasons');}else {$payoutReasons="";}
		if(isset($json->noSaleReason->guid) && $json->noSaleReason->guid!='') {$noSaleReason=$this->getRestaurantOptions($json->noSaleReason->guid,'noSaleReasons');}else {$noSaleReason="";}
		$sql="REPLACE INTO pbc_ToastCashEntries (restaurantID, guid,date,reason,amount,payoutReason,cashDrawer,noSaleReason,type)VALUES(
		'".$this->restaurantID."',
		'".$json->guid."',
		'".date("Y-m-d",strtotime($json->date))."',
		'".$json->reason."',
		'".$json->amount."',
		'".$payoutReasons."',
		'".$cDrawer."',
		'".$noSaleReason."',
		'".$json->type."')";
		$stmt->bind_param('sssssssss',$this->restaurantID,$json->guid,$date,$json->reason,$json->amount,$payoutReasons,$json->cashDrawer->guid,$noSaleReason,$json->type);
		$stmt->execute();
		if($stmt->error!='') {$this->notifyIT("storeCashInDB \n\n".$stmt->error."\n\n".$sql,"SQL Import Error");}
	}
}
