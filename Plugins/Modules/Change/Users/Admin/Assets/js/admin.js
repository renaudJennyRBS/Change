(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Users

		. when(
			'/Change/Users/User',
			{
				templateUrl : 'Change/Users/User/list.twig',
				reloadOnSearch : false
			})

		. when(
			'/Change/Users/User/:id',
			{
				templateUrl : 'Change/Users/User/form.twig',
				reloadOnSearch : false
			})
		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Users
			$delegate.register('Change_Users_User', {
				'form'  : '/Change/Users/User/:id',
				'list'  : '/Change/Users/User'
			});

			return $delegate;

		}]);
	}]);

})();