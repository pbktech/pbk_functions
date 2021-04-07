<?php
global $wp;
global $wpdb;
global $ret;
$cu = wp_get_current_user();
$page = home_url(add_query_arg(array(), $wp->request));
wp_localize_script('closure-script', 'closure_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
$query = "SELECT levelUpID,restaurantName FROM pbc2.pbc_pbrestaurants WHERE levelUpID is not null AND isOpen =1";
$records = $wpdb->get_results($query);
$restaurants = '';
if (!empty($records)) {
    foreach ($records as $rec) {
        $restaurants .= "\n<option value='" . $rec->levelUpID . "'>" . $rec->restaurantName . "</option>";
    }
}
?>
<!-- Modal -->
<div class="modal fade bd-example-modal-lg" id="exampleModalLong" tabindex="-1" role="dialog"
     aria-labelledby="exampleModalLongTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">
                    <div id="title"></div>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class='form-group'>
                    <form method='post' action='<?php echo $page; ?>' name="modalForm">
                        <input type='hidden' name='closureid' id="closureid" value=''/>
                        <div class='form-group'>
                            <div class='row'>
                                <div class='col'>
                                    <strong>Closure Begins</strong>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col'>
                                    <div class='form-group'>
                                        <label for='startDate'>Date</label>
                                        <input class='form-control datePicker' type='text' id='startdate'
                                               name='startdate' value=""/>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class='form-group'>
                                        <label for='startTime'>Time</label>
                                        <input class='form-control timepicker' type='text' id='starttime'
                                               name='startTime' style='width: 100px;' value=""/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='form-group'>
                            <div class='row'>
                                <div class='col'>
                                    <strong>Closure Ends</strong>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col'>
                                    <div class='form-group'>
                                        <label for='endDate'>Date</label>
                                        <input class='form-control datePicker' type='text' id='enddate' name='enddate'
                                               value=""/>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class='form-group'>
                                        <label for='endTime'>Time</label>
                                        <input class='form-control timepicker' type='text' id='endtime' name='endtime'
                                               style='width: 100px;' value=""/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='form-group'>
                            <div class='row'>
                                <div class='col'>
                                    <label for='restaurantPicker'>Restaurants</label>
                                    <div style='width:100%;'>
                                        <select style='width:100%;' class="custom-select multipleSelect"
                                                id="restaurantPicker" name="change[restaurants][]" multiple="multiple">
                                            <?php echo $restaurants; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-center" id='spinner' style='display: none;'>
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="saveButtons" style="display: block">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save">Save changes</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Button trigger modal -->
<div class="container">
    <button type="button" class="btn btn-primary new_button" id="AddNewClosureButton" data-toggle="modal"
            data-target=".bd-example-modal-lg"
            data-title="Add New Closure" data-starttime="" data-endtime="" data-restaurants="" data-closureid="new">
        Add New Closure
    </button>
</div>
<div class="container-fluid" style="padding-top: 1em;">
    <h4 id='uc'>Upcoming Changes</h4>
    <table id="myTable" class="table table-striped table-hover table-bordered" style="width:100%;">
        <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
        <tr>
            <th style="max-width: 45%;">Restaurants</th>
            <th>Close</th>
            <th>Re-Open</th>
            <th>Actions</th>
        </tr>
        </thead>
    </table>
</div>
<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample"
        aria-expanded="false" aria-controls="collapseExample">
    View Restaurants Temporarily Closed
</button>
<div class="container-fluid" style="padding-top: 1em;">
    <div class="collapse" id="collapseExample">
        <h4 id='uc'>Temporarily Closed Restaurants</h4>
        <table id="closedTable" class="table table-striped table-hover table-bordered" style="width:100%;">
            <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
            <tr>
                <th style="max-width: 45%;">Restaurants</th>
                <th>Close</th>
                <th>Re-Open</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

<?php
add_action('wp_footer', 'restaurantClosuresAJAX');

function restaurantClosuresAJAX() { ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        $('#restaurantPicker').select2({
          placeholder: 'Select some restaurants',
          allowClear: true
        });
        $('.datePicker').datepicker({
          dateFormat: 'MM d, yy'
        });
        $('.timepicker').timepicker({
          'timeFormat': 'h:mm p',
          interval: 15,
          minTime: '5:00 am',
          maxTime: '9:00 pm',
          dynamic: false,
          dropdown: true,
          scrollbar: true
        });
        var myTable = $('#myTable').DataTable({
          lengthMenu: [[25, 50, -1], [25, 50, 'All']],
          ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=get_closure_list&nonce=<?php echo wp_create_nonce("get_closure_list_nonce");?>',
          dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
          columns: [
            { data: 'restaurants' },
            { data: 'closed' },
            { data: 'reopen' },
            { data: 'actions', 'searchable': false, orderable: false }
          ],
          buttons: ['print', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
        });
        var closedTable = $('#closedTable').DataTable({
          lengthMenu: [[25, 50, -1], [25, 50, 'All']],
          ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=get_current_closure_list&nonce=<?php echo wp_create_nonce("get_current_closure_list");?>',
          dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
          columns: [
            { data: 'restaurants' },
            { data: 'closed' },
            { data: 'reopen' }
            ],
          buttons: ['print', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
        });
        $('#exampleModalLong').on('hidden.bs.modal', function(e) {
          $('#restaurantPicker').val(null).trigger('change');
          $('#startdate').val('');
          $('#starttime').val('');
          $('#enddate').val('');
          $('#endtime').val('');
          $('#closureid').val('');
        });
        $('#myTable tbody').on('click', '.edit_button', function(event) {
          $('#title').html(event.target.dataset.title);
          $('#startdate').val(event.target.dataset.startdate);
          $('#starttime').val(event.target.dataset.starttime);
          $('#enddate').val(event.target.dataset.enddate);
          $('#endtime').val(event.target.dataset.endtime);
          $('#closureid').val(event.target.dataset.closureid);
          $('#restaurantPicker').val(event.target.dataset.restaurants.split(','));
          $('#restaurantPicker').trigger('change');
        });
        $('#myTable tbody').on('click', '.delete_schedule', function(event) {
          if (confirm('Delete this closure?')) {
            $.ajax({
              url: '<?php echo admin_url('admin-ajax.php');?>',
              type: 'post',
              data: {
                action: 'remove_closure',
                closureID: event.target.dataset.closureid,
                nonce: '<?php echo wp_create_nonce("remove_closure_nonce");?>'
              },
              success: function(response) {
                myTable.ajax.reload();
              }
            });
          }
        });
        $('.new_button').on('click', function(e) {
          $('#title').html(e.target.dataset.title);
          $('#closureid').val('new');
        });
        $('#save').on('click', function(e) {
          $('#saveButtons').hide();
          $('#spinner').show();
          $.ajax({
            url: '<?php echo admin_url('admin-ajax.php');?>',
            type: 'post',
            data: {
              action: 'add_new_closure',
              startDate: $('#startdate').val(),
              startTime: $('#starttime').val(),
              endDate: $('#enddate').val(),
              endTime: $('#endtime').val(),
              restaurants: $('#restaurantPicker').val(),
              closureID: $('#closureid').val(),
              nonce: '<?php echo wp_create_nonce("add_closure_nonce");?>'
            },
            success: function(response) {
              if (response === '1') {
                $('#saveButtons').show();
                $('#spinner').hide();
                myTable.ajax.reload();
                $('#exampleModalLong').modal('hide');
              } else {
                $('#saveButtons').show();
                $('#spinner').hide();
              }
            }
          });
        });
      });
    </script>
    <?php
}

?>
