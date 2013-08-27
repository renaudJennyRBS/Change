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
			[i18n.trans('m.rbs.catalog.admin.js.category-list | ucf'), "Rbs/Catalog/Category"]
		]);


		$scope.List = {};


		Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
		REST.resource('Rbs_Catalog_Category', $routeParams.id).then(function (category)
		{
			$scope.productsUrl = category.META$.links['productcategorizations'].href;
			$scope.category = category;
			Loading.stop();
		});

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
			var url = REST.getBaseUrl('rbs/catalog/productcategorization/addproducts');
			Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
			$http.post(url, {"categoryId": $scope.category.id , "documentIds": docIds}).success(function (data) {
				Loading.stop();
				$scope.$broadcast('Change:DocumentList:DLRbsCatalogCategoryProducts:call', { 'method' : 'reload' });

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
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogCategoryProducts:call', { 'method' : 'reload' });
				}).error(function errorCallback(data, status)
					{
						data.httpStatus = status;
						$scope.$broadcast('Change:DocumentList:DLRbsCatalogCategoryProducts:call', { 'method' : 'reload' });
					});
			}
		}
	}

	ProductsController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading',
		'RbsChange.Workspace', '$routeParams', '$http'];
	app.controller('Rbs_Catalog_Category_ProductsController', ProductsController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', 'RbsChange.Loading', function (Actions, REST, $http, i18n, Loading) {
			var action = function (ids, $scope, operation, priorities)
			{
				if ((operation == 'remove'))
				{
					var url = REST.getBaseUrl('rbs/catalog/productcategorization/delete');
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
				name: 'Rbs_Catalog_RemoveProductsFromCategory',
				models: '*',
				description: i18n.trans('m.rbs.catalog.admin.js.remove-products-from-category'),
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
				name: 'Rbs_Catalog_HighlightProductsInCategory',
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
					$scope.deleteProductCategorizations($docs);
					//action(ids, $scope, 'add', 1);
				}]
			});

			Actions.register({
				name: 'Rbs_Catalog_RemoveHighlightProductsInCategory',
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