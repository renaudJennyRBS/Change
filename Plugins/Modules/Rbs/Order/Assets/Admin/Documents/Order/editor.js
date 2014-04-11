/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	function rbsDocumentEditorRbsOrderOrderNew(REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
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
						scope.document.currencyCode = data.currencyCode;
					});
					REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id: billingAreaId}).then(function(data) {
						var zones = [];
						angular.forEach(data, function(tax) {
							angular.forEach(tax.zones, function(zone) {
								if (zones.indexOf(zone) == -1) {
									zones.push(zone);
								}
							});
						});
						zones.sort();
						scope.zones = zones;
						if (zones.length == 1) {
							scope.document.context.taxZone = zones[0];
						}
					});
				};

				scope.ownerUpdated = function(ownerId) {
					if (angular.isObject(ownerId) && ownerId.hasOwnProperty('id')) {
						ownerId = ownerId.id;
					}

					if (ownerId) {
						REST.resource('Rbs_User_User', ownerId).then(function(data) {
							if (!scope.document.email) {
								scope.document.email = data.email;
							}
						});
					}
				};

				scope.$watch('document.webStoreId', function(webStoreId) {
					scope.webStoreUpdated(webStoreId);
				}, true);

				scope.$watch('document.billingAreaId', function(billingAreaId) {
					scope.billingAreaUpdated(billingAreaId);
				}, true);

				scope.$watch('document.ownerId', function(ownerId) {
					scope.ownerUpdated(ownerId);
				}, true);
			}
		};
	}

	rbsDocumentEditorRbsOrderOrderNew.$inject = [ 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrderNew', rbsDocumentEditorRbsOrderOrderNew);

	function rbsDocumentEditorRbsOrderOrderEdit(REST, i18n, NotificationCenter, ErrorFormatter, Dialog, $timeout) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				scope.orderContext = {
					showAddressUI: false,
					showShippingUI: false,
					showNewProductLineUI: false,
					showNewCustomLineUI: false,
					showCouponUI: false,
					showNewCouponUI: false,
					showModifiersUI: false,
					showNewDocumentFeeUI: false,
					showNewCustomFeeUI: false,
					showNewDocumentDiscountUI: false,
					showNewCustomDiscountUI: false
				};
				scope.userAddresses = [];
				scope.priceInfo = {
					decimals: 2,
					currencyCode: null,
					taxInfo: [],
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
						priceInfo: scope.priceInfo
					});
				};

				scope.onRestoreContext = function(currentContext) {
					console.log('onRestoreContext');
					var toRestoreData = currentContext.savedData('order');
					scope.orderContext = toRestoreData.orderContext;
					scope.userAddresses = toRestoreData.userAddresses;
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
					console.log(angular.copy(scope.document.currencyCode));
					scope.priceInfo.currencyCode = scope.document.currencyCode;
					REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id: scope.document.billingAreaId}).then(function(data) {
						scope.priceInfo.taxInfo = data;
						scope.priceInfo.currentTaxInfo = taxInfosForZone(scope.priceInfo.taxZone, data);
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
									'lexp': { 'property': 'ownerId' },
									'rexp': { 'value': ownerId }
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
							jQuery($event.target).parents('rbs-document-editor-button-bar').find('.confirmation-area'),
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
			}
		};
	}

	rbsDocumentEditorRbsOrderOrderEdit.$inject = [ 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter',
		'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrderEdit', rbsDocumentEditorRbsOrderOrderEdit);
})();