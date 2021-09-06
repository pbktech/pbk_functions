<?php
$vendorCategories = ['HR/Benefits','IT & Restaurant Resources','R&M/Facilities','Vendor'];
?>
<script>
  let vendorID;
  function vendorPageMessage(message, alertClass) {
    let div = $('<div/>', {
      'class': 'alert alert-dismissible fade show ' + alertClass,
      html: message
    });
    $('<button/>', {
      type: 'button',
      class: 'close',
      html: '<span aria-hidden="true">&times;</span>',
      'data-dismiss': 'alert'
    }).appendTo(div);
    $('#serverMessageVendorContainer').append(div);
  }
  $(document).ready(function() {
    $('#vendorCategory').select2();
    var vendorTable = $('#vendorTable').DataTable({
      lengthMenu: [[25, 50, -1], [25, 50, 'All']],
      ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=supportGetAllVendors',
      dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
      columns: [
        { data: 'department' },
        { data: 'name' },
        { data: 'actions' }
      ],
      buttons: ['print', 'excelHtml5', 'csvHtml5',
        {
          extend: 'pdfHtml5',
          pageSize: 'Letter',
          exportOptions: {
            columns: [0, 1]
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
    $('#addVendorModal').click(function(){
      vendorID = -1;
      $('#vendorHeader').html('Add a new vendor')
      $('#vendorModal').modal('show');
    });
    $('#vendorTable tbody').on('click', '.editVendorButton', function(event) {
      const data = event.target.dataset;
      vendorID = data.vendorId;
      $('#vendorModal').modal('show');
    });
    $('#vendorTable tbody').on('click', '.statusVendorButton', function(e) {
      const data = e.target.dataset;
      let fd = new FormData();
      fd.append('action', 'supportChangeVendorStatus');
      fd.append('equipmentID', data.vendorId);
      fd.append('status', data.status);
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php') ?>',
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        success: function(response) {
          if (response.status === 200) {
            vendorTable.ajax.reload();
            vendorPageMessage(response.msg, 'alert-success');
          } else {
            vendorPageMessage(response.msg, 'alert-danger');
          }
        }
      });
    });
    $('#vendorModal').on('shown.bs.modal', function() {
      if(vendorID !== -1){
        let fd = new FormData();
        fd.append('action', 'supportGetVendorInfo');
        fd.append('equipmentID', vendorID);
        jQuery.ajax({
          url: '<?php echo admin_url('admin-ajax.php') ?>',
          type: 'POST',
          data: fd,
          contentType: false,
          processData: false,
          success: function(response) {
            console.log(response)
            if (response.status === 200) {
              $('#vendorHeader').html('Edit ' + response.info.platform);
              $('#vendorName').val(response.info.platform);
              $('#vendorServices').val(response.info.services);
              $('#vendorCategory').val([response.info.category]).trigger('change');
              $('#vendorCompanyContact').val(response.info.contact.replace(/<br ?\/?>/g, "\n"));
              $('#vendorPBKContact').val(response.info.pbk_contact.replace(/<br ?\/?>/g, "\n"));
              $('#vendorActive').prop('checked', response.info.isActive==='1');

            } else {
              $('#vendorModal').modal('hide');
              vendorPageMessage(response.msg, 'alert-danger');
            }
          }
        });
      }
    });
    $('#vendorModal').on('hidden.bs.modal', function() {
      vendorID = null;
      $('#vendorHeader').html('');
      $('#vendorCategory').val([]).trigger('change');
      $('#vendorName').val('');
      $('#vendorServices').val('');
      $('#vendorCompanyContact').val('');
      $('#vendorPBKContact').val('');
      $('#vendorModalMessage').removeClass('alert-danger').html('');
      $('#vendorActive').prop('checked', true);
    });
    $('#saveVendorButton').click(function(){
      let fd = new FormData();
      fd.append('action', 'supportUpdateVendor');
      fd.append('vendorID', vendorID);
      fd.append('category', $('#vendorCategory').val());
      fd.append('platform', $('#vendorName').val());
      fd.append('isActive', $('#vendorActive').prop('checked'));
      fd.append('services', $('#vendorServices').val());
      fd.append('contact', $('#vendorCompanyContact').val());
      fd.append('pbk_contact', $('#vendorPBKContact').val());
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php') ?>',
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        success: function(response) {
          if (response.status === 200) {
            vendorTable.ajax.reload();
            vendorPageMessage(response.msg, 'alert-success');
            $('#vendorModal').modal('hide');
          } else {
            $('#vendorModalMessage').addClass('alert-danger').html(response.msg);
          }
        }
      });
    });
  });
</script>
<h2>Edit Vendors</h2> <a href="#" id="addVendorModal" class="add-new-h2">Add New</a>

<div class="container-fluid">
    <div class="row" style="width: 100%;">
        <div class="alert" style="width: 100%;" id="serverMessageVendorContainer">
            <div id="serverMessageVendor"></div>
        </div>
    </div>
    <div class="row">
        <div class="container">
            <table id="vendorTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                <tr style="background-color: #0e2244; color: #ffffff; text-align: center; font-weight: bold;">
                    <th>Category</th>
                    <th>Company</th>
                    <th>Actions</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<div class="modal" tabindex="-1" id="vendorModal">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorHeader"></h5>
                <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row" id="vendorModalMessage"></div>
                    <div class="row" style="width: 100%;">
                        <div class="col">
                            <label for="vendorName">Name</label>
                            <input type="text" class="form-control" id="vendorName">
                        </div>
                    </div>
                    <div class="row" style="width: 100%;">
                        <div class="col">
                            <label for="vendorServices">Services</label>
                            <input type="text" class="form-control" id="vendorServices">
                        </div>
                    </div>
                    <div class="row" style="width: 100%;">
                        <div class="col">
                            <div class="form-group">
                                <label for="vendorCompanyContact">Company Contact</label>
                                <textarea class="form-control" id="vendorCompanyContact" rows="3" style="width: 100%;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="width: 100%;">
                        <div class="col">
                            <div class="form-group">
                                <label for="vendorPBKContact">PBK Contact</label>
                                <textarea class="form-control" id="vendorPBKContact" rows="3" style="width: 100%;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="width: 100%;">
                        <div class="col-6">
                            <label for="vendorCategory">Category</label>
                            <br>
                            <select class="custom-select" id="vendorCategory">
                                <option value="">Choose One</option>
                                <?php
                                foreach ($vendorCategories as $vendorCategory){
                                ?>
                                    <option value="<?php echo $vendorCategory;?>"><?php echo $vendorCategory;?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <br>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="vendorActive" checked>
                                <label class="custom-control-label" for="vendorActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeModal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveVendorButton">Save</button>
            </div>
        </div>
    </div>
</div>