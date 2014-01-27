(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsOrderStatusIndicators', function ()
	{
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Order/order-status-indicators.twig',
			scope : {
				order : '='
			}
		};
	});

	app.directive('rbsOrderStatusIndicatorsText', function ()
	{
		return {
			restrict : 'A',
			templateUrl : 'Rbs/Order/order-status-indicators-text.twig',
			scope : {
				order : '='
			}
		};
	});
})();