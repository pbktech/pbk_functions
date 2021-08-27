<?php
global $wpdb;
$return = array();
$cu = wp_get_current_user();
$query = "SELECT restaurantID as 'id', restaurantName as 'text' FROM pbc_pbrestaurants WHERE isOpen = 1 ";
if (!in_array("administrator", $cu->roles) && !in_array("editor", $cu->roles) && !in_array("author", $cu->roles)) {
    $query .= "AND restaurantID IN (SELECT restaurantID FROM pbc_pbr_managers WHERE managerID = '" . $cu->ID . "')";
}
$result = $wpdb->get_results($query);

?>
    <div class="container-fluid" style="width: 100%;">
            <div class="alert" id="ticketResponse"></div>
        <div class="row">
            <div class="col">
                <div class="form-group" style="width: 100%;">
                    <label for="restaurant">Your Name</label>
                    <input type="text" id="personName" class="form-control" style="width: 100%;"/>
                </div>
            </div>
            <div class="col">
                <div class="form-group" style="width: 100%;">
                    <label for="restaurant">Restaurant</label>
                    <?php
                    if (count($result) === 1) {
                        ?>
                    <input disabled type="text" style="width: 100%;" class="form-control" id="restaurant"
                           value="<?php echo $result[0]->text; ?>">
                    <input type="hidden" id="restaurantID" value="<?php echo $result[0]->id; ?>">
                    <?php
                    }else{
                    ?>
                        <select class="custom-select" name="restaurantID" id="restaurantID" style="width: 100%;">
                            <option value="-1">Choose a restaurant</option>
                            <?php
                                foreach($result as $r){
                                ?>
                            <option value="<?php echo $r->id;?>"><?php echo $r->text;?></option>
                                <?php
                                }
                            ?>
                        </select>
                        <?php
                    }
                    ?>
                </div>
                <div class="w-100"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-4">
                <label for="make">Make<span class="text-muted mmsID" style="font-weight: normal; font-style: italic;"></span></label><input type="text" class="form-control mmsData" id="make" />
            </div>
            <div class="col-4">
                <label for="model">Model<span class="text-muted mmsID" style="font-weight: normal; font-style: italic;"></span></label><input type="text" class="form-control mmsData" id="model" />
            </div>
            <div class="col-4">
                <label for="serial">Serial<span class="text-muted mmsID" style="font-weight: normal; font-style: italic;"></span></label><input type="text" class="form-control mmsData" id="serial" />
            </div>
        </div>
        <div class="row" id="vendorSection" ></div>
        <div class="row">
            <div class="col-12">
                <label for="issueDescription">Issue Description</label>
                <textarea id="issueDescription" style="width: 100%;" rows="10" class="form-control"
                          placeholder="Please be as descriptive as possible. If you've reached out to a vendor, lets us know when you called and the ticket number. A minimum of 50 characters are required"></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-12" style="padding-top: 1em;"><label>File Upload <span class="text-muted" style="font-style: italic; font-weight: normal;">Use the file upload to attach pictures, invoices, quotes or other supporting information.</span></label></div>
        </div>
        <div class="row">
            <div class="col">
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
    </div>
<?php
