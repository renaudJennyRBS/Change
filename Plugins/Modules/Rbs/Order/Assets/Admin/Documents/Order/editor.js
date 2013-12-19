(function () {

	"use strict";

	function rbsOrderOrderEditor (Utils, REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Order/Order/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.showNewLineUI = false;
				scope.showAddressUI = false;
				scope.showShippingUI = false;
				scope.userAddresses = [];
				scope.priceInfo = {
					taxInfo : [],
					billingArea : {},
					zones : []
				};
				scope.populateAddressList = function(ownerId)
				{
					if(!ownerId)
					{
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

				scope.billingAreaUpdated = function(billingAreaId)
				{
					if(!billingAreaId)
					{
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

				scope.onReady = function ()
				{
					scope.showNewLineUI = scope.document.isNew();
					if (! scope.document.linesData) {
						scope.document.linesData = [];
					}
				};

				// This watches for modifications in the user doc in order to fill the address list
				scope.$watch('document.ownerId', function (ownerId, old) {
					scope.populateAddressList(ownerId);
				}, true);

				scope.$watch('document.billingAreaId', function (billingAreaId, old) {
					scope.billingAreaUpdated(billingAreaId);
				}, true);

				scope.$watch('document.contextData.taxZone', function (taxZone, old) {
					scope.priceInfo.taxZone = taxZone;
				}, true);

				editorCtrl.init('Rbs_Order_Order');
			}
		};
	}

	rbsOrderOrderEditor.$inject = [ 'RbsChange.Utils', 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrder', rbsOrderOrderEditor);

})();