(function ()
{
	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider.when('/Rbs/Stock/', { templateUrl: 'Rbs/Stock/InventoryEntry/list.twig', reloadOnSearch: false })
			.when('/Rbs/Stock/Sku/', { templateUrl: 'Rbs/Stock/Sku/list.twig', reloadOnSearch: false })
			.when('/Rbs/Stock/Sku/:id', { templateUrl: 'Rbs/Stock/Sku/form.twig', reloadOnSearch: false })
			.when('/Rbs/Stock/InventoryEntry/', { templateUrl: 'Rbs/Stock/InventoryEntry/list.twig', reloadOnSearch: false })
			.when('/Rbs/Stock/InventoryEntry/:id', { templateUrl: 'Rbs/Stock/InventoryEntry/form.twig', reloadOnSearch: false });
	}]);

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.register('Rbs_Stock_Sku', {
				'list': '/Rbs/Stock/Sku/',
				'form': '/Rbs/Stock/Sku/:id/'
			});
			$delegate.register('Rbs_Stock_InventoryEntry', {
				'list': '/Rbs/Stock/InventoryEntry/',
				'form': '/Rbs/Stock/InventoryEntry/:id'
			});
			return $delegate;
		}]);
	}]);
})();