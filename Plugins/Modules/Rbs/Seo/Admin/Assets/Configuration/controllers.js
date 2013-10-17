(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for configuration.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param REST
	 * @param $http
	 * @constructor
	 */
	function ConfigurationController($scope, Breadcrumb, MainMenu, i18n, REST, $http)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.seo.admin.js.module-name | ucf'), "Rbs/Seo"]
		]);
		Breadcrumb.setPath([i18n.trans('m.rbs.seo.admin.js.configuration | ucf')]);

		$scope.activateChange = function (model){
			console.log(model.name + ': ' + model.activated);
		};

		MainMenu.loadModuleMenu('Rbs_Seo');
	}

	ConfigurationController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n', 'RbsChange.REST', '$http'];
	app.controller('Rbs_Seo_Configuration_ConfigurationController', ConfigurationController);

})();