(function () {

	var app = angular.module('RbsChange');

	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

			// Collection

			. when(
			'/Rbs/Collection/Collection',
			{
				templateUrl : 'Rbs/Collection/Collection/list.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Collection/Collection/:id',
			{
				templateUrl : 'Rbs/Collection/Collection/form.twig',
				reloadOnSearch : false
			})

			// Item
			. when(
			'/Rbs/Collection/Item',
			{
				templateUrl : 'Rbs/Collection/Item/list.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Collection/Item/:id',
			{
				templateUrl : 'Rbs/Collection/Item/form.twig',
				reloadOnSearch : false
			})
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Collection
			$delegate.register('Rbs_Collection_Collection', {
				'form'  : '/Rbs/Collection/Collection/:id',
				'list'  : '/Rbs/Collection/Collection'
			});

			// Item
			$delegate.register('Rbs_Collection_Item', {
				'form'  : '/Rbs/Collection/Item/:id',
				'list'  : '/Rbs/Collection/Item'
			});

			return $delegate;

		}]);
	}]);
})();