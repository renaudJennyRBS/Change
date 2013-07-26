(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider
			.when('/Rbs/Store/', { templateUrl: 'Rbs/Store/WebStore/list.twig', reloadOnSearch: false })
			.when('/Rbs/Store/WebStore/', { templateUrl: 'Rbs/Store/WebStore/list.twig', reloadOnSearch: false })
			.when('/Rbs/Store/WebStore/:id', { templateUrl: 'Rbs/Store/WebStore/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Rbs_Store_WebStore', {
				'form': '/Rbs/Store/WebStore/:id',
				'list': '/Rbs/Store/WebStore/'
			});
			return $delegate;
		}]);
	}]);
})();