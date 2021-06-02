<?php
add_action( 'wp_ajax_get_availableRestaurants', 'availableRestaurants' );
add_action( 'wp_ajax_get_restaurantOptions', 'restaurantOptions' );
add_action( 'wp_ajax_set_signature', 'updateSignature' );

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

function updateSignature(){
    $data = array();
    $result = array();

    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        foreach ($_REQUEST['data'] as $d) {
            $data[$d['field']] = $d['value'];
        }
        $newSignature = '
<div style="padding-left: 16px;background:#ffffff">
    <div style="float:left;padding:1px 10px 0 0">
        <img src="https://c2.theproteinbar.com/PBK-Logo_Tertiary_Full-Color_92.png" alt=""
             style="padding:3px 0px 0px 10px;width:51px;height:51px">
    </div>
    <div style="float:left;padding:1px 10px 0 0">
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#0e2244;margin:0;text-transform:capitalize">
            <span id="name">' . $data["name"] . '</span><br>
            <span style="color: #f36c21" id="location">' . $data["location"] . '</span>
        </div>';
        if(!empty($data['title'])){
            $newSignature.='
                    <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;margin:0px;color: #93c47d;">
            <span id="title">' . $data["title"] . '</span>
        </div>';
        }
        $newSignature.='
        <div style="font-style:normal;color:#000000;font-size:12px;line-height:20px;font-family:Arial,Helvetica,sans-serif;font-size-adjust:none;margin:0px">
            <span id="address">' . $data["address"] . '</span>
        </div>
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
            <span>P</span> <span style="font-weight:normal;color:#404040;" id="phone">' . $data["phone"] . '</span></div>
        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
            <span>E</span> <span style="font-weight:normal;color:#404040;" id="email"><a
                        href="mailto:' . $data["email"] . '" style="text-decoration:none;color:#404040;" target="_blank">' . $data["email"] . '</a></span>
        </div>
    </div>
</div>
<div style="clear:both"></div>';
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

    header('Content-Type: application/json');
    echo json_encode($result);
    wp_die();
}