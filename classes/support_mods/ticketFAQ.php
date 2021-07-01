<?php
global $wpdb;
$allIssues = [];
$commonIssues = $wpdb->get_results("SELECT * FROM pbc_support_common WHERE itemID = " . $_REQUEST['itemID']);
if ($commonIssues) {
    foreach ($commonIssues as $i) {
        $faqSteps = $wpdb->get_results("SELECT * FROM pbc_support_trouble_steps psts, pbc_support_trouble_assign psta WHERE psta.issueID = " . $i->issueID . " AND psta.stepID = psts.stepID ORDER BY psta.stepOrder");
        $allIssues[] = (object)["ci" => $i, "steps" => $faqSteps];
    }
}
$count = 0;
if (count($allIssues) > 0) {
    ?>
    <script>
      const issueList = <?php echo json_encode($allIssues);?>;
      $(document).ready(function() {
        $('.btn-block').click(function(e) {
//          const issueItem = e.target.dataset('issueid');
          console.log(e.target);
        });
      });
    </script>
    <div class="container-fluid">
        <?php
        foreach ($allIssues as $i) {
            ?>
            <div class="row" style="padding: .5em;">
                <button type="button" class="btn btn-link btn-lg btn-block"
                        data-issueid="<?php echo $count; ?>"><?php echo $i->ci->issueTitle; ?></button>
            </div>
            <?php
            $count++;
        }
        ?>
    </div>
    <?php
} else {
    include "ticketEditor.php";
}