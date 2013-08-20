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
				.route('home', 'Rbs/Theme/', { 'redirectTo': 'Rbs/Theme/Theme/'});

			$delegate.model('Rbs_Theme_Theme')
				.route('tree', '/Rbs/Theme/Theme/:id/Templates/', 'Rbs/Theme/PageTemplate/list.twig');

			$delegate.routesForModels([
				'Rbs_Theme_Theme',
				'Rbs_Theme_PageTemplate'
			]);

			return $delegate;
		}]);

	}]);

})();