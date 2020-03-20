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
		$default = dirname(ABSPATH) . '/config.json';
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
    $stmt->bind_param('sssss',$d['guid'],$d['deliveryService'],$d['restaurantID'],$d['trackingURL'],$d['dateOfBusiness'],$d['deliveryCost'],$d['deliveryID']);
    $stmt->execute();
  }
  function updatedPMTips($u=array()){
    $tip=($u['tip']*100);
    $ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/x-www-form-urlencoded","Authorization: Basic ".base64_encode($this->apiKey.":")));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array("v1_customer_id"=>$this->customerID,"v1_delivery_id"=>$u['deliveryID'],"tip_by_customer"=>$tip)));
		curl_setopt($ch, CURLOPT_URL,$this->requestURL. "v1/customers/" . $this->customerID ."/deliveries");
		$result=curl_exec($ch);
    $rslt=json_decode($result);
    if(isset($rslt->kind) && $rslt->kind=="error"){
      include "ToastReport.php";
      $tst=new ToastReport;
      $tst->reportEmail("jon@theproteinbar.com",print_r($rslt,true),"Postmates Update Error");
    }else{
      $stmt=$this->mysqli->prepare("UPDATE pbc_DeliveryRequests SET deliveryTip=? WHERE guid=?");
      $stmt->bind_param('ss',$tip,$u['guid']);
      $stmt->execute();
    }
  }
}
