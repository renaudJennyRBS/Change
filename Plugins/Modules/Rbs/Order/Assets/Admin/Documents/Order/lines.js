/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	function rbsOrderOrderEditorLines(Utils, REST, $http, Dialog, i18n) {
		return {
			restrict: 'E',
			templateUrl: 'Document/Rbs/Order/Order/lines.twig',
			scope: true,

			link: function(scope, element) {
				scope.data = {
					newLineProducts: [],
					newCustomLine: getClearCustomLine(),
					articleCount: 0,
					loadingProductInfo: false,
					removedLines: [],
					editedLineIndex: null
				};
				scope.listLines = [];
				scope.selection = {
					all: false,
					lines: [],
					empty: function() {
						for (var i = 0; i < scope.selection.lines.length; i++) {
							if (scope.selection.lines[i]) {
								return false;
							}
						}
						return true;
					},
					clear: function() {
						for (var i = 0; i < scope.selection.lines.length; i++) {
							scope.selection.lines[i] = false;
						}
					}
				};

				scope.addCustomLine = function() {
					scope.data.newCustomLine.index = scope.document.lines.length;
					scope.data.newCustomLine.key = scope.data.newCustomLine.items[0].codeSKU;
					scope.document.lines.push(scope.data.newCustomLine);
					scope.data.newCustomLine = getClearCustomLine();
					scope.updateNewLineUI(null);
				};

				scope.addProductLines = function() {
					scope.data.loadingProductInfo = true;
					var ids = Utils.toIds(scope.data.newLineProducts);
					angular.forEach(ids, function(id) {
						var data = { orderId: scope.document.id, productId: id };
						$http.post(REST.getBaseUrl('rbs/order/getOrderLineByProduct'), data, REST.getHttpConfig())
							.success(function(result) {
								scope.data.loadingProductInfo = false;
								var line = result.line;
								line.index = scope.document.lines.length;
								scope.document.lines.push(line);
							})
							.error(function() {
								scope.data.loadingProductInfo = false;
							});
					});
					scope.data.newLineProducts = [];
				};

				scope.removeLines = function() {
					for (var i = scope.selection.lines.length - 1; i >= 0; i--) {
						if (scope.selection.lines[i]) {
							removeLineByIndex(i);
						}
					}
				};

				scope.removeLine = function(lineIndex) {
					removeLineByIndex(lineIndex);
				};

				scope.restoreRemovedLine = function(lineIndex) {
					scope.document.lines.push(scope.data.removedLines[lineIndex]);
					scope.data.removedLines.splice(lineIndex, 1);
				};

				scope.trashRemovedLine = function(lineIndex) {
					scope.data.removedLines.splice(lineIndex, 1);
				};

				scope.selectShippingMode= function ($event) {
					// Choose default shipping mode for the selected lines.
					var foundShippingMode = null;
					var multipleShippingModes = false;
					for (var i = 0; i < scope.selection.lines.length; i++) {
						if (scope.selection.lines[i]) {
							var line = scope.listLines[i];
							if (!multipleShippingModes && line.options && line.options.shippingMode) {
								if (foundShippingMode && line.options.shippingMode != foundShippingMode) {
									multipleShippingModes = true;
								}
								else {
									foundShippingMode = line.options.shippingMode;
								}
							}
						}
					}
					if (foundShippingMode && !multipleShippingModes) {
						scope.data.currentLineShippingMode = foundShippingMode;
					}
					else {
						scope.data.currentLineShippingMode = "";
					}

					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						i18n.trans('m.rbs.order.adminjs.order_set_shipping_mode | ucf'),
						'<select class="form-control" data-ng-model="data.currentLineShippingMode"' +
							' data-rbs-items-from-collection="Rbs_Shipping_Collection_ShippingModes"><option value="">' +
							i18n.trans('m.rbs.order.adminjs.order_select_shipping_mode | ucf') + '</option></select>',
						scope,
						{
							'pointedElement': jQuery($event.target),
							'primaryButtonClass' : 'btn-success',
							'cssClass': 'default'
						}
					)
						.then(function () {
							var modified = false;
							for (var i = 0; i < scope.selection.lines.length; i++) {
								if (scope.selection.lines[i]) {
									var line = scope.listLines[i];
									modified = scope.setLineShippingMode(line) || modified;
								}
							}

							if (modified) {
								scope.$emit('shippingModesUpdatedFromLines');
							}
							scope.selection.clear();
						});
				};

				scope.setLineShippingMode = function(line) {
					if (angular.isArray(line.options) || !angular.isObject(line.options)) {
						line.options = {};
					}
					var modified = false;
					if (scope.data.currentLineShippingMode) {
						var mode = parseInt(scope.data.currentLineShippingMode);
						modified = line.options.shippingMode != mode;
						line.options.shippingMode = mode;
					}
					else if (line.options.shippingMode != undefined) {
						modified = true;
						line.options.shippingMode = undefined;
					}
					return modified;
				};

				scope.updateNewLineUI = function(mode) {
					if (mode == 'product') {
						scope.orderContext.showNewCustomLineUI = false;
						scope.orderContext.showNewProductLineUI = !scope.orderContext.showNewProductLineUI;
					}
					else if (mode == 'custom') {
						scope.orderContext.showNewProductLineUI = false;
						scope.orderContext.showNewCustomLineUI = !scope.orderContext.showNewCustomLineUI;
					}
					else {
						scope.orderContext.showNewProductLineUI = false;
						scope.orderContext.showNewCustomLineUI = false;
					}
				};

				scope.$watchCollection('document.lines', function(lines) {
					var listLines = [];
					var selected = [];
					if (lines) {
						for (var i = 0; i < lines.length; i++) {
							listLines.push(lines[i]);
							selected.push(false);
						}
						scope.listLines = listLines;
						scope.selection.lines = selected;
					}
				});

				// This watches for modifications in the lines, made by the user, such as quantity for each line.
				scope.$watch('document.lines', function(lines, old) {
					if (scope.document && lines !== old) {
						scope.data.articleCount = 0;
						for (var i = 0; i < lines.length; i++) {
							scope.data.articleCount += lines[i].quantity;
						}
						if (scope.amountsModified() || scope.isPropertyModified('linesAmountWithTaxes')
							|| scope.isPropertyModified('linesAmountWithoutTaxes')) {
							if (scope.priceInfo.withTax) {
								scope.document.linesAmountWithTaxes = 0;
							}
							else {
								scope.document.linesAmountWithoutTaxes = 0;
							}
							for (i = 0; i < lines.length; i++) {
								lines[i].index = i;
								var value = lines[i].items[0].price.value;
								if (value) {
									if (scope.priceInfo.withTax) {
										scope.document.linesAmountWithTaxes += lines[i].quantity * value;
									}
									else {
										scope.document.linesAmountWithoutTaxes += lines[i].quantity * value;
									}
								}
							}
						}
					}
				}, true);

				scope.$watch('data.newLineProducts.length', function(value) {
					if (value) {
						scope.orderContext.showNewLineUI = true;
					}
				});

				scope.$watch('selection.all', function(allSelected) {
					for (var i = 0; i < scope.selection.lines.length; i++) {
						scope.selection.lines[i] = allSelected;
					}
				});

				function removeLineByIndex(index) {
					scope.selection.lines.splice(index, 1);
					scope.data.removedLines.push(scope.document.lines[index]);
					scope.document.lines.splice(index, 1);
				}

				function getClearCustomLine() {
					return {
						"index": 0,
						"key": null,
						"designation": "",
						"quantity": 1,
						"items": [
							{
								"codeSKU": "",
								"reservationQuantity": 1,
								"price": {
									"value": null,
									"withTax": scope.priceInfo.withTax,
									"taxCategories": {}
								},
								"options": {
									"lockedPrice": true
								}
							}
						],
						"options": {}
					};
				}
			}
		};
	}
	rbsOrderOrderEditorLines.$inject = [ 'RbsChange.Utils', 'RbsChange.REST', '$http', 'RbsChange.Dialog', 'RbsChange.i18n' ];
	angular.module('RbsChange').directive('rbsOrderLines', rbsOrderOrderEditorLines);

	function rbsOrderOrderEditorLineEditor() {
		return {
			restrict: 'E',
			templateUrl: 'Document/Rbs/Order/Order/lineEditor.twig',
			require: 'ngModel',
			scope: {
				'priceInfo': "="
			},

			link: function(scope, element, attrs, ngModel) {
				scope.line = {};

				ngModel.$render = function ngModelRenderFn() {
					scope.line = ngModel.$viewValue;
					var price = scope.line.items[0].price;
					if (!angular.isObject(price.taxCategories)) {
						price.taxCategories = {};
					}
				};
			}
		};
	}
	angular.module('RbsChange').directive('rbsOrderLineEditor', rbsOrderOrderEditorLineEditor);
})();