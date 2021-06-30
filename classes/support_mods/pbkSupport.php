<?php
add_action( 'wp_ajax_getSupportMod', 'getSupportMod' );
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