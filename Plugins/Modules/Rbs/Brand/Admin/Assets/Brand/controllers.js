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
			[i18n.trans('m.rbs.brand.admin.js.module-name | ucf'), "Rbs/Brand"],
			[i18n.trans('m.rbs.brand.admin.js.brand-list | ucf'), "Rbs/Brand/Brand/"]
		]);

		MainMenu.loadModuleMenu('Rbs_Brand');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Brand_Brand_ListController', ListController);

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
			[i18n.trans('m.rbs.brand.admin.js.module-name | ucf'), "Rbs/Brand"],
			[i18n.trans('m.rbs.brand.admin.js.brand-list | ucf'), "Rbs/Brand/Brand/"]
		]);
		FormsManager.initResource($scope, 'Rbs_Brand_Brand');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Brand_Brand_FormController', FormController);
})();