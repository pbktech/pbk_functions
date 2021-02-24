<?php
global $wpdb;
global $ret;
$groups = array();
$sections = $wpdb->get_results("SELECT * FROM pbc_public_nutritional_sections ORDER BY viewOrder");
foreach ($sections as $section) {
    $groups[$section->sectionID] = $section->section;
}
$allergens = array("Wheat/Gluten", "Egg", "Peanut", "Tree Nuts", "Dairy", "Soy Protein", "Sesame", "Fish/Shellfish");
$preferences = array("Vegetarian", "Vegan", "Keto", "Paleo");
?>
<div id="message" style="text-align: center;"></div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-3">
                <button class="btn btn-outline-info" id="newItem">Add a New Item</button>
            </div>
            <div class="col-9">
                <select style="width: 100%" class="js-data-example-ajax form-control"></select>
            </div>
        </div>
        <div class="row" style="padding-top: .5rem">
            <div class="col-3">
                <button class="btn btn-outline-secondary" id="downloadList">Export All Items</button>
            </div>
            <div class="col-9">
                <div class="form-group row" id="uploadArea">
                    <label for="uploadSCV">Bulk Upload</label>
                    <div class="col-6">
                        <input type="file" class="form-control-file" id="uploadSCV" name="csv" accept="text/csv">
                    </div>
                    <div class="col-3">
                        <button class="btn btn-outline-success" style="display: none;" disabled id="processUpload"><i class="fas fa-upload"></i></button>
                    </div>
                </div>
                <div id="uploadSpinner" style="display: none;">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid" id="nutritional" style="display: none;">
        <form class="needs-validation" id="nutrition_form" method="post" action="<?php echo home_url(add_query_arg(array(), $wp->request));?>" novalidate>
        <div class="row">
            <h2 id="title"></h2>
            <input type="hidden" name="itemID" id="itemID" value="" />
        </div>
        <div class='row'>
            <div class='col'><label for='itemName'>Item Name</label><br><input class='form-control' required type='text' name='itemName' value='' id='itemName' />
            </div>
            <div class='col'><label for='itemSection'>Category</label><br>
                <select class='form-control' name='itemSection' required id='itemSection'>
                    <option value=''>Choose One</option>
                    <?php
                    foreach ($groups as $id => $name) {
                        echo "<option value='" . $id . "'>" . $name . "</option>";
                    }
                    ?>
                    ?>
                </select>
            </div>
            <div class='col'><label for=''></label><br>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="published">
                    <label class="custom-control-label" for="published">Published?</label>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class='col'><label for='PR'>Protein</label><br><input class='form-control' required type='text'
                                                                       name='itemInfo[PR]' value='' id='PR'/></div>
            <div class='col'><label for='Cal'>Calories</label><br><input class='form-control' required type='text'
                                                                         name='itemInfo[Cal]' value='' id='Cal'/></div>
            <div class='col'><label for='TF'>Total Fat</label><br><input class='form-control' required type='text'
                                                                         name='itemInfo[TF]' value='' id='TF'/></div>
        </div>
        <div class='row'>
            <div class='col'><label for='SF'>Saturated Fat</label><br><input class='form-control' required type='text'
                                                                             name='itemInfo[SF]' value='' id='SF'/>
            </div>
            <div class='col'><label for='TRF'>Trans Fat</label><br><input class='form-control' required type='text'
                                                                          name='itemInfo[TRF]' value='' id='TRF'/></div>
            <div class='col'><label for='CHO'>Cholesterol</label><br><input class='form-control' required type='text'
                                                                            name='itemInfo[CHO]' value='' id='CHO'/>
            </div>
        </div>
        <div class='row'>
            <div class='col'><label for='SOD'>Sodium</label><br><input class='form-control' required type='text'
                                                                       name='itemInfo[SOD]' value='' id='SOD'/></div>
            <div class='col'><label for='NC'>Net Carbs</label><br><input class='form-control' required type='text'
                                                                         name='itemInfo[NC]'  value='' id='NC'/></div>
            <div class='col'><label for='TC'>Total Carbs</label><br><input class='form-control' required type='text'
                                                                           name='itemInfo[TC]' value='' id='TC'/></div>
        </div>
        <div class='row'>
            <div class='col'><label for='DF'>Dietary Fiber</label><br><input class='form-control' required type='text'
                                                                             name='itemInfo[DF]' value='' id='DF'/>
            </div>
            <div class='col'><label for='SG'>Sugars</label><br><input class='form-control' required type='text'
                                                                      name='itemInfo[SG]' value='' id='SG'/></div>
            <div class='col'><label for='toastGUID'>Toast GUID</label><br><input class='form-control' type='text'
                                                                                 name='toastGUID' value=''
                                                                                 id='toastGUID'/></div>
        </div>
        <div class='row'>
            <div class='col'>
                <div class='form-group'>
                    <h4>Allergens</h4>
                    <?php
                    $id = 0;
                    foreach ($allergens as $allergen) {
                        ?>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name='allergens[]'
                                   value='<?php echo $allergen; ?>' id="allergen_<?php echo $id; ?>">
                            <label class="custom-control-label"
                                   for="allergen_<?php echo $id; ?>"><?php echo $allergen; ?></label>
                        </div>
                        <?php
                        $id++;
                    }
                    ?>
                </div>
            </div>
            <div class='col'>
                <div class='form-group'>
                    <h4>Dietary Preferences</h4>
                    <?php
                    $id = 0;
                    foreach ($preferences as $preference) {
                        ?>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name='$preferences[]'
                                   value='<?php echo $preference; ?>' id="preference_<?php echo $id; ?>">
                            <label class="custom-control-label"
                                   for="preference_<?php echo $id; ?>"><?php echo $preference; ?></label>
                        </div>
                        <?php
                        $id++;
                    }
                    ?>
                </div>
            </div>
        </div>
        </form>
        <div>
            <div class="text-center" id='spinner' style='display: none;'>
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <button id="saveItem" class='btn btn-primary'>Save</button>
        </div>
    </div>
<?php
add_action('wp_footer', 'nutritionAJAX');

function nutritionAJAX() { ?>

    <script>
      jQuery(document).ready(function($) {
        const allergen = ['Wheat/Gluten', 'Egg', 'Peanut', 'Tree Nuts', 'Dairy', 'Soy Protein', 'Sesame', 'Fish/Shellfish'];
        const preference = ['Vegetarian', 'Vegan', 'Keto', 'Paleo'];
        let i;
        $('#uploadSCV').change(function() {
          //on change event
          $('#processUpload').show();
          $('#processUpload').prop('disabled', false);
        });
        $('#processUpload').click(function(){
          $('#uploadArea').hide();
          $('#uploadSpinner').show();
          let fd = new FormData();
          let files = $('#uploadSCV')[0].files;
          if(files.length > 0 ) {
            fd.append('file', files[0]);
            fd.append('action', 'import_nutritional');
          $.ajax({
              url: '<?php echo admin_url('admin-ajax.php');?>',
              type: "POST",
              data: fd,
              processData: false,
              contentType: false,
              success: function (result) {
                $('#message').addClass("alert alert-info");
                $('#message').html(result.records + " items have been updated.");
                $('#uploadArea').show();
                $('#uploadSpinner').hide();
              }
            });
          }
        });

        $('#saveItem').click(function(event) {
          let forms = document.getElementsByClassName('needs-validation');
          let isValid = 1;
          let validation = Array.prototype.filter.call(forms, function(form) {
              if (form.checkValidity() === false) {
                event.preventDefault();
                event.stopPropagation();
                isValid =0;
              }
            form.classList.add('was-validated');

          }, false);
          if (isValid === 1) {
            const allergens = [];
            const preferences = [];
            for (i = 0; i < preference.length; i++) {
              if ($('#preference_' + i).prop('checked') === true){
                preferences.push($('#preference_' + i).val());
              }
            }
            for (i = 0; i < allergen.length; i++) {
              if ($('#allergen_' + i).prop('checked') === true){
                allergens.push($('#allergen_' + i).val());
              }
            }
            const itemInfo = {
              PR: $('#PR').val(),
              Cal: $('#Cal').val(),
              TF: $('#TF').val(),
              SF: $('#SF').val(),
              TRF: $('#TRF').val(),
              CHO: $('#CHO').val(),
              SOD: $('#SOD').val(),
              NC: $('#NC').val(),
              TC: $('#TC').val(),
              DF: $('#DF').val(),
              SG: $('#SG').val()
            };
            $('#saveItem').hide();
            $('#spinner').show();
            $.ajax({
              url: '<?php echo admin_url('admin-ajax.php');?>',
              type: 'post',
              data: {
                action: 'add_item_nutritional',
                itemInfo: itemInfo,
                itemID: $('#itemID').val(),
                itemName: $('#itemName').val(),
                itemSection: $('#itemSection').val(),
                published: $('#published').prop('checked'),
                toastGUID: $('#toastGUID').val(),
                allergens: allergens,
                preferences: preferences,
                nonce: '<?php echo wp_create_nonce("add_closure_nonce");?>'
              },
              success: function(response) {
                if (response.status === 200) {
                  $('#message').addClass("alert alert-success");
                } else {
                  $('#message').addClass("alert alert-danger");
                }
                $('#message').html(response.message);
                $('#message').scrollTop();
                $('#saveItem').show();
                $('#spinner').hide();
                setTimeout(function(){
                  $("#message").hide("20000")
                }, 30000);
                $('#nutritional').hide();
                $('.js-data-example-ajax').val(null).trigger('change');
                $('#title').html('Add A New Item');
                $('#itemID').val('');
                $('#itemName').val('');
                $('#itemSection').val('');
                $('#published').prop('checked', false);
                $('#PR').val('');
                $('#Cal').val('');
                $('#TF').val('');
                $('#SF').val('');
                $('#TRF').val('');
                $('#CHO').val('');
                $('#SOD').val('');
                $('#NC').val('');
                $('#TC').val('');
                $('#DF').val('');
                $('#SG').val('');
                $('#toastGUID').val('');
                for (i = 0; i < preference.length; i++) {
                  $('#preference_' + i).prop('checked', false);
                }
                for (i = 0; i < allergen.length; i++) {
                  $('#allergen_' + i).prop('checked', false);
                }
              }
            });
          }
        });
        $('.js-data-example-ajax').select2({
          placeholder: 'Start typing an item name to edit',
          ajax: {
            url: '<?php echo admin_url('admin-ajax.php') ?>?action=get_nutrition_list',
            dataType: 'json',
            data: function(params) {
              var query = {
                item: params.term,
                type: 'public'
              };
              return query;
            }
          }
        });
        $('#downloadList').click(function(){
          $.ajax({
            url: "<?php echo admin_url('admin-ajax.php') ?>?action=export_nutritional",
            dataType: 'text',
            success: function(result) {
              var uri = 'data:application/csv;charset=UTF-8,' + encodeURIComponent(result);
              window.open(uri, 'full_nutritional_info.csv');
            }
          });
        })
        $('.js-data-example-ajax').on('select2:select', function(e) {
          const data = e.params.data;

          $.ajax({
            url: "<?php echo admin_url('admin-ajax.php') ?>?action=get_nutrition_info&item=" + data.id,
            success: function(result) {
              $('#nutritional').show();
              const itemInfo = JSON.parse(result.itemInfo);
              if (itemInfo.allergens) {
                const allergens = itemInfo.allergens.split(', ');
                for (i = 0; i < allergen.length; i++) {
                  $('#allergen_' + i).prop('checked', allergens.includes(allergen[i]));
                }
              }
              if (itemInfo.preferences) {
                const preferences = itemInfo.preferences.split(', ');
                let i;
                for (i = 0; i < preference.length; i++) {
                  $('#preference_' + i).prop('checked', preferences.includes(preference[i]));
                }
              }
              $('#itemName').val(result.itemName);
              $('#itemID').val(result.idpbc_public_nutritional);
              $('#title').html(result.itemName);
              $('#itemSection').val(result.itemSection);
              $('#published').prop('checked', result.published === '1');
              $('#PR').val(itemInfo.PR);
              $('#Cal').val(itemInfo.Cal);
              $('#TF').val(itemInfo.TF);
              $('#SF').val(itemInfo.SF);
              $('#TRF').val(itemInfo.TRF);
              $('#CHO').val(itemInfo.CHO);
              $('#SOD').val(itemInfo.SOD);
              $('#NC').val(itemInfo.NC);
              $('#TC').val(itemInfo.TC);
              $('#DF').val(itemInfo.DF);
              $('#SG').val(itemInfo.SG);
              $('#toastGUID').val(result.toastGUID);
            }
          });
        });
        $('#newItem').click(function() {
          $('#nutritional').show();
          $('.js-data-example-ajax').val(null).trigger('change');
          $('#title').html('Add A New Item');
          $('#itemID').val('');
          $('#itemName').val('');
          $('#itemSection').val('');
          $('#published').prop('checked', false);
          $('#PR').val('');
          $('#Cal').val('');
          $('#TF').val('');
          $('#SF').val('');
          $('#TRF').val('');
          $('#CHO').val('');
          $('#SOD').val('');
          $('#NC').val('');
          $('#TC').val('');
          $('#DF').val('');
          $('#SG').val('');
          $('#toastGUID').val('');
          for (i = 0; i < preference.length; i++) {
            $('#preference_' + i).prop('checked', false);
          }
          for (i = 0; i < allergen.length; i++) {
            $('#allergen_' + i).prop('checked', false);
          }
        });
      });
    </script>

    <?php
}
