<?php
global $wpdb;
$ticket = new PBKSupportTicket($_REQUEST['id']);

$responses = $wpdb->get_results("SELECT * FROM pbc_support_ticket_responses WHERE ticketID = " . $ticket->getTicketID() . " ORDER BY responseTime DESC");
$issue = $wpdb->get_var("SELECT issueTitle FROM pbc_support_common WHERE issueID = " . $ticket->getItemId());
$mms = $ticket->getMms();
?>
<script>
  const update = {};
  const attachedFiles = [];
  let closeTicket = false;

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
    $('#updateModal').on('hidden.bs.modal', function() {
      $('#finalCost').hide();
    });

    $('#saveButton').click(function() {
      $('#buttonSpin').show();
      $('#saveButton').hide();

      const errors = [];
      if ($('#processing').is(':visible')) {
        errors.push('Please wait for files to finish uploading.');
      }
      if ($('#personName').val() === '') {
        errors.push('Please enter your name.');
        $('#personName').addClass('is-invalid');
      }
      if ($('#issueDescription').val().length < 50) {
        errors.push('Please enter more information in the description.');
        $('#issueDescription').addClass('is-invalid');
      }
      if(closeTicket){
        update.close = closeTicket;
        update.repairCost = parseFloat($('#repairCost').val());
      }
      if (errors.length) {
        $('#buttonSpin').hide();
        $('#saveButton').show();
        $('#ticketResponse').addClass('alert-danger');
        $('#ticketResponse').append(errors.join('<br>'));
      } else {
        let fd = new FormData();
        update.ticketID = '<?php echo $_REQUEST['id'];?>';
        update.attachedFiles = attachedFiles;
        update.personName= $('#personName').val();
        update.issueDescription= $('#issueDescription').val();
        fd.append('action', 'updateTicket');
        fd.append('data', JSON.stringify(update));
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
              $('#updateModal').modal('hide');
              location.reload();
            } else {
              $('#ticketResponse').addClass('alert-danger');
              $('#ticketResponse').append(response.message.join('<br>'));
            }
          }
        });
      }
    });
    $('.btn-outline-info').click(function(){
      $('#updateModal').modal('show');
    });
    $('.btn-outline-warning').click(function(){
      $('#updateModal').modal('show');
      closeTicket = true;
      $('#finalCost').show();
    });
  });
</script>
<h2>Update Your Issue</h2>
<?php
if($ticket->getStatus() === "Closed"){
?>
<div class="alert alert-info">This ticket has been closed.</div>
    <?php
}else{
    ?>
<div class="btn-group" role="group">
    <button class="btn btn-outline-info">Add An Update</button>
    <button class="btn btn-outline-warning">Close This Ticket</button>
</div>
    <?php
}
    ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-6">
            <strong for="name">Reported By</strong><p  id="name"><?php echo $ticket->getPersonName();?></p>
        </div>
        <div class="col-6">
            <strong for="restaurant">Restaurant</strong><p  id="restaurant"><?php echo $wpdb->get_var("SELECT restaurantName FROM pbc_pbrestaurants WHERE restaurantID = ". $ticket->getRestaurantID());?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <strong for="device">Equipment/Device</strong><p id="device"><?php echo $wpdb->get_var("SELECT itemName FROM pbc_support_items WHERE itemID = " . $ticket->getAreaID());?></p>
        </div>
        <div class="col-6">
            <strong for="issue">Reported Issue</strong><p id="issue"><?php echo empty($issue) ? " --- " : $issue;?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-4">
            <strong for="device">Make</strong><p id="name"><?php echo empty($mms['make']) ? " --- " : $mms['make']; ?></p>
        </div>
        <div class="col-4">
            <strong for="device">Model</strong><p id="name"><?php echo empty($mms['model']) ? " --- " : $mms['model']; ?></p>
        </div>
        <div class="col-4">
            <strong for="device">Serial</strong><p id="name"><?php echo empty($mms['serial']) ? " --- " : $mms['serial']; ?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <strong for="device">Opened On</strong><p id="name"><?php echo date("m/d/Y h:i a", strtotime($ticket->getOpenTime())); ?></p>
        </div>
        <div class="col-6">
            <strong for="issue">Total Time</strong><p id="elapsed"></p>
        </div>
    </div>
    <div class="row">
        <strong>Ticket Updates</strong>
        <div class="col-12">
            <div style="height: 300px; overflow: auto;">
                <?php
                if($responses){
                    $files = [];
                    foreach ($responses as $r){
                        if(!empty($r->responseFiles)){
                            $attachedFiles = json_decode($r->responseFiles);
                            foreach ($attachedFiles as $f){
                                $files[] = "<a href='" .$f->link. "' target='_blank'>" .$f->name. "</a>";
                            }
                        }
                        ?>
                        <blockquote class="blockquote text-left" style="padding-bottom: 1em;">
                            <p class="mb-0"><?php echo nl2br($r->responseText);?></p>
                            <?php
                            if(!empty($files)){
                            ?>
                            <footer class="blockquote-footer">Attached Files: <?php echo implode(", ", $files);?></footer>
                            <?php
                            }
                            ?>
                        </blockquote>
                        <span class="text-muted"><?php echo date("m/d/Y h:i a", strtotime($r->responseTime)); ?> by <?php echo $r->responseName;?></span>
                        <?php
                    }
                }

                ?>
            </div>
        </div>
    </div>

</div>

<div class="modal" tabindex="-1" id="updateModal">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add An Update</h5>
                <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert" id="ticketResponse"></div>
                <form class="needs-validation" novalidate >
                    <div class="form-row">
                        <div class="col-12">
                            <label for="personName">Your Name</label>
                            <input type="text" class="form-control" id="personName" value="" >
                        </div>
                    </div>
                    <div class="form-row" id="finalCost" style="display: none;">
                        <div class="col-12">
                            <label for="personName">Cost of Repair</label>
                            <input type="text" class="form-control" id="repairCost" value="" >
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-12">
                            <label for="issueDescription">Your Update</label>
                            <textarea id="issueDescription" required style="width: 100%;" rows="10" class="form-control"
                                      placeholder="Please be as descriptive as possible. If you've reached out to a vendor, lets us know when you called and the ticket number."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">

                            <!-- Our markup, the important part here! -->
                            <div id="drag-and-drop-zone" class="dm-uploader p-5" style="border: dashed;">
                                <div class="upload-response"></div>
                                <div class="btn btn-primary btn-block mb-5" id="uploadButton">
                                    Select Files
                                    <input type="file" name="files[]" class="files-data form-control" multiple/>
                                </div>
                                <div class="text-center" style="display: none;" id="processing">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-header">
                                    File List
                                </div>

                                <ul class="list-unstyled p-2 d-flex flex-column col" id="files">
                                    <li class="text-muted text-center empty">No files uploaded.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
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

<?php
