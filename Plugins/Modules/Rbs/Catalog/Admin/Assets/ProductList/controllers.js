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
	 * @param Workspace
	 * @param $routeParams
	 * @param $http
	 * @constructor
	 */
	function ProductsController($scope, Breadcrumb, i18n, REST, Loading, Workspace, $routeParams, $http)
	{
		Workspace.collapseLeftSidebar();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.productlist-list | ucf'), "Rbs/Catalog/ProductList"]
		]);

		$scope.List = {};

		Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
		REST.resource('Rbs_Catalog_ProductList', $routeParams.id).then(function (productList)
		{
			$scope.productsUrl = productList.META$.links['productListItems'].href;
			$scope.productList = productList;
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

		$scope.$on('$destroy', function () {
			Workspace.restore();
		});

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

		$scope.addProductsFromPicker = function ()
		{
			var docIds = [];
			for (var i in $scope.List.productsToAdd)
			{
				docIds.push($scope.List.productsToAdd[i].id);
			}
			var url = REST.getBaseUrl('rbs/catalog/productlistitem/addproducts');
			Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
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
		'RbsChange.Workspace', '$routeParams', '$http'];
	app.controller('Rbs_Catalog_ProductList_ProductsController', ProductsController);

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
					Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
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
				description: i18n.trans('m.rbs.catalog.admin.js.remove-products-from-list'),
				label: i18n.trans('m.rbs.catalog.admin.js.remove|ucf'),
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
				description: i18n.trans('m.rbs.catalog.admin.js.highlight-products'),
				label: i18n.trans('m.rbs.catalog.admin.js.highlight|ucf'),
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
				description: i18n.trans('m.rbs.catalog.admin.js.remove-highlight-products'),
				label: i18n.trans('m.rbs.catalog.admin.js.remove-highlight|ucf'),
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