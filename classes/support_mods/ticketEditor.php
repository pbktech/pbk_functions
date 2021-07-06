<?php
global $wpdb;
$return = array();
$cu = wp_get_current_user();
$query = "SELECT restaurantID as 'id', restaurantName as 'text' FROM pbc_pbrestaurants WHERE isOpen = 1 ";
if (!in_array("administrator", $cu->roles) && !in_array("editor", $cu->roles)  && !in_array("author", $cu->roles)) {
    $query.= "restaurantID IN (SELECT restaurantID FROM pbc_pbr_managers WHERE managerID = '" . $cu->ID . "')";
}
$result = $wpdb->get_results($query);

?>
<div class="container">
    <div class="row">
        <div class="col-2">
            <div class="form-group">
                <label for="restaurant">Restaurant</label>
                <?php
                if(count($result) === 1) {
                    ?>
                    <input disabled type="text" class="form-control" id="restaurant" value="<?php echo $result[0]->text;?>">
                    <input type="hidden" id="restaurantID" value="<?php echo $result[0]->id;?>">
                    <?php
                }else{
                    ?>
                        <script>
                          $(document).ready(function() {
                            $('#restaurantID').select2({
                              placeholder: {
                                id: '-1', // the value of the option
                                text: 'Choose a restaurant'
                              },
                              allowClear: true,
                              data: <?php echo json_encode($result);?>
                            });
                          });
                        </script>
                    <select class="form-control" name="restaurantID" id="restaurantID" style="width: 100%;">
                    </select>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php
