(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Payment')
				.route('home', 'Rbs/Payment', { 'redirectTo': 'Rbs/Payment/Connector/'});

			$delegate.routesForModels([
				'Rbs_Payment_Connector',
				'Rbs_Payment_Transaction'
			]);

			return $delegate;
		}]);
	}]);

	__change.createEditorForModel('Rbs_Payment_Connector');
	__change.createEditorForModel('Rbs_Payment_Transaction');

})();