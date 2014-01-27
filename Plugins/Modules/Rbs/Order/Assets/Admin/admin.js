(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Order', 'Rbs/Order', { 'redirectTo': 'Rbs/Order/Order/'})
				.routesForModels(['Rbs_Order_Order','Rbs_Order_Invoice','Rbs_Order_Shipment', 'Rbs_Payment_Transaction']);
			return $delegate.module(null);
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Order_Invoice');
	__change.createEditorForModel('Rbs_Commerce_Process');
})();