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
			[i18n.trans('m.change.catalog.admin.js.module-name | ucf'), "Change/Catalog"],
			[i18n.trans('m.change.catalog.admin.js.currency-list | ucf'), "Change/Catalog/Currency"]
		]);

		var DL = DocumentList.initScope($scope, 'Change_Catalog_Currency');
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': i18n.trans('m.change.catalog.admin.js.currency | ucf'), 'url': 'Change/Catalog/Currency/new', 'icon': 'file' }
		];

		// Configure DataTable columns
		DL.columns.shift(); // remove the status column.
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.change.admin.admin.js.modification-date | ucf'), sortable: true });

		MainMenu.loadModuleMenu('Change_Catalog');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Change_Catalog_Currency_ListController', ListController);

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
			[i18n.trans('m.change.catalog.admin.js.module-name | ucf'), "Change/Catalog"],
			[i18n.trans('m.change.catalog.admin.js.currency-list | ucf'), "Change/Catalog/Currency"]
		]);
		FormsManager.initResource($scope, 'Change_Catalog_Currency');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Change_Catalog_Currency_FormController', FormController);
})();