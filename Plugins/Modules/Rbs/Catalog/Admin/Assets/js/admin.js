(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Rbs/Catalog', { templateUrl: 'Rbs/Catalog/Product/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product', { templateUrl: 'Rbs/Catalog/Product/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/Prices/', { templateUrl: 'Rbs/Catalog/Product/product-prices.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/ProductCategorization/', { templateUrl: 'Rbs/Catalog/Product/categories.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id/:LCID', { templateUrl: 'Rbs/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Product/:id', { templateUrl: 'Rbs/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category', { templateUrl: 'Rbs/Catalog/Category/list.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category/:id/ProductCategorization/', { templateUrl: 'Rbs/Catalog/Category/products.twig', reloadOnSearch: false })
			.when('/Rbs/Catalog/Category/:id/:LCID', { templateUrl: 'Rbs/Catalog/Category/form.twig', reloadOnSearch: false })
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
				'productcategorizations': 'Rbs/Catalog/Category/:id/ProductCategorization/',
				'tree': '/Rbs/Catalog/nav/?tn=:id'
			});
			$delegate.register('Rbs_Catalog_Product', {
				'form': '/Rbs/Catalog/Product/:id/:LCID',
				'list': '/Rbs/Catalog/Product/',
				'i18n': '/Rbs/Catalog/Product/:id/:LCID/translate-from/:fromLCID',
				'productcategorizations': 'Rbs/Catalog/Product/:id/ProductCategorization/',
				'prices': 'Rbs/Catalog/Product/:id/Prices/'
			});
			return $delegate;
		}]);
	}]);
})();