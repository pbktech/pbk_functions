<?php
global $wpdb;
$items = array();
$supportItems = $wpdb->get_results("SELECT itemID, itemName FROM pbc_support_items WHERE isActive = 1 order by itemName");
if($supportItems){
    $items[] = array("id" => -1, "text" => "Select an item to begin your report");
    foreach ($supportItems as $item){
        $allIssues = [];
        $commonIssues = $wpdb->get_results("SELECT * FROM pbc_support_common WHERE itemID = " . $item->itemID);
        if ($commonIssues) {
            foreach ($commonIssues as $i) {
                $faqSteps = $wpdb->get_results("SELECT * FROM pbc_support_trouble_steps psts, pbc_support_trouble_assign psta WHERE psta.issueID = " . $i->issueID . " AND psta.stepID = psts.stepID ORDER BY psta.stepOrder");
                $allIssues[] = ["ci" => $i, "steps" => $faqSteps];
            }
        }
        $items[] = array("id" => $item->itemID, "text" => $item->itemName, "commonIssues" => $allIssues);
    }
}
?>
<script>
  function clearInputs(){

  }
  const allIssues =  <?php echo json_encode($items);?>;
  $(document).ready(function() {
    $('.js-example-basic-single').select2({
      placeholder: {
        id: '-1', // the value of the option
        text: 'Select an item to begin your report'
      },
      allowClear: true,
      data: allIssues,
    });
    $('#issueSelector').on('select2:select', function (e) {
      const data = e.params.data;
      const issues = allIssues.filter(issue => issue.id === data.id);

      console.log(issues);
      let workArea = "";
      for (let i = 0; i < issues[0].commonIssues.length; i++) {
        workArea = workArea + '<div class="row"><button type="button" class="btn btn-link btn-lg btn-block" data-issueid="' + i + '" >' + issues[0].commonIssues[i].ci.issueTitle + '</button></div>';
      }
      $('#workArea').html(workArea);
      $('.modal-header').html("<h3>" + data.text + "</h3>");

      $('#issueModal').modal('show');
    });
    $('#issueModal').on('hidden.bs.modal', function () {
      $('#issueSelector').val(null).trigger('change');
      $('.modal-header').html("");
      $('.modal-body').html("");
    })
  });
</script>
<h2>Report A New Issue</h2>
<div class="container-fluid">
    <select class="js-example-basic-single form-control" name="issue" id="issueSelector" style="width: 100%;">
    </select>
</div>
    <div class="modal" tabindex="-1" id="issueModal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid" id="workArea">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
<?php
