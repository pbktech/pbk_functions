<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
add_action("wp_ajax_subscribers_get_trans", "subscribers_get_trans");
add_action("wp_ajax_subscribers_get_users", "subscribers_get_users");
add_action("wp_ajax_subscribers_cancel", "subscribers_cancel");

function subscribers_get_trans() {
    $data = array("data" => array());
    if (wp_verify_nonce($_REQUEST['nonce'], '_get_trans_' . $_REQUEST['uis'])) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT concat('$', format(amount, 2)) as 'price',transactionType, transactionStatus, DATE_FORMAT(transactionTime, '%m/%d/%Y %r') as 'datetime' FROM pbc_subscriptions_transactions WHERE subscriptionID  = '" . $_REQUEST['uis'] . "'", ARRAY_A
        );
        if ($results) {
            foreach ($results as $r) {
                $data['data'][] = $r;
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function subscribers_get_users() {
    global $wpdb;
    $data = array("data" => array());
    $results = $wpdb->get_results(
        "SELECT guestName,phoneNumber,emailAddress,planName,DATE_FORMAT(dateStarted, '%c/%d/%Y') as 'signedUp', userID,isActive, DATE_FORMAT(dateEnded, '%c/%d/%Y') as 'ended' FROM pbc_subscriptions ps, pbc_subscriptions_plans psp WHERE firstData is not null AND ps.subPlan = psp.planID"
    );
    if ($results) {
        foreach ($results as $r) {
            $showDelete = '<button disabled title="Canceled on ' . $r->ended . '" class="btn btn-outline-dark"><i class="fas fa-skull-crossbones"></i></button>';
            if ($r->isActive == 1) {
                $showDelete = '<button title="Cancel" data-toggle="tooltip" data-subname="' . $r->planName . '" data-placement="bottom" data-nonce="' . wp_create_nonce('_cancel_' . $r->userID) . '" data-uid="' . $r->userID . '" data-guest="' . $r->guestName . '" class="btn btn-outline-danger cancelUser"><i class="far fa-trash-alt" data-nonce="' . wp_create_nonce('_cancel_' . $r->userID) . '" data-subname="' . $r->planName . '" data-uid="' . $r->userID . '" data-guest="' . $r->guestName . '"></i></button>';
            }
            $data['data'][] = [
                "guestName" => $r->guestName,
                "isActive" => ($r->isActive == 1 ? 'Active' : 'Canceled'),
                "phoneNumber" => preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "($1) $2-$3", str_replace("+", "", $r->phoneNumber)),
                "emailAddress" => $r->emailAddress,
                "planName" => $r->planName,
                "signedUp" => $r->signedUp,
                "actions" => $showDelete . '&nbsp;<button title="View Transactions" id="" class="btn btn-outline-primary showModal" data-toggle="tooltip" data-placement="bottom" data-nonce="' . wp_create_nonce('_get_trans_' . $r->userID) . '" data-uid="' . $r->userID . '" data-guest="' . $r->guestName . '"><i data-nonce="' . wp_create_nonce('_get_trans_' . $r->userID) . '" data-uid="' . $r->userID . '" data-guest="' . $r->guestName . '" class="fas fa-list showModal"></i></button>',
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function subscribers_cancel() {
    if (wp_verify_nonce($_REQUEST['nonce'], '_cancel_' . $_REQUEST['uid'])) {
        global $wpdb;
        $wpdb->update(
            'pbc_subscriptions',
            array(
                'isActive' => 0,
                'dateEnded' => date('Y-m-d h:i:s')
            ),
            array('userID' => $_REQUEST['uid']),
            array(
                '%d',
                '%s'
            ),
            array('%d')
        );
        if (empty($wpdb->last_error)) {
            $status = 200;
            $class = "alert-success";
            $message = $_REQUEST['guest'] . " has been updated.";
        } else {
            $status = 400;
            $class = "alert-danger";
            $message = "There was an error canceling, the database said: <br>" . $wpdb->last_error;
        }
    }else{
        $status = 400;
        $class = "alert-danger";
        $message = "Security Check Failed. The subscription has not been canceled.";
    }
    header('Content-Type: application/json');
    echo json_encode(["status" => $status, "class" => $class, "message" => $message]);
    wp_die();
}
