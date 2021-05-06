<?php
$currency = [100 => 100, 50 => 50, 20 => 20, 10 => 10, 5 => 5, 1 => 1, "Quarters" => .25, "Dimes" => .1, "Nickels" => .05, "Pennies" => .01];
?>
<script>
  jQuery(document).ready(function($) {
    $('#restaurantPicker').select2({
      ajax: {
        url: '<?php echo admin_url('admin-ajax.php');?>?action=get_availableRestaurants',
        dataType: 'json'
      }
    });
    $('.changeCurrency').change(function(e){
      const item = e.target;
      const itemID = item.id.split('-');
      let value = parseInt($(this).val());
      let totalValue = 0.00;

      if(isNaN(value)){
        value = 0;
      }
      $(this).val(value);
  //    $('#currency-' + itemID[1] + '-hidden').val(value * item.dataset.denomination)
      $('.changeCurrency').each(function() {
        const denom = parseFloat($(this)[0].dataset.denomination);
        console.log(totalValue)
        totalValue += parseFloat($(this).val()) * denom;
      });
      $('#totalField').addClass('h1').html("$" + totalValue.toFixed(2));
    });
  });
</script>
<div class="container">
    <div class="row">
        <div class="col">
            <label for="firstName">First Name</label><input id="firstName" class="form-control" tabindex="1" />
        </div>
        <div class="col">
            <label for="lastName">Last Name</label><input id="lastName" class="form-control" tabindex="2" />
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label for="restaurantPicker">Restaurant</label><select class="form-control"  tabindex="3" id="restaurantPicker"></select>
        </div>
        <div class="col">
            <label for="date">Today's Date</label><input id="date" class="form-control" disabled value="<?php echo date('m/d/Y');?>" />
        </div>
    </div>
    <hr />
    <div class="row" style="text-align: center;">
        <div class="col-3 align-self-start">&nbsp;</div>
        <div class="col-6 align-self-center">
            <label class="sr-only" for="countType">Counted Item</label>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <div class="input-group-text">Counted Item</div>
                </div>
                <select class="form-control" id="countType"  tabindex="4">
                    <option value="">Choose One</option>
                    <option value="safe">Safe</option>
                    <option value="drawer1">Drawer 1</option>
                    <option value="drawer2">Drawer 2</option>
                    <option value="drawer3">Drawer 3</option>
                </select>
            </div>
        </div>
        <div class="col-3 align-self-end">&nbsp;</div>
    </div>
    <div class="row row-cols-2" style="text-align: center;">
    <?php
    $tab = 4;
        foreach ($currency as $c => $d){
            $tab++;
    ?>
        <div class="col">
            <label class="sr-only" for="currency-<?php echo $c;?>"><?php echo $c;?></label>
            <div class="input-group mb-2">
                <div class="input-group-prepend" style="text-align: right;">
                    <div class="input-group-text" style="width: 100px; text-align: right;"><?php echo $c;?></div>
                </div>
                <input class="form-control changeCurrency" data-denomination="<?php echo $d;?>" id="currency-<?php echo $c;?>" type="text"  tabindex="<?php echo $tab;?>" />
            </div>
        </div>
<?php
}
?>
    </div>
    <div class="row">
        <div class="col-3 align-self-start">&nbsp;</div>
        <div class="col-6 align-self-center" id="totalField" style="text-align: center;">
        </div>
        <div class="col-3 align-self-end">&nbsp;</div>
    </div>
</div>
