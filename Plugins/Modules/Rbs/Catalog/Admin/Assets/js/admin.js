(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Catalog_ProductListItem');

	__change.createEditorsForLocalizedModel('Rbs_Catalog_Attribute');

	__change.createEditorForModelTranslation('Rbs_Catalog_Product');


	app.run(['$templateCache', function($templateCache) {
		$templateCache.put(
			'picker-item-Rbs_Catalog_Product.html',
			'<span style="line-height: 30px"><img rbs-storage-image="item.adminthumbnail" thumbnail="XS"/> (= item.label =)</span>'
		);
	}]);


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Catalog_Product')
				.route('prices', 'Rbs/Catalog/Product/:id/Prices/', 'Rbs/Catalog/Product/product-prices.twig')
				.route('cross-selling-lists', 'Rbs/Catalog/Product/:id/CrossSellingProductLists/', 'Rbs/Catalog/Product/product-cross-selling.twig')
				.route('variant-group', 'Rbs/Catalog/Product/:id/VariantGroup/', 'Rbs/Catalog/VariantGroup/list.twig');

			$delegate.model('Rbs_Catalog_ProductList')
				.route('productListItems', 'Rbs/Catalog/ProductList/:id/ProductListItem/', 'Rbs/Catalog/ProductList/products.twig');
			$delegate.model('Rbs_Catalog_SectionProductList')
				.route('productListItems', 'Rbs/Catalog/SectionProductList/:id/ProductListItem/', 'Rbs/Catalog/ProductList/products.twig');
			$delegate.model('Rbs_Catalog_CrossSellingProductList')
				.route('productListItems', 'Rbs/Catalog/CrossSellingProductList/:id/ProductListItem/', 'Rbs/Catalog/ProductList/products.twig');

			$delegate.model('Rbs_Catalog')
				.route('home', 'Rbs/Catalog', { 'redirectTo': 'Rbs/Catalog/Product/'});

			/*$delegate.model('Rbs_Catalog_VariantGroup')
				.route('product-variant-group', 'Rbs/Catalog/Product/:product/VariantGroup/:id', 'Rbs/Catalog/VariantGroup/editor.twig');*/

			$delegate.routesForLocalizedModels(['Rbs_Catalog_Product', 'Rbs_Catalog_Attribute']);
			$delegate.routesForModels(['Rbs_Catalog_ProductList', 'Rbs_Catalog_SectionProductList', 'Rbs_Catalog_CrossSellingProductList',
				'Rbs_Catalog_ProductListItem', 'Rbs_Catalog_VariantGroup' ]);
			return $delegate;
		}]);
	}]);

	app.service('RbsChange.ProductListService', ['RbsChange.MainMenu', function (MainMenu) {
		return {
			'addListContent' : function (scope) {
				MainMenu.addAsideTpl('productlist-content', 'Rbs/Catalog/ProductList/productlist-content-aside-menu.twig', scope);
				return MainMenu;
			}
		};
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
							"property" : "variantGroup"
						},
						"rexp" : {
							"value": 0
						}
					}
				]
			}
		};
	});

	function ProductsController($scope)
	{
		$scope.productsQuery =
		{
			"model": "Rbs_Catalog_Product",
			"where" : {
				"and" : [
					{
						"op" : "eq",
						"lexp" : {
							"property" : "variant"
						},
						"rexp" : {
							"value" : false
						}
					}
				]
			}
		};
	}

	ProductsController.$inject = ['$scope'];
	app.controller('Rbs_Catalog_Product_ProductsController', ProductsController);

	function ProductListsPickerController($scope)
	{
		$scope.productListsPickerQuery =
		{
			"model": "Rbs_Catalog_ProductList",
			"where" : {
				"and" : [
					{
						"op" : "eq",
						"lexp" : {
							"property" : "variant"
						},
						"rexp" : {
							"value" : false
						}
					}
				]
			}
		};
	}
})();