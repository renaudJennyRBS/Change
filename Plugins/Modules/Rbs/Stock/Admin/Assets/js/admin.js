(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editor for 'Rbs_Stock_InventoryEntry' Model.
	__change.createEditorForModel('Rbs_Stock_InventoryEntry');


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model(null)
				.route('home', 'Rbs/Stock', { 'redirectTo': 'Rbs/Stock/Sku/'});

			$delegate.routesForModels([
				'Rbs_Stock_Sku',
				'Rbs_Stock_InventoryEntry'
			]);

			return $delegate;
		}]);

	}]);
})();