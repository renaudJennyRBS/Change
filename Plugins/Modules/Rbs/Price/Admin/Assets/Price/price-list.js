(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsPriceList', ['RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading', 'RbsChange.EditorManager',
		function (i18n, REST, Loading, EditorManager)
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Catalog/Price/price-list.twig',
			replace : false,
			scope : { product : '=' },

			link : function (scope, elm, attrs) {
				scope.List = {};
				scope.webStoresLoading = true;
				Loading.start(i18n.trans('m.rbs.store.admin.js.webstore-list-loading'));
				REST.collection('Rbs_Store_WebStore').then(function (webStores)
				{
					scope.List.selectedWebStore = null;
					scope.List.webStores = webStores.resources;
					Loading.stop();
					scope.webStoresLoading = false;
				});

				scope.$watch('List.selectedWebStore', function (newValue, oldValue)
				{
					if (newValue === oldValue)
					{
						return;
					}

					scope.List.billingAreas = [];
					scope.List.selectedBillingArea = null;
					if (newValue)
					{
						scope.List.billingAreasLoading = true;
						Loading.start(i18n.trans('m.rbs.catalog.admin.js.billingarea-list-loading'));
						REST.resource('Rbs_Store_WebStore', newValue.id)
							.then(function (webStore)
							{
								scope.List.billingAreas = webStore.billingAreas;
								Loading.stop();
								scope.List.billingAreasLoading = false;
							});
					}
				}, true);

				scope.$watch('List.selectedBillingArea', function (newValue, oldValue)
				{
					if (newValue === oldValue)
					{
						return;
					}

					if (scope.List.selectedWebStore && newValue)
					{
						var query = {
							"model": "Rbs_Catalog_Price",
							"where": {
								"and": [
									{
										"op": "eq",
										"lexp": { "property" : "product" },
										"rexp": { "value": scope.product.id }
									},
									{
										"op" : "eq",
										"lexp": { "property" : "webStore" },
										"rexp": { "value": scope.List.selectedWebStore.id }
									},
									{
										"op": "eq",
										"lexp": { "property" : "billingArea" },
										"rexp": { "value": newValue.id }
									}
								]
							},
							"order": [
								{
									"property": "value",
									"order": "asc"
								}
							]
						};
						scope.List.query = query;

						REST.resource(scope.List.selectedBillingArea).then(function (area) {
								scope.List.currencySymbol = aera.currencyCode;
							}
						);
					}
				});

				scope.cascadeCreatePrice = function () {
					var price = REST.newResource('Rbs_Catalog_Price');
					price.webStore = scope.List.selectedWebStore;
					price.billingArea = scope.List.selectedBillingArea;
					price.product = scope.product;
					EditorManager.cascade(price, scope.product.label);
				};
			}
		};
	}]);
})();