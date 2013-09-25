(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Catalog_Listing');
	__change.createEditorForModel('Rbs_Catalog_ProductCategorization');

	__change.createEditorsForLocalizedModel('Rbs_Catalog_Attribute');

	__change.createEditorForModelTranslation('Rbs_Catalog_Product');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Catalog_Product')
				.route('prices', 'Rbs/Catalog/Product/:id/Prices/', 'Rbs/Catalog/Product/product-prices.twig');

			$delegate.model('Rbs_Catalog_Listing')
				.route('productcategorizations', 'Rbs/Catalog/Listing/:id/ProductCategorization/', 'Rbs/Catalog/Listing/products.twig');

			$delegate.model('Rbs_Catalog')
				.route('home', 'Rbs/Catalog', { 'redirectTo': 'Rbs/Catalog/Product/'});

			$delegate.routesForLocalizedModels(['Rbs_Catalog_Product', 'Rbs_Catalog_Attribute']);
			$delegate.routesForModels(['Rbs_Catalog_Listing', 'Rbs_Catalog_DeclinationGroup']);
			return $delegate;
		}]);
	}]);

	app.controller('rbsProductCategorizableSelector', function ($scope) {
		$scope.productCategorizableQuery= { "model": "Rbs_Catalog_Product",
			"where": {
				"and" : [
					{
						"op" : "eq",
						"lexp" : {
							"property" : "categorizable"
						},
						"rexp" : {
							"value": true
						}
					}
				]
			}
		};
	});

	app.controller('rbsProductEmptyGroupSelector', function ($scope) {
		$scope.productEmptyGroupQuery= { "model": "Rbs_Catalog_Product",
			"where": {
				"and" : [
					{
						"op" : "eq",
						"lexp" : {
							"property" : "declinationGroup"
						},
						"rexp" : {
							"value": 0
						}
					}
				]
			}
		};
	});
})();