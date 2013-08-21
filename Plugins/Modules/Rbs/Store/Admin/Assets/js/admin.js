(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model(null).route('home', 'Rbs/Store/', { 'redirectTo': 'Rbs/Store/WebStore/'});
			$delegate.routesForModels(['Rbs_Store_WebStore']);
			return $delegate;
		}]);
	}]);

})();