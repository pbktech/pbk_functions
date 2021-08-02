<?php
global $wpdb;
$items = array();
$supportItems = $wpdb->get_results("SELECT itemID, itemName FROM pbc_support_items WHERE isActive = 1 order by itemName");
if ($supportItems) {
    $items[] = array("id" => -1, "text" => "Select an item to begin your report");
    foreach ($supportItems as $item) {
        $allIssues = [];
        $commonIssues = $wpdb->get_results("SELECT issueID, issueTitle, isEmergency FROM pbc_support_common WHERE itemID = " . $item->itemID);
        if ($commonIssues) {
            foreach ($commonIssues as $i) {
                $faqSteps = $wpdb->get_results("SELECT * FROM pbc_support_trouble_steps psts, pbc_support_trouble_assign psta WHERE psta.issueID = " . $i->issueID . " AND psta.stepID = psts.stepID ORDER BY psta.stepOrder");
                $allIssues[] = ["ci" => $i, "steps" => $faqSteps];
            }
        }
        $items[] = array("id" => $item->itemID, "text" => $item->itemName, "commonIssues" => $allIssues);
    }
}
$return = array();
?>
    <script>
      const ticketData = {};

      function clearInputs() {

      }

      function createWorkArea(html) {
        $('#workArea').remove();
        $('.modal-body').append($('<div/>', {
          id: 'workArea',
          className: 'container-fluid',
          html: html
        }));
      }

      function ticketStart() {
        const html = $('#ticketContainer').html();
        createWorkArea(html);
      }

      const problem = {};
      const attachedFiles = [];
      const allIssues =  <?php echo json_encode($items);?>;
      $(document).ready(function() {
        $('body').on('change', '.files-data', function(e) {
          e.preventDefault;
          $('#uploadButton').hide();
          $('#processing').show();
          let fd = new FormData();
          let files_data = $('.files-data');

          $.each($(files_data), function(i, obj) {
            $.each(obj.files, function(j, file) {
              fd.append('files[' + j + ']', file);
            });
          });
          fd.append('action', 'uploadPBKImage');
          let link = "";
          $.ajax({
            type: 'POST',
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response) {
              $('.empty').remove();
              for (let i = 0; i < response.length; i++) {
                attachedFiles.push(response[i]);
                if(response[i].link){
                  link = '<a href="' + response[i].link + '" target="_blank">' + response[i].name + '</a>'
                } else {
                  link = response[i].name;
                }
                $('#files').append('<li>' + link + '</li>');
              }
              $('#uploadButton').show();
              $('#processing').hide();
            }
          });
        });
        $('.js-example-basic-single').select2({
          placeholder: {
            id: '-1', // the value of the option
            text: 'Select an item to begin your report'
          },
          theme: 'bootstrap4',
          allowClear: true,
          data: allIssues
        });

        $('#issueSelector').on('select2:select', function(e) {
          const data = e.params.data;
          const issues = allIssues.filter(item => item.id === data.id);
          const issue = issues[0];
          problem.area= data.id;
          $('#issueModal').modal('show');
          if (issue.commonIssues.length) {
            let html = '';

            for (let i = 0; i < issue.commonIssues.length; i++) {
              html += '<div class="row"><button type="button" class="btn btn-link btn-lg btn-block commonIssue" data-itemid="' + data.id + '" data-issueid="' + i + '" >' + issue.commonIssues[i].ci.issueTitle + '</button></div>';
            }
            createWorkArea(html);
          } else {
            ticketStart();
          }

          $('.modal-header').html('<h3>' + data.text + '</h3>');

          $('.commonIssue').click(function(e) {
            const issueID = e.target.dataset.issueid;
            const thisIssue = issue.commonIssues[issueID].ci;
            const steps = issue.commonIssues[issueID].steps;
            problem.issue= thisIssue.issueID ;
            if (thisIssue.isEmergency === '1') {
              let html;
              createWorkArea('<div class="container"><div class="row" style="width: 100%;"><div class="alert alert-danger" style="text-align: center;">THIS IS AN EMERGENCY PLEASE CONTACT THE VENDOR BELOW</div><div class="alert alert-warning" style="text-align: center;">PBK Technology <a href="tel: 312-300-2587">312-300-2587</a> </div></div></div>');
              return;
            }
            if (steps.length) {

            } else {
              ticketStart();
            }
            $('.closeModal').click(function() {
              /*delete data, issues, issue, issueID, thisIssue, steps;*/
            });
          });
        });
        $('#issueModal').on('hidden.bs.modal', function() {
          $('#issueSelector').val(null).trigger('change');
          $('#workArea').remove();
          $('#workArea').show();
          $('#ticketContainer').hide();
          $('.modal-header').html('');
          $('.modal-body').html('');
        });
        $('#saveButton').click(function() {
          $('#buttonSpin').show();
          $('#saveButton').hide();

          const errors = [];
          if ($('#processing').is(':visible')) {
            errors.push('Please wait for files to finish uploading.');
          }
          if ($('#restaurantID').val() === '-1') {
            errors.push('Please select a restaurant.');
            $('#restaurantID').addClass('is-invalid');
          }
          if ($('#personName').val() === '') {
            errors.push('Please enter your name.');
            $('#personName').addClass('is-invalid');
          }
          if ($('#issueDescription').val().length < 50) {
            errors.push('Please enter more information in the description.');
            $('#issueDescription').addClass('is-invalid');
          }
          if (errors.length) {
            $('#buttonSpin').hide();
            $('#saveButton').show();
            $('#ticketResponse').addClass('alert-danger');
            $('#ticketResponse').append(errors.join('<br>'));
          } else {
            let fd = new FormData();

            problem.attachedFiles = attachedFiles;
            problem.restaurantID= $('#restaurantID').val();
            problem.personName= $('#personName').val();
            problem.issueDescription= $('#issueDescription').val();

            fd.append('action', 'startTicket');
            fd.append('data', JSON.stringify(problem));
            $.ajax({
              type: 'POST',
              url: '<?php echo admin_url('admin-ajax.php'); ?>',
              data: fd,
              contentType: false,
              processData: false,
              success: function(response) {
                $('#buttonSpin').hide();
                $('#saveButton').show();
                if(response.status === 200){
                  $('#issueModal').modal('hide');
                  $('#serverResponse').addClass('alert-success');
                  $('#serverResponse').html("The new ticket has been created.");
                } else {
                  $('#ticketResponse').addClass('alert-danger');
                  $('#ticketResponse').append(response.message.join('<br>'));
                }
              }
            });
          }
        });
        var myTable = $('#myTable').DataTable({
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
        $('#issueModal').on('hidden.bs.modal', function (event) {
          myTable.ajax.reload();
        });
      });
    </script>
    <h2>Report A New Issue</h2>
    <div class="container-fluid">
        <select class="js-example-basic-single form-control" name="issue" id="issueSelector" style="width: 100%;">
        </select>
    </div>
    <div class="alert" id="serverResponse"></div>
    <h2>Currently Open Tickets</h2>
    <div class="container-fluid">
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
    <div class="modal" tabindex="-1" id="issueModal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closeModal" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveButton">Save</button>
                    <button class="btn btn-success" type="button" id="buttonSpin" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <span class="sr-only">Loading...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid" id="ticketContainer" style="display: none;">
        <?php include "ticketEditor.php"; ?>
    </div>
<?php
