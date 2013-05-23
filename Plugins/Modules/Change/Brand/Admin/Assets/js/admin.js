(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Change/Brand', { templateUrl: 'Change/Brand/Brand/list.twig', reloadOnSearch: false })
			.when('/Change/Brand/Brand', { templateUrl: 'Change/Brand/Brand/list.twig', reloadOnSearch: false })
			.when('/Change/Brand/Brand/:id/:LCID', { templateUrl: 'Change/Brand/Brand/form.twig', reloadOnSearch: false })
			.when('/Change/Brand/Brand/:id', { templateUrl: 'Change/Brand/Brand/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Change_Brand_Brand', {
				'form': '/Change/Brand/Brand/:id/:LCID',
				'list': '/Change/Brand/Brand/:LCID',
				'i18n': '/Change/Brand/Brand/:id/:LCID/translate-from/:fromLCID'
			});
			return $delegate;
		}]);
	}]);
})();