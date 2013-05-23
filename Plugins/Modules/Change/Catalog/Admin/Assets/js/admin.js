(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Change/Catalog', { templateUrl: 'Change/Catalog/Category/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Category', { templateUrl: 'Change/Catalog/Category/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Category/:id/:LCID', { templateUrl: 'Change/Catalog/Category/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Category/:id', { templateUrl: 'Change/Catalog/Category/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/nav/', { templateUrl : 'Change/Catalog/Category/list.twig', reloadOnSearch : false })
			.when('/Change/Catalog/Product', { templateUrl: 'Change/Catalog/Product/list.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Product/:id/:LCID', { templateUrl: 'Change/Catalog/Product/form.twig', reloadOnSearch: false })
			.when('/Change/Catalog/Product/:id', { templateUrl: 'Change/Catalog/Product/form.twig', reloadOnSearch: false });
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
			return $delegate;
		}]);
	}]);
})();