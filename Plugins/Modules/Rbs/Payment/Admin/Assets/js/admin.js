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
				'Rbs_Payment_DeferredConnector',
				'Rbs_Payment_Transaction'
			]);

			return $delegate;
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Payment_DeferredConnector');
	__change.createEditorForModel('Rbs_Payment_Transaction');
})();