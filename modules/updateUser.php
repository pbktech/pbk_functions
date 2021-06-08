<?php
putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/silicon-will-769-d21d82edbb3a.json');
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes(array('https://www.googleapis.com/auth/admin.directory.user', 'https://www.googleapis.com/auth/admin.directory.group', 'https://www.googleapis.com/auth/admin.directory.group.member'));
$client->setSubject("jon@theproteinbar.com");
$dir = new Google_Service_Directory($client);
$groups = $dir->groups->listGroups(array('domain' => 'theproteinbar.com', 'maxResults' => 500));
$list = $dir->users->listUsers(array('domain' => 'theproteinbar.com', 'maxResults' => 500));
$google = array("users" => array(), "groups" => array(), "addresses" => array());
global $wpdb;
$google["addresses"][] = array("name" => "Store Support Center", "address" => "231 S LaSalle St. Suite 2100, Chicago, IL 60604");
$results = $wpdb->get_results('SELECT restaurantID, restaurantName, phone, email, CONCAT(address1, " ", address2, " ", city, ", ", state, " ", zip) as \'address\' FROM pbc_pbrestaurants pp WHERE isOpen = 1 ');
if ($results) {
    foreach ($results as $r) {
        $google["addresses"][] = array("name" => $r->restaurantName, "address" => $r->address, "email" => $r->email, "phone" => $r->phone, "id" => $r->restaurantID);
    }
}
foreach ($list->getUsers() as $l) {
    if ($l->orgUnitPath !== "/PB Connect" && $l->orgUnitPath !== "/System Devices" && $l->orgUnitPath !== "/Store Support") {
        $title = "";
        if (!empty($l->organizations[0]['title'])) {
            $title = $l->organizations[0]['title'];
        }
        $department = "";
        if (!empty($l->organizations[0]['department'])) {
            $department = $l->organizations[0]['department'];
        }
        $phone = "";
        if (!empty($l->phones[0]['value'])) {
            $phone = $l->phones[0]['value'];
        }
        $google["users"][] = array(
            "email" => $l->primaryEmail,
            "firstName" => $l->name->givenName,
            "lastName" => $l->name->familyName,
            "title" => $title,
            "department" => $department,
            "orgUnit" => $l->orgUnitPath,
            "phone" => $phone
        );
    }
}
foreach ($groups->getGroups() as $g) {
    $google["groups"][] = array("id" => $g->email, "text" => $g->name . " " . "(" . $g->email . ")");
}
?>
    <div class="container">
        <div class="row">
            <div class="col"><div id="message"></div></div>
        </div>
        <div class="row" id="buttons">
            <div class="col">
                <button class="btn btn-primary" id="newUser">New User</button>
            </div>
            <div class="col">
                <button class="btn btn-secondary" id="updateUser">Update User</button>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptHeader">Please Choose an Employee to Update</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container" id="receiptBody">
                        <select class="js-example-basic-single form-control" name="userToUpdate" style="width: 100%;">
                            <option value="">Choose One</option>
                            <?php
                            for ($i = 0; $i < count($google["users"]); $i++) {
                                echo "<option value='" . $i . "'>" . $google["users"][$i]['firstName'] . " " . $google["users"][$i]['lastName'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div id="processingDiv" style="display: none;">
    <img src='<?php echo PBKF_URL;?>/assets/images/processing.gif' style='height:92px;width:92px;' />
</div>
    <div class="container-fluid" id="updateDiv" style="display: none;padding-top: 1em;">
        <div class="row">
            <div class="col-6">
                <div style="padding-left: 10px;background:#ffffff">
                    <div style="float:left;padding:1px 10px 0 0">
                        <img src="https://c2.theproteinbar.com/PBK-Logo_Tertiary_Full-Color_92.png" alt=""
                             style="padding:3px 0px 0px 10px;">
                    </div>
                    <div style="float:left;padding:1px 10px 0 0">
                        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#0e2244;margin:0;text-transform:capitalize">
                            <span id="name">Name</span><br>
                            <span style="color: #f36c21" >Protein Bar & Kitchen</span>
                        </div>
                        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;margin:0px;color: #93c47d;">
                            <span id="title">Title</span>
                        </div>
                        <div style="font-style:normal;color:#404040;font-size:12px;line-height:20px;font-family:Arial,Helvetica,sans-serif;font-size-adjust:none;margin:0px">
                            <span id="address">Address</span>
                        </div>
                        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
                            <span>P</span> <span style="font-weight:normal;color:#404040;" id="phone">Phone</span>
                        </div>
                        <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
                            <span>E</span> <span style="font-weight:normal;color:#404040;" id="email">Email</span>
                        </div>
                    </div>
                </div>
                <div style="clear:both"></div>
            </div>
            <div class="col-6">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="locationSelect">Location</label>
                            <select class="js-example-basic-single form-control toClear" name="" id="locationSelect"
                                    style="width: 100%;">
                                <option value="">Choose One</option>
                                <?php
                                for ($i = 0; $i < count($google["addresses"]); $i++) {
                                    echo "<option value='" . $i . "'>" . $google["addresses"][$i]['name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control toClear" data-name="name" id="name"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="Email">Email</label>
                            <input type="text" class="form-control toClear" data-name="email" id="Email"/>
                        </div>
                    </div>
                </div>
                <div class="row hiddenLine" style="display: none;">
                    <div class="col">
                        <div class="form-group">
                            <label for="Phone">Phone</label>
                    <input type="text" class="form-control toClear" data-name="phone" id="Phone"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group" id="title" >
                            <label for="titleSelect">Title</label>
                            <span id="titleSelector" style=" display: none;">
                    <select class="js-example-basic-single form-control toClear titleSelect" id="titleSelect"
                            style="width: 100%;" >
                        <option value="">Choose One</option>
                        <option value="General Manager">General Manager</option>
                        <option value="Assistant Manager">Assistant Manager</option>
                    </select>
                    </span>
                            <span id="titleInputField" style=" display: none;">
                    <input type="text" class="form-control toClear titleInput" data-name="titleInput" id="titleInput" style="width: 100%;"/>
                    </span>
                </div>
            </div>
        </div>
        </div>
        <div class="row" style="width: 100%;">
            <div class="col">
                        <div class="form-group">
                            <label for="notify">Who to Notify?</label>
                    <input type="text" class="form-control toClear" data-name="notify" id="notify"/>
                        </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="googleGroups"><strong>Select Groups</strong></label><br>
                    <select style="width: 100%;" class="js-example-basic-multiple form-control" name="googleGroups[]"
                            id="googleGroups" multiple="multiple"></select>
                </div>
            </div>
        </div>
        <div class="row">
            <button class="btn btn-primary saveButton">Save</button>
            <button class="btn btn-secondary cancelButton">Cancel</button>
        </div>
    </div>
<?php
add_action('wp_footer', function () use ($google) {
    gmailUserJQ($google);
});
function gmailUserJQ($google) {
    ?>
    <script>
      const allUsers = <?php print_r(json_encode($google['users'])); ?>;
      const locations =  <?php print_r(json_encode($google['addresses'])); ?>;
      const message = {};
      let locationID;

      function clearInputs() {
        $('.toClear').each(function(index) {
          $(this).val('');
        });
        $('.hiddenLine').hide();
        $('#titleInputField').hide();
        $('#titleSelector').hide();
        $('#name').html('Name');
        $('#location').html('Location');
        $('#title').html('Title');
        $('#address').html('Address');
        $('#phone').html('Phone');
        $('#email').html('Email');
      }

      jQuery(document).ready(function() {
        $('.js-example-basic-single').select2();
        $('.js-example-basic-multiple').select2({
          data:<?php print_r(json_encode($google['groups'])); ?>
        });
        $('#locationSelect').change(function(e) {
          const location = locations[e.target.value];
          locationID = location.id;
          clearInputs();
          if (location.name === 'Store Support Center') {
            $('.hiddenLine').show();
            $('#titleInputField').show();
            $('#titleSelector').hide();
          } else {
            $('#phone').html(location.phone);
            $('#titleInputField').hide();
            $('#titleSelector').show();
          }
          $('#address').html(location.address);
        });
        $('#titleSelect').change(function(e){
          $('#title').html(e.target.value);
        });
        $('#newUser').click(function() {
          $('#updateDiv').show();
          $('#buttons').hide();
        });
        $('.cancelButton').click(function() {
          clearInputs();
          $('#updateDiv').hide();
          $('#buttons').show();
        });
        $('#updateUser').click(function() {
          $('#buttons').hide();
          $('#viewModal').modal('show');
        });
        $('.toClear').blur(function(e) {
          const line = e.target;
          let suffix = '';
          if(line.dataset.name === 'email'){
            suffix = '@theproteinbar.com';
          }
          $('#' + line.dataset.name).html(line.value + suffix);
        });
        $('.saveButton').click(function() {
                $('#updateDiv').hide();
                $('#processingDiv').show();
          const info = [];
          $('.toClear').each(function(index) {
            info.push({ field: $(this).data('name'), value: $(this).val() });
          });
          let title;
          if ($('#titleSelect').val()){
            title = $('#titleSelect').val();
          }else {
            title = $('#titleInput').val();
          }
          info.push({field: 'title', value: title});
          info.push({field: 'restaurant', value: locationID});
          info.push({field: 'groups', value: $('#googleGroups').val()})
          confirm = {
            action: 'add_google_user',
            data: info
          };
          jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php');?>',
            type: 'POST',
            data: confirm,
            success: function(r) {
              if(r.status === 200){
                  clearInputs();
              }else{
                $('#updateDiv').show();
              }
              $('#processingDiv').hide();
              $('#message').addClass(r.class).html(r.text);
            }
          });
        });
      });
    </script>
    <?php
}