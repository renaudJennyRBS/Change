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
	function ListController($scope, DocumentList, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.category-list | ucf'), "Rbs/Catalog/Category"]
		]);

		var DL = DocumentList.initScopeForTree($scope);
		DL.setDefaultTreeName('Rbs_Catalog');
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': i18n.trans('m.rbs.catalog.admin.js.category | ucf'), 'url': 'Rbs/Catalog/Category/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.splice(1, 0, { id: 'type', label: "", width: "24px", align: "center" });
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.rbs.admin.admin.js.modification-date | ucf'), sortable: true });
		DL.columns.push({ id: 'activated', label: i18n.trans('m.rbs.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Rbs_Catalog');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
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
})();