(function() {
	"use strict";

	function rbsOrderOrderEditor(REST, i18n, NotificationCenter, ErrorFormatter, Dialog, $timeout) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Order/Order/editor.twig',
			replace: false,
			require: 'rbsDocumentEditor',

			link: function(scope, element, attrs, editorCtrl) {
				scope.orderContext = {
					showAddressUI: false,
					showShippingUI: false,
					showShipmentUI: false
				};
				scope.userAddresses = [];
				scope.shipments = [];
				scope.priceInfo = {
					decimals: 2,
					currencyCode: null,
					taxInfo: [],
					zones: [],
					withTax: false
				};
				scope.amounts = {
					totalFeesAmount: 0,
					totalDiscountsAmount: 0
				};

				var contextRestored = false;

				scope.onSaveContext = function(currentContext) {
					console.log('onSaveContext');
					currentContext.savedData('order', {
						orderContext: scope.orderContext,
						userAddresses: scope.userAddresses,
						shipments: scope.shipments,
						priceInfo: scope.priceInfo
					});
				};

				scope.onRestoreContext = function(currentContext) {
					console.log('onRestoreContext');
					var toRestoreData = currentContext.savedData('order');
					scope.orderContext = toRestoreData.orderContext;
					scope.userAddresses = toRestoreData.userAddresses;
					scope.shipments = toRestoreData.shipments;
					scope.priceInfo = toRestoreData.priceInfo;
					contextRestored = true;
				};

				// When this method returns false, taxes and amount details are hidden until the document is saved.
				scope.amountsModified = function() {
					return scope.isPropertyModified('lines') || scope.isPropertyModified('fees')
						|| scope.isPropertyModified('discount') || scope.isPropertyModified('creditNotes');
				};

				scope.onLoad = function() {
					if (angular.isArray(scope.document.context) || !angular.isObject(scope.document.context)) {
						scope.document.context = {};
					}

					if (!angular.isArray(scope.document.lines)) {
						scope.document.lines = [];
					}

					if (angular.isArray(scope.document.address) || !angular.isObject(scope.document.address)) {
						scope.document.address = {};
					}

					if (!angular.isArray(scope.document.shippingModes)) {
						scope.document.shippingModes = [];
					}
				};

				scope.onReady = function() {
					scope.orderContext.showNewLineUI = scope.document.isNew();
					var shipmentsLink = scope.document.getLink('shipments');
					if (shipmentsLink) {
						var successCallback = function(data) {
							scope.shipments = data.resources;
						};
						var errorCallback = function(error) {
							NotificationCenter.error(
								i18n.trans('m.rbs.order.adminjs.order_invalid_query_order_shipments | ucf'),
								ErrorFormatter.format(error)
							);
							console.error(error);
						};
						REST.call(shipmentsLink, { column: ['code', 'shippingModeCode', 'trackingCode', 'carrierStatus'] })
							.then(successCallback, errorCallback);
					}
				};

				scope.webStoreUpdated = function(webStoreId) {
					if (!webStoreId) {
						return;
					}
					REST.resource('Rbs_Store_WebStore', webStoreId).then(function(data) {
						scope.document.context.pricesValueWithTax = data.pricesValueWithTax;
						if (angular.isArray(data['billingAreas'])) {
							if (data['billingAreas'].length == 1) {
								scope.document.billingAreaId = data['billingAreas'][0].id;
							}
						}
					});
				};

				scope.billingAreaUpdated = function(billingAreaId) {
					if (!billingAreaId) {
						return;
					}
					REST.resource('Rbs_Price_BillingArea', billingAreaId).then(function(data) {
						scope.priceInfo.currencyCode = data.currencyCode;
						scope.document.currencyCode = data.currencyCode;
					});
					REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id: billingAreaId}).then(function(data) {
						scope.priceInfo.taxInfo = data;
						scope.priceInfo.currentTaxInfo = taxInfosForZone(scope.priceInfo.taxZone, data);
						var zones = [];
						angular.forEach(scope.priceInfo.taxInfo, function(tax) {
							angular.forEach(tax.zones, function(zone) {
								if (zones.indexOf(zone) == -1) {
									zones.push(zone);
								}
							});
						});
						zones.sort();
						scope.priceInfo.zones = zones;
						if (zones.length == 1) {
							scope.document.context.taxZone = zones[0];
						}
					});
				};

				scope.ownerUpdated = function(ownerId) {
					if (angular.isObject(ownerId) && ownerId.hasOwnProperty('id')) {
						ownerId = ownerId.id;
					}

					if (!angular.isNumber(ownerId)) {
						scope.userAddresses = [];
						scope.owner = {};
						return;
					}

					var query = {
						'model': 'Rbs_Geo_Address',
						'where': {
							'and': [
								{
									'op': 'eq',
									'lexp': {
										'property': 'ownerId'
									},
									'rexp': {
										'value': ownerId
									}
								}
							]
						}
					};

					REST.query(query, {'column': ['label', 'addressFields', 'fieldValues']}).then(function(data) {
						scope.userAddresses = data.resources;
					});

					if (ownerId) {
						REST.resource('Rbs_User_User', ownerId).then(function(data) {
							scope.owner = data;
							if (!scope.document.email) {
								scope.document.email = data.email;
							}
						});
					}
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

				scope.isContentEditable = function() {
					return scope.document.processingStatus == 'edition';
				};

				scope.showLinesAmount = function() {
					if (!scope.document) {
						return false;
					}
					else if (!angular.isArray(scope.document['fees']) || scope.document['fees'].length == 0) {
						return false;
					}
					else if (!angular.isArray(scope.document['discounts']) || scope.document['discounts'].length == 0) {
						return false;
					}
					return true;
				};

				scope.showTotalAmount = function() {
					if (!scope.document) {
						return false;
					}
					else if (!angular.isArray(scope.document['creditNotes']) || scope.document['creditNotes'].length == 0) {
						return false;
					}
					return true;
				};

				scope.updateOrderStatus = function(status, $event) {
					var options = {
						pointedElement: jQuery($event.target),
						primaryButtonClass: 'btn-success',
						cssClass: 'default'
					};
					if (status == 'edition') {
						options.primaryButtonClass = 'btn-warning';
						options.cssClass = 'warning';
					}
					else if (status == 'canceled') {
						options.primaryButtonClass = 'btn-danger';
						options.cssClass = 'danger';
					}
					Dialog.confirmEmbed(
							jQuery($event.target).parents('rbs-form-button-bar').find('.confirmation-area'),
							i18n.trans('m.rbs.order.adminjs.order_update_status_confirm_title_' + status + ' | ucf'),
							i18n.trans('m.rbs.order.adminjs.order_update_status_confirm_message_' + status + ' | ucf'),
							scope,
							options
						)
						.then(function() {
							scope.document.processingStatus = status;
							// Wait for angular to check changes.
							$timeout(function() { scope.submit(); });
						});
				};

				function taxInfosForZone(taxZone, taxInfo) {
					var currentTaxInfo = [];
					if (taxZone && angular.isArray(taxInfo)) {
						for (var i = 0; i < taxInfo.length; i++) {
							if (taxInfo[i].zones.indexOf(taxZone) > -1) {
								currentTaxInfo.push(taxInfo[i]);
							}
						}
					}
					return currentTaxInfo;
				}

				scope.$watch('document.webStoreId', function(webStoreId) {
					scope.webStoreUpdated(webStoreId);
				}, true);

				scope.$watch('document.billingAreaId', function(billingAreaId) {
					scope.billingAreaUpdated(billingAreaId);
				}, true);

				// This watches for modifications in the user doc in order to fill the address list.
				scope.$watch('document.ownerId', function(ownerId) {
					scope.ownerUpdated(ownerId);
				}, true);

				scope.$watch('document.context.taxZone', function(taxZone) {
					scope.priceInfo.taxZone = taxZone;
					scope.priceInfo.currentTaxInfo = taxInfosForZone(taxZone, scope.priceInfo.taxInfo);
				}, true);

				scope.$watch('document.context.pricesValueWithTax', function(pricesValueWithTax) {
					scope.priceInfo.withTax = pricesValueWithTax;
				}, true);

				scope.$watch('document.context.decimals', function(decimals) {
					scope.priceInfo.decimals = decimals;
				}, true);

				// This refreshes shippingModesObject to be synchronized with order editor.
				scope.$on('shippingModesUpdatedFromLines', function() {
					scope.$broadcast('shippingModesUpdated');
					scope.orderContext.showShippingUI = true;
				});

				editorCtrl.init('Rbs_Order_Order');
			}
		};
	}

	rbsOrderOrderEditor.$inject = [ 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter',
		'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrder', rbsOrderOrderEditor);
})();