<?php
add_action('wp_ajax_getSupportMod', 'getSupportMod');
add_action('wp_ajax_get_ticket_list', 'get_ticket_list');
add_action('wp_ajax_uploadPBKImage', 'uploadPBKImage');
add_action('wp_ajax_startTicket', 'startTicket');
add_action('wp_ajax_updateTicket', 'updateTicket');
add_action('wp_ajax_notifyEmergency', 'notifyEmergency');
add_action('wp_ajax_supportUpdateContacts', 'supportUpdateContacts');
add_action('wp_ajax_supportGetEquipmentList', 'supportGetEquipmentList');
add_action('wp_ajax_supportGetEquipmentInfo', 'supportGetEquipmentInfo');
add_action('wp_ajax_supportChangeEquipmentStatus', 'supportChangeEquipmentStatus');
add_action('wp_ajax_supportChangeEquipment', 'supportChangeEquipment');
add_action('wp_ajax_supportGetEquipmentCommonList', 'supportGetEquipmentCommonList');

function supportChangeEquipment(){
    global $wpdb;
    $item = $wpdb->get_var("SELECT itemName FROM pbc_support_items psi WHERE itemID = " . $_REQUEST['equipmentID']);
    $answer = ["status" => 200, "msg" => $item . " as been updated."];
    if($_REQUEST['equipmentID'] === '0'){
        $_REQUEST['equipmentID'] = $wpdb->get_var("SELECT MAX(itemID) +1 as 'nextID' FROM pbc_support_items psi WHERE itemID !=2147483646 AND itemID != 2147483647");
    }
    $wpdb->replace("pbc_support_items",
        [
            "itemID" => $_REQUEST['equipmentID'],
            "department" => $_REQUEST['department'],
            "itemName" => $_REQUEST['itemName'],
            "isActive" => $_REQUEST['isActive'] === 'true' ?1:0,
            "requireMMS" => $_REQUEST['requireMMS'] === 'true' ?1:0,
            "vendorID" => $_REQUEST['vendorID'],
            "redirect" => $_REQUEST['redirect']
        ],
        ["%d", "%s", "%s", "%d", "%d", "%d", "%s"]
    );
    if(!empty($wpdb->last_error)){
        $answer = ["status" => 400, "msg" => "There was an error saving: " . $wpdb->last_error];
    }
    showJsonAjax($answer);
}

function supportChangeEquipmentStatus(){
    global $wpdb;
    $item = $wpdb->get_var("SELECT itemName FROM pbc_support_items psi WHERE itemID = " . $_REQUEST['equipmentID']);
    $answer = ["status" => 200, "msg" => $item . " as been " . $_REQUEST['status'] . "d."];
    $wpdb->update("pbc_support_items",
    ["isActive" => $_REQUEST['status'] === "activate" ? 1:0],
    ["itemID" => $_REQUEST['equipmentID']]
    );
    if(!empty($wpdb->last_error)){
        $answer = ["status" => 400, "msg" => "There was an error saving: " . $wpdb->last_error];
    }
    showJsonAjax($answer);
}

function supportGetEquipmentInfo(){
    global $wpdb;
    $answer = ["status" => 404, "msg" => "Equipment Not Found"];
    $item = $wpdb->get_row("SELECT * FROM pbc_support_items WHERE itemID = " . $_REQUEST['equipmentID']);
    if($item){
        $answer = ["status" => 200, "info" => $item];
    }
    showJsonAjax($answer);
}

function supportGetEquipmentCommonList(){
    global $wpdb;
    $name = $wpdb->get_var("SELECT itemName FROM pbc_support_items psi WHERE itemID = " . $_REQUEST['equipmentID']);
    if(empty($name)){
        $answer = ["status" => 404, "msg" => "The equipment was not found."];
    }else {
        $answer = ["status" => 200, "name" => $name];
        $item = $wpdb->get_results("SELECT * FROM pbc_support_common WHERE itemID = " . $_REQUEST['equipmentID']);
        if ($item) {
            $answer = ["status" => 200, "name" => $name, "info" => $item];
        }
    }
    showJsonAjax($answer);
}


function supportGetEquipmentList() {
    global $wpdb;
    $data = array("data" => array());
    $items = $wpdb->get_results("SELECT * FROM pbc_support_items");
    if ($items) {
        foreach ($items as $i) {
            if($i->isActive == 1){
                $active = '<button class="btn btn-link text-danger statusButton" data-status="deactivate" data-equipment-id="'.$i->itemID.'" title="Deactivate"><i data-status="deactivate" data-equipment-id="'.$i->itemID.'" class="bi bi-x-square"></i></button>';
            }else{
                $active = '<button class="btn btn-link text-success statusButton" data-status="activate" data-equipment-id="'.$i->itemID.'" title="Activate"><i data-status="activate" data-equipment-id="'.$i->itemID.'" class="bi bi-check-square"></i></button>';
            }
            $data['data'][] = [
                "name" => $i->itemName,
                "department" => $i->department,
                "isActive" => $i->isActive,
                "actions" => '
                <div class="btn-group">
                        <button class="btn btn-link editButton" data-equipment-id="'.$i->itemID.'" title="Edit"><i data-equipment-id="'.$i->itemID.'" class="bi bi-pencil-square"></i></button>
                        <button class="btn btn-link text-info commonIssueButton" data-equipment-id="'.$i->itemID.'" title="Common Issues"><i data-equipment-id="'.$i->itemID.'" class="bi bi-clipboard"></i></button>
                        '.$active.'
                 </div>'
            ];
        }
    }
    showJsonAjax($data);
}

function supportUpdateContacts() {
    $answer = ["status" => 200, "msg" => "The contacts have been updated."];
    global $wpdb;
    $data = json_decode(stripslashes($_REQUEST['data']));
    $support = new PBKSupport();
    $departments = $support->getDepartments();
    foreach ($departments as $d) {
        $wpdb->query("DELETE FROM pbc_support_contact WHERE department = '$d'");
        if (!empty($wpdb->last_error)) {
            $answer = ["status" => 400, "msg" => "ERROR: " . $wpdb->last_error];
            break;
        }
        foreach ($data->$d as $user) {
            $wpdb->insert("pbc_support_contact",
                ["department" => $d, "userID" => $user],
                ["%s", "%d"]
            );
            if (!empty($wpdb->last_error)) {
                $answer = ["status" => 400, "msg" => "ERROR: " . $wpdb->last_error];
                break;
            }
        }
    }
    showJsonAjax($answer);
}

/**
 * @throws Exception
 */
function notifyEmergency() {
    global $wpdb;
    $cu = wp_get_current_user();
    $data = json_decode(stripslashes($_REQUEST['data']));
    if (!empty($data->issue)) {
        $itemID = $data->issue;
    }
    $returnEmails = [];
    $emails = $wpdb->get_results("SELECT user_email FROM pbc_users WHERE ID IN (
    SELECT userID FROM pbc_support_contact psc, pbc_support_items psi WHERE psc.department = psi.department AND psi.itemID = '" . $data->area . "')");
    if ($emails) {
        foreach ($emails as $email) {
            $returnEmails[] = $email->user_email;
        }
    }
    $notify = new PBKNotify();
    $subject = "[PBK Ticket] ***POSSIBLE EMERGENCY***";
    $notify->setMethod("sendEmail");
    $notify->setRecipients($returnEmails);
    $notify->setSubject($subject);
    $notify->setTemplate("ticket_emergency.html");
    $notify->setTemplateOptions([
        "name" => $cu->display_name,
        "issue" => $wpdb->get_var("SELECT issueTitle FROM pbc_support_common WHERE issueID = " . $data->issue),
        "area" => $wpdb->get_var("SELECT itemName FROM pbc_support_items psi WHERE itemID =" . $data->area)
    ]);
    $notify->sendMessage();
    showJsonAjax($notify->sendMessage());
}

function updateTicket() {
    $answer = ['message' => [], 'status' => 200];
    $cu = wp_get_current_user();
    $data = json_decode(stripslashes($_REQUEST['data']));
    if (empty($data->personName)) {
        $answer['message'][] = "Please enter your name.";
        $answer['status'] = 400;
    }
    if (empty($data->issueDescription)) {
        $answer['message'][] = "Please describe the issue.";
        $answer['status'] = 400;
    }
    if ($answer["status"] === 200) {
        $ticket = new PBKSupportTicket($data->ticketID);
        $ticket->setDescription($data->issueDescription);
        $ticket->setPersonName($data->personName);
        $ticket->setFiles($data->attachedFiles);
        $ticket->setCost(round(floatval($data->repairCost), 2));
        $ticket->recordResponse($cu->ID);
        if ($data->close) {
            $status = "Closed";
        } else {
            if ((!in_array("administrator", $cu->roles) && !in_array("editor", $cu->roles) && !in_array("author", $cu->roles))) {
                $status = "Waiting for Vendor";
            } else {
                $status = "Waiting for Restaurant";
            }
        }

        $ticket->updateStatus($status);
    }
    showJsonAjax($answer);
}

function startTicket() {
    $answer = ['message' => [], 'status' => 200];
    $itemID = 0;
    $cu = wp_get_current_user();
    $data = json_decode(stripslashes($_REQUEST['data']));
    if (empty($data->restaurantID)) {
        $answer['message'][] = "Please enter your name.";
        $answer['status'] = 400;
    }
    if (empty($data->personName)) {
        $answer['message'][] = "Please enter your name.";
        $answer['status'] = 400;
    }
    if (empty($data->issueDescription)) {
        $answer['message'][] = "Please describe the issue.";
        $answer['status'] = 400;
    }
    if (empty($data->area)) {
        $answer['message'][] = "Please select a Device.";
        $answer['status'] = 400;
    }
    if (!empty($data->issue)) {
        $itemID = $data->issue;
    }
    if ($answer["status"] === 200) {
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

function uploadPBKImage() {
    $answer = [];
    global $wpdb;

    foreach ($_FILES['files']['name'] as $f => $name) {
        $fileInfo = explode(".", $name);
        $ext = end($fileInfo);
        $wpdb->insert("pbc_files_stored", ["fileName" => json_encode(["name" => $name, "extension" => $ext]), "page" => "support"], ['%s', '%s']);
        $fileID = $wpdb->insert_id;
        $guid = $wpdb->get_var("SELECT UuidFromBin(publicUnique) FROM pbc_files_stored WHERE fileID = " . $fileID);
        upload_object("pbk-support", $guid . "." . $ext, $_FILES["files"]["tmp_name"][$f]);
        $answer[] = ["name" => basename($name), "fileID" => $fileID, "link" => "https://storage.googleapis.com/pbk-support/" . $guid . "." . $ext];
    }
    showJsonAjax($answer);
}

function get_ticket_list() {
    global $wpdb;
    $return = array();
    $cu = wp_get_current_user();
    $query = "SELECT openedTime, restaurantName, itemName, ticketStatus, UuidFromBin(publicUnique) as 'guid' FROM pbc_support_ticket pst, pbc_support_items psi, pbc_pbrestaurants pp WHERE pst.areaID = psi.itemID AND pst.restaurantID = pp.restaurantID AND ticketStatus != 'Closed'";
    if (!in_array("administrator", $cu->roles) && !in_array("editor", $cu->roles) && !in_array("author", $cu->roles)) {
        $query .= " AND pp.restaurantID IN (SELECT restaurantID FROM pbc_pbr_managers WHERE managerID = '" . $cu->ID . "')";
    }
    $result = $wpdb->get_results($query);
    if ($result) {
        foreach ($result as $r) {
            $return[] = [
                "date" => date("m/d/Y g:i a", strtotime($r->openedTime)),
                "restaurant" => $r->restaurantName,
                "item" => $r->itemName,
                "status" => $r->ticketStatus,
                "actions" => "<a href='" . add_query_arg(["id" => $r->guid], home_url($path = 'support')) . "' target='_blank'>Update</a>"
            ];
        }
    }
    showJsonAjax((object)["data" => $return]);
}

function showJsonAjax($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    wp_die();
}

function getSupportMod() {
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