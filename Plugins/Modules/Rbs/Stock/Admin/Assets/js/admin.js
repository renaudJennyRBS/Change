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
				.route('home', 'Rbs/Stock/', { 'redirectTo': 'Rbs/Stock/InventoryEntry/'});

			$delegate.routesForModels([
				'Rbs_Stock_Sku',
				'Rbs_Stock_InventoryEntry'
			]);

			return $delegate;
		}]);

	}]);
})();