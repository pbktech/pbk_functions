<script>
  jQuery(document).ready(function($) {
    $('.datePicker').datepicker({
      dateFormat : 'mm/dd/yy'
    });
    $('#submit').click(function() {
      $('.processing').show();
    });
  });
</script>
<div class='wrap'>
    <h2>PBK Manager Cash Log Archive</h2>
    <h3>Please Select Dates:</h3>
    <div class='container'>
        <form method='get' action='<?php echo admin_url('admin.php'); ?>'>
            <input type='hidden' name='page' value='pbk-cs-archive'/>
            <div class="form-group">
                <div class='row'>
                    <div class='col'>
                        <label for="startDate">Start Date</label><br/>
                        <input type="text" id="startDate" name="startDate" class="datePicker"/>
                    </div>
                    <div class='col'>
                        <label for="endDate">End Date</label><br/>
                        <input type="text" id="endDate" name="endDate" class="datePicker"/>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <input id='submit' type='submit' value='SEARCH'/>
            </div>
        </form>
        <div class="row processing" style="display: none; text-align: center;">
            <img src='<?php echo PBKF_URL; ?>/assets/images/processing.gif' style='height:92px;width:92px;'/>
        </div>
    </div>
<?php
if(!empty($_REQUEST['startDate']) && !empty($_REQUEST['endDate'])){
    ?>
    <script>
        jQuery(document).ready(function($){
          $('#dataTable').DataTable({
            lengthMenu: [[25, 50, -1], [25, 50, 'All']],
            ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=adminGetCashLogs&startDate=<?php echo date("Y-m-d", strtotime($_REQUEST['startDate']));?>&endDate=<?php echo date("Y-m-d", strtotime($_REQUEST['endDate']));?>',
            dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
            columns: [
              { data: 'restaurant' },
              { data: 'countType' },
              { data: 'dateTime' },
              { data: 'total' },
              { data: 'view', orderable: false }
            ],
            buttons: ['print', 'excelHtml5', 'csvHtml5',
              {
                extend: 'pdfHtml5',
                pageSize: 'Letter',
                exportOptions: {
                  columns: [0, 1, 2, 3, 4]
                },
                customize: function(doc) {
                  doc.content[1].table.widths = ['20%', '20%', '30%', '20%',
                    '10%', '14%', '14%', '14%'];
                  doc.content.splice(0, 1, {
                    margin: [0, 0, 0, 12],
                    alignment: 'center',
                    image: 'data:image/png;base64,<?php echo DOC_IMG;?>',
                    fit: [400, 103]
                  });
                }
              }
            ]
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
          });        })
    </script>
    <hr />
    <table id="dataTable" class="table table-striped" style="width: 100%;padding-top: 1em;">
        <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
        <tr>
            <th>Restaurant</th>
            <th>Counted Item</th>
            <th>Date/Time</th>
            <th>Total Counted</th>
            <th></th>
        </tr>
        </thead>
    </table>
<?php
    echo ccModal();
}
?>
</div>

