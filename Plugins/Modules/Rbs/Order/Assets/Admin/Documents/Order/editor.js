(function () {

	"use strict";

	function rbsOrderOrderEditor (Utils, REST, Dialog, i18n, $filter, $q)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Order/Order/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				var extend = {

					articleCount : 0,
					showNewLineUI : false,
					showAddressUI : false,
					showShippingUI : false,
					showShippingAddressUI : false,
					loadingProductInfo : false,
					removedLines : [],
					address : {},
					shippingAddress : {},
					currentLineShippingMode : "",
					editedShippingMode : {},
					shippingDetails: {},

					addNewLines : function ()
					{
						extend.loadingProductInfo = true;
						REST.call(
							REST.getBaseUrl('rbs/order/productPriceInfo'),
							{
								'products' : Utils.toIds(scope.document.newLineProducts),
								'webStore' : scope.document.webStoreId,
								'billingArea' : scope.document.billingAreaId,
								'zone' : scope.document.zone
							}
						).then(function (products) {
								extend.loadingProductInfo = false;
								angular.forEach(products, function (product) {
									scope.document.linesData.push(makeOrderLine(
										scope.document.linesData.length + 1,
										product,
										1
									));
								});
								scope.document.newLineProducts = undefined;
							});
					},

					removeLines : function (lines)
					{
						angular.forEach(lines, function (line) {
							scope.document.linesData.splice(line.number-1, 1);
							line.selected = false;
							extend.removedLines.push(line);
							updateLines();
						});
					},

					restoreRemovedLine : function (lineIndex)
					{
						scope.document.linesData.push(extend.removedLines[lineIndex]);
						extend.removedLines.splice(lineIndex, 1);
						updateLines();
					},

					trashRemovedLine : function (lineIndex)
					{
						extend.removedLines.splice(lineIndex, 1);
					},

					populateAddressFields: function(addressDoc) {
						if(angular.isObject(addressDoc)){
							var addressFields = addressDoc.addressFields;
							if(angular.isObject(addressFields)){
								if(!angular.isObject(scope.document.contextData)){
									scope.document.contextData = {};
								}
								scope.document.contextData.addressFields = addressFields.id;
								scope.document.addressData = addressDoc.fieldValues;
							}
						}
					},

					setShippingMode : function (lines, embedDialog, target)
					{
						// choose default shipping mode for the lines selected
						var foundShippingMode = null;
						var multipleShippingModes = false;
						angular.forEach(lines, function (line) {
							if(!multipleShippingModes && line.options.shippingMode){
								if(foundShippingMode && line.options.shippingMode != foundShippingMode){
									multipleShippingModes = true;
								}
								else{
									foundShippingMode = line.options.shippingMode;
								}
							}
						});
						if(multipleShippingModes){
							scope.extend.currentLineShippingMode = "";
						}
						else if (foundShippingMode){
							scope.extend.currentLineShippingMode = foundShippingMode;
						}
						else {
							scope.extend.currentLineShippingMode = "";
						}

						var promise;
						var message = '<select class="form-control" ng-model="extend.currentLineShippingMode" rbs-items-from-collection="Rbs_Generic_Collection_ShippingModes"><option value="">'+i18n.trans('m.rbs.order.adminjs.order_select_shipping_mode | ucf')+'</option></select>';

						if (embedDialog) {
							promise = Dialog.confirmEmbed(
								embedDialog,
								i18n.trans('m.rbs.order.adminjs.order_set_shipping_mode | ucf'),
								message,
								scope,
								{
									'pointedElement'    : target
								}
							);
						} else if (target) {
							// ($el, title, message, options) {
							promise = Dialog.confirmLocal(
								target,
								i18n.trans('m.rbs.order.adminjs.order_set_shipping_mode | ucf'),
								message,
								{
									"placement": "bottom"
								}
							);
						}

						promise.then(function () {
							var modified = false;
							angular.forEach(lines, function (line) {
								modified = scope.extend.setLineShippingMode(line) || modified;
							});

							if(modified){
								scope.extend.refreshShippingModes();
								scope.extend.showShippingUI = true;
							}
						});

					},

					setLineShippingMode : function(line)
					{
						var options = line.options;
						var modified = false;
						if(scope.extend.currentLineShippingMode){
							modified = options.shippingMode != scope.extend.currentLineShippingMode;
							options.shippingMode = scope.extend.currentLineShippingMode;
						}
						else if (options.shippingMode != undefined)
						{
							modified = true;
							options.shippingMode = undefined;
						}
						return modified;
					},

					refreshShippingModes : function()
					{
						if(!angular.isObject(scope.document.shippingData)){
							scope.document.shippingData = [];
						}
						var shippingModes = scope.document.shippingData;
						angular.forEach(shippingModes, function (shippingMode) {
							shippingMode.lines = [];
						});
						angular.forEach(scope.document.linesData, function (line) {
							var shippingModeId = line.options.shippingMode;
							if(shippingModeId){
								var matchingShippingModes = $filter('filter')(shippingModes, {'id': shippingModeId});
								if(matchingShippingModes.length){
									angular.forEach(matchingShippingModes, function (shippingMode) {
										shippingMode.lines.push(line.options.lineNumber);
									});
								}
								else{
									shippingModes.push({'id': shippingModeId, lines: [line.options.lineNumber]});
								}
							}
						});
					},

					populateShippingDetails: function(response) {
						var shippingDetails = {};
						angular.forEach(response.resources, function (shippingDoc) {
							shippingDetails[shippingDoc.id] = shippingDoc;
						});
						scope.extend.shippingDetails = shippingDetails;
					},

					editShippingAddress: function (shippingId) {
						angular.forEach(scope.document.shippingData, function (shipping){
							if(shipping.id == shippingId){
								console.log(shipping);
								if(!angular.isObject(shipping.address))
								{
									shipping.address = {};
								}
								scope.extend.editedShippingMode = shipping;
							}
						});
						scope.extend.showShippingAddressUI = true;
					},

					populateShippingAddressFields: function(addressDoc) {
						if(angular.isObject(addressDoc)){
							var addressFields = addressDoc.addressFields;
							if(angular.isObject(addressFields)){
								scope.extend.editedShippingMode.addressFields = addressFields.id;
								scope.extend.editedShippingMode.address = addressDoc.fieldValues;
							}
						}
					}
			};

				scope.getProductsBySku = function (query)
				{
					console.log(query);
					return ['toto'];
				};


				function makeOrderLine (number, product, quantity)
				{
					var item = {
						codeSKU : product.boInfo.sku.code,
						quantity : quantity || 1,
						priceValue : null
					};
					if(angular.isObject(product.boInfo.price)) {
						item.priceValue = product.boInfo.price.boValue;
					}

					return {
						designation : product.label,
						quantity : quantity || 1,
						items : [item],
						options : {
							lineNumber : number,
							visual : product.adminthumbnail
						}
					};
				}


				function updateLines ()
				{
					extend.articleCount = 0;
					for (var i=0 ; i < scope.document.linesData.length ; i++) {
						scope.document.linesData[i].number = i + 1;
						extend.articleCount += scope.document.linesData[i].quantity;
					}
				}

				scope.extend = extend;

				scope.onReady = function ()
				{
					extend.showNewLineUI = scope.document.isNew();
					if (! scope.document.linesData) {
						scope.document.linesData = [];
					}
				};

					// This watches for modifications in the lines, made by the user, such as quantity for each line.
				scope.$watch('document.linesData', function (lines, old) {
					if (scope.document && lines !== old) {
						scope.document.amountWithTax = 0;
						extend.articleCount = 0;
						for (var i=0 ; i < lines.length ; i++) {
							extend.articleCount += lines[i].quantity;
							scope.document.amountWithTax += lines[i].quantity * lines[i].items[0].priceValue;
						}
					}
				}, true);

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('extend.address.doc', function (addressDoc, old) {
					if(angular.isObject(addressDoc)){
						REST.resource(addressDoc.model, addressDoc.id).then(scope.extend.populateAddressFields);
					}
				}, true);

				// This watches for modifications in the shipping address doc in order to fill the address form
				scope.$watch('extend.shippingAddress.doc', function (addressDoc, old) {
					if(angular.isObject(addressDoc)){
						REST.resource(addressDoc.model, addressDoc.id).then(scope.extend.populateShippingAddressFields);
					}
				}, true);

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('document.shippingData', function (shippingData, old) {
					if(angular.isObject(shippingData) && !angular.isObject(old)){
						REST.collection('Rbs_Shipping_Mode').then(scope.extend.populateShippingDetails);
					}
				}, true);

				editorCtrl.init('Rbs_Order_Order');
			}
		};
	}

	rbsOrderOrderEditor.$inject = [ 'RbsChange.Utils', 'RbsChange.REST', 'RbsChange.Dialog', 'RbsChange.i18n', '$filter', '$q' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrder', rbsOrderOrderEditor);

})();