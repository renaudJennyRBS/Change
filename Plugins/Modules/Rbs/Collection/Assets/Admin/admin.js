(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Collection_Item');


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Collection')
				.route('home', 'Rbs/Collection', { 'redirectTo': 'Rbs/Collection/Collection/'});

			$delegate.routesForModels([
				'Rbs_Collection_Collection'
			]);
			$delegate.routesForLocalizedModels([
				'Rbs_Collection_Item'
			]);
			return $delegate;
		}]);

	}]);

})();