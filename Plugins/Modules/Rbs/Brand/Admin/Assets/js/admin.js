(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Brand')
				.route('home', 'Rbs/Brand', { 'redirectTo': 'Rbs/Brand/Brand/'});

			$delegate.routesForLocalizedModels([
				'Rbs_Brand_Brand'
			]);
			return $delegate;
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Brand_Brand');

})();