<?php
add_action( 'wp_ajax_get_availableRestaurants', 'availableRestaurants' );
add_action( 'wp_ajax_get_restaurantOptions', 'restaurantOptions' );
add_action( 'wp_ajax_set_signature', 'updateSignature' );
add_action( 'wp_ajax_add_google_user', 'addGoogleUser' );
add_action( 'wp_ajax_get_support_contacts', 'supportContacts' );
add_action( 'wp_ajax_get_ssc_contacts', 'sscContacts' );
add_action( 'wp_ajax_get_directory', 'get_directory' );

function get_directory(){
    $return = array();
    global $wpdb;
    $restaurants = $wpdb->get_results("SELECT * FROM pbc_pbrestaurants where isOpen=1 order by restaurantID");
    foreach($restaurants as $restaurant){
        $r = new Restaurant($restaurant->restaurantID);
        $return[] = [
            "restaurantID" => $restaurant->restaurantID,
            "restaurant" => "<a title=\"Restaurant Hours\" href=\"restaurant-hours/#".$restaurant->restaurantCode."\">#".$restaurant->restaurantID." ".$restaurant->restaurantName."</a><br />" .date("m/d/Y",strtotime($restaurant->openingDate)),
            "email" => "<a href=\"mailto:".$restaurant->email."\" target=\"_blank\">".str_replace("theproteinbar.com","", $restaurant->email)."</a>",
            "phone" => "<a href=\"tel:+1".str_replace(".", '', $restaurant->phone)."\">".$restaurant->phone."</a>",
            "address" => "<a href=\"https://maps.google.com/maps?q=Protein+Bar+".str_replace(" ", "+", $restaurant->address1). "+" . $restaurant->city."+".$restaurant->state."+".$restaurant->zip."\" target='_blank'>" . $restaurant->address1 . "<br />". $restaurant->city.", ".$restaurant->state." ".$restaurant->zip."</a>",
            "gmagm" => "<a href=\"mailto:".$r->getManagerEmail("GM")."\" target=\"_blank\">" .$r->getManagerName("GM"). "</a><br /><a href=\"mailto:".$r->getManagerEmail("AGM")."\" target=\"_blank\">" .$r->getManagerName("AGM"). "</a>",
            "am" => "<a href=\"mailto:".$r->getManagerEmail("AM")."\" target=\"_blank\">" .$r->getManagerName("AM"). "</a>"
        ];
    }
    header('Content-Type: application/json');
    echo json_encode((object)["data" => $return]);
    wp_die();
}

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
    $contacts = $wpdb->get_results("SELECT category, platform, services, contact, pbk_contact FROM pbc_support_contacts WHERE isActive=1 and contactID!=0");
    header('Content-Type: application/json');
    echo json_encode((object)["data" => $contacts]);
    wp_die();
}

function sscContacts(){
    global $wpdb;
    $contacts = $wpdb->get_results("select position, support_provided, phone, display_name, user_email FROM pbc_ssc_contacts psc, pbc_users pu WHERE pu.ID = psc.userID ORDER BY display_name");
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
        $notify = new PBKNotify();
        $notify->setMethod("sendEmail");
        $notify->setRecipients($data['notify'] . ", tech@theproteinbar.com");
        $notify->setSubject("Welcome to Protein Bar & Kitchen!");
        $notify->setTemplate("newuser.html");
        $notify->setTemplateOptions([
            "name" => $name[0],
            "email" => $data['email'],
            "password" => $password
        ]);
        $notify->sendMessage();

        setSignatureTask(["name" => $data['name'], "title" => $title, "address" => $restaurant->address, "phone" => $restaurant->phone, "email" => $email]);
        $user_id = wp_insert_user( array(
            'user_login' => $email,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $name[0],
            'last_name' => $name[1],
            'display_name' => $name[0] . " " . $name[1],
            'role' => 'subscriber'
        ));
        if($user_id){
            if(in_array($data['title'],['General Manager', 'Assistant Manager'])) {
                $mgrType = $data['title'] === 'General Manager' ? "GM" : "AM";
                $existing = $wpdb->get_row("SELECT * FROM pbc_pbr_managers WHERE restaurantID = ". $data['restaurant'] . " AND mgrType = '" . $mgrType . "'");
                if($existing){
                    $access = "AA" . $user_id;
                }else{
                    $access= $mgrType;
                }
                $wpdb->insert(
                    "pbc_pbr_managers",
                    [
                        "restaurantID" => $data['restaurant'],
                        "mgrType" => $access,
                        "managerID" => $user_id
                    ],
                    [
                        "%d", "%s", "%d"
                    ]
                );
            }
        }
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