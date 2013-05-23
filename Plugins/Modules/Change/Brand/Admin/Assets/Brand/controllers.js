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
			["Marques", "Change/Brand/Brand"]
		]);

		var DL = DocumentList.initScope($scope, 'Change_Brand_Brand');
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': "Marque", 'url': 'Change/Brand/Brand/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif.", sortable: true });
		DL.columns.push({ id: 'activated', label: "Activé", width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Change_Catalog');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu'];
	app.controller('Change_Brand_Brand_ListController', ListController);

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
			["Marques", "Change/Brand/Brand"]
		]);
		FormsManager.initResource($scope, 'Change_Brand_Brand');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager'];
	app.controller('Change_Brand_Brand_FormController', FormController);
})();