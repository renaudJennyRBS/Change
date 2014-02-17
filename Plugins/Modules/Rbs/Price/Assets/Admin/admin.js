(function () {
	"use strict";

	var app = angular.module('RbsChange');

	app.controller('rbsPriceBasePriceSelector', function ($scope) {
		if ($scope.document.sku)
		{
			$scope.priceBaseQuery= { "model": "Rbs_Price_Price",
				"where": {
					"and" : [
						{
							"op" : "eq",
							"lexp" : {
								"property" : "sku"
							},
							"rexp" : {
								"value": $scope.document.sku.id
							}
						},
						{
							"op" : "neq",
							"lexp" : {
								"property" : "id"
							},
								"rexp" : {
								"value": $scope.document.id
							}
						}
					]
				}
			};
		}
		else
		{
			$scope.priceBaseQuery= { "model": "Rbs_Price_Price"};
		}
	});
})();