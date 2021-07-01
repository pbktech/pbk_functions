<?php
add_action( 'wp_ajax_getSupportMod', 'getSupportMod' );
add_action( 'wp_ajax_get_ticket_list', 'get_ticket_list' );

function get_ticket_list(){
    global $wpdb;
    $return = array();
    $cu = wp_get_current_user();
    $query = "SELECT * FROM pbc_support_ticket pst, pbc_support_items psi, pbc_pbrestaurants pp WHERE pst.itemID = psi.itemID AND pst.restaurantID = pp.restaurantID AND ticketStatus != 'Closed'";
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
                "status" => $r->tiketStatus,
                "actions" => ""
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