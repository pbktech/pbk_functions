<?php

final class PBKUser
{
    private $userExists = false;
    private $userID;
    private $guestUser = false;
    private $mysqli;
    public $userDetails = null;
    public $yesvno = array(1=>"Yes",0=>"No");

    public function __construct($mysql, $user=null)
    {
        if (!isset($mysql)) {
            $report=new ToastReport;
            $m="Users class failed to construct. Missing MySQLi object.";
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            exit;
        }
        $this->setmysqli($mysql);
        if (isset($user)) {
            $this->userCheck($user);
        }
    }

    public function userCheck(string $user): ?int
    {
        $stmt1=$this->mysqli->prepare("SELECT id,password FROM pbc_minibar_user WHERE  email_address=?");
        $stmt1->bind_param("s", $user);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $row1 = $result1->fetch_object();
        if (isset($row1->id)) {
            $this->setUserID($row1->id);
            if (isset($row1->password)) {
                $this->setUserExists(true);
            } else {
                $this->setGuestUser(true);
            }
            return $row1->id;
        }
        return false;
    }

    public function doRegister(object $request): array
    {
        if ($this->getUserExists()) {
            return array("message"=>"Username is already being used.","Variant"=>"danger");
        }
        if ($this->getGuestUser()) {
            if (!$this->updateUser($request)) {
                return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
            }
        } else {
            if ($newUserID=$this->addNewUser($request)) {
                $this->setUserID($newUserID);
            } else {
                return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
            }
        }
        if ($linkHEX=$this->generateHexLink("user_registration")) {
            $report=new ToastReport;
            $m="Thank you for signing up for an account with Protein Bar & Kitchen. Please click this link you confrim your email. <a href='https://www.pbkminibar.com/confirm/".$linkHEX."'>https://www.pbkminibar.com/confirm/".$linkHEX."</a>";
            $report->reportEmail($request->user, $m, "Minibar Email Verification");
            return array("message"=>"Thank you for signing up. Please check your email for an email confirmation.","Variant"=>"success");
        } else {
            return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
        }
        return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
    }

    public function doLogin(object $request): array
    {
        if (!$this->getUserExists()) {
            return array("message"=>"Invalid Username/Password","Variant"=>"danger");
        }
        $stmt=$this->mysqli->prepare("SELECT COUNT(*) as 'count' FROM pbc_minibar_users_failed_login WHERE mbUserID=? AND currentTime >= DATE_SUB(NOW(),INTERVAL 1 HOUR)");
        $stmt->bind_param("s", $this->userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->count) && $row->count>5) {
            $this->userDetails->isLocked=1;
            $this->lockUserAccount();
        }
        if ($this->userDetails->isLocked==1) {
            return array("message"=>"Your account is locked. Please reset your password to unlock it.","Variant"=>"danger");
        }
        if ($this->userDetails->isConfirmed==0) {
            return array("message"=>"Your must confirm your email before you can access your account.","Variant"=>"danger");
        }
        if (password_verify($request->password, $this->userDetails->password)==1) {
            $loginTime=date("Y-m-d G:i:s");
            $loginExpires=date("Y-m-d G:i:s", strtotime('+3 hours'));
            $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_sessions (mbUserId,loginTime,expireTime)VALUES(?,?,?)");
            $stmt->bind_param("sss", $this->userID, $loginTime, $loginExpires);
            $stmt->execute();
            if (isset($stmt->error) && $stmt->error!='') {
                $report=new ToastReport;
                $m="User (".$this->userID.") failed to create hex link.<br><br>LP: ".print_r($request,true)."<br><br>DB Error: " . $stmt->error;
                $report->reportEmail("errors@theproteinbar.com", $m, "User error");
                return array("message"=>"There was an error logging you in. This error has been reported.","Variant"=>"danger");
            }
            $newid=$stmt->insert_id;
            $stmt=$this->mysqli->prepare("SELECT UuidFromBin(SessionGUID) as 'session' FROM pbc2.pbc_minibar_users_sessions WHERE sessionID = ?");
            $stmt->bind_param("i", $newid);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_object();
            if (isset($row->session)) {
                $orders=$this->getUserOrders('individual');
                $addresses=$this->getUserAddresses();
                $groupOrders=$this->getUserOrders('group');
                $grouplinks = $this->getGroupLinks();
                $houseaccounts = $this->getHouseAccounts();
                return array(
                    "message"=>"Login Successful",
                    "Variant"=>"success",
                    "sessionID"=>$row->session,
                    "guestName"=>$this->userDetails->real_name1,
                    "addresses" => $addresses,
                    "phone" => $this->userDetails->phone_number,
                    "email" => $this->userDetails->email_address,
                    "orders" => $orders,
                    "groupOrders" => $groupOrders,
                    "grouplinks" => $grouplinks,
                    "houseAccounts" => $houseaccounts
                );
            }
        } else {
            $ip=$this->getClientIP();
            $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_failed_login (mbUserID,ipAddress)VALUES(?,?)");
            $stmt->bind_param("ss", $this->userID, $ip);
            $stmt->execute();
            if (isset($stmt->error) && $stmt->error!='') {
                $report=new ToastReport;
                $m="User (".$this->userID.") failed to insert failed login.<br><br>DB Error: " . $stmt->error;
                $report->reportEmail("errors@theproteinbar.com", $m, "User error");
                return array("message"=>"There was an error logging you in. This error has been reported.","Variant"=>"danger");
            }
        }
        return array("message"=>"Invalid Username/Password","Variant"=>"danger");
    }

    private function getHouseAccounts(): array{
        $orders = array();
        $stmt = $this->mysqli->prepare("SELECT UuidFromBin(publicUnique) as 'guid', companyName, maxIndividualOrder FROM pbc_minibar_ha_users, pbc_minibar_house_accounts  WHERE 
           userID =? AND houseAccountID = accountID");
        $stmt->bind_param("s", $this->userID);
        $stmt->execute();
        if($result = $stmt->get_result()) {
            while ($rows = $result->fetch_object()) {
                $orders[] = $rows;
            }
        }
        return $orders;
    }


    private function getGroupLinks(): array{
        $orders = array();
        $stmt = $this->mysqli->prepare("SELECT linkHEX,DATE_FORMAT(linkExpires, '%M %d %Y') as 'orderDate',mbService, linkSlug FROM pbc_minibar_users_links pmul,pbc_minibar_order_header pmoh, pbc_minibar pm  WHERE 
           pmul.mbUserID =? AND linkExpires >= NOW() AND pmul.orderHeaderID = pmoh.headerID AND pmoh.minibarID = pm.idpbc_minibar AND linkPurpose = 'group_order'");
        $stmt->bind_param("s", $this->userID);
        $stmt->execute();
        if($result = $stmt->get_result()) {
            while ($rows = $result->fetch_object()) {
                $orders[] = $rows;
            }
        }
        return $orders;
    }

    private function getUserOrders(string $type=null): array{
        $orders = array();
        if($type=='group'){
            $stmt = $this->mysqli->prepare("SELECT UuidFromBin(pbc_minibar_order_header.publicUnique) as 'checkGUID', company, DATE_FORMAT(dateDue,'%c/%d/%Y %l:%i %p') as 'dateDue', DATE_FORMAT(dateOrdered,'%c/%d/%Y %l:%i %p') as 'orderDate' FROM pbc_minibar_order_header, pbc_minibar pm WHERE pbc_minibar_order_header.mbUserID = ? AND pm.idpbc_minibar = minibarID AND orderType='minibar' and isGroup=1");
        }else {
            $stmt = $this->mysqli->prepare("SELECT UuidFromBin(pbc_minibar_order_check.publicUnique) as 'checkGUID', company, DATE_FORMAT(dateDue,'%c/%d/%Y %l:%i %p') as 'dateDue', DATE_FORMAT(checkAdded,'%c/%d/%Y %l:%i %p') as 'orderDate' FROM pbc_minibar_order_check,pbc_minibar_order_header, pbc_minibar pm WHERE mbOrderID = headerID AND pbc_minibar_order_check.mbUserID = ? AND pm.idpbc_minibar = minibarID AND orderType='minibar' and isGroup=0");
        }
        $stmt->bind_param("s", $this->userID);
        $stmt->execute();
        if($result = $stmt->get_result()) {
            while ($rows = $result->fetch_object()) {
                $orders[] = $rows;
            }
        }
        return $orders;
    }

    private function getUserAddresses(): array{
        $addresses=array();
        $stmt=$this->mysqli->prepare("SELECT addressID,addressType as 'type', street,addStreet,city,state,zip FROM pbc_minibar_users_address WHERE mbUserID=? AND isDeleted=0");
        $stmt->bind_param("s", $this->userID);
        $stmt->execute();
        if($result = $stmt->get_result()) {
            while ($rows = $result->fetch_object()) {
                $addresses[] = $rows;
            }
        }
        return $addresses;
    }

    private function lockUserAccount(): bool
    {
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET isLocked=1 WHERE id='".$this->userID."'");
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to lock account.<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        return true;
    }
    public function addUserAddress(object $request): array{
        $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_address (mbUserID, addressType, street, addStreet, city, state, zip, isDeleted)VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss",
            $this->userID,
            $request->type,
            $request->street,
            $request->addstreet,
            $request->city,
            $request->state,
            $request->zip,
            $request->isDeleted
        );
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to insert new address.<br><br>LP: ".print_r($request,true)."<br><br>DB Error: " . $stmt->error . "<br><br>Request: " . print_r($request, true);
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return array("message"=>"There was an error logging you in. This error has been reported.","Variant"=>"danger");
        }else{
            return array("message"=>"Address Added.","Variant"=>"success", "address"=>[
                "addressID" => $stmt->insert_id,
                "type" => $request->type,
                "street" => $request->street,
                "addStreet" => $request->addStreet,
                "city" => $request->city,
                "state" => $request->state,
                "zip" => $request->zip
            ]);
        }
    }
    public function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    public function checkSession(string $sessionGUID): string
    {
        $stmt=$this->mysqli->prepare("SELECT mbUserId FROM pbc_minibar_users_sessions WHERE SessionGUID = UuidToBin(?) AND expireTime >= NOW()");
        $stmt->bind_param("s", $sessionGUID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->mbUserId)) {
            return $row->mbUserId;
        }
        $this->doLogout($sessionGUID);
        return false;
    }

    public function getContactFomSession(string $sessionGUID): object{
        if($ID =  $this -> checkSession($sessionGUID)){
            $stmt=$this->mysqli->prepare("SELECT real_name1,id,phone_number,email_address FROM pbc_minibar_user WHERE id=?");
            $stmt->bind_param("s", $ID);
            $stmt->execute();
            $result = $stmt->get_result();
            if($row = $result->fetch_object()){
                return $row;
            }
        }
        return (object)[null];
    }
    public function generateHexLink(string $lp, array $orderparams = null): string
    {
        if(empty($orderparams)) {
            $stmt = $this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_links (mbUserID, linkPurpose)VALUES(?,?)");
            $stmt->bind_param("ss", $this->userID, $lp);
        }else{
            $stmt = $this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_links (mbUserID, linkPurpose, linkExpires, orderHeaderID, mbService)VALUES(?,?,?,?,?)");
            $stmt->bind_param("sssss", $this->userID, $lp, $orderparams['linkExpires'], $orderparams['orderHeaderID'], $orderparams['mbService']);
        }
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to create hex link.<br><br>LP: ".$lp."<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        $linkId=$stmt->insert_id;
        $stmt=$this->mysqli->prepare("SELECT linkHEX FROM pbc_minibar_users_links WHERE linkID=?");
        $stmt->bind_param("s", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->linkHEX)) {
            return $row->linkHEX;
        }
        return false;
    }
    public function doLogout(string $sessionID): bool
    {
      $logOutTime=date("Y-m-d G:i:s");
      $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_users_sessions SET logoutTime=? WHERE SessionGUID = UuidToBin(?)");
      $stmt->bind_param("ss", $logOutTime,$sessionID);
      $stmt->execute();
      if (isset($stmt->error) && $stmt->error!='') {
          return false;
      }
      return true;
    }
    public function checkValidLinkHEX($linkHEX, $lp)
    {
        $stmt=$this->mysqli->prepare("SELECT * FROM pbc_minibar_users_links,pbc_minibar_user WHERE linkHEX=? AND linkPurpose=? AND id=mbUserID");
        $stmt->bind_param("ss", $linkHEX, $lp);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->id)) {
            $this->setUserID($row->id);
            return true;
        }
        return false;
    }
    public function doForgotPassword()
    {
        if (!empty($this->userID)) {
            $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET requireNewPassword=1, isLocked=0 WHERE id='".$this->userID."'");
            $stmt->execute();
            if (isset($stmt->error) && $stmt->error!='') {
                $report=new ToastReport;
                $m="User (".$this->userID.") failed to lock password.<br><br>DB Error: " . $stmt->error;
                $report->reportEmail("errors@theproteinbar.com", $m, "User error");
                return false;
            }
            if ($linkHEX=$this->generateHexLink("forgot_password")) {
                $report=new ToastReport;
                $m="Someone has requested a new password to Protein Bar & Kitchen using this email. Please use this link to reset your password. <a href='https://www.pbkminibar.com/forgotpass/".$linkHEX."'>https://www.pbkminibar.com/forgotpass/".$linkHEX."</a>";
                $report->reportEmail($this->userDetails->email_address, $m, "Protein Bar & Kitchen Password Reset");
            } else {
                return false;
            }
        }
        return array("message"=>"Thank you for your request. If we find an account associated with your email address, we will email you instructions to reset your password.","Variant"=>"success");
    }
    private function addNewUser($request)
    {
        $phone=$this->cleanPhone($request->phone);
        $emailConsent=$this->switchEmailConsent($request->emailConsent);
        $password = password_hash($request->password, PASSWORD_DEFAULT);
        $stmt=$this->mysqli->prepare("INSERT INTO pbc_minibar_user (login_name, password, email_address, real_name1, phone_number, emailConsent)VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $request->user, $password, $request->user, $request->name, $phone, $emailConsent);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User failed to add.<br><br>Request: ".print_r($request, true)."<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        return $stmt->insert_id;
    }

    public function updateUserInfo($field, $value){
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET ".$field."=? WHERE id=?");
        $stmt->bind_param("ss", $value, $this->userID);
        $stmt->execute();
        if(isset($stmt->error) && $stmt->error!=''){
            return ["status" => 400, "msg" => "Error saving."];
        }else{
            return ["status" => 200, "msg" => "Save successful."];
        }
    }

    public function removeAddress($id){
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_users_address SET isDeleted=1 WHERE addressID=?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        if(isset($stmt->error) && $stmt->error!=''){
            return ["status" => 400, "msg" => "Error saving."];
        }else{
            return ["status" => 200, "msg" => "Address Removed"];
        }
    }

    private function loadUserDetails():void
    {
        $stmt1=$this->mysqli->prepare("SELECT email_address,real_name1,phone_number,emailConsent,password,isLocked,requireNewPassword,isConfirmed FROM pbc_minibar_user WHERE  id=?");
        $stmt1->bind_param("s", $this->userID);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $this->userDetails = $result1->fetch_object();
    }

    private function updateUser($request)
    {
        $phone=$this->cleanPhone($request->phone);
        $emailConsent=$this->switchEmailConsent($request->emailConsent);
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET login_name=?, real_name1=?, phone_number=?, emailConsent=? WHERE id='".$this->userID."'");
        $stmt->bind_param("ssss", $request->user, $request->name, $phone, $emailConsent);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to update.<br><br>Request: ".print_r($request, true)."<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        if (!empty($request->password)) {
            $this->updatePassword($request->password);
        }
        return true;
    }
    public function updatePassword($password)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET password=?,requireNewPassword=0 WHERE id='".$this->userID."'");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to update password<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        return true;
    }
    public function getUserDetails(){
        return $this->userDetails;
    }

    public function cleanPhone($phone)
    {
        return preg_replace('/\D+/', '', $phone);
    }
    public function switchEmailConsent($emailConsent)
    {
        if ($emailConsent=='on') {
            return 1;
        }
        return 0;
    }
    public function getUserExists()
    {
        if ($this->userExists === true) {
            return true;
        }
        return false;
    }
    public function getGuestUser()
    {
        if ($this->guestUser === true) {
            return true;
        }
        return false;
    }
    public function setUserExists($var)
    {
        if ($var===true) {
            $this->loadUserDetails();
        }
        $this->userExists=$var;
    }
    public function getUserID()
    {
        return $this->userID;
    }
    public function setUserID($var)
    {
        $this->userID=$var;
        $this->loadUserDetails();
    }
    public function setGuestUser($var)
    {
        if ($var===true) {
            $this->loadUserDetails();
        }
        $this->guestUser=$var;
    }
    public function setmysqli($var)
    {
        $this->mysqli=$var;
    }
}
