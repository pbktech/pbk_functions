<?php
add_action( 'wp_ajax_get_nutrition_list', 'nutritionItems' );
add_action( 'wp_ajax_get_nutrition_info', 'nutritionInfo' );
add_action( 'wp_ajax_add_item_nutritional', 'addItemNutritional' );

function nutritionItems(){
    global $wpdb;
    $items = array();
    header('Content-Type: application/json');
    $allItems = $wpdb->get_results("SELECT itemName,published,idpbc_public_nutritional FROM pbc_public_nutritional WHERE itemName LIKE '%".$_REQUEST['item']."%'");
    if($allItems){
        foreach($allItems as $item) {
            $pub =$item->published === "0" ? (" (inactive)") : ("");
            $items['results'][] = array("id"=>$item->idpbc_public_nutritional,"text" => stripslashes($item->itemName) . $pub);
        }
    }
    echo json_encode($items);
    wp_die();
}

function nutritionInfo(){
    global $wpdb;
    header('Content-Type: application/json');
    $item = $wpdb->get_row("SELECT * FROM pbc_public_nutritional WHERE idpbc_public_nutritional = '".$_REQUEST['item']."'");
    echo json_encode($item);
    wp_die();
}

function addItemNutritional(){
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce( $_REQUEST['nonce'], "add_closure_nonce")) {
        wp_die();
    }
    global $wpdb;
    $itemInfo = $_REQUEST['itemInfo'];
    if (isset($_REQUEST['allergens'])) {
        $itemInfo['allergens'] = implode(", ", $_REQUEST['allergens']);
    } else {
        $itemInfo['allergens'] = "";
    }
    if (isset($_REQUEST['preferences'])) {
        $itemInfo['preferences'] = implode(", ", $_REQUEST['preferences']);
    } else {
        $itemInfo['preferences'] = "";
    }
    $itemInfo = json_encode($itemInfo);
    if($_REQUEST['published'] === "true"){
        $published = 1;
    }else{
        $published = 0;
    }
    if ($_REQUEST['itemID'] == '_NEW') {
        $wpdb->query($wpdb->prepare(
            "
      INSERT INTO pbc_public_nutritional (itemName,itemSection,published,itemInfo,toastGUID)VALUES(%s,%s,%s,%s,%s)
      ",
            $_REQUEST['itemName'],
            $_REQUEST['itemSection'],
            $published,
            $itemInfo,
            $_REQUEST['toastGUID']
        ));
    } else {
        $wpdb->query($wpdb->prepare(
            "
      REPLACE INTO pbc_public_nutritional (idpbc_public_nutritional,itemName,itemSection,published,itemInfo,toastGUID)VALUES(%s,%s,%s,%s,%s,%s)
      ",
            $_REQUEST['itemID'],
            $_REQUEST['itemName'],
            $_REQUEST['itemSection'],
            $published,
            $itemInfo,
            $_REQUEST['toastGUID']
        ));
    }
    if ($wpdb->last_error !== '') {
        $result = ["status" => 400, "message" => "There was an error saving: " . $wpdb->last_error];
    }else{
        $result = ["status" => 200, "message" => $_REQUEST['itemName'] . " has been saved."];
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    wp_die();
}