(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Rbs/Price/', { templateUrl: 'Rbs/Price/Price/list.twig', reloadOnSearch: false })
			.when('/Rbs/Price/Price/', { templateUrl: 'Rbs/Price/Price/list.twig', reloadOnSearch: false })
			.when('/Rbs/Price/Price/:id', { templateUrl: 'Rbs/Price/Price/form.twig', reloadOnSearch: false })
			.when('/Rbs/Price/Tax/', { templateUrl: 'Rbs/Price/Tax/list.twig', reloadOnSearch: false })
			.when('/Rbs/Price/Tax/:id', { templateUrl: 'Rbs/Price/Tax/form.twig', reloadOnSearch: false })
			.when('/Rbs/Price/BillingArea/', { templateUrl: 'Rbs/Price/BillingArea/list.twig', reloadOnSearch: false })
			.when('/Rbs/Price/BillingArea/:id',{ templateUrl: 'Rbs/Price/BillingArea/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Rbs_Price_Tax', {
				'list': '/Rbs/Price/Tax/:LCID',
				'form': '/Rbs/Price/Tax/:id/:LCID'
			});
			$delegate.register('Rbs_Price_Price', {
				'list': '/Rbs/Price/Price/:LCID',
				'form': '/Rbs/Price/Price/:id/:LCID'
			});
			$delegate.register('Rbs_Catalog_Shop', {
				'form': '/Rbs/Catalog/Shop/:id/:LCID',
				'list': '/Rbs/Catalog/Shop/:LCID',
				'i18n': '/Rbs/Catalog/Shop/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Rbs_Price_BillingArea', {
				'form': '/Rbs/Price/BillingArea/:id/:LCID',
				'list': '/Rbs/Price/BillingArea/:LCID',
				'i18n': '/Rbs/Price/BillingArea/:id/:LCID/translate-from/:fromLCID'
			});
			return $delegate;
		}]);
	}]);
})();