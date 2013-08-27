(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for menu.
	 *
	 * @param $scope
	 * @param REST
	 * @constructor
	 */
	function MenuController($scope, REST)
	{
		REST.collection('Rbs_Theme_Theme').then(function (themes)
		{
			$scope.themes = themes.resources;
		});
	}

	MenuController.$inject = ['$scope', 'RbsChange.REST'];
	app.controller('Rbs_Theme_MenuController', MenuController);

})();