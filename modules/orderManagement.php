<style>
    .ui-timepicker-container{
        z-index:99999 !important;
    }
    .red {
        background-color: #f8d7da !important;
    }
</style>
<div class="container">
    <div class="row" id="message"></div>
    <div class="row" id="processing" style="display: none;">
        <div class="spinner-border text-center" role="status">
            <span class="sr-only">Processing...</span>
        </div>
    </div>
    <div class="row">
        <div class="col-4">
            <label for='name'>Guest Name</label>
            <input class='form-control' type="text" id="guestName" name="name" value=""/>
        </div>
        <div class="col-4">
            <label for='startDate'>Start Date</label>
            <input class='form-control datePicker' type="text" id="startDate" name="startDate" value=""/>
        </div>
        <div class="col-4">
            <label for='endDate'>End Date</label>
            <input class='form-control datePicker' type="text" id="endDate" name="endDate" value=""/>
        </div>
    </div>
    <div class="row" style="padding-top: 1em; padding-bottom: 1em;">
        <div class="col-4">
            <button class="btn-info" id="search">SEARCH</button>
        </div>
    </div>
</div>
<div id="results" style="display: none;">
    <table id="dataTable" style="width: 100%;">
        <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Ordered</th>
                <th>Due</th>
                <th>Type</th>
                <th></th>
            </tr>
        </thead>
    </table>
</div>
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptHeader"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container" id="receiptBody"></div>
            </div>
            <div class="modal-footer" id="receiptFooter">
                <div class="col-6" id="receiptMessage" style="text-align: center;"></div>
                <div class="col-4">
                    <div class="btn-group" role="group" aria-label="Basic example">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i></button>
                        <a href="" target="_blank" id="receiptLink"><button type="button" class="btn btn-info"><i class="fas fa-print"></i></button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="duplicateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptHeader">Duplicate Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container" id="duplicateBody">
                    <div id="duplicateMessage"></div>
                    <div class="row"><strong>Please Enter a New Due Date</strong></div>
                    <div class="row">
                        <div class="col">
                            <label for='newDate'>Date</label>
                            <input class='form-control datePicker' type="text" id="newDate" name="newDate" value=""/>
                        </div>
                        <div class="col">
                            <label for='newTime'>Time</label>
                            <input class='form-control timepicker' type="text" id="newTime" name="newTime" value=""/>
                            <input type="hidden" name="headerID" id="headerID" value="">
                        </div>
                    </div>
                </div>
                <div class="container" id="duplicateProcessing" style="display: none">
                    <div class="spinner-border text-center" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="duplicateFooter">
                <div class="col-4">
                    <div class="btn-group" role="group" aria-label="Basic example">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i></button>
                        <button type="button" class="btn btn-info" id="duplicateSubmit"><i class="fas fa-check"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog" aria-hidden="true">

</div>
<?php
add_action('wp_footer', 'omAJAX');
function omAJAX() { ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        let guestName = $('#guestName').val();
        let startDate = $('#startDate').val();
        let endDate = $('#endDate').val();
        $('.datePicker').datepicker({
          dateFormat: 'mm/dd/yy'
        });
        $('.timepickerol').clockpicker();
        $('.timepicker').timepicker({
          timeFormat: 'h:mm p',
          interval: 15,
          minTime: '6am',
          maxTime: '9:00pm',
          dynamic: false,
          dropdown: true,
          scrollbar: true,
        });
        $('#guestName').blur(function() {
          guestName = $('#guestName').val();
        });
        $('#startDate').blur(function() {
          startDate = $('#startDate').val();
        });
        $('#guestName').blur(function() {
          endDate = $('#endDate').val();
        });
        let myTable = $('#dataTable').DataTable( {
          lengthMenu: [[25, 50, -1], [25, 50, 'All']],
          ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=om_get_orders&name=' + guestName + '&startDate=' + startDate + '&endDate=' + endDate,
          dom: "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'B><'col-sm-12 col-md-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-4'i><'col-sm-12 col-md-8'p>>",
          columns: [
            { data: "name"},
            { data: "company" },
            { data: "ordered" },
            { data: "due" },
            { data: "orderType" },
            { data: "actions", orderable: false }
          ],
          "createdRow": function( row, data, dataIndex ) {
            if (data.deleted === "1") {
              $(row).addClass('alert-danger');
            }
            if (new Date() < data.microTime) {
              $(row).addClass('alert-warning');
            }
          },
          buttons: ['print','excelHtml5','csvHtml5',
            {
              extend: 'pdfHtml5',
              pageSize: 'Letter',
              exportOptions: {
                columns: [0, 1, 2, 3, 4]
              },
              customize: function ( doc ) {
                doc.content[1].table.widths = [ '20%',  '20%', '30%', '20%',
                  '10%', '14%', '14%', '14%'];
                doc.content.splice( 0, 1, {
                  margin: [ 0, 0, 0, 12 ],
                  alignment: 'center',
                  image: 'data:image/png;base64,<?php echo DOC_IMG;?>',
                  fit: [400, 103]
                } );
              }
            }
          ]
        } );
        $('#dataTable tbody').on( 'click', '.showReceipt', function (event) {
          const data = event.target.dataset;
          const linkParse = data.receiptlink.split('/receipt/');

          confirm = {
            f: 'receipt',
            guid: linkParse[1],
          };
          jQuery.ajax({
            url: '/pbk_api/checkout',
            type: 'POST',
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(confirm),
            success: function(response) {
              let receiptBody = '';
              let grandTotal = 0;
              $('#receiptHeader').html(response.minibar + ' : ' + response.delivery);
              for(let i=0; i < response.checks.length; i = i + 1){
                let subtotal = 0.00;
                if (response.checks[i]) {
                  const check = response.checks[i];
                  const tax = parseFloat(check.totals.tax);
                  let discounts = 0;
                  receiptBody = receiptBody + '<div><div class="row"><div class="col-12" style="font-weight: bold;">' + check.tab + '</div></div>';
                  for (let ia = 0; ia < check.items.length; ia = ia + 1){
                    const item = check.items[ia];

                    subtotal = subtotal + (item.quantity * (item.price));
                    receiptBody = receiptBody + '<div class="row"><div class="col-2">' + item.quantity + '</div><div class="col-8">' + item.name + '</div><div class="col-2" style="text-align: right">$' + item.price +  '</div></div>';
                  }
                  receiptBody = receiptBody + '<div class="row"><div class="col-10" style="text-align: right">Subtotal</div><div class="col-2" style="text-align: right">$' + subtotal.toFixed(2) + '</div></div>' ;
                  check.discounts.length && check.discounts.map((discount) => {
                    const discountAmount = parseFloat(discount.discountAmount);
                    receiptBody = receiptBody + '<div class="row" style="color: #dc3545;  font-style: italic;"><div class="col-10" style="text-align: right;">' + discount.discountName + ' (' + discount.promoCode + ')</div><div class="col-2" style="text-align: right">$' + discountAmount.toFixed(2) + '</div></div>' ;
                    discounts = discounts + parseFloat(discount.discountAmount);
                  });
                  const total = subtotal + tax;
                  grandTotal = grandTotal + total;
                  receiptBody = receiptBody + '<div class="row"><div class="col-10" style="text-align: right">Tax</div><div class="col-2" style="text-align: right">$' + tax.toFixed(2) + '</div></div>' ;
                  receiptBody = receiptBody + '<div class="row"><div class="col-10" style="text-align: right">Total</div><div class="col-2" style="text-align: right">$' + total.toFixed(2) + '</div></div>' ;
                  check.payments && check.payments.map((payment) => {
                    receiptBody = receiptBody + '<div class="row"><div class="col-10" style="text-align: right">' + payment.paymentType + ' - ' + payment.cardNum.substring(payment.cardNum.length - 4) + '</div><div class="col-2" style="text-align: right">$' + payment.paymentAmount + '</div></div>' ;
                  });
                  receiptBody = receiptBody + '</div>';
                }
              }
              response.payment.length && response.payment.map((payment) => {
                receiptBody = receiptBody + '<div class="row">Amount applied to ' + payment.paymentType + ' ending in ' + payment.cardNum + ': $' + grandTotal + '</div>';
              });
              receiptBody = receiptBody + '<div class="row" style="font-size: 10px;padding-top: 1em;">' + data.receiptlink + '</div>';
              $('#receiptLink').attr("href", data.receiptlink + '?print=yes');
              $('#receiptURL').html(data.receiptlink);
              $('#receiptBody').html(receiptBody)
              $('#receiptModal').modal('show');
            }
          });
        });
        $('#dataTable tbody').on( 'click', '.cancelOrder', function (event) {
          const data = event.target.dataset;
          let msgClass = 'alert-danger';
          $('#processing').show();
          $("html, body").animate({ scrollTop: 0 }, "slow");
          const confirm = {
            'action': 'om_cancel',
            'headerID': data.orderid
          };
          jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php') ?>',
            type: 'POST',
            data: confirm,
            success: function(response) {
              if (response.status === 200){
                msgClass = 'alert-success';
              }
              $('#processing').hide();
              $('#message').addClass("alert " + msgClass).html(response.msg);
              setTimeout(function(){
                $('#message').removeClass("alert " + msgClass).html("");
              }, 30000);
              myTable.ajax.reload();
            }
          });
        });
        $('#dataTable tbody').on( 'click', '.duplicateOrder', function (event) {
          const data = event.target.dataset;
          $('#headerID').val(data.orderid);
          $('#duplicateModal').modal('show');
        });
        $('#duplicateSubmit').click(function(){
          let valid = true;
          if(!$('#newTime').val()){
            $('#duplicateMessage').addClass("alert alert-danger").html("You must set a new time");
            valid = false;
          }
          if(!$('#newDate').val()){
            $('#duplicateMessage').addClass("alert alert-danger").html("You must set a new date");
            valid =  false;
          }
          if(valid) {
            $('#duplicateBody').hide();
            $('#duplicateProcessing').show();
            $('#duplicateMessage').removeClass("alert alert-danger").html("");
            const confirm = {
              'action': 'om_duplicate',
              'headerID': $('#headerID').val(),
              'newDate': $('#newTime').val(),
              'newTime': $('#newDate').val()
            };
            jQuery.ajax({
              url: '<?php echo admin_url('admin-ajax.php') ?>',
              type: 'POST',
              data: confirm,
              success: function(response) {
                console.log(response)
                if (response.status === 200){
                  $('#message').addClass("alert alert-success").html(response.msg);
                  setTimeout(function(){
                    $('#message').removeClass("alert alert-success").html("");
                  }, 30000);
                  $('#duplicateModal').modal('hide');
                }else{
                  $('#message').addClass("alert alert-danger").html(response.msg);
                }
                $('#duplicateBody').show();
                $('#duplicateProcessing').hide();
              }
            });
          }
        });
        $('#search').click(function(e){
          myTable.ajax.reload();
          $('#results').show();
        });
        $('#duplicateModal').on('hidden.bs.modal', function (event) {
          $('#headerID').val("");
          $('#newTime').val("");
          $('#newDate').val("");
          myTable.ajax.reload();
        })
      });
    </script>
    <?php
}

?>