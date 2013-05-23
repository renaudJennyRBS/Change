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
	 * @constructor
	 */
	function ListController($scope, DocumentList, Breadcrumb, MainMenu)
	{
		Breadcrumb.resetLocation([
			["Catalogue", "Change/Catalog"],
			["Catégories", "Change/Catalog/Category"]
		]);

		var DL = DocumentList.initScopeForTree($scope);
		DL.setDefaultTreeName('Change_Catalog');
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': "Catégorie", 'url': 'Change/Catalog/Category/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.splice(1, 0, { id: 'type', label: "", width: "24px", align: "center" });
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif.", sortable: true });
		DL.columns.push({ id: 'activated', label: "Activé", width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Change_Catalog');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu'];
	app.controller('Change_Catalog_Category_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @constructor
	 */
	function FormController($scope, Breadcrumb, FormsManager)
	{
		Breadcrumb.setLocation([
			["Catalogue", "Change/Catalog"],
			["Catégories", "Change/Catalog/Category"]
		]);
		FormsManager.initResource($scope, 'Change_Catalog_Category');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager'];
	app.controller('Change_Catalog_Category_FormController', FormController);
})();