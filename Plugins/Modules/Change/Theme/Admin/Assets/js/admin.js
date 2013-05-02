(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Home

		. when(
			'/Change/Theme',
			{
				templateUrl : 'Change/Theme/PageTemplate/list.twig',
				reloadOnSearch : false
			})

		// Website

		. when(
			'/Change/Theme/PageTemplate',
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
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Pages
			$delegate.register('Change_Theme_PageTemplate', {
				'form'  : '/Change/Theme/PageTemplate/:id/:LCID',
				'list'  : '/Change/Theme/PageTemplate/:LCID',
				'i18n'  : '/Change/Theme/PageTemplate/:id/:LCID/translate-from/:fromLCID'
			});

			return $delegate;

		}]);
	}]);

})();