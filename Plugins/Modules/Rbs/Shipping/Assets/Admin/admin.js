(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Shipping')
				.route('home', 'Rbs/Shipping', { 'redirectTo': 'Rbs/Shipping/Mode/'});

			$delegate.routesForLocalizedModels([
				'Rbs_Shipping_Mode'
			]);

			return $delegate;
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Shipping_Mode');
})();