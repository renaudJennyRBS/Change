(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Rbs/Geo', { templateUrl: 'Rbs/Geo/Zone/list.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/Zone', { templateUrl: 'Rbs/Geo/Zone/list.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/Zone/:id/:LCID', { templateUrl: 'Rbs/Geo/Zone/form.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/Zone/:id', { templateUrl: 'Rbs/Geo/Zone/form.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/Country', { templateUrl: 'Rbs/Geo/Country/list.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/Country/:id/:LCID', { templateUrl: 'Rbs/Geo/Country/form.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/Country/:id', { templateUrl: 'Rbs/Geo/Country/form.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/TerritorialUnit', { templateUrl: 'Rbs/Geo/TerritorialUnit/list.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/TerritorialUnit/:id/:LCID', { templateUrl: 'Rbs/Geo/TerritorialUnit/form.twig', reloadOnSearch: false })
			.when('/Rbs/Geo/TerritorialUnit/:id', { templateUrl: 'Rbs/Geo/TerritorialUnit/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Rbs_Geo_Zone', {
				'form': '/Rbs/Geo/Zone/:id/:LCID',
				'list': '/Rbs/Geo/Zone/:LCID',
				'i18n': '/Rbs/Geo/Zone/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Rbs_Geo_Country', {
				'form': '/Rbs/Geo/Country/:id/:LCID',
				'list': '/Rbs/Geo/Country/:LCID',
				'i18n': '/Rbs/Geo/Country/:id/:LCID/translate-from/:fromLCID'
			});
			$delegate.register('Rbs_Geo_TerritorialUnit', {
				'form': '/Rbs/Geo/TerritorialUnit/:id/:LCID',
				'list': '/Rbs/Geo/TerritorialUnit/:LCID',
				'i18n': '/Rbs/Geo/TerritorialUnit/:id/:LCID/translate-from/:fromLCID'
			});
			return $delegate;
		}]);
	}]);
})();