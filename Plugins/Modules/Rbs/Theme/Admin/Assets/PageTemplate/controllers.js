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
	 * @param REST
	 * @constructor
	 */
	function ListController($scope, $routeParams, DocumentList, Breadcrumb, MainMenu, i18n, REST)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.theme.admin.js.module-name | ucf'), "Rbs/Theme"]
		]);

		if ($routeParams.theme)
		{
			REST.resource($routeParams.theme).then(function (theme)
			{
				Breadcrumb.setPath([theme]);
			});
		}

		MainMenu.loadModuleMenu('Rbs_Theme');
	}

	ListController.$inject =
		['$scope', '$routeParams', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n',
			'RbsChange.REST'];
	app.controller('Rbs_Theme_PageTemplate_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @param REST
	 * @param Utils
	 * @constructor
	 */
	function FormController ($scope, Breadcrumb, FormsManager, i18n, REST, Utils) {

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.theme.admin.js.module-name | ucf'), "Rbs/Theme"]
		]);
		FormsManager.initResource($scope, 'Rbs_Theme_PageTemplate').then(function (pageTemplate) {
			if ( ! Utils.isNew(pageTemplate) ) {
				REST.resource(pageTemplate.theme).then(function (theme) {
					Breadcrumb.setPath([theme]);
				});
			}
		});
	}

	FormController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.FormsManager',
		'RbsChange.i18n',
		'RbsChange.REST',
		'RbsChange.Utils'
	];

	app.controller('Rbs_Theme_PageTemplate_FormController', FormController);
})();