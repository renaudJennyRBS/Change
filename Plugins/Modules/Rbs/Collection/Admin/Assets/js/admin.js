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
			$delegate.model(null)
				.route('home', 'Rbs/Collection', { 'redirectTo': 'Rbs/Collection/Collection/'});

			$delegate.routesForModels([
				'Rbs_Collection_Collection'
			]);
			return $delegate;
		}]);

	}]);

})();