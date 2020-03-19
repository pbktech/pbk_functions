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
		curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/x-www-form-urlencoded","Authorization: Basic ".$this->apiKey));
		curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&",$request));
		curl_setopt($ch, CURLOPT_URL,$this->requestURL. "v1/customers/" . $this->customerID ."/deliveries");
		$result=curl_exec($ch);
		return json_decode($result);
	}

}
