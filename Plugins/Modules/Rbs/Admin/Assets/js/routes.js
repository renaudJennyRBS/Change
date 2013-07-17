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
			'/404',
			{
				templateUrl : 'Rbs/Admin/404.twig',
				reloadOnSearch : false
			})
		. when(
			'/authenticate',
			{
				template : '<div></div>',
				controller : ['$location', 'OAuthService', function($location, OAuthService){
					console.log('Authenticate', $location.search());
					OAuthService.getAccessToken($location.search()['oauth_token'], $location.search()['oauth_verifier']);
				}]
			})
		.otherwise({ redirectTo: '/404'})
		;

	}]);

})();