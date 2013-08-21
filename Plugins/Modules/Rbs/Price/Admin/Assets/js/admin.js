(function ()
{
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
				.route('home', 'Rbs/Price', { 'redirectTo': 'Rbs/Price/Price/'});

			$delegate.routesForModels([
				'Rbs_Price_Tax',
				'Rbs_Price_Price',
				'Rbs_Price_BillingArea'
			]);

			return $delegate;
		}]);

	}]);
})();