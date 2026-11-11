<script>
	
	var working_hours;

	$(document).ready(function() {
		setTimeout(function(){
			"use strict";  
			$('.work_instruction').click();

		}, 1);
	});

	(function($) {
		"use strict";  

		appValidateForm($("body").find('#add_update_working_hour'), {
			'working_hour_name': 'required',
			'hours_per_day': 'required',
		});    


		<?php if(isset($opening_stock_data)){ ?>
			var dataObject_pu = <?php echo json_encode($opening_stock_data) ; ?>;
		<?php }else{?>
			var dataObject_pu = [];
		<?php } ?>

		setTimeout(function(){

			var hotElement1 = document.getElementById('item_add_opening_stock_hs');

			working_hours = new Handsontable(hotElement1, {
				licenseKey: 'non-commercial-and-evaluation',


				contextMenu: true,
				manualRowMove: true,
				manualColumnMove: true,
				stretchH: 'none',
				autoWrapRow: true,
				rowHeights: 30,
				defaultRowHeight: 100,
				minRows: <?php echo new_html_entity_decode($min_row); ?>,
				width: '100%',
				height: '350px',
				licenseKey: 'non-commercial-and-evaluation',
				rowHeaders: true,
				autoColumncommodity_group: {
					samplingRatio: 23
				},
				

				filters: true,
				manualRowRecommodity_group: true,
				manualColumnRecommodity_group: true,
				allowInsertRow: true,
				allowRemoveRow: true,
				columnHeaderHeight: 40,
				minSpareRows: 1,
				colWidths: [40, 260, 200, 140, 150, 150, 130, 120, 130, 150, 10],
				rowHeights: 30,
				
				rowHeaderWidth: [44],
				hiddenColumns: {
					columns: [0, 10],
					indicators: true
				},


				columns: [
				{
					type: 'text',
					data: 'id',
				},
				
				{
					type: 'text',
					data: 'commodity_id',
					renderer: customDropdownRenderer,
					editor: "chosen",
					chosenOptions: {
						data: <?php echo json_encode($commodity_code_name); ?>
					},
				},
				{
					type: 'text',
					data: 'warehouse_id',
					renderer: customDropdownRenderer,
					editor: "chosen",
					chosenOptions: {
						data: <?php echo json_encode($units_warehouse_name); ?>
					}

				},

				{
					type: 'text',
					data: 'lot_number',
				},

				{
					type: 'date',
					data: 'expiry_date',
					dateFormat: 'YYYY-MM-DD',
					correctFormat: true,
					defaultDate: "<?php echo _d(date('Y-m-d')) ?>"
				},
				{
					data: 'inventory_number',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
				},
				{
					data: 'c_qty',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
				},
				{
					type: 'text',
					data: 'c_type',
					readOnly: true,
				},
				{
					data: 'c_value',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
					readOnly: true,
				},
				{
					type: 'text',
					data: 'c_unit',
					readOnly: true,
				},
				{
					data: 'base_inventory_number',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
				},

				],

				colHeaders: [

				"<?php echo _l('id'); ?>",
				"<?php echo _l('commodity_name'); ?>",
				"<?php echo _l('warehouse_name'); ?>",
				"<?php echo _l('lot_number'); ?>",
				"<?php echo _l('expiry_date'); ?>",
				"<?php echo _l('inventory_number'); ?>",
				"Conversion Qty",
				"Conversion Type",
				"Conversion Value",
				"Conversion Unit",

				],

				data: dataObject_pu,
				afterChange: function(changes, source) {
					if (!changes || source === 'auto_conversion') {
						return;
					}

					var shouldRecalculate = false;
					for (var i = 0; i < changes.length; i++) {
						var row = changes[i][0];
						var prop = changes[i][1];

						if (prop === 'commodity_id') {
							var rowId = working_hours.getDataAtRowProp(row, 'id');
							var currentInventory = parseFloat(working_hours.getDataAtRowProp(row, 'inventory_number'));
							if (!rowId) {
								working_hours.setDataAtRowProp(row, 'base_inventory_number', isNaN(currentInventory) ? 0 : currentInventory, 'auto_conversion');
							}
							setRowItemConversion(row, true);
							shouldRecalculate = true;
						}

						if (prop === 'c_qty' || prop === 'c_value' || prop === 'c_unit' || prop === 'c_type') {
							calculateInventoryByConversion(row);
							shouldRecalculate = true;
						}

						if (prop === 'inventory_number') {
							var currentQty = working_hours.getDataAtRowProp(row, 'c_qty');
							if (currentQty === null || currentQty === '' || isNaN(parseFloat(currentQty)) || parseFloat(currentQty) === 0) {
								var invValue = parseFloat(working_hours.getDataAtRowProp(row, 'inventory_number'));
								working_hours.setDataAtRowProp(row, 'base_inventory_number', isNaN(invValue) ? 0 : invValue, 'auto_conversion');
							}
						}
					}

					if (shouldRecalculate) {
						this.render();
					}
				},
			});

			var conversionFactors = {
				meters: 39.3701,
				yards: 36,
				square_meters: 1550.0031,
				square_yards: 1296,
				pieces: 1
			};

			function parseCommodityId(rawValue) {
				if (rawValue === null || rawValue === undefined || rawValue === '') {
					return '';
				}
				var strValue = String(rawValue);
				if (strValue.indexOf('|') !== -1) {
					strValue = strValue.split('|')[0];
				}
				return strValue;
			}

			function getItemMetaById(commodityId) {
				var list = <?php echo json_encode($commodity_code_name); ?>;
				for (var i = 0; i < list.length; i++) {
					if (String(list[i].id) === String(commodityId)) {
						return list[i];
					}
				}
				return null;
			}

			function setRowItemConversion(row, forceDefaultQty) {
				var commodityId = parseCommodityId(working_hours.getDataAtRowProp(row, 'commodity_id'));
				var itemMeta = getItemMetaById(commodityId);

				if (!itemMeta) {
					working_hours.setDataAtRowProp(row, 'c_type', '', 'auto_conversion');
					working_hours.setDataAtRowProp(row, 'c_value', '', 'auto_conversion');
					working_hours.setDataAtRowProp(row, 'c_unit', '', 'auto_conversion');
					working_hours.setDataAtRowProp(row, 'c_qty', '', 'auto_conversion');
					return;
				}

				var cType = itemMeta.unit_conversion_type || '';
				var cValue = itemMeta.unit_conversion_value || '';
				var cUnit = itemMeta.conversion_unit || '';
				var cQty = working_hours.getDataAtRowProp(row, 'c_qty');

				working_hours.setDataAtRowProp(row, 'c_type', cType, 'auto_conversion');
				working_hours.setDataAtRowProp(row, 'c_value', cValue, 'auto_conversion');
				working_hours.setDataAtRowProp(row, 'c_unit', cUnit, 'auto_conversion');

				if (forceDefaultQty === true && cType !== '' && cValue !== '' && (cQty === null || cQty === '')) {
					working_hours.setDataAtRowProp(row, 'c_qty', 1, 'auto_conversion');
				}

				if (forceDefaultQty === true || (cQty !== null && cQty !== '')) {
					calculateInventoryByConversion(row);
				}
			}

			function calculateInventoryByConversion(row) {
				var cType = working_hours.getDataAtRowProp(row, 'c_type');
				var cQty = parseFloat(working_hours.getDataAtRowProp(row, 'c_qty'));
				var cValue = parseFloat(working_hours.getDataAtRowProp(row, 'c_value'));
				var cUnitRaw = working_hours.getDataAtRowProp(row, 'c_unit');
				var cUnit = cUnitRaw ? String(cUnitRaw).toLowerCase() : '';
				var baseInventory = parseFloat(working_hours.getDataAtRowProp(row, 'base_inventory_number'));
				if (isNaN(baseInventory)) {
					baseInventory = 0;
				}

				if (!cType || !cUnit || isNaN(cValue) || cValue <= 0 || isNaN(cQty) || cQty === 0) {
					working_hours.setDataAtRowProp(row, 'inventory_number', parseFloat(baseInventory.toFixed(2)), 'auto_conversion');
					return;
				}

				var factor = conversionFactors[cUnit] ? conversionFactors[cUnit] : 1;
				var inventoryNumber = baseInventory + (cQty * cValue * factor);
				working_hours.setDataAtRowProp(row, 'inventory_number', parseFloat(inventoryNumber.toFixed(2)), 'auto_conversion');
			}

			for (var rowIndex = 0; rowIndex < working_hours.countRows(); rowIndex++) {
				var initialInventory = parseFloat(working_hours.getDataAtRowProp(rowIndex, 'inventory_number'));
				var initialBase = parseFloat(working_hours.getDataAtRowProp(rowIndex, 'base_inventory_number'));
				if (isNaN(initialBase)) {
					working_hours.setDataAtRowProp(rowIndex, 'base_inventory_number', isNaN(initialInventory) ? 0 : initialInventory, 'auto_conversion');
				}

				var rowCommodityId = parseCommodityId(working_hours.getDataAtRowProp(rowIndex, 'commodity_id'));
				if (rowCommodityId !== '') {
					setRowItemConversion(rowIndex, false);
				}
			}
		},300);

	})(jQuery);


	function customDropdownRenderer(instance, td, row, col, prop, value, cellProperties) {
		"use strict";
		var selectedId;
		var optionsList = cellProperties.chosenOptions.data;

		if(typeof optionsList === "undefined" || typeof optionsList.length === "undefined" || !optionsList.length) {
			Handsontable.cellTypes.text.renderer(instance, td, row, col, prop, value, cellProperties);
			return td;
		}

		var values = (value + "").split("|");
		value = [];
		for (var index = 0; index < optionsList.length; index++) {

			if (values.indexOf(optionsList[index].id + "") > -1) {
				selectedId = optionsList[index].id;
				value.push(optionsList[index].label);
			}
		}
		value = value.join(", ");

		Handsontable.cellTypes.text.renderer(instance, td, row, col, prop, value, cellProperties);
		return td;
	}

	$('.btn_add_opening_stock').on('click', function() {
		'use strict';

		var valid_add_opening_stock = $('#item_add_opening_stock_hs').find('.htInvalid').html();

		if(valid_add_opening_stock){
			alert_float('danger', "<?php echo _l('data_must_number') ; ?>");
		}else{
			$('.btn_add_opening_stock').attr( "disabled", "disabled" );
			$('input[name="item_add_opening_stock_hs"]').val(JSON.stringify(working_hours.getData()));   
			$('#add_opening_stock').submit(); 

		}
	});

</script>
