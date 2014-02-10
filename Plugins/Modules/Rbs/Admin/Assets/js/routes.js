(function () {

	"use strict";

	//-------------------------------------------------------------------------
	//
	// Routes definition
	// See the "routes.js" file in each module.
	//
	//-------------------------------------------------------------------------

	var app = angular.module('RbsChange');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			var home = {
				"/": {
					"module": "Rbs_Admin", "name": "dashboard",
					"rule": {
						"templateUrl": "Rbs/Admin/dashboard/dashboard.twig",
						"labelKey":"m.rbs.admin.adminjs.home | ucf"
				}
			}};
			$delegate.applyConfig(home);
			$delegate.applyConfig(__change.routes);

			return $delegate;
		}]);
	}]);

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider
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
					OAuthService.getAccessToken($location.search()['oauth_token'], $location.search()['oauth_verifier']);
				}]
			})
		.otherwise({ redirectTo: '/404'})
		;
	}]);
})();