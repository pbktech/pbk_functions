<div class='wrap'>
  <h2>PBK Health Screen Archive</h2>
  <div id='ServerResponse'></div>
<?php
global $wpdb;
echo "";
$restaurant = new Restaurant();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
}
if (isset($_GET['id'])) {
    $result=$wpdb->get_row("SELECT * FROM pbc_pbk_orders,pbc_pbrestaurants WHERE pbc_pbk_orders.restaurantID = pbc_pbrestaurants.restaurantID AND pbc_pbk_orders.guid='".$_GET['id']."'");
    if ($result) {
        $info=json_decode($result->orderData);
        $lang=$info->language;
        $one=1;
        $two=2;
        $three=3; ?>
    <div class='container-fluid' id='queryResults'>
    <div class="alert alert-info" role="alert">This form was entered in <?php echo $info->language; ?></div>
      <div class='row' style='background-color:#f9b58f;color:#000000;'>
        <div class='col'><label>&nbsp;</label><br><strong><?php echo $info->name; ?></strong></div>
        <div class='col'><label>Temp 1</label><br><strong><?php echo $info->Temp1; ?></strong></div>
        <div class='col'><label>Temp 2</label><br><strong><?php echo $info->Temp2; ?></strong></div>
      </div>
      <div class='row' style='background-color:#e7e6e6;color:#000000;'>
        <div class='col'><?php echo html_entity_decode($info->Questions->$one->$lang); ?></div>
        <div class='col'><strong><?php echo $info->question->$one; ?></strong></div>
      </div>
      <div class='row' style='background-color:#e7e6e6;color:#000000;'>
        <div class='col'><?php echo html_entity_decode($info->Questions->$two->$lang); ?></div>
        <div class='col'><strong><?php echo $info->question->$two; ?></strong></div>
      </div>
      <div class='row' style='background-color:#e7e6e6;color:#000000;'>
        <div class='col'><?php echo html_entity_decode($info->Questions->$three->$lang); ?></div>
        <div class='col'><strong><?php echo $info->question->$three; ?></strong></div>
      </div>
    </div>
<?php
    } else {
        echo "<div class='alert alert-warning'>Health Screen not Found</div>";
    }
} else {
    ?>
  <h3>Please Select Dates:</h3>
  <div class='container'>
    <form  method='get' action='<?php echo admin_url('admin.php'); ?>'>
    <input type='hidden' name='page' value='pbr-hs-archive' />
    <div class="form-group">
      <div class='row'>
        <div class='col'>
          <?php echo $restaurant->buildDateSelector('startDate', "Starting Date"); ?>
        </div>
        <div class='col'>
          <?php echo $restaurant->buildDateSelector('endDate', "Ending Date"); ?>
        </div>
      </div>
    </div>
    <div class=\"form-group\">
      <input id='submit' type='submit' value='SEARCH' />
    </div>
    </form>
  </div>
<?php
  if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
      $result=$wpdb->get_results("SELECT pbc_pbk_orders.guid as 'guid',restaurantName,orderDate,orderData,json_unquote(JSON_EXTRACT(orderData ,'$.name')) as 'employeeName',pbc_pbk_orders.guid as 'id' FROM pbc_pbk_orders,pbc_pbrestaurants WHERE pbc_pbk_orders.restaurantID = pbc_pbrestaurants.restaurantID AND orderDate BETWEEN '".date("Y-m-d", strtotime($_GET['startDate']))." 00:00:00' AND '".date("Y-m-d", strtotime($_GET['endDate']))." 23:59:59' ");
      if ($result) {
          $d=array();
          foreach ($result as $key) {
              $d['Results'][]=array(
          "<a href='" . admin_url("admin.php?page=pbr-hs-archive&id=".$key->id)."' target='_blank'>" . $key->employeeName . "</a>",
          $key->restaurantName,
          date("m/d/Y", strtotime($key->orderDate)),
          "<button type=\"button\" class=\"btn btn-primary\" data-toggle=\"modal\" data-target=\"#hsModal\" data-guid='".$key->guid."' data-restaurant='".$key->restaurantName."' data-date='".date("m/d/Y g:i:s a", strtotime($key->orderDate))."' data-whatever='".$key->orderData."'>View</button>"
        );
          }
          $d['Options'][]="\"order\": [ 0, 'asc' ]";
          $d['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
          $d['File']="PBK_Health_Screens_";
          $d['Headers']=array("Name","Restaurant","Date","");
          $report= new ToastReport;
          echo $report->showResultsTable($d); ?>
<div class="modal fade" id="hsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="exampleModalLabel"><span id="modal-title"></span><br><span id="subHead"></span></h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
    <div class='container-fluid'>
    <div class="alert alert-info" role="alert"><span id="language"></span></div>
      <div class='row' style='background-color:#f9b58f;color:#000000;padding-top:.5em;padding-bottom:.5em;'>
        <div class='col'><label>Temp 1</label><br><strong><span id="temp1"></span></strong></div>
        <div class='col'><label>Temp 2</label><br><strong><span id="temp2"></span></strong></div>
      </div>
      <div class="row"><div class="col"> <hr style="width:50%"> </div></div>
      <div class='row' style='background-color:#e7e6e6;color:#000000;padding-top:.5em;padding-bottom:.5em;'>
        <div class='col'><span id="question1"></span></div>
        <div class='col'><strong><span id="answer1"></span></strong></div>
      </div>
      <div class="row"><div class="col"> <hr style="width:50%"> </div></div>
      <div class='row' style='background-color:#e7e6e6;color:#000000;padding-top:.5em;padding-bottom:.5em;'>
        <div class='col'><span id="question2"></span></div>
        <div class='col'><strong><span id="answer2"></span></strong></div>
      </div>
      <div class="row"><div class="col"> <hr style="width:50%"> </div></div>
      <div class='row' style='background-color:#e7e6e6;color:#000000;padding-top:.5em;padding-bottom:.5em;'>
        <div class='col'><span id="question3"></span></div>
        <div class='col'><strong><span id="answer3"></span></strong></div>
      </div>
    </div>
    </div>
    <div class="modal-footer">
      <form class="hs_send_form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <input type="hidden" name="action" value="hs_send" id="" />
        <input type="hidden" name="guids[]" value="" id="guid" />
      <button type="button" class="btn btn-primary" id="send">Send</button>
      </form>
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
  </div>
</div>
</div>
<script>
jQuery(document).ready(function($) {
  $('.hs_send_form').on('submit', function(e) {
    e.preventDefault();
    var $form = $(this);
    console.log($form.serialize());
    jQuery.post($form.attr('action'), $form.serialize(), function(response) {
      jQuery("#ServerResponse").html(response);
    });
  });
});
jQuery('#hsModal').on('show.bs.modal', function (event) {
  var button = jQuery(event.relatedTarget);
  var obj = button.data('whatever');
  var restaurant = button.data('restaurant');
  var dateOftest = button.data('date');
  var modal = jQuery(this);
  var lang = obj.language;
  console.log(obj.Questions);
  var questions = obj.Questions;
  var answers = obj.question;
  modal.find('#modal-title').text('Health Screen for ' + obj.name);
  modal.find('#subHead').text('at ' + restaurant + ' on ' + dateOftest);
  modal.find('#language').text('This form was entered in ' + lang);
  modal.find('#temp1').text(obj.Temp1 + "\xB0");
  modal.find('#temp2').text(obj.Temp2 + "\xB0");
  modal.find('#guid').val(obj.guid);
  jQuery.each( questions, function( key, value ) {
    if(lang=="English"){modal.find('#question'+ key).text(value.English);}
    if(lang=="Spanish"){modal.find('#question'+ key).text(value.Spanish);}
  });
  jQuery.each( answers, function( key, value ) {
    modal.find('#answer'+ key).text(value);
  });
});
</script>
<?php
      } else {
          echo "<div class='alert alert-warning'>No Health Screens found for the dates selected ".date("m/d/Y", strtotime($_GET['startDate']))." - ".date("m/d/Y", strtotime($_GET['endDate'])).".</div>";
      }
  }
}
echo "</div>";
