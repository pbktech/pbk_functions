<?php
add_action( 'wp_ajax_get_nutrition_list', 'nutritionItems' );
add_action( 'wp_ajax_get_nutrition_info', 'nutritionInfo' );
add_action( 'wp_ajax_add_item_nutritional', 'addItemNutritional' );
add_action( 'wp_ajax_export_nutritional', 'nutritionExport' );
add_action( 'wp_ajax_import_nutritional', 'nutritionImport' );

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

function nutritionImport() {
    global $wpdb;
    header('Content-Type: application/json');
    $row = 1;
    if (($handle = fopen($_FILES['file']['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if($data[0]==="ID" || count($data)!==18){continue;}
            $published = strtolower($data[3]) === "yes" ? 1 : 0;
            $allergens = "";
            $preferences = "";
            if(!empty($data[17])){$preferences = str_replace("; ", ", ", $data[17]);}
            if(!empty($data[16])){$allergens = str_replace("; ", ", ", $data[16]);}
            $itemSection = $wpdb->get_var("SELECT sectionID FROM pbc_public_nutritional_sections WHERE section='". $data[2] ."'");
            $itemInfo = json_encode([
                "PR" => $data[4],
                "Cal" => $data[5],
                "TF" => $data[6],
                "SF" => $data[7],
                "TRF" => $data[8],
                "CHO" => $data[9],
                "SOD" => $data[10],
                "NC" => $data[11],
                "TC" => $data[12],
                "DF" => $data[13],
                "SG" => $data[14],
                "allergens" => $allergens,
                "preferences" => $preferences
            ]);
            if(empty($data[0])){
                $wpdb->query($wpdb->prepare(
                    "
      INSERT INTO pbc_public_nutritional (itemName,itemSection,published,itemInfo,toastGUID)VALUES(%s,%s,%s,%s,%s)
      ",
                    $data[1],
                    $itemSection,
                    $published,
                    $itemInfo,
                    $data[15]
                ));
            }else{
                $wpdb->query($wpdb->prepare(
                    "
      REPLACE INTO pbc_public_nutritional (idpbc_public_nutritional,itemName,itemSection,published,itemInfo,toastGUID)VALUES(%s,%s,%s,%s,%s,%s)
      ",
                    $data[0],
                    $data[1],
                    $itemSection,
                    $published,
                    $itemInfo,
                    $data[15]
                ));
            }
            if(empty($wpdb->last_error)) {
                $row++;
            }
        }
    }
    echo json_encode(array("records" =>$row));
    wp_die();
}

function nutritionExport(){
    global $wpdb;
    $new_csv = fopen('/tmp/report.csv', 'w');
    fputcsv($new_csv,array("ID","Name", "Category", "Published", "Protein", "Calories", "Total Fat","Saturated Fat", "Trans Fat", "Cholesterol", "Sodium", "Net Carbs", "Total Carbs", "Dietary Fiber", "Sugars", "Toast GUID", "Allergens", "Preferences"));
    $rawItems = $wpdb->get_results("SELECT * FROM pbc_public_nutritional ppn, pbc_public_nutritional_sections ppns  WHERE ppn.itemSection = ppns.sectionID ");
    foreach($rawItems as $item){
        $info = json_decode($item->itemInfo);
        $published = $item->published === "1" ? "Yes" : "No";
        $allergens = "";
        $preferences = "";
        if(!empty($info->preferences)){$preferences = str_replace(", ", "; ", $info->preferences);}
        if(!empty($info->allergens)){$allergens = str_replace(", ", "; ", $info->allergens);}
        fputcsv($new_csv,array(
            $item->idpbc_public_nutritional,
            $item->itemName,
            $item->section,
            $published,
            $info->PR,
            $info->Cal,
            $info->TF,
            $info->SF,
            $info->TRF,
            $info->CHO,
            $info->SOD,
            $info->NC,
            $info->TC,
            $info->DF,
            $info->SG,
            $item->toastGUID,
            $allergens,
            $preferences
        ));
    }
    fclose($new_csv);
    header("Content-type: text/csv");
    header("Content-disposition: attachment; filename=all_nutrition_items.csv");
    readfile("/tmp/report.csv");
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