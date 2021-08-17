<?php
add_action( 'wp_ajax_getSupportMod', 'getSupportMod' );
add_action( 'wp_ajax_get_ticket_list', 'get_ticket_list' );
add_action( 'wp_ajax_uploadPBKImage', 'uploadPBKImage' );
add_action( 'wp_ajax_startTicket', 'startTicket' );
add_action( 'wp_ajax_updateTicket', 'updateTicket' );

function updateTicket(){
    $answer = ['message' => [], 'status' => 200];
    $cu = wp_get_current_user();
    $data = json_decode(stripslashes($_REQUEST['data']));
    if(empty($data->personName)){
        $answer['message'][] = "Please enter your name.";
        $answer['status'] = 400;
    }
    if(empty($data->issueDescription)){
        $answer['message'][] = "Please describe the issue.";
        $answer['status'] = 400;
    }
    if($answer["status"] === 200){
        $ticket = new PBKSupportTicket($data->ticketID);
        $ticket->setDescription($data->issueDescription);
        $ticket->setPersonName($data->personName);
        $ticket->setFiles($data->attachedFiles);
        $ticket->setCost(round(floatval($data->repairCost),2));
        $ticket->recordResponse($cu->ID);
        if($data->close){
            $ticket->updateStatus("Closed");
        }
    }
    showJsonAjax($answer);
}

function startTicket(){
    $answer = ['message' => [], 'status' => 200];
    $itemID = 0;
    $cu = wp_get_current_user();
    $data = json_decode(stripslashes($_REQUEST['data']));
    if(empty($data->restaurantID)){
        $answer['message'][] = "Please enter your name.";
        $answer['status'] = 400;
    }
    if(empty($data->personName)){
        $answer['message'][] = "Please enter your name.";
        $answer['status'] = 400;
    }
    if(empty($data->issueDescription)){
        $answer['message'][] = "Please describe the issue.";
        $answer['status'] = 400;
    }
    if(empty($data->area)){
        $answer['message'][] = "Please select a Device.";
        $answer['status'] = 400;
    }
    if(!empty($data->issue)){
        $itemID = $data->issue;
    }
    if($answer["status"] === 200){
        $ticket = new PBKSupportTicket("_NEW");
        $ticket->setAreaID($data->area);
        $ticket->setDescription($data->issueDescription);
        $ticket->setItemId($itemID);
        $ticket->setPersonName($data->personName);
        $ticket->setRestaurantID($data->restaurantID);
        $ticket->setFiles($data->attachedFiles);
        $ticket->setMms(["make" => $data->make, "model" => $data->model, "serial" => $data->serial]);
        $ticket->addNewTicket($cu->ID);
    }
    showJsonAjax($answer);
}

function uploadPBKImage(){
    $answer = [];
    global $wpdb;

    foreach($_FILES['files']['name'] as $f => $name){
        $fileInfo = explode(".", $name);
        $ext = end($fileInfo);
        $wpdb->insert("pbc_files_stored",["fileName" => json_encode(["name" => $name, "extension" =>$ext]), "page" => "support"], ['%s','%s']);
        $fileID = $wpdb->insert_id;
        $guid = $wpdb->get_var("SELECT UuidFromBin(publicUnique) FROM pbc_files_stored WHERE fileID = " . $fileID);
        upload_object("pbk-support", $guid . "." . $ext, $_FILES["files"]["tmp_name"][$f]);
        $answer[] = ["name" => basename($name), "fileID" => $fileID, "link" => "https://storage.googleapis.com/pbk-support/" . $guid . "." . $ext];
    }
    showJsonAjax($answer);
}

function get_ticket_list(){
    global $wpdb;
    $return = array();
    $cu = wp_get_current_user();
    $query = "SELECT openedTime, restaurantName, itemName, ticketStatus, UuidFromBin(publicUnique) as 'guid' FROM pbc_support_ticket pst, pbc_support_items psi, pbc_pbrestaurants pp WHERE pst.areaID = psi.itemID AND pst.restaurantID = pp.restaurantID AND ticketStatus != 'Closed'";
    if (!in_array("administrator", $cu->roles) && !in_array("editor", $cu->roles)  && !in_array("author", $cu->roles)) {
        $query.= " AND pp.restaurantID IN (SELECT restaurantID FROM pbc_pbr_managers WHERE managerID = '" . $cu->ID . "')";
    }
    $result = $wpdb->get_results($query);
    if ($result){
        foreach ($result as $r){
            $return[] = [
                "date" => date("m/d/Y g:i a", strtotime($r->openedTime)),
                "restaurant" => $r->restaurantName,
                "item" => $r->itemName,
                "status" => $r->ticketStatus,
                "actions" => "<a href='" . add_query_arg( ["id" => $r->guid], home_url( $path = 'support') ). "' target='_blank'>Update</a>"
            ];
        }
    }
    showJsonAjax((object)["data" => $return]);
}

function showJsonAjax($response){
    header('Content-Type: application/json');
    echo json_encode($response);
    wp_die();
}
function getSupportMod(){
    global $wpdb;
    $allIssues = [];
    $commonIssues = $wpdb->get_results("SELECT * FROM pbc_support_common WHERE itemID = " . $_REQUEST['itemID']);
    if ($commonIssues) {
        foreach ($commonIssues as $i) {
            $faqSteps = $wpdb->get_results("SELECT * FROM pbc_support_trouble_steps psts, pbc_support_trouble_assign psta WHERE psta.issueID = " . $i->issueID . " AND psta.stepID = psts.stepID ORDER BY psta.stepOrder");
            $allIssues[] = (object)["ci" => $i, "steps" => $faqSteps];
        }
    }
    showJsonAjax($allIssues);
}