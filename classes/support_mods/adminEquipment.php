<?php
global $wpdb;
$support = new PBKSupport();
$departments = $support->getDepartments();
$vendors = $wpdb->get_results("SELECT contactID, platform FROM pbc_support_contacts WHERE isActive=1");
?>
    <script>
      let equipmentID, commonIssues, issuesSave;
      const vendors = JSON.parse('<?php echo json_encode($vendors);?>');

      function equipmentPageMessage(message, alertClass) {
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
        $('#serverMessageEquipmentContainer').append(div);
      }

      function selectVendors(groupID){
        let vendorsSelect = $('<select/>',{
          id: groupID,
          class: 'custom-select'
        });
        for(let i=0; i<vendors.length; i++) {
          $('<option/>', {
            value: vendors[i].contactID,
            html: vendors[i].platform
          }).appendTo(vendorsSelect);
        }
        return vendorsSelect;
      }

      function createToggle(groupID, label){
        let col = $('<div/>',{
          class: 'col-3'
        });
        let wrapper = $('<div/>', {
          class: 'custom-control custom-switch',
        }).appendTo(col);
        $('<input/>',{
          id: groupID,
          class: 'custom-control-input',
          type: 'checkbox'
        }).appendTo(wrapper);
        $('<label/>',{
          class: 'custom-control-label',
          for: groupID,
          html: label
        }).appendTo(wrapper);

        return col[0].outerHTML;
      }
      function commonIssueRow(issue){
        if(!issue){return;}

        vendorsSelect = selectVendors('commonIssueVendor'+issue.issueID);
        let row = $('<div/>',{
          class: 'row clearRow',
          style: 'width: 100%; border: #9d9d9d solid 1px;'
        });
        let heading = $('<h2/>',{
            class: '',
            style: 'background-color: #f7f7f7;width: 100%;',
        }).appendTo(row);
        let container = $('<div/>',{
          class: 'collapse',
          style: "width: 95%;margin: auto;",
          'data-parent': "#commonIssueModalAccordion",
          id: 'row' + issue.issueID,
        }).appendTo(row);
        $('<button/>',{
          class: 'btn btn-link btn-block text-left',
          type: 'button',
          'data-toggle': "collapse",
          'data-target': '#row' + issue.issueID,
          'aria-expanded': "true",
          'aria-controls': '#row' + issue.issueID,
          html: issue.issueTitle
        }).appendTo(heading);
        $('<div/>', {
          class: 'form-row',
          html: '<label for="commonIssueName' + issue.issueID + '">Name</label>'+
            '<input type="text" class="form-control" id="commonIssueName' + issue.issueID + '" value="'+ issue.issueTitle+'">'+
            '</div>'
        }).appendTo(container);
        $('<div/>', {
          class: 'form-row',
          style: 'padding-top: .5em; padding-bottom: .5em;',
          html: '<div class="col">'+
            '<label for="commonIssueName' + issue.issueID + '">Vendor</label><br>'+vendorsSelect[0].outerHTML+
            '</div>'
        }).appendTo(container);


        $('<div/>', {
          class: 'form-row',
          html: createToggle('commonIssueActive' + issue.issueID,'Active') + createToggle('commonIssueEmergency' + issue.issueID,'Emergency')
        }).appendTo(container);

        $('#commonIssueModalAccordion').append(row);
        $('#commonIssueVendor'+issue.issueID).select2();
        $('#commonIssueActive' + issue.issueID).prop('checked', parseInt(issue.isActive) === 1);
        $('#commonIssueEmergency' + issue.issueID).prop('checked', issue.isEmergency === '1');
        $('#equipmentActive').prop('checked', true);
        if(issue.vendorID){
          $('#commonIssueVendor'+issue.issueID).val([issue.vendorID]).trigger('change');
        }

      }
      $(document).ready(function() {
        $('#equipmentDepartment').select2();
        $('#commonIssueVendorNew').select2();
        $('#equipmentVendor').select2();
        var myTable = $('#equipmentTable').DataTable({
          lengthMenu: [[25, 50, -1], [25, 50, 'All']],
          ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=supportGetEquipmentList',
          dom: '<\'row\'<\'col-sm-12 col-md-4\'l><\'col-sm-12 col-md-4\'B><\'col-sm-12 col-md-4\'f>><\'row\'<\'col-sm-12\'tr>><\'row\'<\'col-sm-12 col-md-4\'i><\'col-sm-12 col-md-8\'p>>',
          columns: [
            { data: 'department' },
            { data: 'name' },
            { data: 'actions' }
          ],
          'createdRow': function(row, data, dataIndex) {
            if (data.isActive === '0') {
              $(row).addClass('alert-danger');
            }
          },
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
        $('#equipmentTable tbody').on('click', '.editButton', function(event) {
          const data = event.target.dataset;
          equipmentID = data.equipmentId;
          $('#equipmentModal').modal('show');
        });
        $('#equipmentTable tbody').on('click', '.commonIssueButton', function(event) {
          const data = event.target.dataset;
          equipmentID = data.equipmentId;
          $('#commonIssueModal').modal('show');
        });
        $('#commonIssueModal').on('shown.bs.modal', function() {
          if (equipmentID !== 0) {
            commonIssues = [];
            let fd = new FormData();
            fd.append('action', 'supportGetEquipmentCommonList');
            fd.append('equipmentID', equipmentID);
            jQuery.ajax({
              url: '<?php echo admin_url('admin-ajax.php') ?>',
              type: 'POST',
              data: fd,
              contentType: false,
              processData: false,
              success: function(response) {
                if (response.status === 200) {
                  $('#commonIssueModalHeader').html(response.name + ' common issues');
                  if (response.info && response.info.length) {
                    commonIssues = response.info;
                    for(let i=0; i<=response.info.length; i++){
                      commonIssueRow(response.info[i]);
                    }
                  } else {
                    $('#collapseOne').addClass('show');
                  }
                } else {
                  $('#commonIsssueModal').modal('hide');
                  equipmentPageMessage(response.msg, 'alert-danger');
                }
              }
            });

          }
        });
        $('#commonIssueModal').on('hidden.bs.modal', function() {
          $('#collapseOne').removeClass('show');
          $('.clearRow').remove();
          $('#commonIssueNameNew').val("");
          $('#commonIssueEmergencyNew').prop('checked', false);
          $('#commonIssueActiveNew').prop('checked', true);
          $('#commonIssueVendorNew').val([]).trigger('change');
        });
        $('#addEquipmentModal').click(function() {
          equipmentID = 0;
          $('#equipmentModalHeader').html('Add a New Piece of Equipment');
          $('#equipmentActive').prop('checked', true);
          $('#equipmentModal').modal('show');
        });
        $('#equipmentModal').on('shown.bs.modal', function() {
          if (equipmentID !== 0) {
            let fd = new FormData();
            fd.append('action', 'supportGetEquipmentInfo');
            fd.append('equipmentID', equipmentID);
            jQuery.ajax({
              url: '<?php echo admin_url('admin-ajax.php') ?>',
              type: 'POST',
              data: fd,
              contentType: false,
              processData: false,
              success: function(response) {
                if (response.status === 200) {
                  const isActive = response.info.isActive === '1';
                  const requireMMS = response.info.requireMMS === '1';
                  $('#equipmentModalHeader').html('Update ' + response.info.itemName);
                  $('#equipmentName').val(response.info.itemName);
                  $('#equipmentRedirect').val(response.info.redirect);
                  $('#equipmentActive').prop('checked', isActive);
                  $('#equipmentMMS').prop('checked', requireMMS);
                  $('#equipmentVendor').val([response.info.vendorID]).trigger('change');
                  $('#equipmentDepartment').val([response.info.department]).trigger('change');
                } else {
                  $('#equipmentModal').modal('hide');
                  equipmentPageMessage(response.msg, 'alert-danger');
                }
              }
            });

          }
        });
        $('#equipmentTable tbody').on('click', '.statusButton', function(e) {
          const data = e.target.dataset;
          let fd = new FormData();
          fd.append('action', 'supportChangeEquipmentStatus');
          fd.append('equipmentID', data.equipmentId);
          fd.append('status', data.status);
          jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php') ?>',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response) {
              if (response.status === 200) {
                myTable.ajax.reload();
                equipmentPageMessage(response.msg, 'alert-success');
              } else {
                equipmentPageMessage(response.msg, 'alert-danger');
              }
            }
          });
        });
        $('#saveEquipmentButton').click(function() {
          let fd = new FormData();
          fd.append('action', 'supportChangeEquipment');
          fd.append('equipmentID', equipmentID);
          fd.append('department', $('#equipmentDepartment').val());
          fd.append('itemName', $('#equipmentName').val());
          fd.append('isActive', $('#equipmentActive').prop('checked'));
          fd.append('requireMMS', $('#equipmentMMS').prop('checked'));
          fd.append('vendorID', $('#equipmentVendor').val());
          fd.append('redirect', $('#equipmentRedirect').val());
          jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php') ?>',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response) {
              if (response.status === 200) {
                myTable.ajax.reload();
                equipmentPageMessage(response.msg, 'alert-success');
                $('#equipmentModal').modal('hide');
              } else {
                $('#serverMessageEquipmentModal').addClass('alert-danger');
                $('#serverMessageEquipmentModal').html(response.msg);
              }
            }
          });
        });
        $('#saveCommonIssueButton').click(function(){
          issuesSave = [];
          if($('#commonIssueNameNew').val()){
            issuesSave.push({
              issueTitle: $('#commonIssueNameNew').val(),
              itemId: equipmentID,
              isActive: $('#commonIssueActiveNew').prop('checked'),
              isEmergency: $('#commonIssueEmergencyNew').prop('checked'),
              vendorID: $('#commonIssueVendorNew').val(),
              issueID: 0
            });
          }
          if($('#commonIssueEmergencyNew').prop('checked') && !$('#commonIssueVendorNew').val()){
            alert($('#commonIssueNameNew').val() + ' has been identified as an emergency. You must select a vendor.');
            return;
          }
          for(let i=0; i<commonIssues.length; i++){
            issuesSave.push({
              issueTitle: $('#commonIssueName' + commonIssues[i].issueID).val(),
              itemId: equipmentID,
              isActive: $('#commonIssueActive' + commonIssues[i].issueID).prop('checked'),
              isEmergency: $('#commonIssueEmergency' + commonIssues[i].issueID).prop('checked'),
              vendorID: $('#commonIssueVendor' + commonIssues[i].issueID).val(),
              issueID:  commonIssues[i].issueID
            });
            if($('#commonIssueEmergency' + commonIssues[i].issueID).prop('checked') && !$('#commonIssueVendor' + commonIssues[i].issueID).val()){
              alert($('#commonIssueName' + commonIssues[i].issueID).val() + ' has been identified as an emergency. You must select a vendor.');
              return;
            }
          }
          let fd = new FormData();
          fd.append('action', 'supportSaveCommonIssues');
          fd.append('equipmentID', equipmentID);
          fd.append('data', JSON.stringify(issuesSave));
          jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php') ?>',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response) {
            }
          });
          $('#commonIssueModal').modal('hide');
          equipmentPageMessage("Common Issue List Updated", 'alert-success');
        });
        $('#equipmentModal').on('hidden.bs.modal', function(event) {
          $('#equipmentModalHeader').html('');
          $('#equipmentName').val('');
          $('#equipmentRedirect').val('');
          $('#equipmentActive').prop('checked', false);
          $('#equipmentMMS').prop('checked', false);
          $('#equipmentVendor').val([]).trigger('change');
          $('#equipmentDepartment').val([]).trigger('change');

        });
        $('#serverMessageEquipmentContainer').on('closed.bs.alert', function() {
          $('#serverMessageEquipmentContainer').removeClass('alert-danger');
          $('#serverMessageEquipment').html('');
        });
      });
    </script>
    <h2>Edit Equipment</h2> <a href="#" id="addEquipmentModal" class="add-new-h2">Add New</a>

    <div class="container-fluid">
        <div class="row" style="width: 100%;">
            <div class="alert" style="width: 100%;" id="serverMessageEquipmentContainer">
                <div id="serverMessageEquipment"></div>
            </div>
        </div>
        <div class="row">
            <div class="container">
                <table id="equipmentTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr style="background-color: #0e2244; color: #ffffff; text-align: center; font-weight: bold;">
                        <th>Department</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="modal" tabindex="-1" id="equipmentModal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="equipmentModalHeader"></h5>
                    <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row" style="width: 100%;">
                            <div class="alert" style="width: 100%;" id="serverMessageEquipmentModal"></div>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <label for="equipmentName">Name</label>
                                <input type="text" class="form-control" id="equipmentName">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-6">
                                <label for="equipmentDepartment">Department</label>
                                <select class="custom-select" id="equipmentDepartment">
                                    <option value="">Choose One</option>
                                    <?php
                                    foreach ($departments as $d) {
                                        ?>
                                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="equipmentVendor">Vendor</label>
                                <select class="custom-select" id="equipmentVendor">
                                    <option value="">Choose One</option>
                                    <?php
                                    if ($vendors) {
                                        foreach ($vendors as $v) {
                                            ?>
                                            <option value="<?php echo $v->contactID; ?>"><?php echo $v->platform; ?></option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row" style="padding-top: .5em;">
                            <div class="col-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="equipmentActive">
                                    <label class="custom-control-label" for="equipmentActive">Active</label>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="equipmentMMS">
                                    <label class="custom-control-label" for="equipmentMMS">Require MMS</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <label for="equipmentRedirect">Redirect Link</label>
                                <input type="text" class="form-control" id="equipmentRedirect">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closeModal" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveEquipmentButton">Save</button>
                    <button class="btn btn-success" type="button" id="buttonSpin" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <span class="sr-only">Loading...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" tabindex="-1" id="commonIssueModal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commonIssueModalHeader"></h5>
                    <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row" style="width: 100%;">
                            <div class="alert" style="width: 100%;" id="serverMessageCommonIssueModal"></div>
                        </div>
                        <div class="accordion" id="commonIssueModalAccordion" style="width: 100%;">
                            <div class="row" style="width: 100%; border: #9d9d9d solid 1px;">
                                <h2 class="" style="background-color: #f7f7f7;width: 100%;">
                                    <button class="btn btn-link btn-block text-left" type="button"
                                            data-toggle="collapse" data-target="#collapseOne" aria-expanded="true"
                                            aria-controls="collapseOne">
                                        Add New Common Issue
                                    </button>
                                </h2>
                                <div id="collapseOne" class="collapse" aria-labelledby="headingOne"
                                     data-parent="#commonIssueModalAccordion" style="width: 95%;margin: auto;">
                                        <div class="form-row">
                                            <div class="col">
                                                <label for="commonIssueNameNew">Name</label>
                                                <input type="text" class="form-control" id="commonIssueNameNew">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="col">
                                                <label for="commonIssueVendorNew">Vendor</label>
                                                <br>
                                                <select class="custom-select" id="commonIssueVendorNew">
                                                    <option value="">Choose One</option>
                                                    <?php
                                                    if ($vendors) {
                                                        foreach ($vendors as $v) {
                                                            ?>
                                                            <option value="<?php echo $v->contactID; ?>"><?php echo $v->platform; ?></option>
                                                            <?php
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row" style="padding-top: .5em; padding-bottom: .5em;">
                                            <div class="col-3">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="commonIssueActiveNew" checked>
                                                    <label class="custom-control-label" for="commonIssueActiveNew">Active</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="commonIssueEmergencyNew">
                                                    <label class="custom-control-label" for="commonIssueEmergencyNew">Emergency</label>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closeModal" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveCommonIssueButton">Save</button>
                </div>
            </div>
        </div>
    </div>
<?php
