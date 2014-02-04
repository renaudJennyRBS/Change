(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	//__change.createEditorForModel('Rbs_Catalog_ProductListItem');

	__change.createEditorForModelTranslation('Rbs_Catalog_Attribute');

	__change.createEditorForModelTranslation('Rbs_Catalog_Product');


	app.run(['$templateCache', '$rootScope', '$location', 'RbsChange.REST', 'RbsChange.i18n', function ($templateCache, $rootScope, $location, REST, i18n)
	{
		$templateCache.put(
			'picker-item-Rbs_Catalog_Product.html',
			'<span style="line-height: 30px"><img rbs-storage-image="item.adminthumbnail" thumbnail="XS"/> (= item.label =)</span>'
		);

		// Update Breadcrumb.
		$rootScope.$on('Change:UpdateBreadcrumb', function (event, eventData, breadcrumbData, promises) {
			updateBreadcrumb(event, eventData, breadcrumbData, promises, REST, i18n, $location);
		});
	}]);


	/**
	 * Updates the Breadcrumb when the default implementation is not the desired behavior.
	 * @param event
	 * @param eventData
	 * @param breadcrumbData
	 * @param promises
	 * @param REST
	 * @param $location
	 */
	function updateBreadcrumb (event, eventData, breadcrumbData, promises, REST, i18n, $location)
	{
		if (eventData.modelName === 'Rbs_Catalog_CrossSellingProductList')
		{
			var p, search = $location.search();
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
		else if (eventData.route.relatedModelName == "Rbs_Catalog_VariantGroup")
		{
			event.preventDefault();
			p = REST.resource(eventData.route.params.id).then(function (variant)
			{
				breadcrumbData.resource = [i18n.trans('m.rbs.catalog.documents.variantgroup | ucf'), variant.url('variant-list', {productId:eventData.route.params.productId})];
				if (eventData.route.ruleName == 'variant-list')
				{
					breadcrumbData.resourceModifier = null;
				}
				else
				{
					breadcrumbData.resourceModifier = eventData.route.labelKey || eventData.route.ruleName;
				}
				breadcrumbData.location.push([variant.rootProduct.label, variant.rootProduct.url('form')]);
				console.log(breadcrumbData);
			});
			promises.push(p);
		}
	}


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Catalog', 'Rbs/Catalog', { 'redirectTo': 'Rbs/Catalog/Product/'});
			$delegate.model('Rbs_Catalog_Product')
				.route('prices', 'Rbs/Catalog/Product/:id/Prices/',
						{'templateUrl':'Document/Rbs/Catalog/Product/product-prices.twig', 'labelKey':'m.rbs.price.admin.price_list | ucf'})
				.route('cross-selling-lists', 'Rbs/Catalog/Product/:id/CrossSellingProductLists/',
						{'templateUrl':'Document/Rbs/Catalog/Product/product-cross-selling.twig', 'labelKey':'m.rbs.catalog.admin.crosssellingproductlist_title | ucf'})
				.route('product-lists','Rbs/Catalog/Product/:id/ProductLists/',
						{'templateUrl':'Document/Rbs/Catalog/Product/product-lists.twig', 'labelKey':'m.rbs.catalog.admin.productlist_list | ucf'})
				.route('variant-new', 'Rbs/Catalog/Product/:id/VariantGroup/new',
					{'templateUrl':'Document/Rbs/Catalog/VariantGroup/form.twig', 'labelKey':'m.rbs.catalog.documents.variantgroup | ucf'});


			$delegate.model('Rbs_Catalog_ProductList')
				.route('productListItems', 'Rbs/Catalog/ProductList/:id/Products/', 'Document/Rbs/Catalog/ProductList/products.twig');
			$delegate.model('Rbs_Catalog_SectionProductList')
				.route('productListItems', 'Rbs/Catalog/SectionProductList/:id/Products/', 'Document/Rbs/Catalog/ProductList/products.twig');
			$delegate.model('Rbs_Catalog_CrossSellingProductList')
				.route('productListItems', 'Rbs/Catalog/CrossSellingProductList/:id/Products/', 'Document/Rbs/Catalog/ProductList/products.twig');

			$delegate.routesForModels(['Rbs_Stock_Sku']);

			$delegate.routesForLocalizedModels(['Rbs_Catalog_Product', 'Rbs_Brand_Brand']);
			$delegate.routesForModels(['Rbs_Catalog_ProductList', 'Rbs_Catalog_SectionProductList', 'Rbs_Catalog_CrossSellingProductList',
				'Rbs_Catalog_ProductListItem']);

			$delegate.model('Rbs_Catalog_VariantGroup')
				.route('variant-list', 'Rbs/Catalog/Product/:productId/VariantGroup/:id/list/', 'Document/Rbs/Catalog/VariantGroup/variant-list.twig')
				.route('variant-edit', 'Rbs/Catalog/Product/:productId/VariantGroup/:id/edit', {'templateUrl': 'Document/Rbs/Catalog/VariantGroup/variant-form.twig', 'labelKey':'m.rbs.catalog.admin.variantgroup_variant_edit | ucf'})
				.route('variant-config', 'Rbs/Catalog/Product/:productId/VariantGroup/:id',
					{'templateUrl':'Document/Rbs/Catalog/VariantGroup/form.twig', 'labelKey':'m.rbs.catalog.admin.variantgroup_variant_config | ucf'})

			$delegate.model("Rbs_Catalog_SectionProductList").route('list', '/Rbs/Catalog/ProductList/', {'templateUrl':'Document/Rbs/Catalog/ProductList/list.twig'});

			return $delegate.module(null);
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
						"rexp" : ["Property", "Integer", "DocumentId", "Float", "DateTime", "String"]
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