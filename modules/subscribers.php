<div class="container" style="text-align: center;"><div id="message"></div></div>
<table id='myTable' class="table table-striped table-hover table-bordered" style='width:100%;'>
    <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
        <tr>
            <th>Active</th>
            <th>Name</th>
            <th>Phone Number</th>
            <th>Email</th>
            <th>Plan Name</th>
            <th>Signed Up</th>
            <th></th>
        </tr>
    </thead>
</table>

<?php
add_action('wp_footer', 'subscribersAJAX');

function subscribersAJAX() { ?>

    <script type="text/javascript">
      var guestName;

      jQuery(document).ready(function($) {
       var myTable = $('#myTable').DataTable({
          lengthMenu: [[25, 50, -1], [25, 50, 'All']],
          ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=subscribers_get_users',
          dom: "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'B><'col-sm-12 col-md-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-4'i><'col-sm-12 col-md-8'p>>",
          columns: [
            { data: "isActive", "visible": false },
            { data: "guestName"},
            { data: "phoneNumber" },
            { data: "emailAddress" },
            { data: "planName" },
            { data: "signedUp" },
            { data: "actions", orderable: false }
            ],
          buttons: ['print','excelHtml5','csvHtml5',
            {
              extend: 'pdfHtml5',
              pageSize: 'Letter',
              exportOptions: {
                columns: [1, 2, 3, 4, 5]
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
        });

        $('#myTable tbody').on( 'click', '.cancelUser', function (e) {
          if(confirm('Are you sure you want to cancel this ' + e.target.dataset.subname + ' subscription for ' + e.target.dataset.guest + '?')) {
            var data = {
              'action': 'subscribers_cancel',
              'uid': e.target.dataset.uid,
              'guest': e.target.dataset.guest,
              'nonce': e.target.dataset.nonce
            };

            jQuery.ajax({
              url: '<?php echo admin_url('admin-ajax.php') ?>', // this will point to admin-ajax.php
              type: 'POST',
              data: data,
              success: function(response) {
                $('#message').addClass( "alert " + response.class );
                $('#message').html(response.message);
                myTable.ajax.reload();
              }
            });
          }
        });
        $('#myTable tbody').on( 'click', '.showModal', function (event) {
          guestName = event.target.dataset.guest;

          $('#txnModal').modal('show');
          if ( $.fn.dataTable.isDataTable('#modalTable')) {
            $('#modalTable').DataTable();
          } else {
            $('#modalTable').DataTable({
              lengthMenu: [[25, 50, -1], [25, 50, 'All']],
              dom: "<'row'<'col-sm-12 col-md-12'B>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-4'i><'col-sm-12 col-md-8'p>>",
              buttons: [
                'copy',
                {
                  extend: 'excel',
                  messageTop: guestName + ' transaction record'
                },
                {
                  extend: 'pdfHtml5',
                  messageTop: guestName + ' transaction record',
                  customize: function ( doc ) {
                    doc.content[2].table.widths =
                      Array(doc.content[2].table.body[0].length + 1).join('*').split('');
                    doc.content.splice( 0, 1, {
                      margin: [ 0, 0, 0, 12 ],
                      alignment: 'center',
                      image: 'data:image/png;base64,<?php echo DOC_IMG;?>',
                      fit: [400, 103]
                    } );
                  }
                },
                {
                  extend: 'print',
                  messageTop: guestName + ' transaction record'
                }
              ],
              columns: [
                { data: "price" },
                { data: "transactionType" },
                { data: "transactionStatus" },
                { data: "datetime" }],
              ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=subscribers_get_trans&uis=' + event.target.dataset.uid + '&nonce=' + event.target.dataset.nonce,
            });
          }
          $('#txnModal').on('shown.bs.modal', function (modalEvent) {
            var modal = $(this);
            modal.find('.modal-title').text(guestName);

          });
          $('#txnModal').on('hidden.bs.modal', function (e) {
            $("#modalTable").DataTable().clear().destroy();
          });
        });
          $('#txnModal').on('shown.bs.modal', function (modalEvent) {
            var modal = $(this);
            modal.find('.modal-title').text(guestName);

          });
        $('.hideModal').click(function() {
          $("#modalTable").DataTable().clear().destroy();
        });
        $('#txnModal').on('hidden.bs.modal', function (e) {
          $("#modalTable").DataTable().clear().destroy();
        });
      });
    </script>
    <div class="modal fade" id="txnModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close hideModal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id='modalTable' class="display table table-striped table-hover table-bordered" style="width:100%;">
                        <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary hideModal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

