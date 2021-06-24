<?php
function pbr_show_restaurants() {
    ?>
<script>
  $(document).ready(function() {
    $('#myTable').DataTable({
      lengthMenu: [[25, 50, -1], [25, 50, 'All']],
      ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=get_directory',
      dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
      columns: [
        { data: 'restaurant', "orderable": false },
        { data: 'email', "orderable": false },
        { data: 'phone', "orderable": false },
        { data: 'address', "orderable": false },
        { data: 'gmagm', "orderable": false },
        { data: 'am', "orderable": false },
        { data: 'restaurantID', "visible": false }
      ],
      "order": [[ 6, "asc" ]],
      buttons: ['print', 'excelHtml5', 'csvHtml5',
        {
          extend: 'pdfHtml5',
          pageSize: 'Letter',
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5]
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
        <th>RESTAURANT</th>
        <th>EMAIL</th>
        <th>PHONE</th>
        <th>ADDRESS</th>
        <th>GM/AGM</th>
        <th>AM</th>
    </tr>
    </thead>
</table>
<?php
}
