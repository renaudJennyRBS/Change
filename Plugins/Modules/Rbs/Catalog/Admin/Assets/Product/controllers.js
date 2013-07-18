(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	function PricesController($scope, $routeParams, $location, REST, i18n)
	{
		$scope.shopId = $routeParams.shopId;
		$scope.areaId = $routeParams.areaId;
		$scope.List = {};

		var query = {
			"model": "Rbs_Catalog_Price",
			"where": {
				"and": [
					{
						"op": "eq",
						"lexp": { "property" : "product" },
						"rexp": { "value": $routeParams.id}
					}/*,
					{
						"op" : "eq",
						"lexp": { "property" : "shop" },
						"rexp": { "value": scope.List.selectedShop.id }
					},
					{
						"op": "eq",
						"lexp": { "property" : "billingArea" },
						"rexp": { "value": newValue.id }
					}*/
				]
			},
			"order": [
				{
					"property": "value",
					"order": "asc"
				}
			]
		};
		$scope.List.query = query;

		var updateLocation = function (newDocId, oldDocId){
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
		};

		$scope.changeShop = function(shopId){
			updateLocation(shopId, $routeParams.shopId);
		};

		$scope.changeArea = function(areaId){
			updateLocation(areaId, $routeParams.areaId);
		};
	}

	PricesController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.i18n'];
	app.controller('Rbs_Catalog_Product_PricesController', PricesController);


	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @constructor
	 */
	function ListController($scope, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), "Rbs/Catalog/Product"]
		]);

		MainMenu.loadModuleMenu('Rbs_Catalog');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Catalog_Product_ListController', ListController);

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
			[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), "Rbs/Catalog/Product"]
		]);
		FormsManager.initResource($scope, 'Rbs_Catalog_Product');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Catalog_Product_FormController', FormController);

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
	function CategoriesController($scope, Breadcrumb, i18n, REST, Loading, Workspace, $routeParams, $http)
	{
		Workspace.collapseLeftSidebar();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), "Rbs/Catalog/Product"]
		]);

		$scope.List = {selectedCondition: null, productsToAdd: []};
		Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));
		REST.resource('Rbs_Catalog_Product', $routeParams.id).then(function (product)
		{
			$scope.document = product;
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
				url = '/catalog/product/' + $scope.document.id + '/categories/' + $scope.List.selectedCondition.id + '/';
			}
			$scope.categoryListUrl = url;
		});

		$scope.addInCategories = function (docIds, priorities)
		{
			var conditionId = $scope.List.selectedCondition.id;
			var url = REST.getBaseUrl('catalog/product/' + $scope.document.id + '/categories/' + conditionId + '/');
			$http.put(url, {addCategoryIds: docIds, priorities: priorities}, REST.getHttpConfig())
				.success(function (data)
				{
					// TODO use data
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductCategories:call', { 'method' : 'reload' });
				})
				.error(function errorCallback(data, status)
				{
					data.httpStatus = status;
					$scope.$broadcast('Change:DocumentList:DLRbsCatalogProductCategories:call', { 'method' : 'reload' });
				});
		};

		$scope.addCategoriesFromPicker = function ()
		{
			var docIds = [];
			for (var i in $scope.List.categoriesToAdd)
			{
				docIds.push($scope.List.categoriesToAdd[i].id);
			}
			$scope.addInCategories(docIds, 0);
			$scope.List.categoriesToAdd = [];
		};

		$scope.hasCategoriesToAdd = function ()
		{
			return !$scope.List.categoriesToAdd || $scope.List.categoriesToAdd.length == 0;
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
			$scope.addInCategories([doc.id], doc._highlight ? 1 : 0); // _highlight is already updated.
		}
	}

	CategoriesController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Loading',
		'RbsChange.Workspace', '$routeParams', '$http'];
	app.controller('Rbs_Catalog_Product_CategoriesController', CategoriesController);

	/**
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', function (Actions, REST, $http, i18n) {
			Actions.register({
				name: 'Rbs_Catalog_RemoveProductFromCategories',
				models: '*',
				description: i18n.trans('m.rbs.catalog.admin.js.remove-product-from-categories'),
				label: i18n.trans('m.rbs.catalog.admin.js.remove'),
				selection: "+",
				execute: ['$docs', '$scope', function ($docs, $scope) {
					var categoryIds = [];
					for (var i in $docs)
					{
						categoryIds.push($docs[i].id);
					}
					var conditionId = $scope.data.conditionId;
					var url = REST.getBaseUrl('catalog/product/' + $scope.data.containerId + '/categories/' + conditionId + '/');
					$http.put(url, {"removeCategoryIds": categoryIds}, REST.getHttpConfig())
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