(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Rbs/Brand', { templateUrl: 'Rbs/Brand/Brand/list.twig', reloadOnSearch: false })
			.when('/Rbs/Brand/Brand/', { templateUrl: 'Rbs/Brand/Brand/list.twig', reloadOnSearch: false })
			.when('/Rbs/Brand/Brand/:id/:LCID', { templateUrl: 'Rbs/Brand/Brand/form.twig', reloadOnSearch: false })
			.when('/Rbs/Brand/Brand/:id', { templateUrl: 'Rbs/Brand/Brand/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Rbs_Brand_Brand', {
				'form': '/Rbs/Brand/Brand/:id/:LCID',
				'list': '/Rbs/Brand/Brand/:LCID',
				'i18n': '/Rbs/Brand/Brand/:id/:LCID/translate-from/:fromLCID'
			});
			return $delegate;
		}]);
	}]);
})();