<?php
global $wpdb;
$support = new PBKSupport();
$departments = $support->getDepartments();
$contacts = [];
$restaurant = new Restaurant();
$allUsers=$restaurant->getUserNames();
foreach ($departments as $d){
    $contacts[$d] = [];
}
$sc = $wpdb->get_results("SELECT ID, display_name, department, contactID FROM pbc_users pu , pbc_support_contact psc WHERE psc.userID = pu.ID ");
if($sc){
    foreach ($sc as $c){
        $sc_names = [];
        $contacts[$c->department][] = $c;
    }
}
?>
<script>
  jQuery(document).ready(function() {
      <?php
      foreach ($contacts as $department => $users){
      $selected = "";
         if(!empty($users)){
             $su = [];
             foreach ($users as $u){
                 $su[] = $u->ID;
             }
             $selected = "$('#users".$department."').val(['" . implode("','", $su) . "']).trigger('change');";
         }
        ?>
      $('#users<?php echo $department;?>').select2();
      <?php
        echo $selected;
      }
      ?>
    $('#saveContacts').click(function(){
      $("#serverMessage").html("");
      $("#serverMessageContainer").removeClass("alert-success").removeClass("alert-danger");
      let contacts = {};
      <?php
      foreach ($contacts as $department => $users){
      ?>
        contacts.<?php echo $department;?> = $('#users<?php echo $department;?>').val();
    <?php
        }
    ?>
      let fd = new FormData();
      fd.append('action', 'supportUpdateContacts');
      fd.append('data', JSON.stringify(contacts));
      $.ajax({
        type: 'POST',
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        data: fd,
        contentType: false,
        processData: false,
        success: function(response) {
          if(response.status === 200){
            $("#serverMessageContainer").addClass("alert-success");
          }else{
            $("#serverMessageContainer").addClass("alert-danger");
          }
          $("#serverMessage").html(response.msg);
        }
      });
    });
  });
</script>
<h2>Edit Departments</h2>

<div class="container-fluid">
    <div class="row" style="width: 100%;">
        <div class="alert alert-dismissible fade show" style="width: 100%;" id="serverMessageContainer">
            <div id="serverMessage"></div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
    <div class="row">
        <div class="container">
            <h3>Contacts</h3>
            <?php
            foreach ($contacts as $department => $users){
            ?>
            <div class="row" style="padding: .25em">
                <div class="col">
                    <label for="users<?php echo $department;?>"><strong><?php echo $department;?></strong></label>
                </div>
                <div class="col">
                    <select name='users<?php echo $department;?>[]' class="custom-select multipleSelect" style='width:100%;' id='users<?php echo $department;?>' multiple >
                        <?php
                        foreach($allUsers as $user){
                            echo "<option value='".$user->ID."'>".$user->display_name."</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
    </div>
    <div class="row" style="text-align: right"><button class="btn btn-primary" id="saveContacts">Update Contacts</button> </div>
</div>
<?php