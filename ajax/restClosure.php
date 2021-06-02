<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

add_action( 'wp_ajax_get_closure_list', 'closureList' );
add_action( 'wp_ajax_get_current_closure_list', 'currentClosures' );
add_action( 'wp_ajax_add_new_closure', 'addClosure');
add_action( 'wp_ajax_remove_closure', 'deleteClosure');

function currentClosures(){
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce( $_REQUEST['nonce'], "get_current_closure_list")) {
        wp_die();
    }
    global $wpdb;
    $restClose=array("data" => array());
    $closures = $wpdb->get_results("SELECT * FROM pbc_pbr_closures ppc WHERE isDeleted = 0 AND closureTime <= NOW() AND reopenTime >= NOW() ");
    if($closures) {
        foreach ($closures as $c) {
            $restaurants = array();
            foreach (json_decode($c->restaurantID) as $id => $r) {
                $restaurants[] = $r;
            }
            $restClose['data'][] = array(
                "restaurants" => implode(", ", $restaurants),
                "closed" => date("m/d/Y g:i a", strtotime($c->closureTime)),
                "reopen" => date("m/d/Y g:i a", strtotime($c->reopenTime))
            );
        }
    }
    header('Content-Type: application/json');
    echo json_encode($restClose);
    wp_die();
}

function closureList(){

    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce( $_REQUEST['nonce'], "get_closure_list_nonce")) {
        wp_die();
    }
    global $wpdb;
    $restClose=array("data" => array());

    $closures = $wpdb->get_results("SELECT * FROM pbc_pbr_closures ppc WHERE isDeleted = 0 AND closureTime >= NOW() ");
    if($closures) {
        foreach ($closures as $c) {
            $restaurants = array();
            foreach (json_decode($c->restaurantID) as $id =>$r){$restaurants[]=$r;}
            $restClose['data'][] = array(
                "restaurants"=>implode(", ", $restaurants),
                "closed" => date("m/d/Y g:i a", strtotime($c->closureTime)),
                "reopen" => date("m/d/Y g:i a", strtotime($c->reopenTime)),
                "actions" =>
                '<button type="button" class="btn outline-warning edit_button" id="edit-' . $c->id . '" data-toggle="modal" data-target=".bd-example-modal-lg"
                            data-nonce="' . wp_create_nonce("add_closure_nonce_".$c->id) . '"
                            data-closureid="' . $c->id . '"
                            data-title="Update Closure"
                            data-starttime="' . date("g:i a", strtotime($c->closureTime)) . '"
                            data-endtime="' . date("g:i a", strtotime($c->reopenTime)) . '"
                            data-startdate="' . date("m/d/Y", strtotime($c->closureTime)) . '"
                            data-enddate="' . date("m/d/Y", strtotime($c->reopenTime)) . '"
                            data-restaurants="' . implode(",", array_keys(json_decode($c->restaurantID, true))) . '"
                    >
<i class="far fa-edit"
                            data-nonce="' . wp_create_nonce("add_closure_nonce_".$c->id) . '"
                            data-closureid="' . $c->id . '"
                            data-title="Update Closure"
                            data-starttime="' . date("g:i a", strtotime($c->closureTime)) . '"
                            data-endtime="' . date("g:i a", strtotime($c->reopenTime)) . '"
                            data-startdate="' . date("m/d/Y", strtotime($c->closureTime)) . '"
                            data-enddate="' . date("m/d/Y", strtotime($c->reopenTime)) . '"
                            data-restaurants="' . implode(",", array_keys(json_decode($c->restaurantID, true))) . '"
></i>
                    </button>
                    <button type="button" class="btn outline-danger delete_schedule" id="delete-' . $c->id . '" data-closureID="' . $c->id . '" data-nonce="' . wp_create_nonce("remove_closure_nonce_".$c->id) . '" ><i class="far fa-trash-alt" data-closureID="' . $c->id . '" data-nonce="' . wp_create_nonce("remove_closure_nonce_".$c->id) . '" ></i></button>
                '
            );
        }
    }
    header('Content-Type: application/json');
    echo json_encode($restClose);
    wp_die();
}

function deleteClosure(){
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce( $_REQUEST['nonce'], "remove_closure_nonce")) {
        echo 1;
        wp_die();
    }
    global $wpdb;

    if($taskID = $wpdb->get_var("SELECT taskID FROM pbc_pbr_closures WHERE id = '" . $_REQUEST['closureID'] ."' ")){
        $taskIDs = explode("::",$taskID);
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $tasks = new task_engine($mysqli);
        $tasks->delete_task($taskIDs[0]);
        $tasks->delete_task($taskIDs[1]);
        $wpdb->update('pbc_pbr_closures',array('isDeleted' => '1'),array( 'id' => $_REQUEST['closureID'] ),array('%s'),array( '%s' ));
        if(!empty($wpdb->last_error)){echo $wpdb->last_error;}
    }else{
        echo "Record Not Found";
    }
    wp_die();
}

function addClosure(){
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce( $_REQUEST['nonce'], "add_closure_nonce")) {
        echo 1;
        wp_die();
    }
    global $wpdb;
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $tasks = new task_engine($mysqli);
    $r = array();
    foreach($_REQUEST['restaurants'] as $restaurant){
        $r[$restaurant] = $wpdb->get_var("SELECT restaurantName FROM pbc_pbrestaurants WHERE levelUpID = '" . $restaurant ."' ");
    }
    $r = json_encode($r);

    $startDate = date("Y-m-d", strtotime($_REQUEST["startDate"]));
    $startTime = date("H:i:s", strtotime($_REQUEST["startTime"]));
    $endDate = date("Y-m-d", strtotime($_REQUEST["endDate"]));
    $endTime = date("H:i:s", strtotime($_REQUEST["endTime"]));

    $close = $startDate . " " . $startTime;
    $reopen =  $endDate . " " . $endTime;
    if($_REQUEST['closureID'] === "new") {
        $startTask = $tasks->add_task(['what' => 'execBackground',
            'target' => "/home/jewmanfoo/levelup-website-bot/change.sh ",
            'files' => json_encode(['restaurants'=> $_REQUEST['restaurants'], 'action' => 'false']),
            'dueDate' => $close]);

        $endTask = $tasks->add_task(['what' => 'execBackground',
            'target' => "/home/jewmanfoo/levelup-website-bot/change.sh ",
            'files' => json_encode(['restaurants'=> $_REQUEST['restaurants'], 'action' => 'true']),
            'dueDate' => $reopen]);
        if(empty($startTask)){
            echo "Failed to create Start Task";
            wp_die();
        }
        if(empty($endTask)){
            echo "Failed to create End Task";
            wp_die();
        }
        $tasks = $startTask . "::" . $endTask;
        $wpdb->query(
            $wpdb->prepare("INSERT INTO pbc_pbr_closures (restaurantID, closureTime, reopenTime, taskID)VALUES (%s, %s, %s, %s)",
                $r, $close, $reopen,$tasks
            ));
        if($error = $wpdb->last_error){
            $tasks->delete_task($startTask);
            $tasks->delete_task($endTask);
            echo $error;
        }else{
            echo "1";
        }
    }else{
        if($taskID = $wpdb->get_var("SELECT taskID FROM pbc_pbr_closures WHERE id = '" . $_REQUEST['closureID'] ."' ")){
            $wpdb->update('pbc_pbr_closures',
                array('restaurantID' => $r,'closureTime' => $close,'reopenTime' =>$reopen),
                array( 'id' => $_REQUEST['closureID'] ),
                array('%s'),
                array( '%s' ));
            $taskIDs = explode("::",$taskID);
            $tasks->update_task ($taskIDs[0], array('files' => json_encode(['restaurants'=> $_REQUEST['restaurants'], 'action' => 'false']),'dueDate' => $close));
            $tasks->update_task ($taskIDs[1], array('files' => json_encode(['restaurants'=> $_REQUEST['restaurants'], 'action' => 'true']),'dueDate' => $reopen));
            echo "1";
        }else{
            echo "Record Not Found";
        }
    }
    wp_die();
}