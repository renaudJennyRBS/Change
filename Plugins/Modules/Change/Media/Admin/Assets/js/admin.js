(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Change/Media', { templateUrl: 'Change/Media/Image/list.twig', reloadOnSearch: false })
			.when('/Change/Media/Image', { templateUrl: 'Change/Media/Image/list.twig', reloadOnSearch: false })
			.when('/Change/Media/Image/:id/:LCID', { templateUrl: 'Change/Media/Image/form.twig', reloadOnSearch: false })
			.when('/Change/Media/Image/:id', { templateUrl: 'Change/Media/Image/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Change_Media_Image', {
				'form': '/Change/Media/Image/:id/:LCID',
				'list': '/Change/Media/Image/:LCID',
				'i18n': '/Change/Media/Image/:id/:LCID/translate-from/:fromLCID'
			});
			return $delegate;
		}]);
	}]);
})();