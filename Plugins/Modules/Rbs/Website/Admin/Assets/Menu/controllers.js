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
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"],
			[i18n.trans('m.rbs.website.admin.js.menu-list | ucf'), "Rbs/Website/Menu"]
		]);

		DocumentList.initScope($scope, "Rbs_Website_Menu");

		$scope.createActions = [
			{ 'label': i18n.trans('m.rbs.website.admin.js.menu | ucf'), 'url': 'Rbs/Website/Menu/new' }
		];

		MainMenu.loadModuleMenu('Rbs_Website');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Website_Menu_ListController', ListController);

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
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"],
			[i18n.trans('m.rbs.website.admin.js.menu-list | ucf'), "Rbs/Website/Menu"]
		]);
		FormsManager.initResource($scope, 'Rbs_Website_Menu');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_Website_Menu_FormController', FormController);
})();