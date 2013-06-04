(function () {

	"use strict";

	//-------------------------------------------------------------------------
	//
	// Routes definition
	// See the "routes.js" file in each module.
	//
	//-------------------------------------------------------------------------

	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider
		. when(
			'/',
			{
				templateUrl : 'Rbs/Admin/dashboard/dashboard.twig',
				reloadOnSearch : false
			})

		. when(
			'/login',
			{
				templateUrl : 'Rbs/Users/login.twig',
				reloadOnSearch : false
			})
		;

	}]);

})();