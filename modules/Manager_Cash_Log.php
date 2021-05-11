<?php

$currency = [100 => 100, 50 => 50, 20 => 20, 10 => 10, 5 => 5, 1 => 1, "Quarters" => .25, "Dimes" => .1, "Nickels" => .05, "Pennies" => .01,
    "Rolled Quarters" => 10, "Rolled Dimes" => 5, "Rolled Nickels" => 2, "Rolled Pennies" => .5];
?>
<script>
  jQuery(document).ready(function($) {
    $('#restaurantPicker').select2({
      ajax: {
        url: '<?php echo admin_url('admin-ajax.php');?>?action=get_availableRestaurants',
        dataType: 'json'
      }
    });
    $('#restaurantPicker').change(function(){
      confirm = {
        action: 'get_restaurantOptions',
        restaurantID: $('#restaurantPicker').val()
      };
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php');?>',
        type: 'POST',
        data: confirm,
        success: function(response) {
          if(response.options !== null) {
            const result = JSON.parse(response.options);
            const drawers = parseInt(result.cash.number_of_drawers);
            for (let i = 1; i <= drawers; i = i + 1) {
              $('#countType').append(new Option('Drawer ' + i, 'drawer_' + i));
            }
          }
        }
      });
    });
    $('.changeCurrency').change(function() {
      let value = parseInt($(this).val());
      let totalValue = 0.00;

      if (isNaN(value)) {
        value = 0;
      }
      $(this).val(value);
      $('.changeCurrency').each(function() {
        const denom = parseFloat($(this)[0].dataset.denomination);
        if ($(this).val()) {
          totalValue += parseFloat($(this).val()) * denom;
        }
      });
      $('#totalField').html('<h2>$' + totalValue.toFixed(2) + '</h2>');
    });
    $('#saveForm').click(function() {
      if (!$('#firstName').val() || !$('#lastName').val() || !$('#restaurantPicker').val() || !$('#countType').val()) {
        $('#message').addClass('alert alert-danger').html('Please make sure all header information is complete.');
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return;
      }
        const countType = $('#countType').val().split('_');
        let cType = countType[0];
        if(countType[1]){
            cType = cType + ' ' + countType[1];
        }
      const letsGo = window.confirm('Are you sure you want to save this ' + cType + ' count?')
      if (letsGo) {
        const cash = [];
        $('.changeCurrency').each(function() {
          const keyName = $(this)[0].id;
          const denom = parseFloat($(this)[0].dataset.denomination);
          const val = parseInt($(this).val());

          cash.push({
            keyName,
            calc: val * denom,
            denom,
            val,
          });
        });

        const confirmObj = {
          action: 'addCashLog',
          cash: cash,
          firstName: $('#firstName').val(),
          lastName: $('#lastName').val(),
          countType: $('#countType').val(),
          restaurant: $('#restaurantPicker').val()
        };
        jQuery.ajax({
          url: '<?php echo admin_url('admin-ajax.php');?>',
          type: 'POST',
          data: confirmObj,
          success: function(response) {
            if (response.status === 200) {
              $('.changeCurrency').each(function() {
                $(this).val(0);
              });
              $('#firstName').val('');
              $('#lastName').val('');
              $('#countType').val('');
              $('#restaurantPicker').val('');
              $('#message').removeClass('alert alert-danger').html('');
              $('#totalField').html('<h2>$0.00</h2>');
              $('#message').addClass('alert alert-success').html('The count for ' + cType + ' has been saved');
              setTimeout(function(){
                  $('#message').removeClass('alert alert-danger').removeClass('alert alert-success').html('');
                  }, 60000);
              myTable.ajax.reload();
            } else {

            }
          }
        });
      }
    });
    let myTable = $('#dataTable').DataTable({
      lengthMenu: [[25, 50, -1], [25, 50, 'All']],
      ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=getCashLogs',
      dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
      columns: [
        { data: 'restaurant' },
        { data: 'countType' },
        { data: 'dateTime' },
        { data: 'view', orderable: false }
      ],
      buttons: [
        {
          extend: 'print',
          exportOptions: {
            columns: [0, 1, 2]
          }
        },
        {
          extend: 'excelHtml5',
          exportOptions: {
            columns: [0, 1, 2]
          }
        },
        {
          extend: 'csvHtml5',
          exportOptions: {
            columns: [0, 1, 2]
          }
        },
        {extend: 'pdfHtml5',
          exportOptions: {
            columns: [ 0, 1, 2]
          },
          messageTop: 'Manager Cash Log: <?php echo date("m/d/Y");?>',
          customize: function ( doc ) {
            doc.content.splice( 0, 1, {
              margin: [ 0, 0, 0, 12 ],
              alignment: 'center',
              image: 'data:image/png;base64,<?php echo DOC_IMG;?>',
              fit: [400, 103]
            } );
          }
        }],
    });
    $('#dataTable tbody').on('click', '.viewEntry', function(event) {
      const item = event.target.dataset;

      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php');?>',
        type: 'POST',
        data: {
          action: 'getCashLog',
          logID: item.log,
        },
        success: function(r) {
          const countType = r.countType.split('_');
          let cType = '';
          if(countType[1]){
            cType = countType[0].toUpperCase()  + ' ' + countType[1];
          }else {
            cType = countType[0].toUpperCase();
          }
          const d = new Date(r.timeStamp);

          $('#receiptHeader').html('<h3>' + r.employeeName + ': ' + cType + '</h3><br><div style="text-muted">' + d.toLocaleString() + '</div>');
          let totalAmount = 0.00;
          let body = '<div class="row"><table class="table"><thead><tr><th>Denomination</th><th>Amount</th></tr></thead>';
          const data = JSON.parse(r.cashCount);
          data.map(function(item) {
            const name=item.keyName.split('-');
            if(isNaN(item.calc)){
              item.calc = 0.00;
            }
            if(isNaN(item.val)){
              item.val = 0;
            }
            totalAmount = totalAmount + parseFloat(item.calc);
            body = body +
              '<tr><td>' + name[1] + '</td><td>$' + parseFloat(item.calc).toFixed(2) + ' (' + item.val + ')</td></tr>';
          });
          body = body + '<tr><td>Total</td><td>$' + parseFloat(totalAmount).toFixed(2) + '</td></tr></table></div>';
          $('#receiptBody').html(body);

        }
      });
      $('#viewModal').modal('show');
    });
  });
</script>
<div class="container">
    <div class="row" id="message"></div>
    <div class="row">
        <div class="col">
            <label for="firstName">First Name</label><input id="firstName" class="form-control" tabindex="1" required/>
        </div>
        <div class="col">
            <label for="lastName">Last Name</label><input id="lastName" class="form-control" tabindex="2" required/>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label for="restaurantPicker">Restaurant</label><select class="custom-select" tabindex="3"
                                                                    id="restaurantPicker" required></select>
        </div>
        <div class="col">
            <label for="date">Today's Date</label><input id="date" class="form-control" disabled
                                                         value="<?php echo date('m/d/Y'); ?>"/>
        </div>
    </div>
    <hr/>
    <div class="row" style="text-align: center;">
        <div class="col-3 align-self-start">&nbsp;</div>
        <div class="col-6 align-self-center">
            <label class="sr-only" for="countType">Counted Item</label>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <div class="input-group-text">Counted Item</div>
                </div>
                <select class="custom-select" id="countType" tabindex="4" required>
                    <option value="">Choose One</option>
                    <option value="safe">Safe</option>
                    <option value="deposit">Deposit</option>
                </select>
            </div>
        </div>
        <div class="col-3 align-self-end">&nbsp;</div>
    </div>
    <div class="row">
        <div class="col-3 align-self-start">&nbsp;</div>
        <div class="col-6 align-self-center" id="totalField" style="text-align: center;"><h2>$0.00</h2>
        </div>
        <div class="col-3 align-self-end">&nbsp;</div>
    </div>
    <div class="row row-cols-2" style="text-align: center;">
        <?php
        $tab = 4;
        foreach ($currency as $c => $d) {
            $tab++;
            ?>
            <div class="col">
                <label class="sr-only" for="currency-<?php echo $c; ?>"><?php echo $c; ?></label>
                <div class="input-group mb-2">
                    <div class="input-group-prepend" style="text-align: right;">
                        <div class="input-group-text" style="width: 200px; text-align: right;"><?php echo $c; ?></div>
                    </div>
                    <input class="form-control changeCurrency" data-denomination="<?php echo $d; ?>"
                           id="currency-<?php echo $c; ?>" type="number" tabindex="<?php echo $tab; ?>"/>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <div class="row">
        <div class="col">
            <button id="saveForm">Save</button>
        </div>
    </div>
</div>
<div class="container d-none d-lg-block" style="padding-top: 1.5em;">
    <h2>Today's Entries</h2>
    <table id="dataTable" class="table table-striped" style="width: 100%;">
        <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
        <tr>
            <th>Restaurant</th>
            <th>Counted Item</th>
            <th>Time</th>
            <th></th>
        </tr>
        </thead>
    </table>
</div>
<?php
echo ccModal();