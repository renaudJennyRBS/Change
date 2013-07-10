(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

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
	 * List actions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', function (Actions, REST, $http) {
			Actions.register({
				name: 'Rbs_Catalog_RemoveProductFromCategories',
				models: '*',
				description: "Retirer ce produit des catégories sélectionnées.",
				label: "Retirer",
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