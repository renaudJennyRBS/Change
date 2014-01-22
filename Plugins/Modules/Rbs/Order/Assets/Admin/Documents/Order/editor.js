(function () {

	"use strict";

	function rbsOrderOrderEditor (Utils, REST, i18n, NotificationCenter, ErrorFormatter)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Order/Order/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {

				scope.showNewLineUI = false;
				scope.showAddressUI = false;
				scope.showShippingUI = false;
				scope.userAddresses = [];
				scope.priceInfo = {
					taxInfo : [],
					billingArea : {},
					zones : [],
					withTax : false
				};

				scope.populateAddressList = function(ownerId) {
					if(!ownerId) {
						scope.userAddresses = [];
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

					REST.query(query, {'column': ['label', 'addressFields', 'fieldValues']}).then(function (data){
						scope.userAddresses = data.resources;
					});
				};

				scope.billingAreaUpdated = function(billingAreaId) {
					if(!billingAreaId) {
						return;
					}
					REST.resource('Rbs_Price_BillingArea', billingAreaId).then(function(data){
						scope.priceInfo.billingArea = data;
						scope.document.currencyCode = data.currencyCode;
					});
					REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id:billingAreaId}).then(function(data){
						scope.priceInfo.taxInfo = data;
						var zones = [];
						angular.forEach(scope.priceInfo.taxInfo, function(tax){
							angular.forEach(tax.zones, function(zone){
								if(zones.indexOf(zone) == -1){
									zones.push(zone);
								}
							});
						});
						zones.sort();
						scope.priceInfo.zones = zones;
					});
				};

				scope.webStoreUpdated = function(webStoreId) {
					if(!webStoreId) {
						return;
					}
					REST.resource('Rbs_Store_WebStore', webStoreId).then(function(data) {
						scope.document.contextData.pricesValueWithTax = data.pricesValueWithTax;
					});
				};

				scope.onLoad = function() {
					if (angular.isArray(scope.document.contextData) || !angular.isObject(scope.document.contextData)) {
						scope.document.contextData = {};
					}

					if (!angular.isArray(scope.document.linesData)) {
						scope.document.linesData = [];
					}

					if (angular.isArray(scope.document.addressData) || !angular.isObject(scope.document.addressData)) {
						scope.document.addressData = {};
					}
				};

				scope.onReady = function() {
					scope.showNewLineUI = scope.document.isNew();

					if (!scope.isNew()) {
						REST.call(scope.document.getLink('shipments'), {
							column: ['code', 'shippingModeCode', 'trackingCode', 'carrierStatus']
						}).then(function (data){
							scope.shipments = data.resources;
						}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.order_invalid_query_order_shipments | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
					}
				};

				// This watches for modifications in the user doc in order to fill the address list
				scope.$watch('document.ownerId', function (ownerId) {
					scope.populateAddressList(ownerId);
				}, true);

				scope.$watch('document.billingAreaId', function (billingAreaId) {
					scope.billingAreaUpdated(billingAreaId);
				}, true);

				scope.$watch('document.webStoreId', function (webStoreId) {
					scope.webStoreUpdated(webStoreId);
				}, true);

				scope.$watch('document.contextData.taxZone', function (taxZone) {
					scope.priceInfo.taxZone = taxZone;
				}, true);

				scope.$watch('document.contextData.pricesValueWithTax', function (pricesValueWithTax) {
					scope.priceInfo.withTax = pricesValueWithTax;
				}, true);

				// This refreshes shippingDataObject to be synchronized with order editor
				scope.$on('shippingModesUpdatedFromLines', function (event) {
					scope.$broadcast('shippingModesUpdated');
					scope.showShippingUI = true;
				});

				editorCtrl.init('Rbs_Order_Order');
			}
		};
	}

	rbsOrderOrderEditor.$inject = [ 'RbsChange.Utils', 'RbsChange.REST', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrder', rbsOrderOrderEditor);

})();