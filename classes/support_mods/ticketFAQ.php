<script>

</script>
<?php
global $wpdb;
$commonIssues = $wpdb->get_results("SELECT * FROM pbc_support_common WHERE itemID = " . $_REQUEST['itemID']);
$count = 0;
if($commonIssues){
    foreach ($commonIssues as $i){
?>
    <div style="padding: .5em;"><button type="button" class="btn btn-link btn-lg btn-block" data-issueid="<?php echo $i->issueID;?>"><?php echo $i->issueTitle;?></button></div>
<?php
        $count++;
    }
}else{
    include "ticketStart.php";
}