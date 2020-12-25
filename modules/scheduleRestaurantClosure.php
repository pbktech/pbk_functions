<?php
global $wp;
global $wpdb;
global $ret;
$cu = wp_get_current_user();
$page = home_url(add_query_arg(array(), $wp->request));
wp_localize_script('closure-script', 'closure_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
$query = "SELECT restaurantID,restaurantName FROM pbc2.pbc_pbrestaurants WHERE levelUpID is not null AND isOpen =1";
$records = $wpdb->get_results($query);
$restaurants = '';
if (!empty($records)) {
    foreach ($records as $rec) {
        $restaurants .= "\n<option value='" . $rec->restaurantID . "'>" . $rec->restaurantName . "</option>";
    }
}
?>
<script>
  function closureList() {
    jQuery.ajax({
      url: '<?php echo admin_url('admin-ajax.php');?>',
      type: 'get',
      data: {
        action: 'get_closure_list',
        nonce: '<?php echo wp_create_nonce("get_closure_list_nonce");?>'
      },
      success: function(response) {
        console.log(response.data);

        jQuery('#example').DataTable({
          'data': response.data,
          fixedHeader: true,
          'deferRender': true,
          'processing': true,
          columns: [
            { "visible": false, "targets": 0 },
            { title: 'Close' },
            { title: 'Re-Open' },
            { title: 'Restaurants' },
            { title: 'Actions',"searchable": false, "orderable": false }
          ]
        });
        jQuery('.datePicker').datepicker({
          dateFormat: 'MM d, yy'
        });

        jQuery('#restaurantPicker').select2({
          allowClear: true,
          theme: 'classic'
        });
        jQuery(".itemName").on("click", function(e) {
          var elem = jQuery("#" + e.target.id),
            restaurants = elem.data("restaurants"),
            modalTitle = elem.data("title");
          console.log(elem)
          jQuery( "#title" ).replaceWith( modalTitle );
          jQuery('#starttime').val(elem.data("starttime"));
          jQuery('#endtime').val(elem.data("endtime"));
          jQuery('#startdate').val(elem.data("startdate"));
          jQuery('#enddate').val(elem.data("enddate"));
          jQuery('#closureid').val(elem.data("closureid"));
          if(restaurants){
            jQuery('#restaurantPicker').val([restaurants]);
            jQuery("#restaurantPicker").trigger("change");
          }
        });
        jQuery('.deleteSchedule').on('click', function(e) {
          var elem = jQuery('#' + e.target.id);
          alert(elem.data('closureid'));
        });

      }
    });
  }

  jQuery(document).ready(function() {
    closureList();
    jQuery('#save').on('click', function(e) {
      jQuery('#saveButtons').hide();
      jQuery('#spinner').show();
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php');?>',
        type: 'post',
        data: {
          action: 'add_new_closure',
          startDate: jQuery('#startdate').val(),
          startTime: jQuery('#starttime').val(),
          endDate: jQuery('#enddate').val(),
          endTime: jQuery('#endtime').val(),
          restaurants: jQuery('#restaurantpicker').val(),
          closureID: jQuery('#closureid').val(),
          nonce: '<?php echo wp_create_nonce("add_closure_nonce");?>'
        },
        success: function(response) {
          if (response === '1') {
            closureList();
            jQuery('#saveButtons').show();
            jQuery('#spinner').hide();
            jQuery('#exampleModalLong').modal('hide');
          } else {
            alert(response);
            jQuery('#saveButtons').show();
            jQuery('#spinner').hide();
          }
        }
      });
    });
  });
</script>
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
                    <form method='post' action='<?php echo $page; ?>'>
                        <input type='hidden' name='closureID' id="closureID" value=''/>
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
                                        <input class='form-control timePicker' type='text' id='starttime'
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
                                        <input class='form-control timePicker' type='text' id='endtime' name='endtime'
                                               style='width: 100px;' value=""/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='form-group'>
                            <div class='row'>
                                <div class='col'>
                                    <label for=''>Restaurants</label>
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
                <div id="spinner" style="display: none; text-align: center;"><img
                            src='<?php echo WP_PLUGIN_URL; ?>/pbk_functions/assets/images/processing.gif'
                            style='height:92px;width:92px;'/></div>
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
    <button type="button" class="btn btn-primary itemName" id="AddNewClosureButton" data-toggle="modal"
            data-target=".bd-example-modal-lg"
            data-title="Add New Closure" data-starttime="" data-endtime="" data-restaurants="" data-closureid="new">
        Add New Closure
    </button>
</div>
<div class="container-fluid" style="padding-top: 1em;">
    <h4 id='uc'>Upcoming Changes</h4>
    <table id="example" class="table table-striped table-bordered" style="width:100%;"></table>
</div>
