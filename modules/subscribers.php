<?php
global $wpdb;
global $wp;
$toast = new ToastReport();
$page = home_url( add_query_arg( array(), $wp->request ) );
if(isset($_REQUEST['cancel']) &&
    $_REQUEST['cancel'] === "1"){
    $wpdb->update(
        'pbc_subscriptions',
        array(
            'isActive' => 0    // integer (number)
        ),
        array( 'userID' => $_REQUEST['userID'] ),
        array(
            '%d'
        ),
        array( '%d' )
    );
    if(empty($wpdb->last_error)){
        echo '<div class="alert alert-warning">The subscription has been canceled.</div>';
    }else{
        echo '<div class="alert alert-danger">The following error occured.<br>' . $wpdb->last_error . '</div>';
    }
}
if(isset($_REQUEST['active']) &&
    $_REQUEST['active'] === "0"){
    $isActive = 0;
    ?>
    <div>
        <form method="get" action="<?php echo $page;?>">
            <button class="btn btn-brand" type="submit">View Users</button>
        </form>
    </div>
    <?php
}else{
    $isActive = 1;
?>
    <div>
        <form method="get" action="<?php echo $page;?>">
            <input type="hidden" name="active" value="0" />
            <button class="btn btn-brand" type="submit">View Inactive Users</button>
        </form>
    </div>
<?php
}
$result = $wpdb->get_results("SELECT guestName,phoneNumber,emailAddress,planName,DATE_FORMAT(dateStarted, '%c/%d/%Y') as 'signedUp', userID FROM pbc_subscriptions ps, pbc_subscriptions_plans psp WHERE isActive = " . $isActive . " AND firstData is not null AND ps.subPlan = psp.planID ");
if($result){
    $D['Options'][]="\"order\": [ 1, 'asc' ]";
    $D['Options'][]="\"lengthMenu\": [ [10, 20, -1], [10, 20, \"All\"] ]";
    $D['Headers']=array("Name", "Phone Number", "Email", "Plan Name", "Signed Up","");
    foreach ($result as $r) {
        $D['Results'][]=array(
            $r->guestName,
            $r->phoneNumber,
            $r->emailAddress,
            $r->planName,
            $r->signedUp,
            '<form method="post" action="' . $page . '">
        <input type="hidden" name="cancel" value="1" />
        <input type="hidden" name="userID" value="'.$r->userID.'" />
        <button class="brand" type="submit" class="btn btn-outline-danger">Cancel</button>
    </form>
'
        );
    }
    echo $toast->showResultsTable($D);

}else{
    ?>
    <div class="alert alert-warning" role="alert">
        There were no subscribers found.
    </div>
<?php
}
?>
