(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
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
			[i18n.trans('m.change.website.admin.js.module-name | ucf'), "Change/Website"],
			[i18n.trans('m.change.website.admin.js.menu-list | ucf'), "Change/Website/Menu"]
		]);

		DocumentList.initScope($scope, "Change_Website_Menu");

		$scope.createActions = [
			{ 'label': i18n.trans('m.change.website.admin.js.menu | ucf'), 'url': 'Change/Website/Menu/new' }
		];

		MainMenu.loadModuleMenu('Change_Website');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Change_Website_Menu_ListController', ListController);

	//-------------------------------------------------------------------------

	/**
	 * @param $scope
	 * @param FormsManager
	 * @param Breadcrumb
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.change.website.admin.js.module-name | ucf'), "Change/Website"],
			[i18n.trans('m.change.website.admin.js.menu-list | ucf'), "Change/Website/Menu"]
		]);
		FormsManager.initResource($scope, 'Change_Website_Menu');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Change_Website_Menu_FormController', FormController);
})();