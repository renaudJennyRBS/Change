(function () {

	var app = angular.module('RbsChange');



	/**
	 * @name Change_Website_MainMenuController
	 */
	function ChangeWebsiteMainMenuController ($scope, $location, REST) {

		var unregisterUserLoginListener = null;

		$scope.currentWebsiteId = parseInt($location.search()['tn'], 10);

		function loadSuccessFn (root) {
			REST.treeChildren(root.resources[0]).then(function (websites) {
				$scope.websites = websites.resources;
				if (!$scope.currentWebsiteId) {
					$scope.currentWebsiteId = $scope.websites[0].id;
				}
				if (unregisterUserLoginListener) {
					unregisterUserLoginListener();
				}
			});
		}

		$scope.$on('Change:BreadcrumbChanged', function (event, bcData) {
			$scope.currentWebsiteId = bcData.website ? bcData.website.id : 0;
		});

		function loadErrorFn () {
			installUserLoginListener();
		}

		function installUserLoginListener () {
			unregisterUserLoginListener = $scope.$on('OAuth:UserLoginSuccess', reload);
		}

		function reload () {
			REST.treeChildren('Change/Website').then(loadSuccessFn, loadErrorFn);
		}

		reload();

	}

	ChangeWebsiteMainMenuController.$inject = [
		'$scope',
		'$location',
		'RbsChange.REST'
	];
	app.controller('Change_Website_MainMenuController', ChangeWebsiteMainMenuController);





	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Home

		. when(
			'/Change/Website',
			{
				templateUrl : 'Change/Website/Website/list.twig',
				reloadOnSearch : false
			})

		// Website

		. when(
			'/Change/Website/Website',
			{
				templateUrl : 'Change/Website/Website/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Website/Website/:id/:LCID',
			{
				templateUrl : 'Change/Website/Website/form.twig',
				reloadOnSearch : false
			})
			. when(
		'/Change/Website/Website/:id',
			{
				templateUrl : 'Change/Website/Website/form.twig',
				reloadOnSearch : false
			})

		// Navigation in topics

		. when(
			'/Change/Website/nav/',
			{
				templateUrl : 'Change/Website/Topic/list.twig',
				reloadOnSearch : false
			})

		// Topic

			. when(
			'/Change/Website/Topic/:id/:LCID',
			{
				templateUrl : 'Change/Website/Topic/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Change/Website/Topic/:id/',
			{
				templateUrl : 'Change/Website/Topic/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Change/Website/StaticPage',
			{
				templateUrl : 'Change/Website/StaticPage/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Website/StaticPage/:id',
			{
				templateUrl : 'Change/Website/StaticPage/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Website/StaticPage/:id/:LCID',
			{
				templateUrl : 'Change/Website/StaticPage/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Website/StaticPage/:id/:LCID/editor',
			{
				templateUrl : 'Change/Website/StaticPage/content-editor.twig',
				reloadOnSearch : false
			})
		. when(
			'/Change/Website/StaticPage/:id/:LCID/translate-from/:fromLCID',
			{
				templateUrl : 'Change/Website/StaticPage/form.twig',
				reloadOnSearch : false
			})

		.otherwise({redirectTo:'/Change/Website/Website/'})

		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Pages
			$delegate.register('Change_Website_StaticPage', {
				'form'  : '/Change/Website/StaticPage/:id/:LCID',
				'editor': '/Change/Website/StaticPage/:id/:LCID/editor',
				'list'  : '/Change/Website/StaticPage/:LCID',
				'i18n'  : '/Change/Website/StaticPage/:id/:LCID/translate-from/:fromLCID'
			});

			// Topics
			$delegate.register('Change_Website_Topic', {
				'form': '/Change/Website/Topic/:id/:LCID',
				'list': '/Change/Website/Topic/:LCID',
				'tree': '/Change/Website/nav/?tn=:id'
			});

			// Websites
			$delegate.register('Change_Website_Website', {
				'form': '/Change/Website/Website/:id/:LCID',
				'list': '/Change/Website/Website/:LCID',
				'tree': '/Change/Website/nav/?tn=:id'
			});

			return $delegate;

		}]);
	}]);

})();