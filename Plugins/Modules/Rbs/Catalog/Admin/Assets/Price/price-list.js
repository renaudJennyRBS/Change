(function ()
{
	angular.module('RbsChange').directive('rbsPriceList', ['RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading', 'RbsChange.FormsManager',
		function (i18n, REST, Loading, FormsManager)
	{
		return {
			restrict: 'E',
			templateUrl: 'Rbs/Catalog/Price/price-list.twig',
			replace: false,
			scope: { product: '=' },

			link: function (scope, elm, attrs) {
				scope.List = {};
				scope.shopsLoading = true;
				Loading.start(i18n.trans('m.rbs.catalog.admin.js.shop-list-loading'));
				REST.collection('Rbs_Catalog_Shop').then(function (shops)
				{
					scope.List.selectedShop = null;
					scope.List.shops = shops.resources;
					Loading.stop();
					scope.shopsLoading = false;
				});

				scope.$watch('List.selectedShop', function (newValue, oldValue)
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
						REST.resource('Rbs_Catalog_Shop', newValue.id)
							.then(function (shop)
							{
								scope.List.billingAreas = shop.billingAreas;
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

					if (scope.List.selectedShop && newValue)
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
										"lexp": { "property" : "shop" },
										"rexp": { "value": scope.List.selectedShop.id }
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
								REST.resource(area.currency).then(function (currency) {
										scope.List.currencySymbol = currency.symbol;
									}
								);
							}
						);
					}
				});

				scope.cascadeCreatePrice = function () {
					var price = REST.newResource('Rbs_Catalog_Price');
					price.shop = scope.List.selectedShop;
					price.billingArea = scope.List.selectedBillingArea;
					price.product = scope.product;
					FormsManager.cascadeEditor(price, scope.product.label);
				};
			}
		};
	}]);
})();