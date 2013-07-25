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
			[i18n.trans('m.rbs.store.admin.js.module-name | ucf'), "Rbs/Store/"],
			[i18n.trans('m.rbs.store.admin.js.webstore-list | ucf'), "Rbs/Store/WebStore/"]
		]);

		MainMenu.loadModuleMenu('Rbs_Store');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Store_WebStore_ListController', ListController);

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
			[i18n.trans('m.rbs.store.admin.js.module-name | ucf'), "Rbs/Store/"],
			[i18n.trans('m.rbs.store.admin.js.webstore-list | ucf'), "Rbs/Store/WebStore/"]
		]);
		FormsManager.initResource($scope, 'Rbs_Store_WebStore');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Store_WebStore_FormController', FormController);
})();