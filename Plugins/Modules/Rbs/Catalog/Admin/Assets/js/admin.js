(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Change/Catalog', { templateUrl: 'Change/Catalog/Shop/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Shop', { templateUrl: 'Change/Catalog/Shop/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Shop/:id/:LCID', { templateUrl: 'Change/Catalog/Shop/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Shop/:id', { templateUrl: 'Change/Catalog/Shop/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Product', { templateUrl: 'Change/Catalog/Product/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Product/:id/:LCID', { templateUrl: 'Change/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Product/:id', { templateUrl: 'Change/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Price', { templateUrl: 'Change/Catalog/Price/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Price/:id/:LCID', { templateUrl: 'Change/Catalog/Price/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Price/:id', { templateUrl: 'Change/Catalog/Price/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/BillingArea', { templateUrl: 'Change/Catalog/BillingArea/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/BillingArea/:id/:LCID',
			{ templateUrl: 'Change/Catalog/BillingArea/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/BillingArea/:id',
			{ templateUrl: 'Change/Catalog/BillingArea/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Currency', { templateUrl: 'Change/Catalog/Currency/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Currency/:id', { templateUrl: 'Change/Catalog/Currency/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Category', { templateUrl: 'Change/Catalog/Category/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Category/:id/:LCID',
			{ templateUrl: 'Change/Catalog/Category/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Category/:id', { templateUrl: 'Change/Catalog/Category/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/nav/', { templateUrl: 'Change/Catalog/Category/list.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Change_Catalog_Category', {
				'form': '/Change/Catalog/Category/:id/:LCID',
				'list': '/Change/Catalog/Category/:LCID',
				'i18n': '/Change/Catalog/Category/:id/:LCID/translate-from/:fromLCID',
				'tree': '/Change/Catalog/nav/?tn=:id'
			});
			$delegate.register('Change_Catalog_Product', {
				'form': '/Change/Catalog/Product/:id/:LCID',
				'list': '/Change/Catalog/Product/:LCID',
				'i18n': '/Change/Catalog/Product/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Change_Catalog_Price', {
				'form': '/Change/Catalog/Price/:id/:LCID',
				'list': '/Change/Catalog/Price/:LCID',
				'i18n': '/Change/Catalog/Price/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Change_Catalog_Shop', {
				'form': '/Change/Catalog/Shop/:id/:LCID',
				'list': '/Change/Catalog/Shop/:LCID',
				'i18n': '/Change/Catalog/Shop/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Change_Catalog_BillingArea', {
				'form': '/Change/Catalog/BillingArea/:id/:LCID',
				'list': '/Change/Catalog/BillingArea/:LCID',
				'i18n': '/Change/Catalog/BillingArea/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Change_Catalog_Currency', {
				'form': '/Change/Catalog/Currency/:id/:LCID',
				'list': '/Change/Catalog/Currency/:LCID'
			});
			return $delegate;
		}]);
	}]);
})();