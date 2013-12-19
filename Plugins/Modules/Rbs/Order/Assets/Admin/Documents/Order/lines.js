(function () {

	"use strict";

	function rbsOrderOrderEditorLines (Utils, REST, Dialog, i18n)
	{
		return {
			restrict : 'E',
			templateUrl : 'Document/Rbs/Order/Order/lines.twig',
			scope: true,

			link : function (scope, element, attrs)
			{
				var extend = {

					articleCount : 0,
					loadingProductInfo : false,
					removedLines : [],
					editedLine : {},
					priceInfo : scope.priceInfo,

					addLine : function (lines, embedDialog, target)
					{
						var line = {
							"designation" : "",
							"quantity" : 1,
							"items" : [{
								"codeSKU" : "",
								"quantity" : 1,
								"priceValue" : null
							}],
							"options" : {}
						};
						scope.extend.editedLine = line;

						var promise;
						var message = '<rbs-order-line-editor ng-model="extend.editedLine" price-info="extend.priceInfo"/>';

						if (embedDialog) {
							promise = Dialog.confirmEmbed(
								embedDialog,
								i18n.trans('m.rbs.order.adminjs.order_add_line | ucf'),
								message,
								scope,
								{
									'pointedElement'    : target
								}
							);
						}
						else {
							return;
						}

						promise.then(function () {
							scope.extend.editedLine.options.lineNumber = scope.document.linesData.length + 1;
							scope.document.linesData.push(scope.extend.editedLine);
						});

					},

					addNewLines : function ()
					{
						extend.loadingProductInfo = true;
						REST.call(
							REST.getBaseUrl('rbs/order/productPriceInfo'),
							{
								'products' : Utils.toIds(scope.document.newLineProducts),
								'webStore' : scope.document.webStoreId,
								'billingArea' : scope.document.billingAreaId,
								'zone' : scope.document.contextData.taxZone
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
						}
						else {
							return;
						}

						promise.then(function () {
							var modified = false;
							angular.forEach(lines, function (line) {
								modified = scope.extend.setLineShippingMode(line) || modified;
							});

							if(modified){
								scope.$broadcast('shippingModesUpdated');
								scope.showShippingUI = true;
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
						item.taxCategories = product.boInfo.price.taxCategories;
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

				scope.listLines = [];
				scope.$watchCollection('document.linesData', function (lines, old) {
					var listLines = [];
					if (lines) {
						for (var i=0 ; i < lines.length ; i++) {
							listLines.push(lines[i]);
						}
						scope.listLines = listLines;
					}
				});

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

			}
		};
	}
	rbsOrderOrderEditorLines.$inject = [ 'RbsChange.Utils', 'RbsChange.REST', 'RbsChange.Dialog', 'RbsChange.i18n' ];
	angular.module('RbsChange').directive('rbsOrderLines', rbsOrderOrderEditorLines);
})();