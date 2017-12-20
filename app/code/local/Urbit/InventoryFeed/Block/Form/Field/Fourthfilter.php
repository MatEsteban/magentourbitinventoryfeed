<?php

/**
 * Class Urbit_InventoryFeed_Block_Form_Field_FourthFilter
 */
class Urbit_InventoryFeed_Block_Form_Field_FourthFilter extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $value = $element->getEscapedValue();
        $name  = $element->getName();
        $id    = $element->getHtmlId();

        ob_start();

        ?>
        <div class="ff-row">
            <div class="ff-col">
                <select name=<?= $name ?>[from]" id="<?= $id ?>" size="8" multiple="multiple">
                </select>
            </div>

            <div class="ff-col ff-buttons">
                <button type="button" id="<?= $id ?>_rightAll">Select all</button>
                <button type="button" id="<?= $id ?>_rightSelected">Select</button>
                <button type="button" id="<?= $id ?>_leftSelected">Remove</button>
                <button type="button" id="<?= $id ?>_leftAll">Remove all</button>
            </div>

            <div class="ff-col">
                <input type="hidden" name="<?= $name ?>" value="">
                <select name="<?= $id ?>[]" id="<?= $id ?>_to" size="8" multiple="multiple"></select>
            </div>

            <select name="<?= $name ?>[]" id="<?= $id ?>_to_hidden" size="8" multiple="multiple" style="display: none"></select>
        </div>
        <div class="loader">
            <div class="spinner"></div>
            <div class="background"></div>
        </div>
        <script>
            jQuery(function($) {
                //update hidden result multiselect when right multiselect has been changed
                var updateSelect = function(){
                    var $target = $("#<?= $id ?>_to_hidden");
                    $target.html("");
                   
                    $("#<?= $id ?>_to").find("option").each(function(){
                        $(this).clone().prop("selected", true).appendTo($target);
                    });
                };

                $('#<?= $id ?>').multiselect({
                    search: {
                        left: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
                        right: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
                        submitAllRight: true
                    },
                    fireSearch: function(value) {
                        return value.length > 3;
                    },
                    afterMoveToLeft: updateSelect,
                    afterMoveToRight: updateSelect,
                });

                getRightOptions();

                $("#inventoryfeed_config_filter_tag, #inventoryfeed_config_filter_category").change(getLeftOptions);
                $("#inventoryfeed_config_filter_stock").on('input', getLeftOptions);

                //get options for left multiselect by ajax call
                function getLeftOptions() {
                    var selectedTags = $("#inventoryfeed_config_filter_tag").val();
                    var selectedCollects = $("#inventoryfeed_config_filter_category").val();
                    var stock = $("#inventoryfeed_config_filter_stock").val();
                    var result = $.map($("#<?= $id ?>[to] option"), function(option) {
                        return option.value;
                    });
                    
                    var $loading = $('.loader').hide();

                    $.ajax({
                        beforeSend: function () {
                            $loading.show();
                        },
                        type: "POST",
                        url: "<?php echo $this->getUrl('urbit_inventoryfeed/index'); ?>",
                        data: {
                            action:"filter_handler",
                            tags: selectedTags,
                            collects: selectedCollects,
                            stock: stock,
                            result: result,
                            ajax_left: true
                        }
                    }).done(function( data ) {
                        var json = JSON.stringify(data);
                        var products = JSON.parse(json);
                        var $leftSelectBox = $("#<?= $id ?>");
                        var rightMultiselectValues = $.map($('#inventoryfeed_config_filter_product_id_to option'), function (e) {
                            return e.value;
                        });

                        $leftSelectBox.empty();

                        $.each(products, function (key, value) {
                            //remove duplicates
                            if (jQuery.inArray(value.id, rightMultiselectValues) !== -1) {
                                return;
                            }

                            $leftSelectBox.append($("<option></option>")
                                .attr("value", value.id).text(value.id + " - "+ value.name));
                        });

                        $loading.hide();
                    });
                }
                
                //get options for right multiselect by ajax call
                function getRightOptions() {
                     $.ajax({
                        type: "POST",
                        url: "<?php echo $this->getUrl('urbit_inventoryfeed/index'); ?>",
                        data: {
                            ajax_right: true
                        }
                    }).done(function( data ) {
                        var json = JSON.stringify(data);
                        var options = JSON.parse(json);
                        var $rightSelectBox = $("#inventoryfeed_config_filter_product_id_to");

                        $rightSelectBox.empty();
                        
                        if (options) {
                            $.each(options, function (key, value) {
                                $rightSelectBox.append($("<option></option>")
                                .attr("value", value.id).text(value.id + " - "+ value.name));
                            });
                        }

                        updateSelect();
                        getLeftOptions();
                    });
                }
            });
        </script>
        <?php

        return ob_get_clean();
    }

}
