(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Stock_InventoryEntry');


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model(null)
				.route('home', 'Rbs/Stock', { 'redirectTo': 'Rbs/Stock/InventoryEntry/'});

			$delegate.routesForModels([
				'Rbs_Stock_InventoryEntry'
			]);

			return $delegate;
		}]);

	}]);
})();