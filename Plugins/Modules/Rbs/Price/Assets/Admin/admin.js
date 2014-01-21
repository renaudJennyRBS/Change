(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Price_BillingArea');
	__change.createEditorForModelTranslation('Rbs_Price_Fee');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{

			$delegate.module('Rbs_Price', 'Rbs/Price', { 'redirectTo': 'Rbs/Price/Price/'});

			$delegate.model('Rbs_Price_Price')
				.route('discount', 'Rbs/Price/Discount/new', "Document/Rbs/Price/Price/form.twig");

			$delegate.routesForModels([
				'Rbs_Price_Price'
			]);

			$delegate.routesForLocalizedModels([
				'Rbs_Price_Fee'
			]);

			return $delegate.module(null);
		}]);

	}]);

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