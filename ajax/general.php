<?php
add_action( 'wp_ajax_get_availableRestaurants', 'availableRestaurants' );
add_action( 'wp_ajax_get_restaurantOptions', 'restaurantOptions' );
add_action( 'wp_ajax_set_signature', 'updateSignature' );
add_action( 'wp_ajax_add_google_user', 'addGoogleUser' );
add_action( 'wp_ajax_get_support_contacts', 'supportContacts' );

function availableRestaurants(){
    $restaurants = array();
    global $wpdb;
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        $result = $wpdb->get_results("SELECT restaurantName, restaurantID FROM pbc_pbrestaurants where isOpen = 1 AND restaurantCode!='SSC'");
    }else{
        $result = $wpdb->get_results("SELECT restaurantID,restaurantName FROM  pbc_pbrestaurants WHERE restaurantID IN (SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."')");
    }
    foreach($result as $r){
        $restaurants[] = (object)["id" => $r->restaurantID, "text" => $r->restaurantName];
    }
    header('Content-Type: application/json');
    echo json_encode((object)["results" => $restaurants, "pagination" => (object)["more" => true]]);
    wp_die();
}

function supportContacts(){
    global $wpdb;
    $contacts = $wpdb->get_results("SELECT category, platform, services, contact, pbk_contact FROM pbc_support_contacts WHERE isActive=1");
    header('Content-Type: application/json');
    echo json_encode((object)["data" => $contacts]);
    wp_die();
}

function restaurantOptions(){
    $restaurants = array();
    $cu = wp_get_current_user();
    global $wpdb;
    $results = $wpdb->get_results("SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."'");
    foreach ($results as $r){
        $restaurants[] = $r->restaurantID;
    }
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles) || in_array($_REQUEST['restaurantID'], $restaurants, true)) {
        $options = $wpdb->get_row("SELECT options FROM pbc_pbrestaurants WHERE restaurantID = '" . $_REQUEST['restaurantID'] . "'");
    }else{
        wp_die();
    }
    header('Content-Type: application/json');
    echo json_encode($options);
    wp_die();
}

function setSignatureTask($data = array()){
    global $wpdb;
    $wpdb->insert(
        "pbc_tasks",
        array(
            'what' => 'execBackground',
            'target' => "php /home/jewmanfoo/toast-api/signTest.php",
            'files' => $data['email'],
            'text' => '<div style="padding-left: 16px;background:#ffffff">
    <div style="float:left;padding:1px 10px 0 0">
        <img src="https://c2.theproteinbar.com/PBK-Logo_Tertiary_Full-Color_92.png" alt=""
             style="padding:3px 0px 0px 10px;width:51px;height:51px">
    </div>
    <div style="float:left;padding:1px 10px 0 0">
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#0e2244;margin:0;text-transform:capitalize">
            <span id="name">' . $data['name'] . '</span><br><span style="color: #f36c21" id="location">Protein Bar &amp; Kitchen</span>
        </div>
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;margin:0px;color: #93c47d;">
            <span id="title">' . $data['title'] . '</span>
        </div>
        <div style="font-style:normal;color:#000000;font-size:12px;line-height:20px;font-family:Arial,Helvetica,sans-serif;font-size-adjust:none;margin:0px">
            <span id="address">' . $data['address'] . '</span>
        </div>
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
            <span>P</span> <span style="font-weight:normal;color:#404040;" id="phone">' . $data['phone'] . '</span></div>
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
            <span>E</span> <span style="font-weight:normal;color:#404040;" id="email"><a
                        href="mailto:' . $data['email'] . '" style="text-decoration:none;color:#404040;" target="_blank">' . $data['email'] . '</a></span>
        </div>
    </div>
</div>
<div style="clear:both"></div>',
            'dueDate' => date('Y-m-d H:i:s')
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );
}

function updateSignature(){
    $data = array();
    foreach ($_REQUEST['data'] as $d) {
        if(!empty($d['field']) && !empty($d['value'])) {
            $data[$d['field']] = $d['value'];
        }
    }
    /*
    $data = array();
    $result = array();

    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        foreach ($_REQUEST['data'] as $d) {
            $data[$d['field']] = $d['value'];
        }
        putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/silicon-will-769-d21d82edbb3a.json');
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setScopes(array('https://www.googleapis.com/auth/gmail.settings.basic','https://www.googleapis.com/auth/gmail.settings.sharing','https://www.googleapis.com/auth/admin.directory.user'));
        $client->setSubject($data['email']);
        $gmail = new Google_Service_Gmail($client);
        $signature = new Google_Service_Gmail_SendAs();
        $signature->setSignature($newSignature);

        $response = $gmail->users_settings_sendAs->patch($data["email"],$data["email"],$signature);
        if($response->isDefault === 1 && $response->isPrimary === 1){
            $result = ['status' => 200];
        }else{
            $result = ['status' => 400];
        }
    }else{
        $result = ['status' => 401];
    }
*/
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function addGoogleUser(){
    global $wpdb;
    $message = array();
    $status = 200;
    $class = "alert alert-success";
    $data = array();
    foreach ($_REQUEST['data'] as $d) {
        if(!empty($d['field']) && !empty($d['value'])) {
            $data[$d['field']] = $d['value'];
        }
    }
    if(in_array($data['title'],['General Manager', 'Assistant Manager'])){
        $orgPath = '/Chicago/Managers';
        $title = $data['title'];
        $restaurant = $wpdb->get_row('SELECT phone, CONCAT(address1, " ", address2, " ", city, ", ", state, " ", zip) as \'address\' FROM pbc_pbrestaurants pp WHERE restaurantID = ' . $data['restaurant']);
    }else{
        $title = $data['titleInput'];
        $orgPath = '/Store Support';
        $restaurant = (object)["address" => "231 S LaSalle St. Suite 2100, Chicago, IL 60604", "phone" => $data['phone']];
    }
    $name = explode(" ", $data['name']);
    putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/silicon-will-769-d21d82edbb3a.json');
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->setScopes(array('https://www.googleapis.com/auth/admin.directory.user',
        'https://www.googleapis.com/auth/admin.directory.group'));
    $client->setSubject("jon@theproteinbar.com");
    $service = new Google_Service_Directory($client);
    $nameInstance = new Google_Service_Directory_UserName();
    $nameInstance -> setGivenName($name[0]);
    $nameInstance -> setFamilyName($name[1]);
    $email = strtolower($data['email'] . '@theproteinbar.com');
    $password = randomPassword();
    $userInstance = new Google_Service_Directory_User();
    $userInstance -> setName($nameInstance);
    $userInstance -> setHashFunction("MD5");
    $userInstance -> setPrimaryEmail($email);
    $userInstance -> setPassword(hash("md5", $password));
    $userInstance->setChangePasswordAtNextLogin(true);
    $userInstance->setPhones(array("type" => "work", "value" => ""));
    $userInstance->setAddresses(array("type" => "work", "formatted" => ""));
    $userInstance->setIncludeInGlobalAddressList(true);
    $userInstance->setOrgUnitPath($orgPath);
    try{
        $createUserResult = $service->users->insert($userInstance);
        if(!empty($data['groups'])){
            $memberInstance = new Google_Service_Directory_Member();
            $memberInstance->setEmail($email);
            $memberInstance->setRole('MEMBER');
            $memberInstance->setType('USER');
            foreach($data['groups'] as $group) {
                try{
                    $insertMembersResult = $service->members->insert($group, $memberInstance);
                    $message[] = $data['name'] . " has been added.";
                }
                catch (Google_IO_Exception $gioe){
                    $message[]= "Error in group connection: ".$gioe->getMessage();
                }
            }
        }
        require_once dirname(__DIR__) . "/templates/email/public.php";
        $m = getHeader() . "<br><br>
Hi " . $name[0] . ",<br><br>
Welcome to Protein Bar & Kitchen! This email has your PBK Email (powered by Gmail) and Compeat setup and credentials.<br><br>
<strong>PBK Email</strong><br>
Go to https://mail.google.com. Sign in with the credentials below and accept the terms of Gmail. Gmail will require you to change your password when you first login. After signing in for the first time, you can add your email to your phone. <br>
<a href='https://support.apple.com/en-us/HT201320'>How to Add PBK Email To iPhone</a><br>
<a href='https://gsuitetips.com/tips/more/android/android-gmail-setup-instructions/'>How to Add PBK Email to Android</a><br>
<br><br>
U: " . $email . "<br>
P: " . $password . "<br>
<br><br>
<strong>Compeat</strong><br>
Compeat will require you to change your password when you first login.<br><br>
U: " . $data['email'] . "<br>
P: " . $password . "<br>";
        $wpdb->insert(
            "pbc_tasks",
            array(
                'what' => 'sendEmail',
                'target' => $data['notify'] . ", jon@theproteinbar.com",
                'text' => $m,
                'subject' => "Welcome to Protein Bar & Kitchen!",
                'dueDate' => date('Y-m-d H:i:s')
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
        setSignatureTask(["name" => $data['name'], "title" => $title, "address" => $restaurant->address, "phone" => $restaurant->phone, "email" => $email]);
        $message[]= $data['name'] . " has been setup";
    }
    catch (Google_IO_Exception $gioe){
        $message[]= "Error in connection: ".$gioe->getMessage();
        $status = 400;
        $class = "alert alert-danger";
    }
    catch (Google_Service_Exception $gse){
        $message[]= "User already exists: ".$gse->getMessage();
        $status = 400;
        $class = "alert alert-danger";
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => implode('<br>', $message), "class" => $class]);
    wp_die();
}