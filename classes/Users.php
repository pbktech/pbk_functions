<?php

class User{
  private userExists = FALSE;
  private userID;
  private guestUser = FALSE;
  private mysqli;

  function __construct($mysql,$user=null) {
    if(!isset($mysqli)){
      include "ToastReport.php";
      $report=new ToastReport;
      $m="Users class failed to construct. Missing MySQLi object.";
      $report->reportEmail("errors@theproteinbar.com",$m,"User error");
      return array("message"=>"There was an error setting up, this has been reported.","Variant"=>"danger");
    }
		$this->setmysqli($mysql);
    if(isset($user)){
      $this->userCheck($user);
    }
	}

  function userCheck($user){
    $stmt1=$this->mysqli->prepare("SELECT id,password FROM pbc_minibar_user WHERE  email_address=?");
    $stmt1->bind_param("s", $user);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $row1 = $result1->fetch_object();
    if(isset($row1->id)){
      $this->setUserID($row1->id);
      if(isset($row1->password)){
        $this->setUserExists(TRUE);
      }else {
        $this->setGuestUser(TRUE);
      }
    }
  }

  function doRegister($request){
    if($this->userExists){
      return array("message"=>"Username is already being used.","Variant"=>"danger");
    }
  }

  private function updateUser($request){
    $phone=$this->cleanPhone($request->phone);
    $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET login_name=?, password=?, real_name1=?, phone_number=?, emailConsent=? WHERE id='".$this->userID."'");
    $stmt->bind_param("sssss", $request->user,$password,$request->name,$phone,$emailConsent);
    $stmt->execute();

    $userID=$row1->id;
  }

  function cleanPhone($phone){
    return preg_replace('/\D+/', '', $phone);
  }

  function getUserExists(){
    return $this->userExists;
  }
  function setUserExists($var){
    $this->userExists=$var;
  }
  function getUserID(){
    return $this->userExists;
  }
  function setUserID($var){
    $this->userID=$var;
  }
  function setGuestUser($var){
    $this->guestUser=$var;
  }
  function setmysqli($var){
    $this->mysqli=$var;
  }
}
