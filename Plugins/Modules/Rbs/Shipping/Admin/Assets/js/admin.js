(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Shipping')
				.route('home', 'Rbs/Shipping', { 'redirectTo': 'Rbs/Shipping/Mode/'});

			$delegate.routesForModels([
				'Rbs_Shipping_Mode'
			]);

			return $delegate;
		}]);
	}]);

	__change.createEditorForModel('Rbs_Shipping_Mode');
})();