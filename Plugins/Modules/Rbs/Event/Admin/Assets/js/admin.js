(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Event_News');
	__change.createEditorsForLocalizedModel('Rbs_Event_Event');
	__change.createEditorsForLocalizedModel('Rbs_Event_Category');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Event').route('home', 'Rbs/Event', { 'redirectTo': 'Rbs/Event/News/'});
			$delegate.routesForLocalizedModels(['Rbs_Event_News', 'Rbs_Event_Event', 'Rbs_Event_Category']);
			return $delegate;
		}]);
	}]);
})();