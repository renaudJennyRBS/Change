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
				scope.shipments = [];
				scope.priceInfo = {
					currencyCode : null,
					taxInfo : [],
					zones : [],
					withTax : false
				};
				scope.userAddressFieldsId = 0;

				scope.onLoad = function() {
					if (angular.isArray(scope.document.context) || !angular.isObject(scope.document.context)) {
						scope.document.context = {};
					}

					if (scope.document.context.hasOwnProperty('addressFields')) {
						scope.userAddressFieldsId = scope.document.context.addressFields;
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
					scope.showNewLineUI = scope.document.isNew();

					var shipmentsLink = scope.document.getLink('shipments');
					if (shipmentsLink) {
						REST.call(shipmentsLink, {
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

				scope.populateAddressList = function(ownerId) {
					if (!ownerId) {
						scope.userAddresses = [];
						return;
					}

					if (angular.isObject(ownerId)) {
						if (ownerId.hasOwnProperty('id')) {
							ownerId = ownerId.id;
						} else {
							return;
						}
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

					if (!scope.document.email)
					{
						REST.resource('Rbs_User_User', ownerId).then(function(data) {
							scope.document.email = data.email;
						});
					}
				};

				scope.webStoreUpdated = function(webStoreId) {
					if (!webStoreId) {
						return;
					}
					REST.resource('Rbs_Store_WebStore', webStoreId).then(function(data) {
						scope.document.context.pricesValueWithTax = data.pricesValueWithTax;
						if (angular.isArray(data.billingAreas)) {
							if (data.billingAreas.length == 1) {
								scope.document.billingAreaId = data.billingAreas[0].id;
							}
						}
					});
				};


				scope.billingAreaUpdated = function(billingAreaId) {
					if(!billingAreaId) {
						return;
					}
					REST.resource('Rbs_Price_BillingArea', billingAreaId).then(function(data){
						scope.priceInfo.currencyCode = data.currencyCode;
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
						if (zones.length == 1)
						{
							scope.document.context.taxZone = zones[0];
						}
					});
				};

				scope.$watch('document.webStoreId', function (webStoreId) {
					scope.webStoreUpdated(webStoreId);
				}, true);

				scope.$watch('document.billingAreaId', function (billingAreaId) {
					scope.billingAreaUpdated(billingAreaId);
				}, true);

				// This watches for modifications in the user doc in order to fill the address list
				scope.$watch('document.ownerId', function (ownerId) {
					scope.populateAddressList(ownerId);
				}, true);

				scope.$watch('document.context.taxZone', function (taxZone) {
					scope.priceInfo.taxZone = taxZone;
				}, true);

				scope.$watch('document.context.pricesValueWithTax', function (pricesValueWithTax) {
					scope.priceInfo.withTax = pricesValueWithTax;
				}, true);

				// This refreshes shippingModesObject to be synchronized with order editor
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