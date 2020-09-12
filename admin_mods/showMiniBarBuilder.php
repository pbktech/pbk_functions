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
$preselect="";
echo $this->pbk_addImageSelector();
if(isset($info['linkSlug']) && $info['linkSlug']!=""){$links['link']=$info['linkSlug'];}
if(isset($info['services']) && $info['services']!=""){
  $serviceInfo=json_decode($info['services'],true);
  foreach($serviceInfo as $sn=>$si){
    if(isset($si['day']) && count($si['day'])!=0){
      $preselect.="jQuery('#".$sn."deliveryDay').val(['" . implode("','", $si['day']) . "']).trigger('change');\n";
    }
    if(isset($si['menu']) && count($si['menu'])!=0){
      $preselect.="jQuery('#".$sn."menu').val(['" . implode("','", $si['menu']) . "']).trigger('change');\n";
    }
  }
}else {
  foreach($mealServices as $s){
    $serviceInfo[$s]=array("day"=>array(),"cutoff"=>"","delivery"=>"");
  }
}
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
  jQuery('#Breakfastmenu').select2();
  jQuery('#Lunchmenu').select2();
  jQuery('#Dinnermenu').select2();
  <?php echo $preselect;?>
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
              <h5><strong>Active</strong></h5>
              <?php
              foreach(array(0=>"No",1=>"Yes") as $n=>$a){
                if(isset($info['isActive']) && $info['isActive']==$n){$checked="checked";}else{$checked="";}
              ?>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="isActive" id="inlineRadio<?php echo $n;?>" value="<?php echo $n;?>" <?php echo $checked;?>>
                <label class="form-check-label" for="inlineRadio<?php echo $n;?>"><?php echo $a;?></label>
              </div>
              <?php
              }
              ?>
            </div>
          </div>
          <div class='row'>
            <div class='col'>
              <label for='imageFile'><strong>Delivery Image</strong></label>
              <input type='text' class='form-control media-input' name ='imageFile[image]' value='<?php echo $links['image'];?>' /> <button class='media-button'>Select image</button>
            </div>
          </div>
        </div>
        <div id="demographics">
          <div class='row'>
            <div class='col'>
              <label for='lat'><strong>Latitute</strong></label><br />
              <input type='text' class='form-control' id='lat' value='<?php echo $links['lat'];?>' disabled/><br />
              <input type='hidden' name='imageFile[lat]' value='<?php echo $links['lat'];?>'/>
            </div>
            <div class='col'>
              <label for='long'><strong>Longitude</strong></label><br />
              <input type='text' class='form-control' id='long' value='<?php echo $links['long'];?>' disabled/><br />
              <input type='hidden' name='imageFile[long]' value='<?php echo $links['long'];?>'/>
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
          <div class='row'>
            <div class='col'>
              <label for='locationImageFile'><strong>Location Image</strong></label>
              <input type='text' id='locationImageFile' class='form-control media-input' name ='imageFile[locationImage]' value='<?php echo $links['locationImage'];?>' /> <button class='media-button'>Select image</button>
            </div>
            <div class='col'>
              <?php if(isset($links['locationImage'])){?><img src="<?php echo $links['locationImage'];?>" alt='Logo' /><?php }?>
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
              <select name='services[<?php echo $s;?>][day][]' class="custom-select multipleSelect" style="width:100%;" id='<?php echo $s;?>deliveryDay' multiple>
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
              <input type='text' class='timepicker form-control' id='<?php echo $s;?>cutoff' name='services[<?php echo $s;?>][cutoff]' value='<?php echo $serviceInfo[$s]['cutoff'];?>'/><br />
            </div>
            <div class='col'>
              <label for='<?php echo $s;?>delivery'><strong>Delivery Time</strong></label><br />
              <input type='text' class='timepicker form-control' id='<?php echo $s;?>delivery' name='services[<?php echo $s;?>][delivery]' value='<?php echo $serviceInfo[$s]['delivery'];?>'/><br />
            </div>
          </div>
          <div class='row'>
            <div class='col'>
              <?php
              if(isset($info['restaurantID']) && $info['restaurantID']!=0){
               ?>
              <label for='<?php echo $s;?>menu'><strong>Menus</strong></label><br />
              <select name='services[<?php echo $s;?>][menu][]' class="custom-select multipleSelect" style="width:100%;" id='<?php echo $s;?>menu' multiple>
                <option value='33d281c5-3790-423b-8f54-4039a0d24171'>Shakes</option>
                <option value='f3603f5e-ecd2-4e9c-be2d-d74bc9778f44'>Entrees</option>
                <option value='fb381331-1cf3-4fe2-8364-4864bbcaf629'>Breakfast</option>
                <option value='a0feaf5c-349f-4d85-997c-c4874601fb9c'>Beverages</option>
                <option value='567107e9-1537-4c24-b767-8a658b33fbb4'>Sides & Snacks</option>
              </select>
              <?php
            }else{
               ?>
               <div class='alert alert-warning'>Menus cannot be added until a restaurant has been assigned.</div>
              <?php
            }
               ?>
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
