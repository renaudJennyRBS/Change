(function () {

	"use strict";

	var app = angular.module('RbsChange');
	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Commerce', 'Rbs/Commerce', { 'templateUrl': 'Rbs/Commerce/settings.twig'})
				.routesForLocalizedModels(['Rbs_Store_WebStore', 'Rbs_Price_BillingArea', 'Rbs_Shipping_Mode', 'Rbs_Payment_DeferredConnector'])
				.model("Rbs_Payment_Connector").route('list', '/Rbs/Commerce/Connector/', {'templateUrl':'Document/Rbs/Payment/Connector/list.twig'})
				.model("Rbs_Payment_DeferredConnector").route('list', '/Rbs/Commerce/DeferredConnector/', {'templateUrl':'Document/Rbs/Payment/Connector/list.twig'})
				.routesForModels(['Rbs_Price_Tax', 'Rbs_Commerce_Process']);

			return $delegate.module(null);
		}]);
	}]);

})();