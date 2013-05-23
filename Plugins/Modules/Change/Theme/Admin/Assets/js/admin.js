(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Home

		. when(
			'/Change/Theme',
			{
				redirectTo: '/Change/Theme/Theme'
			})

		// PageTemplate

		. when(
			'/Change/Theme/Theme/:theme/Templates',
			{
				templateUrl : 'Change/Theme/PageTemplate/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Theme/PageTemplate/:id/:LCID',
			{
				templateUrl : 'Change/Theme/PageTemplate/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Theme/PageTemplate/:id',
			{
				templateUrl : 'Change/Theme/PageTemplate/form.twig',
				reloadOnSearch : false
			})

		// Theme

		. when(
			'/Change/Theme/Theme',
			{
				templateUrl : 'Change/Theme/Theme/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Theme/Theme/:id/:LCID',
			{
				templateUrl : 'Change/Theme/Theme/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Theme/Theme/:id',
			{
				templateUrl : 'Change/Theme/Theme/form.twig',
				reloadOnSearch : false
			})
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// PageTemplate
			$delegate.register('Change_Theme_PageTemplate', {
				'form'  : '/Change/Theme/PageTemplate/:id/:LCID',
				'list'  : '/Change/Theme/PageTemplate/:LCID',
				'i18n'  : '/Change/Theme/PageTemplate/:id/:LCID/translate-from/:fromLCID'
			});

			// Theme
			$delegate.register('Change_Theme_Theme', {
				'form'  : '/Change/Theme/Theme/:id/:LCID',
				'list'  : '/Change/Theme/Theme/:LCID',
				'i18n'  : '/Change/Theme/Theme/:id/:LCID/translate-from/:fromLCID',
				'tree'  : '/Change/Theme/Theme/:id/Templates'
			});

			return $delegate;

		}]);
	}]);

})();