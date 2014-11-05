(function() {
	"use strict";

	function Editor(REST, $routeParams, ArrayUtils, $q, Query, i18n, NotificationCenter, ErrorFormatter, Dialog, $timeout, $http) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				scope.data = {
					remainLines: [], //In remainLines, we mean lines to remain from the order
					shipmentLines: [], //In shipmentLines, we mean lines to add to this shipment
					asideLines: [], //In asideLines, we mean lines from remain will be not added to this shipment
					preparedLines: [], //In preparedLines, we mean lines already prepared (you can't modify this lines)
					showNewLineUI: false,
					order: null
				};
				scope.orderRemainderAlreadyDefined = false;
				scope.userAddresses = [];
				scope.canEdit = { order: true, carrier: true };

				var contextRestored = false;
				var refreshOrderRemainderLocked = false;

				scope.$on('Navigation.saveContext', function(event, args) {
					args['context'].savedData('shipment', {
						data: scope.data,
						orderRemainderAlreadyDefined: scope.orderRemainderAlreadyDefined,
						userAddresses: scope.userAddresses,
						canEdit: scope.canEdit
					});
				});

				scope.onRestoreContext = function(currentContext) {
					var toRestoreData = currentContext.savedData('shipment');
					scope.data = toRestoreData.data;
					scope.orderRemainderAlreadyDefined = toRestoreData.orderRemainderAlreadyDefined;
					scope.userAddresses = toRestoreData.userAddresses;
					scope.canEdit = toRestoreData.canEdit;
					contextRestored = true;
				};

				function setCarrierByShippingModeCode(shippingModeCode) {
					var p = REST.query(Query.simpleQuery('Rbs_Shipping_Mode', 'code', shippingModeCode));
					p.then(function(data) {
						if (data.resources.length === 1) {
							scope.data.carrier = data.resources[0];
							scope.canEdit.carrier = false;
						}
						else {
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_shipping_mode_not_found_title | ucf'),
								i18n.trans('m.rbs.order.adminjs.shipment_shipping_mode_not_found | ucf',
									{ CODE: shippingModeCode }));
						}
					}, function(error) {
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});
				}

				function populateAddressList(ownerId) {
					if (!ownerId) {
						scope.userAddresses = [];
						return;
					}

					var query = {
						'model': 'Rbs_Geo_Address',
						'where': {
							'and': [
								{
									'op': 'eq',
									'lexp': { 'property': 'ownerId' },
									'rexp': { 'value': ownerId }
								}
							]
						}
					};

					REST.query(query, { 'column': ['label', 'addressFields', 'fieldValues'] }).then(function(data) {
						scope.userAddresses = data.resources;
					});
				}

				function setOrderByOrderId(orderId) {
					var p = REST.resource('Rbs_Order_Order', orderId);
					p.then(function(data) {
						scope.data.order = data;
						populateAddressList(data['ownerId']);
						scope.canEdit.order = false;
					}, function(error) {
						NotificationCenter.error(i18n.trans('m.rbs.order.admin.invalid_query_order | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});
					return p;
				}

				function setShipmentPromises() {
					var promises = [];

					if (scope.document.shippingModeCode) {
						promises.push(setCarrierByShippingModeCode(scope.document.shippingModeCode));
					}
					else {
						setShippingModeWatch();
					}

					if (scope.document.orderId) {
						promises.push(setOrderByOrderId(scope.document.orderId));
					}
					else {
						setOrderWatch();
					}

					if (promises.length === 2) {
						$q.all(promises).then(refreshOrderInformation);
					}
				}

				scope.onLoad = function onLoad() {
					setShipmentPromises();
				};

				scope.onReady = function onReady() {
					if (scope.document.isNew()) {
						// Pre-fill fields if there is data in query url.
						if ($routeParams.hasOwnProperty('orderId')) {
							setOrderByOrderId($routeParams.orderId);
						}
						if ($routeParams.hasOwnProperty('shippingModeId')) {
							var shippingModeId = $routeParams.shippingModeId;
							if (shippingModeId) {
								REST.resource('Rbs_Shipping_Mode', shippingModeId).then(function(data) {
									scope.data.carrier = data;
									scope.canEdit.carrier = false;
								}, function(error) {
									NotificationCenter.error(
										i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
										ErrorFormatter.format(error)
									);
									console.error(error);
								});
							}
						}
					}
				};

				function resetLinesInformation() {
					scope.data.remainLines = [];
					scope.data.shipmentLines = [];
					scope.data.asideLines = [];
				}

				function getShipmentLineFromSKU(codeSKU) {
					if (!angular.isArray(scope.data.shipmentLines)) {
						return null;
					}
					for (var i = 0; i < scope.data.shipmentLines.length; i++) {
						if (scope.data.shipmentLines[i].codeSKU === codeSKU) {
							return scope.data.shipmentLines[i];
						}
					}
					return null;
				}

				function dispatchOrderLines() {
					// Get shipment lines. Sort them in three arrays:
					//  - shipment lines : line already in shipment and present in order remainder
					//  - aside lines : line not in shipment but in order remainder
					//  - new lines : line in shipment but not in order remainder (that mean new lines)
					angular.forEach(scope.data.remainLines, function(remainderLine) {
						var line = getShipmentLineFromSKU(remainderLine.codeSKU);
						if (line) {
							//FIXME += or = ?
							line.quantity = remainderLine.quantity;
							line.newLine = false;
						}
						else {
							// Push it in aside lines.
							scope.data.asideLines.push(remainderLine);
						}
					});
				}

				function refreshOrderRemainder() {
					// Because this is an asynchronous function containing another asynchronous function,
					// it needs a lock to prevent another call during processing.
					// TODO prevent the user from choosing another carrier or order during processing instead of using lock
					if (!refreshOrderRemainderLocked && !scope.document.prepared) {
						refreshOrderRemainderLocked = true;

						var p = REST.call(REST.getBaseUrl('rbs/order/orderRemainder'), {
							orderId: scope.document.orderId,
							shippingModeId: scope.data.carrier.id
						});

						p.then(function(data) {
							scope.data.remainLines = data.remainLines;
							if (!scope.document.lines || scope.document.lines.length === 0) {
								resetLinesInformation();
								for (var i = 0; i < data.remainLines.length; i++) {
									scope.data.shipmentLines.push(data.remainLines[i]);
								}
								refreshOrderRemainderLocked = false;
							}
							else {
								// Sort shipmentLines from documentData.
								dispatchOrderLines();
							}
							if (scope.document.isNew()) {
								scope.document.address = angular.isObject(data.address) ? data.address : { __addressFieldsId: null };
							}
						}, function(error) {
							refreshOrderRemainderLocked = false;
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_request_remainder | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					}
				}

				function refreshOrderInformation() {
					resetLinesInformation();
					angular.forEach(scope.document.lines, function(documentLine) {
						scope.data.shipmentLines.push({
							designation: documentLine.designation,
							codeSKU: documentLine.codeSKU,
							quantityToShip: documentLine.quantity,
							options: documentLine.options,
							allowQuantitySplit: true,
							newLine: true
						});
					});
					refreshOrderRemainderLocked = false;

					if (scope.data.order && (scope.data.carrier && scope.data.carrier.id > 0)) {
						refreshOrderRemainder();
					}
				}

				scope.preSave = function(document) {
					var q = $q.defer();
					if (angular.isObject(document.address) && !isObjectEmpty(document.address)) {
						$http.post(REST.getBaseUrl('Rbs/Geo/AddressLines'), {
							address: document.address,
							addressFieldsId: document.address.__addressFieldsId
						}).success(function(data) {
							document.address.__lines = data;
							q.resolve(data);
						}).error(function(dataError) {
							q.reject(dataError);
						});
					}
					else {
						// Set empty address.
						document.address = { address: { __addressFields: 0 } };
						q.resolve();
					}
					return q.promise;
				};

				scope.moveToShipmentLines = function(asideLine) {
					scope.data.shipmentLines.push(asideLine);
					ArrayUtils.removeValue(scope.data.asideLines, asideLine);
				};

				function getSKUFromProductIdQuery(productId) {
					return {
						model: 'Rbs_Stock_Sku',
						join: [
							{
								model: 'Rbs_Catalog_Product',
								name: 'product',
								property: 'sku'
							}
						],
						where: {
							and: [
								{
									op: 'eq',
									lexp: { property: 'id', join: 'product' },
									rexp: { value: productId, join: 'product' }
								}
							]
						}
					};
				}

				function skuIsInArray(codeSKU, array) {
					var lineIsInArray = false;
					if (codeSKU) {
						angular.forEach(array, function(arrayLine) {
							if (arrayLine.codeSKU) {
								if (arrayLine.codeSKU == codeSKU) {
									lineIsInArray = true;
								}
							}
							else {
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_no_sku_to_compare | ucf'));
							}
						});
					}
					else {
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_no_sku_to_compare | ucf'));
					}
					return lineIsInArray;
				}

				scope.addNewLines = function() {
					angular.forEach(scope.data.newLines, function(article) {
						// A query to get SKU from product.
						REST.query(getSKUFromProductIdQuery(article.id)).then(function(data) {
							scope.data.newLines = [];
							if (data.resources.length === 1) {
								// Check first if the article is not already in order or shipment lines,
								// in this cases, don't add the article because article is not properly a
								// line, we have to made it (the compare function only needs SKU code).
								var codeSKU = data.resources[0].code;
								if (!skuIsInArray(codeSKU, scope.data.remainLines) &&
									!skuIsInArray(codeSKU, scope.data.shipmentLines)) {
									scope.data.shipmentLines.push({
										designation: article.label,
										codeSKU: codeSKU,
										quantity: '',
										options: { productId: article.id },
										allowQuantitySplit: true,
										newLine: true
									});
								}
							}
							else {
								NotificationCenter.warning(i18n.trans('m.rbs.order.adminjs.shipment_product_not_found_title | ucf'),
									i18n.trans('m.rbs.order.adminjs.shipment_product_not_found | ucf',
										{ PRODUCTID: article.id }));
							}
						}, function(error) {
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_sku_from_product_id | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					});
				};

				scope.removeLines = function() {
					var shipmentLinesToRemove = [];
					angular.forEach(scope.data.shipmentLines, function(shipmentLine) {
						if (shipmentLine.selected) {
							shipmentLinesToRemove.push(shipmentLine);
							if (!shipmentLine.newLine) {
								scope.data.asideLines.push(shipmentLine);
							}
						}
					});
					ArrayUtils.removeArray(scope.data.shipmentLines, shipmentLinesToRemove);
				};

				scope.validatePreparation = function($event) {
					var message = i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation | ucf');
					if (!scope.document.address || isObjectEmpty(scope.document.address)) {
						message += '<br /><strong>' +
						i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation_empty_address | ucf') +
						'</strong>';
					}
					Dialog.confirmLocal($event.target,
						i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation_title | ucf'),
						message,
						{ placement: 'top' }
					).then(function() {
							scope.document.prepared = true;
							// Wait for angular to check changes.
							$timeout(function() {
								scope.submit();
							});
						});
				};

				function isObjectEmpty(object) {
					if (angular.isArray(object)) {
						return object.length === 0;
					}
					if (angular.isObject(object)) {
						return Object.getOwnPropertyNames(object).length === 0;
					}
					return false;
				}

				function setOrderWatch() {
					scope.$watch('data.order', function(order) {
						scope.document.orderId = (order) ? order.id : 0;
						refreshOrderInformation();
					}, true);
				}

				function setShippingModeWatch() {
					scope.$watch('data.carrier', function(carrier) {
						if (angular.isObject(carrier) && carrier.id > 0) {
							REST.ensureLoaded(carrier).then(function(data) {
								scope.document.shippingModeCode = data.code;
								if (!angular.isObject(scope.document.context) || angular.isArray(scope.document.context)) {
									scope.document.context = {};
								}
								scope.document.context.shippingModeId = data.id;
								refreshOrderInformation();
							}, function(error) {
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
						}
						else {
							scope.document.shippingModeCode = '';
							refreshOrderInformation();
						}
					});
				}

				scope.$watch('data.selectAllShipmentLines', function(value) {
					angular.forEach(scope.data.shipmentLines, function(shipmentLine) {
						shipmentLine.selected = value;
					});
				});

				function dispatchShipmentLinesInDocumentData(shipmentLines) {
					scope.data.removeLinesDisabled = true;
					scope.data.quantityToShipExceeded = false;
					scope.data.hasNewLines = false;
					scope.document.lines = [];
					var itemCount = 0;

					angular.forEach(shipmentLines, function(shipmentLine) {
						if (shipmentLine.selected) {
							scope.data.removeLinesDisabled = false;
						}
						// Find if a shipping quantity exceeds the remain quantity.
						if (shipmentLine.quantity && shipmentLine['quantityToShip'] > shipmentLine.quantity) {
							scope.data.quantityToShipExceeded = true;
						}
						if (shipmentLine.newLine) {
							scope.data.hasNewLines = true;
						}
						scope.document.lines.push({
							designation: shipmentLine.designation,
							codeSKU: shipmentLine.codeSKU,
							quantity: shipmentLine['quantityToShip'],
							options: shipmentLine.options
						});
						itemCount += shipmentLine['quantityToShip'];
					});

					scope.document.itemCount = itemCount;
				}

				scope.$watch('data.shipmentLines', function(shipmentLines) {
					if (angular.isDefined(shipmentLines)) {
						dispatchShipmentLinesInDocumentData(shipmentLines);
					}
				}, true);

				scope.$watch('document.prepared', function(prepared) {
					if (prepared) {
						// Load shipment lines but just for display.
						angular.forEach(scope.document.lines, function(shipmentLine) {
							scope.data.preparedLines.push(shipmentLine);
						});
					}
				});
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.ArrayUtils', '$q', 'RbsChange.Query', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout', '$http'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderShipmentNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderShipmentEdit', Editor);
})();