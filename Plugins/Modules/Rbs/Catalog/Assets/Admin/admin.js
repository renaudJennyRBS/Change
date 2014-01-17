(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	//__change.createEditorForModel('Rbs_Catalog_ProductListItem');

	__change.createEditorsForLocalizedModel('Rbs_Catalog_Attribute');

	__change.createEditorForModelTranslation('Rbs_Catalog_Product');


	app.run(['$templateCache', '$rootScope', '$location', 'RbsChange.REST', function ($templateCache, $rootScope, $location, REST)
	{
		$templateCache.put(
			'picker-item-Rbs_Catalog_Product.html',
			'<span style="line-height: 30px"><img rbs-storage-image="item.adminthumbnail" thumbnail="XS"/> (= item.label =)</span>'
		);

		// Update Breadcrumb.
		$rootScope.$on('Change:UpdateBreadcrumb', function (event, eventData, breadcrumbData, promises) {
			updateBreadcrumb(eventData, breadcrumbData, promises, REST, $location);
		});
	}]);


	/**
	 * Updates the Breadcrumb when the default implementation is not the desired behavior.
	 * @param eventData
	 * @param breadcrumbData
	 * @param promises
	 * @param REST
	 * @param $location
	 */
	function updateBreadcrumb (eventData, breadcrumbData, promises, REST, $location)
	{
		if (eventData.modelName === 'Rbs_Catalog_CrossSellingProductList')
		{
			var p, search = $location.search();
			// TODO
			// Here only the creation is handled, because there is no 'productId' parameter when editing
			// existing CrossSellingProductLists.
			if (search.hasOwnProperty('productId'))
			{
				breadcrumbData.location.length = 1;
				p = REST.resource(search.productId);
				p.then(function (product) {
					breadcrumbData.location.push(['Product', product.url('list')]); // FIXME i18n
					breadcrumbData.path.push(product);
					breadcrumbData.resource = 'New product list'; // FIXME i18n
				});
				promises.push(p);
			}
		}
	}


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Catalog_Product')
				.route('prices', 'Rbs/Catalog/Product/:id/Prices/', 'Document/Rbs/Catalog/Product/product-prices.twig')
				.route('cross-selling-lists', 'Rbs/Catalog/Product/:id/CrossSellingProductLists/', 'Document/Rbs/Catalog/Product/product-cross-selling.twig')
				.route('product-lists', 'Rbs/Catalog/Product/:id/ProductLists/', 'Document/Rbs/Catalog/Product/product-lists.twig')
				.route('variant-group', 'Rbs/Catalog/Product/:id/VariantGroup/', 'Document/Rbs/Catalog/VariantGroup/list.twig');

			$delegate.model('Rbs_Catalog_ProductList')
				.route('productListItems', 'Rbs/Catalog/ProductList/:id/Products/', 'Document/Rbs/Catalog/ProductList/products.twig');
			$delegate.model('Rbs_Catalog_SectionProductList')
				.route('productListItems', 'Rbs/Catalog/SectionProductList/:id/Products/', 'Document/Rbs/Catalog/ProductList/products.twig');
			$delegate.model('Rbs_Catalog_CrossSellingProductList')
				.route('productListItems', 'Rbs/Catalog/CrossSellingProductList/:id/Products/', 'Document/Rbs/Catalog/ProductList/products.twig');

			$delegate.model('Rbs_Catalog')
				.route('home', 'Rbs/Catalog', { 'redirectTo': 'Rbs/Catalog/Product/' });

			$delegate.model('Rbs_Stock_Sku')
				.route('list', 'Rbs/Catalog/Sku/', 'Document/Rbs/Stock/Sku/list.twig')
				.route('form', 'Rbs/Catalog/Sku/:id', 'Document/Rbs/Stock/Sku/form.twig')
				.route('new' , 'Rbs/Catalog/Sku/new', 'Document/Rbs/Stock/Sku/form.twig')
				.route('timeline', 'Rbs/Catalog/Sku/:id/timeline', { 'templateUrl': 'Rbs/Timeline/timeline.twig?model=Rbs_Stock_Sku', 'controller': 'RbsChangeTimelineController' });

			$delegate.routesForLocalizedModels(['Rbs_Catalog_Product', 'Rbs_Catalog_Attribute']);
			$delegate.routesForModels(['Rbs_Catalog_ProductList', 'Rbs_Catalog_SectionProductList', 'Rbs_Catalog_CrossSellingProductList',
				'Rbs_Catalog_ProductListItem', 'Rbs_Catalog_VariantGroup' ]);

			$delegate.model('Rbs_Catalog_VariantGroup')
				.route('variantList', 'Rbs/Catalog/VariantGroup/:id/VariantList/', 'Document/Rbs/Catalog/VariantGroup/variant-list.twig')
				.route('variantEdit', 'Rbs/Catalog/VariantGroup/:id/Edit', 'Document/Rbs/Catalog/VariantGroup/variant-form.twig');
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

	app.controller('rbsAxisAttributesSelector', function ($scope) {
		$scope.axisAttributesQuery= { "model": "Rbs_Catalog_Attribute",
			"where": {
				"and" : [
					{"op" : "eq",
						"lexp" : {"property" : "axis"},
						"rexp" : {"value": true}
					},
					{"op" : "in",
						"lexp" : {"property" : "valueType"},
						"rexp" : ["Property", "Integer", "DocumentId", "Float", "DateTime", "Code"]
					}
				]
			}
		};
	});

	app.controller('rbsOtherAttributesSelector', function ($scope) {
		$scope.otherAttributesQuery= { "model": "Rbs_Catalog_Attribute",
			"where": {
				"and" : [
					{"op" : "eq",
						"lexp" : {"property" : "axis"},
						"rexp" : {"value": false}
					}
				]
			}
		};
	});
})();