<?php
global $wp;
global $wpdb;
global $ret;
$cu = wp_get_current_user();
$page = home_url(add_query_arg(array(), $wp->request));
wp_localize_script( 'closure-script', 'closure_ajax', array( 'ajax_url' => admin_url('admin-ajax.php')) );
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
    function closureList(){
      jQuery.ajax({
        url : closure_ajax.ajax_url,
        type : 'post',
        data : {
          action : 'closureList'
        },
        success : function( response ) {
          jQuery('.rml_contents').html(response);
        }
      });
    }

  jQuery(document).ready(function() {
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
      jQuery('input.timePicker').timepicker({
        timeFormat: 'h:mm p',
        interval: 15,
        minTime: '5:00am',
        maxTime: '10:00pm',
        dynamic: false,
        dropdown: true,
        scrollbar: true
      });
      jQuery('#startTime').timepicker('setTime', elem.data("startTime"));
      jQuery('#endTime').timepicker('setTime', elem.data("endTime"));
      jQuery('input.change-format').click(function() {
        var input = jQuery(this),
          timepicker = input.closest('div').find('.timePicker'),
          instance = timepicker.timepicker();
        instance.option('timeFormat', jQuery(this).data('format'));
      });
      if(restaurants){
        jQuery('#restaurantPicker').val([restaurants]);
        jQuery("#restaurantPicker").trigger("change");
      }
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
                        <input type='hidden' name='action' value='<?php echo $page; ?>'/>
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
                                        <input class='form-control datePicker' type='text' id='startDate'
                                               name='startDate' value=""/>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class='form-group'>
                                        <label for='startTime'>Time</label>
                                        <input class='form-control timePicker' type='text' id='startTime'
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
                                        <input class='form-control datePicker' type='text' id='endDate' name='endDate'
                                               value=""/>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class='form-group'>
                                        <label for='endTime'>Time</label>
                                        <input class='form-control timePicker' type='text' id='endTime' name='endTime'
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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save">Save changes</button>
            </div>
        </div>
    </div>
</div>
<!-- Button trigger modal -->
<div class="container">
    <button type="button" class="btn btn-primary itemName" id="NEW" data-toggle="modal" data-target=".bd-example-modal-lg"
            data-title="Add New Closure" data-startTime="now" data-endTime="end" data-restaurants=""
    >
        Add New Closure
    </button>
</div>
<div class="container-fluid" style="padding-top: 1em;">
    <table class="table table-striped">
        <thead class="thead-dark">
            <tr>
                <th scope="col">Close</th>
                <th scope="col">Reopen</th>
                <th scope="col" colspan="2">Restaurants</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if($restClose){
            foreach($restClose as $c){
        ?>
            <tr>
                <td><?php echo date("m/d/Y @ g:i a",strtotime($c['closureTime']));?></td>
                <td><?php echo date("m/d/Y @ g:i a",strtotime($c['reopenTime']));?></td>
                <td><?php echo implode(",",$c['RestaurantNames']);?></td>
                <td>
                    <button type="button" class="btn outline-warning itemName" data-toggle="modal" data-target=".bd-example-modal-lg"
                            id="<?php echo $c['id'];?>"
                            data-title="Update Closure"
                            data-startTime="<?php echo date("g:i a",strtotime($c['closureTime']));?>"
                            data-endTime="<?php echo date("g:i a",strtotime($c['reopenTime']));?>"
                            data-restaurants="<?php echo implode(",",$c['RestaurantNameIDs']);?>"
                    >Edit</button>
                    <button type="button" class="btn outline-danger" >Delete</button>
                </td>
            </tr>
        <?php
            }
        }
        ?>
        </tbody>
    </table>
</div>

