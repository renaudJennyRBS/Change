(function () {

	"use strict";

	function rbsOrderOrderEditorLines(Utils, REST, $http, $q, $timeout, Events, Dialog, i18n) {
		return {
			restrict: 'E',
			templateUrl: 'Document/Rbs/Order/Order/lines.twig',
			scope: true,

			link: function (scope, element, attrs) {
				var extend = {

					articleCount: 0,
					paymentAmount: null,
					loadingProductInfo: false,
					removedLines: [],
					editedLine: {},
					priceInfo: scope.priceInfo,

					addLine: function (lines, embedDialog, target) {
						scope.extend.editedLine = {
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
									"options": {}
								}
							],
							"options": {}
						};

						var promise;
						var message = '<rbs-order-line-editor ng-model="extend.editedLine" price-info="extend.priceInfo"/>';

						if (embedDialog) {
							promise = Dialog.confirmEmbed(
								embedDialog, i18n.trans('m.rbs.order.adminjs.order_add_line | ucf'),
								message, scope, {'pointedElement': target}
							);
						} else {
							return;
						}

						promise.then(function () {
							scope.extend.editedLine.index = scope.document.linesData.length;
							scope.document.linesData.push(scope.extend.editedLine);
						});

					},

					addNewLines: function () {
						extend.loadingProductInfo = true;
						var ids = Utils.toIds(scope.document.newLineProducts);
						angular.forEach(ids, function (id) {
							var line = {options: {productId: id}};
							$http.post(REST.getBaseUrl('rbs/order/lineNormalize'),
								{
									'line': line,
									'webStore': scope.document.webStoreId,
									'billingArea': scope.document.billingAreaId,
									'zone': scope.document.contextData.taxZone
								},
								REST.getHttpConfig()
							).success(function (result) {
									extend.loadingProductInfo = false;
									var line = result.line;
									line.index = scope.document.linesData.length;
									scope.document.linesData.push(line);
									scope.document.newLineProducts = undefined;
								})
								.error(function (result)
								{
									extend.loadingProductInfo = false
								});
						});
					},

					removeLines: function (lines) {
						angular.forEach(lines, function (line) {
							scope.document.linesData.splice(line.index, 1);
							line.selected = false;
							extend.removedLines.push(line);
							updateLines();
						});
					},

					restoreRemovedLine: function (lineIndex) {
						scope.document.linesData.push(extend.removedLines[lineIndex]);
						extend.removedLines.splice(lineIndex, 1);
						updateLines();
					},

					trashRemovedLine: function (lineIndex) {
						extend.removedLines.splice(lineIndex, 1);
					},

					setShippingMode: function (lines, embedDialog, target) {
						// choose default shipping mode for the lines selected
						var foundShippingMode = null;
						var multipleShippingModes = false;
						angular.forEach(lines, function (line) {
							if (!multipleShippingModes && line.options.shippingMode) {
								if (foundShippingMode && line.options.shippingMode != foundShippingMode) {
									multipleShippingModes = true;
								} else {
									foundShippingMode = line.options.shippingMode;
								}
							}
						});
						if (multipleShippingModes) {
							scope.extend.currentLineShippingMode = "";
						} else if (foundShippingMode) {
							scope.extend.currentLineShippingMode = foundShippingMode;
						} else {
							scope.extend.currentLineShippingMode = "";
						}

						var promise;
						var message = '<select class="form-control" ng-model="extend.currentLineShippingMode" rbs-items-from-collection="Rbs_Shipping_Collection_ShippingModes"><option value="">' +
							i18n.trans('m.rbs.order.adminjs.order_select_shipping_mode | ucf') + '</option></select>';

						if (embedDialog) {
							promise = Dialog.confirmEmbed(
								embedDialog, i18n.trans('m.rbs.order.adminjs.order_set_shipping_mode | ucf'),
								message, scope, {'pointedElement': target}
							);
						} else {
							return;
						}

						promise.then(function () {
							var modified = false;
							angular.forEach(lines, function (line) {
								modified = scope.extend.setLineShippingMode(line) || modified;
							});

							if (modified) {
								scope.$emit('shippingModesUpdatedFromLines');
							}
						});
					},

					setLineShippingMode: function (line) {
						var options = line.options;
						var modified = false;
						if (scope.extend.currentLineShippingMode) {
							modified = options.shippingMode != scope.extend.currentLineShippingMode;
							options.shippingMode = scope.extend.currentLineShippingMode;
						} else if (options.shippingMode != undefined) {
							modified = true;
							options.shippingMode = undefined;
						}
						return modified;
					}

				};

				function updateLines() {
					extend.articleCount = 0;
					for (var i = 0; i < scope.document.linesData.length; i++) {
						scope.document.linesData[i].index = i;
						extend.articleCount += scope.document.linesData[i].quantity;
					}
				}

				scope.extend = extend;

				scope.listLines = [];

				scope.$watchCollection('document.linesData', function (lines) {
					var listLines = [];
					if (lines) {
						for (var i = 0; i < lines.length; i++) {
							listLines.push(lines[i]);
						}
						scope.listLines = listLines;
					}
				});

				// This watches for modifications in the lines, made by the user, such as quantity for each line.
				scope.$watch('document.linesData', function (lines, old) {
					if (scope.document && lines !== old) {
						scope.extend.paymentAmount = 0;
						extend.articleCount = 0;
						for (var i = 0; i < lines.length; i++) {
							extend.articleCount += lines[i].quantity;
							var value = lines[i].items[0].price.value;
							if (value) {
								scope.extend.paymentAmount += lines[i].quantity * value;
							}
						}
					}
				}, true);

				scope.$on(Events.EditorPreSave, function (event, args) {
					var promises = args['promises'];
					var document = args['document'];

					var orderPromises = [];
					scope.$broadcast('OrderPreSave', {document: document, promises: orderPromises});

					var q = $q.defer();

					if (orderPromises.length) {
						var promise = $q.all(orderPromises);
						promise.then(function () {
							scope.updateAmount(document);
							$timeout(function () {
								q.resolve()
							});
						});
					} else {
						scope.updateAmount(document);
						$timeout(function () {
							q.resolve()
						})
					}
					promises.push(q.promise);
				});

				scope.updateAmount = function (document) {
					var lines = document.linesData;
					document.amount = 0;
					for (var i = 0; i < lines.length; i++) {
						document.amount += (lines[i].priceValueWithTax || lines[i].priceValue);
					}
				};
			}
		};
	}

	rbsOrderOrderEditorLines.$inject =
		[ 'RbsChange.Utils', 'RbsChange.REST', '$http', '$q', '$timeout', 'RbsChange.Events', 'RbsChange.Dialog', 'RbsChange.i18n'
		];
	angular.module('RbsChange').directive('rbsOrderLines', rbsOrderOrderEditorLines);
})();