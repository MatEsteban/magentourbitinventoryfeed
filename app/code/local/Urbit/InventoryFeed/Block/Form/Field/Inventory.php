<?php

/**
 * Class Urbit_InventoryFeed_Block_Form_Field_Inventory
 */
class Urbit_InventoryFeed_Block_Form_Field_Inventory extends Mage_Adminhtml_Block_System_Config_Form_Field
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


        if (!$value) {
            $value = array(
                'type' => '',
                'table' => '',
                'fields' => array(
                    'product_id' => '',
                    'location'   => '',
                    'qty'        => '',
                ),
            );
        } else {

            $valuesArray = explode(',', $value);
           
            $value = array(
                'type' => isset($valuesArray['0']) ? $valuesArray['0'] : '' ,
                'table' => isset($valuesArray['1']) ? $valuesArray['1'] : '',
                'fields' => array(
                    'product_id' => isset($valuesArray['2']) ? $valuesArray['2']: '',
                    'location'   => isset($valuesArray['2']) ? $valuesArray['3'] : '',
                    'qty'        => isset($valuesArray['4']) ? $valuesArray['4'] : '',
                ),
            );
        }

        /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $tables = $connection->query('show tables')->fetchAll(PDO::FETCH_COLUMN);

        ob_start();
        ?>
            <p>
                <select name="<?php echo $name ?>[type]" id="<?php echo $id ?>_type">
                    <option value="">Default</option>
                    <option value="table" <?php echo $value['type'] == 'table' ? 'selected="selected"' : '' ?>>Get from table</option>
                </select>
            </p>
            <p <?php echo $value['type'] == 'table' ? '' : 'style="display: none;"' ?>  id="<?php echo $id ?>_table_block">
                <select name="<?php echo $name ?>[table]" id="<?php echo $id ?>_table">
                    <option value="">- Select table -</option>
                    <?php foreach ($tables as $table): ?>
                        <option value="<?php echo $table ?>" <?php echo $table == $value['table'] ? 'selected="selected"' : '' ?>><?php echo uc_words($table, ' ', '_') ?></option>
                    <?php endforeach ?>
                </select>
            </p>
            <div <?php echo strlen($value['table']) > 2 ? '' : 'style="display: none;"' ?> id="<?php echo $id ?>_fields_block">
                <p>
                    <input type="hidden" id='product_id_hidden' value="<?php echo isset($value['fields']['product_id']) ? $value['fields']['product_id'] : ''?>">
                    <select name="<?php echo $name ?>[field_product_id]" id="<?php echo $id ?>_fields_product_id" class="<?php echo $id ?>_fields">
                        <option selected disabled value="none">- Select Product Id -</option>
                    </select>
                </p>
                <p>
                      <input type="hidden" id='location_hidden' value="<?php echo isset($value['fields']['location']) ? $value['fields']['location'] : '' ?>">
                    <select name="<?php echo $name ?>[field_location]" id="<?php echo $id ?>_fields_location"  class="<?php echo $id ?>_fields">
                        <option selected disabled value="none">- Select Location -</option>
                    </select>
                </p>
                <p>
                      <input type="hidden" id='qty_hidden' value="<?php echo isset($value['fields']['qty']) ? $value['fields']['qty'] : '' ?>">
                    <select name="<?php echo $name ?>[field_qty]" id="<?php echo $id ?>_fields_qty"  class="<?php echo $id ?>_fields">
                        <option selected disabled value="none">- Select Quantity -</option>
                    </select>
                </p>
            </div>
            <script>
                    var
                        $type = document.getElementById('<?php echo $id ?>_type'),
                        $table = document.getElementById('<?php echo $id ?>_table'),
                        $tableBlock = document.getElementById('<?php echo $id ?>_table_block'),
                        $fields = document.getElementsByClassName('<?php echo $id ?>_fields'),
                        $fieldsBlock = document.getElementById('<?php echo $id ?>_fields_block'),
                        getColumnsUrl = '<?= Mage::helper("adminhtml")->getUrl("admin_inventoryfeed/adminhtml_inventoryfeedbackend/getColumns") ?>',
                        $currentProductId = document.getElementById('product_id_hidden'),
                        $currentLocation = document.getElementById('location_hidden'),
                        $currentQty = document.getElementById('qty_hidden'),
                    endvar;

                    var vArr = [$currentProductId.value, $currentLocation.value, $currentQty.value];
                    var labelArray = ['- Select Product Id -', '- Select Location -', '- Select Quantity -'];

                    document.addEventListener('DOMContentLoaded', function(){
                        if ($type.value == 'table') {
                            var event = new Event('change'); 
                            $table.dispatchEvent(event);
                        }
                    });

                    $type.addEventListener('change', function(e){
                        if (this.value == 'table') {
                            showElement($tableBlock);
                        } else {
                            $table.value = '';
                            hideElement($tableBlock);

                            for (var i in $fields) {
                                $fields[i].value = '';
                            }

                            hideElement($fieldsBlock);
                        }
                    });

                    $table.addEventListener('change', function(e){
                        //cleaning
                        for (var i = 0; i < 3; i++) {
                            while ($fields[i].lastChild) {
                                $fields[i].removeChild($fields[i].lastChild);
                            }
                        }

                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', getColumnsUrl + '?table=' + this.value);
                        xhr.send();

                        xhr.onreadystatechange = function () {
                            var DONE = 4;
                            var OK = 200;
                            var i;

                            if (xhr.readyState === DONE) {
                                if (xhr.status === OK) {
                                    //fill columns
                                    var result = JSON.parse(xhr.responseText);

                                    result.forEach(function (element, index) {
                                        for (i = 0; i < 3; i++) {
                                            var e = document.createElement('option');

                                            e.value = element.id;
                                            e.innerHTML = element.name;

                                            if (e.value === vArr[i]) {
                                                e.selected = 'selected';
                                            }

                                            //append option to input
                                            $fields[i].append(e);
                                        }
                                    });

                                    for (i = 0; i < 3; i++) {
                                        if (vArr[i] === "") {
                                            var e = document.createElement('option');

                                            e.value = 'none';
                                            e.innerHTML = labelArray[i];
                                            e.selected = 'selected';
                                            e.disabled = true;
                                            
                                            //append option to input
                                            $fields[i].append(e);
                                        }
                                    }
                                }
                            }
                        }
                    });

                    $table.addEventListener('change', function(e){
                        $fieldsBlock.style.display = this.value == '' ? 'none' : 'block' ;
                    });

                    var hideElement = function (el) {
                        el.style.display = 'none';
                    };

                    var showElement = function (el) {
                        el.style.display = 'block';
                    }
            </script>
        <?php

        return ob_get_clean();
    }

}
