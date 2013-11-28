(function ()
{
	"use strict";

	var app = angular.module('RbsChange');


	function CrossSellingController($scope, $routeParams, Breadcrumb, Loading, REST, i18n, UrlManager, Query)
	{
		$scope.params = {};
		$scope.List = {};

		if (!$scope.product)
		{
			Loading.start();
			REST.resource('Rbs_Catalog_Product', $routeParams.id).then(function(product){
				Loading.stop();
				Breadcrumb.setLocation([
					[i18n.trans('m.rbs.catalog.adminjs.module_name | ucf'), "Rbs/Catalog"],
					[i18n.trans('m.rbs.catalog.adminjs.product_list | ucf'), UrlManager.getUrl(product, 'list')],
					[product.label, UrlManager.getUrl(product, 'form') ],
					[i18n.trans('m.rbs.catalog.adminjs.cross_selling_list | ucf'), "Rbs/Catalog/Product"]]
				);
				$scope.product = product;
				$scope.loadQuery = Query.simpleQuery('Rbs_Catalog_CrossSellingProductList', 'product', product.id);
			});
		}
	}

	CrossSellingController.$inject = ['$scope', '$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.UrlManager', 'RbsChange.Query'];
	app.controller('Rbs_Catalog_Product_CrossSellingController', CrossSellingController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', function (Actions, REST, $http, i18n) {
			Actions.register({
				name: 'Rbs_Catalog_RemoveProductFromProductLists',
				models: '*',
				description: i18n.trans('m.rbs.catalog.adminjs.remove_products_from_list'),
				label: i18n.trans('m.rbs.catalog.adminjs.remove'),
				selection: "+",
				execute: ['$docs', '$scope', function ($docs, $scope) {
					var productListIds = [];
					for (var i in $docs)
					{
						productListIds.push($docs[i].id);
					}
					var conditionId = $scope.data.conditionId;
					var url = REST.getBaseUrl('catalog/product/' + $scope.data.containerId + '/productLists/' + conditionId + '/');
					$http.put(url, {"removeProductListIds": productListIds}, REST.getHttpConfig())
						.success(function (data) {
							$scope.refresh();
						})
						.error(function errorCallback (data, status) {
							data.httpStatus = status;
							$scope.refresh();
						});
				}]
			});
			return Actions;
		}]);
	}]);
})();