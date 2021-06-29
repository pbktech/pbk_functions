<?php
global $wpdb;
$items = array();
$supportItems = $wpdb->get_results("SELECT itemID, itemName FROM pbc_support_items WHERE isActive = 1");
if($supportItems){
    $items[] = array("id" => -1, "text" => "Select an item to begin your report");
    foreach ($supportItems as $i){
        $items[] = array("id" => $i->itemID, "text" => $i->itemName);
    }
}
?>
<script>
  function clearInputs(){

  }
  $(document).ready(function() {
    $('.js-example-basic-single').select2({
      placeholder: {
        id: '-1', // the value of the option
        text: 'Select an item to begin your report'
      },
      allowClear: true,
      data: <?php echo json_encode($items);?>
    });
    $('#issueSelector').on('select2:select', function (e) {
      const data = e.params.data;
      $('.modal-header').html( '<h3>' + data.text + '</h3>');
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php');?>',
        type: 'POST',
        data: {
          action: 'getSupportMod',
          itemID: data.id,
          f: 'ticketFAQ',
        },
        success: function(r) {
          $('.modal-body').html(r);
        }
      });
      $('#issueModal').modal('show');
    });
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

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
<?php
