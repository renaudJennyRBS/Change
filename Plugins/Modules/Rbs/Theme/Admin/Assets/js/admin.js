(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Home

		. when(
			'/Rbs/Theme',
			{
				redirectTo: '/Rbs/Theme/Theme'
			})

		// PageTemplate

		. when(
			'/Rbs/Theme/Theme/:theme/Templates',
			{
				templateUrl : 'Rbs/Theme/PageTemplate/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Theme/PageTemplate/:id/:LCID',
			{
				templateUrl : 'Rbs/Theme/PageTemplate/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Theme/PageTemplate/:id',
			{
				templateUrl : 'Rbs/Theme/PageTemplate/form.twig',
				reloadOnSearch : false
			})

		// Theme

		. when(
			'/Rbs/Theme/Theme',
			{
				templateUrl : 'Rbs/Theme/Theme/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Theme/Theme/:id/:LCID',
			{
				templateUrl : 'Rbs/Theme/Theme/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Theme/Theme/:id',
			{
				templateUrl : 'Rbs/Theme/Theme/form.twig',
				reloadOnSearch : false
			})
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// PageTemplate
			$delegate.register('Rbs_Theme_PageTemplate', {
				'form'  : '/Rbs/Theme/PageTemplate/:id/:LCID',
				'list'  : '/Rbs/Theme/PageTemplate/:LCID',
				'i18n'  : '/Rbs/Theme/PageTemplate/:id/:LCID/translate-from/:fromLCID'
			});

			// Theme
			$delegate.register('Rbs_Theme_Theme', {
				'form'  : '/Rbs/Theme/Theme/:id/:LCID',
				'list'  : '/Rbs/Theme/Theme/:LCID',
				'i18n'  : '/Rbs/Theme/Theme/:id/:LCID/translate-from/:fromLCID',
				'tree'  : '/Rbs/Theme/Theme/:id/Templates'
			});

			return $delegate;

		}]);
	}]);

})();