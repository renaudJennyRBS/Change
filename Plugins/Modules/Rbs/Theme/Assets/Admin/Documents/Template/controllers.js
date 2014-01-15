(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $routeParams
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param REST
	 * @constructor
	 */
	function ListController($routeParams, Breadcrumb, MainMenu, REST)
	{
		if ($routeParams.id)
		{
			REST.resource('Rbs_Theme_Theme', $routeParams.id).then(function (theme)
			{
				Breadcrumb.setPath([theme]);
			});
		}

		MainMenu.loadModuleMenu('Rbs_Theme');
	}

	ListController.$inject = ['$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.REST'];
	app.controller('Rbs_Theme_Template_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param REST
	 * @param Utils
	 * @constructor
	 */
	function FormController ($scope, Breadcrumb, FormsManager, REST, Utils)
	{
		FormsManager.initResource($scope, 'Rbs_Theme_Template').then(function (pageTemplate) {
			if ( ! Utils.isNew(pageTemplate) ) {
				REST.resource(pageTemplate.theme).then(function (theme) {
					Breadcrumb.setPath([theme]);
				});
			}
		});
	}

	FormController.$inject = [ '$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.REST', 'RbsChange.Utils' ];
	app.controller('Rbs_Theme_Template_FormController', FormController);
})();