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
			[i18n.trans('m.rbs.price.admin.js.module-name | ucf'), "Rbs/Price"],
			[i18n.trans('m.rbs.price.admin.js.tax-list | ucf'), "Rbs/Price/Tax"]
		]);

		MainMenu.loadModuleMenu('Rbs_Price');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Price_Tax_ListController', ListController);

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
			[i18n.trans('m.rbs.price.admin.js.module-name | ucf'), "Rbs/Price"],
			[i18n.trans('m.rbs.price.admin.js.tax-list | ucf'), "Rbs/Price/Tax"]
		]);
		FormsManager.initResource($scope, 'Rbs_Price_Tax');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Price_Tax_FormController', FormController);
})();