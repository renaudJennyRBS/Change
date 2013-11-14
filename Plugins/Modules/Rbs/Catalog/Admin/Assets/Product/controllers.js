(function ()
{
	"use strict";

	var app = angular.module('RbsChange');


	function PricesController($scope, $routeParams, $location, Utils, Workspace, Breadcrumb, Loading, REST, i18n, UrlManager)
	{
		Workspace.collapseLeftSidebar();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), "Rbs/Catalog/Product"]
		]);

		$scope.$on('$destroy', function () {
			Workspace.restore();
		});
		$scope.params = {};
		$scope.params.webStoreId = $routeParams.webStoreId;
		$scope.params.areaId = $routeParams.areaId;
		$scope.List = {};
		if ($routeParams.startActivation){
			$scope.params.startActivation = moment($routeParams.startActivation).toDate();
		}

		if ($routeParams.endActivation){
			$scope.params.endActivation = moment($routeParams.endActivation).toDate();
		}

		if (!$scope.product)
		{
			Loading.start();
			REST.resource('Rbs_Catalog_Product', $routeParams.id).then(function(product){
				Loading.stop();
				Breadcrumb.setLocation([
					[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
					[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), UrlManager.getUrl(product, 'list')],
					[product.label, UrlManager.getUrl(product, 'form') ],
					[i18n.trans('m.rbs.price.admin.js.price-list | ucf'), "Rbs/Catalog/Product"]]
				);
				$scope.product = product;
				updatePricesURL();
			});
		}

		/*var updateLocation = function (newDocId, oldDocId){
			var regexp = new RegExp('/' + oldDocId + '/');
			var path = $location.path();
			if (regexp.test(path))
			{
				var replace = newDocId ? '/' + newDocId + '/' : '/';
				$location.path(path.replace(regexp, replace));
			}
			else
			{
				if (newDocId > 0)
				{
					$location.path(path +  newDocId + '/');
				}
			}
		};*/

		var updatePricesURL = function(){
			var params = { 	'areaId': $scope.params.areaId == '' ? null :  $scope.params.areaId ,
							'webStoreId': $scope.params.webStoreId == '' ? null : $scope.params.webStoreId ,
							'startActivation': $scope.params.startActivation ? moment($scope.params.startActivation).format() : null,
							'endActivation':  $scope.params.endActivation ? moment($scope.params.endActivation).format() : null
			};
			if ($scope.product)
			{
				$scope.pricesURL = Utils.makeUrl($scope.product.META$.links['prices'].href, params);
			}
		};

		$scope.changeWebStore = function(webStoreId){
			if (webStoreId == ''){
				webStoreId = null;
				$scope.params.areaId = null;
			}
			$location.search('webStoreId', webStoreId);
			updatePricesURL();
		};

		$scope.changeArea = function(areaId){
			if (areaId == ''){
				areaId = null;
			}
			$location.search('areaId', areaId);
			updatePricesURL();
		};

		$scope.changeStartActivation = function(date){
			if (date)
			{
				$location.search('startActivation', moment(date).format());
			}
			else
			{
				$location.search('startActivation', null);
			}
			updatePricesURL();
		};

		$scope.changeEndActivation = function(date){
			if (date)
			{
				$location.search('endActivation', moment(date).format());
			}
			else
			{
				$location.search('endActivation', null);
			}
			updatePricesURL();
		};
	}

	PricesController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.Utils', 'RbsChange.Workspace', 'RbsChange.Breadcrumb', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.UrlManager'];
	app.controller('Rbs_Catalog_Product_PricesController', PricesController);


	function CrossSellingController($scope, $routeParams, $location, Utils, Workspace, Breadcrumb, Loading, REST, i18n, UrlManager, Query)
	{
		//Workspace.collapseLeftSidebar();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), "Rbs/Catalog/Product"]
		]);

		$scope.$on('$destroy', function () {
			Workspace.restore();
		});
		$scope.params = {};
		$scope.List = {};

		if (!$scope.product)
		{
			Loading.start();
			REST.resource('Rbs_Catalog_Product', $routeParams.id).then(function(product){
				Loading.stop();
				Breadcrumb.setLocation([
					[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
					[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), UrlManager.getUrl(product, 'list')],
					[product.label, UrlManager.getUrl(product, 'form') ],
					[i18n.trans('m.rbs.catalog.admin.js.cross-selling-list | ucf'), "Rbs/Catalog/Product"]]
				);
				$scope.product = product;
				$scope.loadQuery = Query.simpleQuery('Rbs_Catalog_CrossSellingProductList', 'product', product.id);
			});
		}
	}

	CrossSellingController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.Utils', 'RbsChange.Workspace', 'RbsChange.Breadcrumb', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.UrlManager', 'RbsChange.Query'];
	app.controller('Rbs_Catalog_Product_CrossSellingController', CrossSellingController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', function (Actions, REST, $http, i18n) {
			Actions.register({
				name: 'Rbs_Catalog_RemoveProductFromProductLists',
				models: '*',
				description: i18n.trans('m.rbs.catalog.admin.js.remove-product-from-lists'),
				label: i18n.trans('m.rbs.catalog.admin.js.remove'),
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