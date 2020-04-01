<?php
/*
*
*
*
*
*
*
*
*/
use PHPMailer\PHPMailer\PHPMailer;
use Twilio\Rest\Client;
use Twilio\Twiml\MessagingResponse;
require dirname(__DIR__) . '/vendor/autoload.php';

class Delivery {
  private $provider=null;
  private $apiInfo=null;
  private $customerID=null;
  private $authCode=null;
  private $requestURL;
  private $localDB;
  private $config;

  public function __construct($p) {
    $this->setConfig($p);
		$this->connectDB();
  }
  function setConfig($p){
		$default ='/var/www/html/config_dev.json';
		$this->config=json_decode(file_get_contents($default));
		$this->localDB=$this->config->dBase;
    $this->provider=$p;
    $this->apiKey=$this->config->Delivery->$p->API;
    $this->customerID=$this->config->Delivery->$p->Customer;
    $this->requestURL=$this->config->Delivery->$p->URL;
	}
  function connectDB() {
		$this->mysqli = new mysqli($this->config->host, $this->config->username, $this->config->password, $this->config->dBase);
		$this->mysqli->set_charset('utf8mb4');
	}
  function requestPostmatesDelivery($request) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/x-www-form-urlencoded","Authorization: Basic ".base64_encode($this->apiKey.":")));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
		curl_setopt($ch, CURLOPT_URL,$this->requestURL. "v1/customers/" . $this->customerID ."/deliveries");
		$result=curl_exec($ch);
		return json_decode($result);
	}
  function addDelivery($d=array()){
    $stmt=$this->mysqli->prepare("REPLACE INTO pbc_DeliveryRequests(guid,deliveryService,restaurantID,trackingURL,dateOfBusiness,deliveryCost,deliveryID)VALUES(?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssss',$d['guid'],$d['deliveryService'],$d['restaurantID'],$d['trackingURL'],$d['dateOfBusiness'],$d['deliveryCost'],$d['deliveryID']);
    $stmt->execute();
  }
    function schedulePMTips($u=array()){
    $stmt=$this->mysqli->prepare("INSERT INTO pbc2.temp_pm_tips (guid,tip,deliveryID,addedDateTime)VALUES(?,?,?,?)");
    $stmt->bind_param('ssss',$u['guid'],$u['tip'],$u['deliveryID'],$u['addedDateTime']);
    $stmt->execute();
  }

  function updatedPMTips($u=array()){
    $tip=round($u['tip']*100);
//    echo $this->requestURL. "v1/customers/" . $this->customerID ."/deliveries/v1_delivery_id/" . $u['deliveryID']."\n";
//    die;
    $ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/x-www-form-urlencoded","Authorization: Basic ".base64_encode($this->apiKey.":")));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array("tip_by_customer"=>$tip)));
		curl_setopt($ch, CURLOPT_URL,$this->requestURL. "v1/customers/" . $this->customerID ."/deliveries/" . $u['deliveryID']);
		$result=curl_exec($ch);
    $rslt=json_decode($result);
    if(isset($rslt->kind) && $rslt->kind=="error"){
//      include "ToastReport.php";
//      $tst=new ToastReport;
      $this->reportEmail("jon@theproteinbar.com",print_r($rslt,true) . "\n\n" . print_r($u,true),"Postmates Update Error");
    }else{
      $stmt=$this->mysqli->prepare("UPDATE pbc_DeliveryRequests SET deliveryTip=? WHERE guid=?");
      $stmt->bind_param('ss',$tip,$u['guid']);
      $stmt->execute();
    }
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
		$mail->setFrom('otrs@theproteinbar.com', 'Protein Bar & Kitchen');
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
}
