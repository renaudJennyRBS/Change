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
	function ListController($scope, DocumentList, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.change.users.admin.js.module-name | ucf'), "Change/Users/User"]
		]);

		var DL = DocumentList.initScope($scope, 'Change_Users_User');
		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		// Configure DataTable columns
		DL.columns.push({ id: 'activated', label: i18n.trans('m.change.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Change_Users');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Change_Users_User_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.change.users.admin.js.module-name | ucf'), "Change/Users/User"]
		]);
		FormsManager.initResource($scope, 'Change_Users_User');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Change_Users_User_FormController', FormController);
})();