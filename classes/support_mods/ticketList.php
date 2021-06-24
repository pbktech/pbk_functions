<h2>Currently Open Tickets</h2>
<div class="container-fluid">
    <script>
      $(document).ready(function() {
        $('#myTable').DataTable({
          lengthMenu: [[25, 50, -1], [25, 50, 'All']],
          ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=get_ticket_list',
          dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
          columns: [
            { data: 'date' },
            { data: 'restaurant' },
            { data: 'item' },
            { data: 'status' },
            { data: 'actions' }
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
      });
    </script>
    <table id="myTable" class="table table-striped table-bordered" style="width:100%">
        <thead>
        <tr style="background-color: #0e2244; color: #ffffff; text-align: center; font-weight: bold;">
            <th>Date</th>
            <th>Restaurant</th>
            <th>Item</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
    </table>
</div>
<?php
