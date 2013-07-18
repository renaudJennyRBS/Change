(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @constructor
	 */
	function ListController($scope, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.category-list | ucf'), "Rbs/Catalog/Category"]
		]);

		MainMenu.loadModuleMenu('Rbs_Catalog');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Catalog_Category_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, Breadcrumb, FormsManager, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.category-list | ucf'), "Rbs/Catalog/Category"]
		]);
		FormsManager.initResource($scope, 'Rbs_Catalog_Category');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Catalog_Category_FormController', FormController);

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

		$scope.List = {selectedCondition: null, productsToAdd: []};
		Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
		REST.resource('Rbs_Catalog_Category', $routeParams.id).then(function (category)
		{
			$scope.document = category;
			Loading.stop();

			Loading.start(i18n.trans('m.rbs.catalog.admin.js.condition-list-loading'));
			REST.collection('Rbs_Catalog_Condition').then(function (conditions)
			{
				$scope.List.conditions = conditions.resources;
				/*for (var i = 5; i > 0; i--)
				{
					$scope.List.conditions.unshift({id: i, label: 'toto' + i});
				}*/
				$scope.List.conditions.unshift({id: 0, label: i18n.trans('m.rbs.catalog.admin.js.no-condition')});
				if ($scope.List.conditions.length == 1)
				{
					$scope.List.selectedCondition = $scope.List.conditions[0];
				}
				Loading.stop();
			});
		});

		REST.action('collectionItems', { code: 'Rbs_Catalog_Collection_ProductSortOrders' }).then(function (data) {
			$scope.List.sortOrders = data.items;
		});
		REST.action('collectionItems', { code: 'Rbs_Generic_Collection_SortDirections' }).then(function (data) {
			$scope.List.sortDirections = data.items;
		});

		$scope.$watch('List.selectedCondition', function (newValue, oldValue)
		{
			if (newValue === oldValue)
			{
				return;
			}

			var url = '';
			if (newValue)
			{
				url = '/catalog/category/' + $scope.document.id + '/products/' + $scope.List.selectedCondition.id + '/';
			}
			$scope.productListUrl = url;
		});

		$scope.addProducts = function (docIds, priorities)
		{
			var conditionId = $scope.List.selectedCondition.id;
			var url = REST.getBaseUrl('catalog/category/' + $scope.document.id + '/products/' + conditionId + '/');
			$http.put(url, {addProductIds: docIds, priorities: priorities}, REST.getHttpConfig())
				.success(function (data)
				{
					// TODO use data
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogCategoryProducts:call', { 'method' : 'reload' });
				})
				.error(function errorCallback(data, status)
				{
					data.httpStatus = status;
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogCategoryProducts:call', { 'method' : 'reload' });
				});
		};

		$scope.addProductsFromPicker = function ()
		{
			var docIds = [];
			for (var i in $scope.List.productsToAdd)
			{
				docIds.push($scope.List.productsToAdd[i].id);
			}
			$scope.addProducts(docIds, 0);
			$scope.List.productsToAdd = [];
		};

		$scope.hasProductsToAdd = function ()
		{
			return !$scope.List.productsToAdd || $scope.List.productsToAdd.length == 0;
		};

		$scope.canGoBack = function ()
		{
			// TODO
			return true;
		}

		$scope.goBack = function ()
		{
			Breadcrumb.goParent();
		}

		$scope.$on('$destroy', function () {
			Workspace.restore();
		});

		$scope.List.toggleHighlight = function (doc) {
			$scope.addProducts([doc.id], doc._highlight ? 1 : 0); // _highlight is already updated.
		}

		$scope.List.moveTop = function (doc) {
			$scope.addProducts([doc.id], 'top');
		}

		$scope.List.moveUp = function (doc) {
			$scope.addProducts([doc.id], doc._priority + 1);
		}

		$scope.List.moveDown = function (doc) {
			$scope.addProducts([doc.id], doc._priority - 1);
		}

		$scope.List.moveBottom = function (doc) {
			$scope.addProducts([doc.id], 1);
		}
	}

	ProductsController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading',
		'RbsChange.Workspace', '$routeParams', '$http'];
	app.controller('Rbs_Catalog_Category_ProductsController', ProductsController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', function (Actions, REST, $http, i18n) {
			var action = function (ids, $scope, operation, priorities)
			{
				var params =  (operation == 'remove') ? {removeProductIds: ids} : {addProductIds: ids, priorities: priorities};
				var conditionId = $scope.data.conditionId;
				var url = REST.getBaseUrl('catalog/category/' + $scope.data.containerId + '/products/' + conditionId + '/');
				$http.put(url, params, REST.getHttpConfig())
					.success(function (data) {
						// TODO use data
						$scope.refresh();
					})
					.error(function errorCallback (data, status) {
						data.httpStatus = status;
						$scope.refresh();
					});
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
					action(ids, $scope, 'remove', null);
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
					action(ids, $scope, 'add', 1);
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