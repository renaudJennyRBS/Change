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
	 * @param MainMenu
	 * @param $routeParams
	 * @param $http
	 * @constructor
	 */
	function ProductsController($scope, Breadcrumb, i18n, REST, Loading, Workspace, MainMenu, $routeParams, $http)
	{
		Workspace.collapseLeftSidebar();
		MainMenu.hide();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.category-list | ucf'), "Rbs/Catalog/Category"]
		]);

		$scope.List = {selectedCondition: null};
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

		$scope.addProducts = function (docIds)
		{
			var conditionId = $scope.List.selectedCondition.id;
			var url = REST.getBaseUrl('catalog/category/' + $scope.document.id + '/products/' + conditionId + '/');
			$http.put(url, {"addProductIds": docIds, "priorities": 0}, REST.getHttpConfig())
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

		$scope.canGoBack = function ()
		{
			// TODO
			return true;
		}

		$scope.goBack = function ()
		{
			Workspace.expandLeftSidebar();
			MainMenu.show();
			Breadcrumb.goParent();
		}
	}

	ProductsController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading',
		'RbsChange.Workspace', 'RbsChange.MainMenu', '$routeParams', '$http'];
	app.controller('Rbs_Catalog_Category_ProductsController', ProductsController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', function (Actions, REST, $http) {
			Actions.register({
				name: 'Rbs_Catalog_RemoveProductsFromCategory',
				models: '*',
				description: "Retirer les produits sélectionnés de la catégorie.",
				label: "Retirer",
				selection: "+",
				execute: ['$docs', '$scope', function ($docs, $scope) {
					var categoryIds = [];
					for (var i in $docs)
					{
						categoryIds.push($docs[i].id);
					}
					var conditionId = $scope.data.conditionId;
					var url = REST.getBaseUrl('catalog/category/' + $scope.data.containerId + '/products/' + conditionId + '/');
					$http.put(url, {"removeProductIds": categoryIds}, REST.getHttpConfig())
						.success(function (data) {
							// TODO use data
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