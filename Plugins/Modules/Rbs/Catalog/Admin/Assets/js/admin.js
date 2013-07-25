(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Rbs/Catalog', { templateUrl: 'Rbs/Catalog/Product/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Shop', { templateUrl: 'Rbs/Catalog/Shop/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Shop/:id/:LCID', { templateUrl: 'Rbs/Catalog/Shop/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Shop/:id', { templateUrl: 'Rbs/Catalog/Shop/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product', { templateUrl: 'Rbs/Catalog/Product/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/Prices/', { templateUrl: 'Rbs/Catalog/Product/product-prices.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/Prices/:shopId/', { templateUrl: 'Rbs/Catalog/Product/product-prices.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/Prices/:shopId/:areaId/', { templateUrl: 'Rbs/Catalog/Product/product-prices.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/ProductCategorization/', { templateUrl: 'Rbs/Catalog/Product/categories.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/:LCID', { templateUrl: 'Rbs/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id', { templateUrl: 'Rbs/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category', { templateUrl: 'Rbs/Catalog/Category/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category/:id/ProductCategorization/', { templateUrl: 'Rbs/Catalog/Category/products.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category/:id/:LCID',
			{ templateUrl: 'Rbs/Catalog/Category/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category/:id', { templateUrl: 'Rbs/Catalog/Category/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/nav/', { templateUrl: 'Rbs/Catalog/Category/list.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Rbs_Catalog_Category', {
				'form': '/Rbs/Catalog/Category/:id/:LCID',
				'list': '/Rbs/Catalog/Category/:LCID',
				'i18n': '/Rbs/Catalog/Category/:id/:LCID/translate-from/:fromLCID',
				'products': '/Rbs/Catalog/CategoryProducts/:id',
				'tree': '/Rbs/Catalog/nav/?tn=:id'
			});
			$delegate.register('Rbs_Catalog_Product', {
				'form': '/Rbs/Catalog/Product/:id/:LCID',
				'list': '/Rbs/Catalog/Product/:LCID',
				'i18n': '/Rbs/Catalog/Product/:id/:LCID/translate-from/:fromLCID',
				'categories': '/Rbs/Catalog/ProductCategories/:id'
			});
			$delegate.register('Rbs_Catalog_Price', {
				'form': '/Rbs/Catalog/Price/:id/:LCID'
			});
			$delegate.register('Rbs_Catalog_Shop', {
				'form': '/Rbs/Catalog/Shop/:id/:LCID',
				'list': '/Rbs/Catalog/Shop/:LCID',
				'i18n': '/Rbs/Catalog/Shop/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Rbs_Catalog_BillingArea', {
				'form': '/Rbs/Catalog/BillingArea/:id/:LCID',
				'list': '/Rbs/Catalog/BillingArea/:LCID',
				'i18n': '/Rbs/Catalog/BillingArea/:id/:LCID/translate-from/:fromLCID'
			});
			return $delegate;
		}]);
	}]);
})();