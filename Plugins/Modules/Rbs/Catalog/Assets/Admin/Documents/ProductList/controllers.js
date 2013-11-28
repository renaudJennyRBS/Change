(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for products.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param i18n
	 * @param REST
	 * @param Loading
	 * @param $routeParams
	 * @param $http
	 * @param SelectSession
	 * @constructor
	 */
	function ProductsController($scope, Breadcrumb, i18n, REST, Loading, $routeParams, $http, SelectSession, UrlManager)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.adminjs.module_name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.adminjs.productlist_list | ucf'), "Rbs/Catalog/ProductList"]
		]);

		$scope.List = {};

		Loading.start(i18n.trans('m.rbs.admin.adminjs.loading_document | ucf'));
		REST.resource('Rbs_Catalog_ProductList', $routeParams.id).then(function (productList)
		{
			$scope.productsUrl = productList.META$.links['productListItems'].href;
			$scope.productList = productList;
			$scope.List.isSynchronized = productList.hasOwnProperty('synchronizedSection');
			$scope.List.isCrossSelling = productList.hasOwnProperty('crossSellingType');
			if ($scope.List.isCrossSelling)
			{
				//Update Breadcrumb
				Breadcrumb.setLocation([
					[i18n.trans('m.rbs.catalog.adminjs.module_name | ucf'), "Rbs/Catalog"],
					[i18n.trans('m.rbs.catalog.adminjs.product_list | ucf'), UrlManager.getUrl(productList.product, 'list')],
					[productList.product.label, UrlManager.getUrl(productList.product, 'form') ],
					[i18n.trans('m.rbs.catalog.adminjs.cross_selling_list | ucf'), "Rbs/Catalog/Product"]],
					[productList.label, UrlManager.getUrl(productList, 'form')]
				);
			}
			Loading.stop();
		});

		REST.action('collectionItems', { code: 'Rbs_Catalog_Collection_ProductSortOrders' }).then(function (data) {
			$scope.List.sortOrders = data.items;
		});
		REST.action('collectionItems', { code: 'Rbs_Generic_Collection_SortDirections' }).then(function (data) {
			$scope.List.sortDirections = data.items;
		});

		$scope.goBack = function ()
		{
			Breadcrumb.goParent();
		}

		$scope.List.toggleHighlight = function (doc) {
			var url = null;
			if (doc.position < 0)
			{
				url = doc.META$.actions['downplay'].href;
			}
			else
			{
				url = doc.META$.actions['highlight'].href;
			}
			callActionUrlAndReload(url);
		};

		$scope.List.moveTop = function (doc) {
			callActionUrlAndReload(doc.META$.actions['highlighttop'].href);
		};

		$scope.List.moveUp = function (doc) {
			callActionUrlAndReload(doc.META$.actions['moveup'].href);
		};

		$scope.List.moveDown = function (doc) {
			callActionUrlAndReload(doc.META$.actions['movedown'].href);
		};

		$scope.List.moveBottom = function (doc) {
			callActionUrlAndReload(doc.META$.actions['highlightbottom'].href);
		};

		if (SelectSession.started()) {
			SelectSession.commit($scope.List);
		}

		$scope.addProductsFromPicker = function ()
		{
			var docIds = [];
			for (var i in $scope.List.productsToAdd)
			{
				docIds.push($scope.List.productsToAdd[i].id);
			}
			var url = REST.getBaseUrl('rbs/catalog/productlistitem/addproducts');
			Loading.start(i18n.trans('m.rbs.admin.adminjs.loading_document | ucf'));
			$http.post(url, {"productListId": $scope.productList.id , "documentIds": docIds}).success(function (data) {
				Loading.stop();
				$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductListProducts:call', { 'method' : 'reload' });

			}).error(function errorCallback (data, status) {
					data.httpStatus = status;
					Loading.stop();
			});
			$scope.List.productsToAdd = [];
		};

		function callActionUrlAndReload(url)
		{
			if (url)
			{
				$http.get(url).success(function (data)
				{
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductListProducts:call', { 'method' : 'reload' });
				}).error(function errorCallback(data, status)
					{
						data.httpStatus = status;
						$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductListProducts:call', { 'method' : 'reload' });
					});
			}
		}
	}

	ProductsController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading',
		'$routeParams', '$http', 'RbsChange.SelectSession', 'RbsChange.UrlManager'];
	app.controller('Rbs_Catalog_ProductList_ProductsController', ProductsController);



	function ProductListController($scope, $routeParams, $location, Utils, Workspace, Breadcrumb, Loading, REST, i18n, UrlManager, Query)
	{
		$scope.params = {};
		$scope.List = {};

		$scope.loadQuery = {
			"model": "Rbs_Catalog_ProductList",

			"where": {
				"or" : [
					{
						"op" : "eq", // neq, gt, lt, gte, lte
						"lexp" : {
							"property" : "model" //, "join" : "j0"
						},
						"rexp" : {
							"value": "Rbs_Catalog_ProductList"
						}
					},
					{
						"op" : "eq",
						"lexp" : {
							"property" : "model" //, "join" : "j0"
						},
						"rexp" : {
							"value": "Rbs_Catalog_SectionProductList"
						}
					}
				]
			}
		}
	}

	ProductListController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.Utils', 'RbsChange.Workspace', 'RbsChange.Breadcrumb', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.UrlManager', 'RbsChange.Query'];
	app.controller('Rbs_Catalog_ProductList_ProductListController', ProductListController);


	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', 'RbsChange.Loading', function (Actions, REST, $http, i18n, Loading) {
			var action = function (ids, $scope, operation, priorities)
			{
				if ((operation == 'remove'))
				{
					var url = REST.getBaseUrl('rbs/catalog/productlistitem/delete');
					Loading.start(i18n.trans('m.rbs.admin.adminjs.loading_document | ucf'));
					$http.post(url, {"documentIds": ids}).success(function (data) {
						Loading.stop();
						$scope.refresh();

					})
					.error(function errorCallback (data, status) {
							data.httpStatus = status;
							Loading.stop();
							$scope.refresh();
					});
				}
			}

			Actions.register({
				name: 'Rbs_Catalog_RemoveProductsFromProductList',
				models: '*',
				description: i18n.trans('m.rbs.catalog.adminjs.remove_products_from_list'),
				label: i18n.trans('m.rbs.catalog.adminjs.remove|ucf'),
				selection: "+",
				execute: ['$docs', '$scope', function ($docs, $scope) {
					var ids = [];
					for (var i in $docs)
					{
						ids.push($docs[i].id);
					}
					action(ids, $scope, 'remove', 0);
				}]
			});

			Actions.register({
				name: 'Rbs_Catalog_HighlightProductsInProductList',
				models: '*',
				description: i18n.trans('m.rbs.catalog.adminjs.highlight_products'),
				label: i18n.trans('m.rbs.catalog.adminjs.highlight|ucf'),
				selection: "+",
				execute: ['$docs', '$scope', function ($docs, $scope) {
					var ids = [];
					for (var i in $docs)
					{
						if (!$docs[i]._highlight)
						{
							ids.push($docs[i].id);
						}
					}
					$scope.deleteProductListItems($docs);
					//action(ids, $scope, 'add', 1);
				}]
			});

			Actions.register({
				name: 'Rbs_Catalog_RemoveHighlightProductsInProductList',
				models: '*',
				description: i18n.trans('m.rbs.catalog.adminjs.remove_highlight_products'),
				label: i18n.trans('m.rbs.catalog.adminjs.remove_highlight|ucf'),
				selection: "+",
				execute: ['$docs', '$scope', function ($docs, $scope) {
					var ids = [];
					for (var i in $docs)
					{
						if ($docs[i]._highlight)
						{
							ids.push($docs[i].id);
						}
					}
					action(ids, $scope, 'add', 0);
				}]
			});
			return Actions;
		}]);
	}]);
})();