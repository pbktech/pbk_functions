<?php
$mealServices=array("Breakfast","Lunch","Dinner");
if(isset($info['imageFile']) && $info['imageFile']!=""){
  $links=json_decode($info['imageFile'],true);
  $imageAdd="
      <strong>Current Image</strong><br><img src='".$links['image']."' alt='' />
  ";
}else {
  $imageAdd="";
  $links['image']="";
  $links['link']="";
}
if($info['idpbc_minibar']!="_NEW" && isset($links['image']) && $links['image']!=""){
  $sendTest="
  <a href='".admin_url( 'admin.php?page=pbr-edit-minibar&id='.$info['idpbc_minibar'] )."&testEmail=1' class=\"btn btn-secondary\">Send Test Email</a>
  ";
}
if(isset($_GET['testEmail']) && $_GET['testEmail']==1){
  $current_user = wp_get_current_user();
  $report=new ToastReport;
  $content="<div>
  <a href='".$links['link']."' target='_blank'>
  <img src='".$links['image']."' alt='Your Protein Bar MiniBar order has been delivered!' />
  </a>
  </div>'";
  $handle = @fopen($links['image'], 'r');
  if($handle){
    $report->reportEmail($current_user->user_email,$content,"IT'S ALL GOOD: Your meal has arrived!");
    echo switchpbrMessages(4);
  }else {
    echo switchpbrMessages(5);
  }
}
if(isset($links['day']) && count($links['day'])!=0){
  $preselect="jQuery('#LunchdeliveryDay').val(['" . implode("','", $links['day']) . "']).trigger('change');";
}else{
  $preselect="";
}
echo $this->pbk_addImageSelector();
if(isset($info['linkSlug']) && $info['linkSlug']=""){$links['link']=$info['linkSlug'];}
?>
<script>
jQuery(document).ready(function() {
  jQuery( "#tabs" ).tabs();
  jQuery('input.timepicker').timepicker({
    'timeFormat': 'h:mm p',
    interval: 30,
    minTime: '5:00 am',
    maxTime: '9:00 pm',
    dynamic: false,
    dropdown: true,
    scrollbar: true
  });
  jQuery('#BreakfastdeliveryDay').select2();
  jQuery('#LunchdeliveryDay').select2();
  jQuery('#DinnerdeliveryDay').select2();
});
</script>
<div class='container-fluid;'>
  <div id='tabs'>
  <form method="post" action="admin-post.php">
    <input type="hidden" name="action" value="pbk_save_minibar" />
    <input type="hidden" name="idpbc_minibar" value="<?php echo $info['idpbc_minibar'];?>" />
    <div class='row'>
      <div class='col' id="tabs">
        <ul class="nav nav-tabs">
          <li class="nav-item"><a href='#ids'>Base Information</a></li>
          <li class="nav-item"><a href='#demographics'>Location</a></li>
          <li class="nav-item"><a href='#hours'>Services</a></li>
        </ul>
        <div id="ids">
          <div class='row'>
            <div class='col'>
              <label for='restaurantID'><strong>Restaurant</strong></label><br />
              <?php echo $this->buildRestaurantSelector(0,'restaurantID',$info['restaurantID']);?>
            </div>
            <div class='col'>
              <label for='company'><strong>Company Name</strong></label>
              <input type='text' class='form-control' name ='company' value='<?php echo $info['company'];?>' />
            </div>
          </div>
          <div class='row'>
            <div class='col'>
              <label for='imageFile'><strong>Order Link</strong></label>
              <input type='text' class='form-control' name ='linkSlug' value='<?php echo $links['link'];?>' />
            </div>
            <div class='col'>
              <label for='outpostIdentifier'><strong>Toast Dining Option</strong></label>
              <input type='text' class='form-control' name ='outpostIdentifier' value='<?php echo $info['outpostIdentifier'];?>' />
            </div>
          </div>
          <div class='row'>
            <div class='col'>
              <label for='imageFile'><strong>Image</strong></label>
              <input type='text' class='form-control media-input' name ='imageFile[image]' value='<?php echo $links['image'];?>' /> <button class='media-button'>Select image</button>
            </div>
          </div>
        </div>
        <div id="demographics">
          <div class='row'>
            <div class='col'>
              <label for='lat'><strong>Latitute</strong></label><br />
              <input type='text' class='form-control' id='lat' name='imageFile[lat]' value='<?php echo $links['lat'];?>'/><br />
            </div>
            <div class='col'>
              <label for='long'><strong>Longitude</strong></label><br />
              <input type='text' class='form-control' id='long' name='imageFile[long]' value='<?php echo $links['long'];?>'/><br />
            </div>
          </div>
          <div class='row'>
            <div class='col'>
              <label for='address1'><strong>Address 1</strong></label><br />
              <input type='text' class='form-control' id='address1' name='imageFile[addressa]' value='<?php echo $links['addressa'];?>'/><br />
            </div>
            <div class='col'>
              <label for='address2'><strong>Address 2</strong></label><br />
              <input type='text' class='form-control' id='address2' name='imageFile[addressb]' value='<?php echo $links['addressb'];?>'/><br />
            </div>
          </div>
          <div class='row'>
            <div class='col'>
              <label for='City'><strong>City</strong></label><br />
              <input type='text' class='form-control' id='City' name='imageFile[city]' value='<?php echo $links['city'];?>'/><br />
            </div>
            <div class='col'>
              <label for='State'><strong>State</strong></label><br />
              <input type='text' class='form-control' id='State' name='imageFile[state]' value='<?php echo $links['state'];?>'/><br />
            </div>
            <div class='col'>
              <label for='Zip'><strong>Zip Code</strong></label><br />
              <input type='text' class='form-control' id='Zip' name='imageFile[zip]' value='<?php echo $links['zip'];?>'/><br />
            </div>
          </div>
        </div>
        <div id="hours">
          <?php
            foreach($mealServices as $s){
           ?>
          <div class='row'><div class='col'><h4><?php echo $s;?></h4></div></div>
          <div class='row'>
            <div class='col'>
              <label for='<?php echo $s;?>deliveryDay'><strong>Delivery Day</strong></label><br />
              <select name='services[<?php echo $s;?>][day][]' class="custom-select multipleSelect" id='<?php echo $s;?>deliveryDay' multiple>
                <option value='Sunday'>Sunday</option>
                <option value='Monday'>Monday</option>
                <option value='Tuesday'>Tuesday</option>
                <option value='Wednesday'>Wednesday</option>
                <option value='Thursday'>Thursday</option>
                <option value='Friday'>Friday</option>
                <option value='Saturday'>Saturday</option>
              </select>
            </div>
            <div class='col'>
              <label for='<?php echo $s;?>cutoff'><strong>Cutoff Time</strong></label><br />
              <input type='text' class='timepicker form-control' id='<?php echo $s;?>cutoff' name='services[<?php echo $s;?>][cutoff]' value='<?php echo $links['cutoff'];?>'/><br />
            </div>
            <div class='col'>
              <label for='<?php echo $s;?>delivery'><strong>Delivery Time</strong></label><br />
              <input type='text' class='timepicker form-control' id='<?php echo $s;?>delivery' name='services[<?php echo $s;?>][delivery]' value='<?php echo $links['delivery'];?>'/><br />
            </div>
          </div>
        <?php }?>
        </div>
      </div>
        <div class='col'>
        <?php echo $imageAdd;?>
        </div>
      </div>
      <div class='row' style='padding:15px;'>
        <div class='col'>
          <button type="submit" class="btn btn-primary"/>Submit</button>
          <button type="button" class='btn btn-warning' onclick="javascript:window.location='admin.php?page=pbr-edit-minibar';">Cancel</button>
        </div>
      </div>
    </form>
  <div><?php echo $sendTest;?></div>
</div>
