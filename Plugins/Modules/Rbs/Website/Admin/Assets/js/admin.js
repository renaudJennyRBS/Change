(function () {

	var app = angular.module('RbsChange');



	/**
	 * @name Rbs_Website_MainMenuController
	 */
	function ChangeWebsiteMainMenuController ($scope, $location, REST) {

		var unregisterUserLoginListener = null;

		$scope.currentWebsiteId = parseInt($location.search()['tn'], 10);

		function loadSuccessFn (root) {
			REST.treeChildren(root.resources[0]).then(function (websites) {
				$scope.websites = websites.resources;
				if (!$scope.currentWebsiteId && $scope.websites && $scope.websites.length) {
					$scope.currentWebsiteId = $scope.websites[0].id;
				}
				if (unregisterUserLoginListener) {
					unregisterUserLoginListener();
				}
			});
		}

		$scope.$on('Change:TreePathChanged', function (event, bcData) {
			$scope.currentWebsiteId = bcData.website ? bcData.website.id : 0;
		});

		function loadErrorFn () {
			installUserLoginListener();
		}

		function installUserLoginListener () {
			unregisterUserLoginListener = $scope.$on('OAuth:UserLoginSuccess', reload);
		}

		function reload () {
			REST.treeChildren('Rbs/Website').then(loadSuccessFn, loadErrorFn);
		}

		reload();

	}

	ChangeWebsiteMainMenuController.$inject = [
		'$scope',
		'$location',
		'RbsChange.REST'
	];
	app.controller('Rbs_Website_MainMenuController', ChangeWebsiteMainMenuController);


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Home

		. when(
			'/Rbs/Website',
			{
				templateUrl : 'Rbs/Website/Website/list.twig',
				reloadOnSearch : false
			})

		// Website

		. when(
			'/Rbs/Website/Website',
			{
				templateUrl : 'Rbs/Website/Website/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Website/Website/:id/:LCID',
			{
				templateUrl : 'Rbs/Website/Website/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Website/Website/:id',
			{
				templateUrl : 'Rbs/Website/Website/form.twig',
				reloadOnSearch : false
			})

		// Navigation in topics

		. when(
			'/Rbs/Website/nav/',
			{
				templateUrl : 'Rbs/Website/Topic/list.twig',
				reloadOnSearch : false
			})

		// Topic

		. when(
			'/Rbs/Website/Topic/:id/:LCID',
			{
				templateUrl : 'Rbs/Website/Topic/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/Website/Topic/:id/',
			{
				templateUrl : 'Rbs/Website/Topic/form.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/Website/StaticPage',
			{
				templateUrl : 'Rbs/Website/StaticPage/list.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Website/StaticPage/:id',
			{
				templateUrl : 'Rbs/Website/StaticPage/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Website/StaticPage/:id/:LCID',
			{
				templateUrl : 'Rbs/Website/StaticPage/form.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Website/StaticPage/:id/:LCID/editor',
			{
				templateUrl : 'Rbs/Website/StaticPage/content-editor.twig',
				reloadOnSearch : false
			})
		. when(
			'/Rbs/Website/StaticPage/:id/:LCID/translate-from/:fromLCID',
			{
				templateUrl : 'Rbs/Website/StaticPage/form.twig',
				reloadOnSearch : false
			})

		// Menu

			. when(
			'/Rbs/Website/Menu',
			{
				templateUrl : 'Rbs/Website/Menu/list.twig',
				reloadOnSearch : false
			})
			. when(
			'/Rbs/Website/Menu/:id',
			{
				templateUrl : 'Rbs/Website/Menu/form.twig',
				reloadOnSearch : false
			})

			.otherwise({redirectTo:'/Rbs/Website/Website/'})

		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Pages
			$delegate.register('Rbs_Website_StaticPage', {
				'form'  : '/Rbs/Website/StaticPage/:id/:LCID',
				'editor': '/Rbs/Website/StaticPage/:id/:LCID/editor',
				'list'  : '/Rbs/Website/StaticPage/:LCID',
				'i18n'  : '/Rbs/Website/StaticPage/:id/:LCID/translate-from/:fromLCID'
			});

			// Topics
			$delegate.register('Rbs_Website_Topic', {
				'form': '/Rbs/Website/Topic/:id/:LCID',
				'list': '/Rbs/Website/Topic/:LCID',
				'tree': '/Rbs/Website/nav/?tn=:id'
			});

			// Websites
			$delegate.register('Rbs_Website_Website', {
				'form': '/Rbs/Website/Website/:id/:LCID',
				'list': '/Rbs/Website/Website/:LCID',
				'tree': '/Rbs/Website/nav/?tn=:id'
			});

			// Menus
			$delegate.register('Rbs_Website_Menu', {
				'form': '/Rbs/Website/Menu/:id',
				'list': '/Rbs/Website/Menu/:LCID'
			});

			return $delegate;

		}]);
	}]);

})();