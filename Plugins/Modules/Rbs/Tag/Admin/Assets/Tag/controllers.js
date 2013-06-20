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
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.tag.admin.js.module-name | ucf'), "Rbs/Tag"]
		]);

		MainMenu.loadModuleMenu('Rbs_Tag');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Tag_Tag_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param FormsManager
	 * @param Breadcrumb
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.tag.admin.js.module-name | ucf'), "Rbs/Tag"]
		]);
		FormsManager.initResource($scope, 'Rbs_Tag_Tag');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_Tag_Tag_FormController', FormController);

})();