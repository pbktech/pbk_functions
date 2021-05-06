<?php
$currency = [100, 50, 20, 10, 5, 1, "Quarters", "Dimes", "Nickels", "Pennies"];
?>
<script>
  jQuery(document).ready(function($) {
    $('#restaurantPicker').select2({
      ajax: {
        url: '<?php echo admin_url('admin-ajax.php');?>?action=get_availableRestaurants',
        dataType: 'json'
      }
    });
  });
</script>
<div class="container">
    <div class="row">
        <div class="col">
            <label for="firstName">First Name</label><input id="firstName" class="form-control" />
        </div>
        <div class="col">
            <label for="lastName">Last Name</label><input id="lastName" class="form-control" />
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label for="restaurantPicker">Restaurant</label><select class="form-control" id="restaurantPicker"></select>
        </div>
        <div class="col">
            <label for="date">Last Name</label><input id="date" class="form-control" disabled value="<?php echo date('m/d/Y');?>" />
        </div>
    </div>
    <div class="row" style="text-align: center;">
        <div class="col-3 align-self-start">&nbsp;</div>
        <div class="col-6 align-self-center">
            <label for="countType">Counted Item</label>
            <select class="form-control" id="countType">
                <option value="">Choose One</option>
                <option value="safe">Safe</option>
                <option value="drawer1">Drawer 1</option>
                <option value="drawer2">Drawer 2</option>
                <option value="drawer3">Drawer 3</option>
            </select>
        </div>
        <div class="col-3 align-self-end">&nbsp;</div>
    </div>
    <div class="row row-cols-2" style="text-align: center;">
    <?php
        foreach ($currency as $c){
    ?>
        <div class="col">
            <label for="currency<?php echo $c;?>"><?php echo $c;?></label>
            <input class="form-control changeCurrency" id="currency<?php echo $c;?>" type="text" />
        </div>
<?php
}
?>
    </div>
</div>
