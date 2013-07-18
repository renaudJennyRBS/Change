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
		. when('/Rbs/Website',{ templateUrl : 'Rbs/Website/Website/list.twig', reloadOnSearch : false })

		// ------ KEEP THE 'Section' and 'Menu' ROUTES BEFORE THE 'Website' ONES !

		// Section functions
		. when('/Rbs/Website/Topic/:id/Functions/',   { templateUrl : 'Rbs/Website/SectionPageFunction/list.twig', reloadOnSearch : false })
		. when('/Rbs/Website/Website/:id/Functions/', { templateUrl : 'Rbs/Website/SectionPageFunction/list.twig', reloadOnSearch : false })

		// Menu
		. when('/Rbs/Website/Menu/', { templateUrl : 'Rbs/Website/Menu/list.twig', reloadOnSearch : false })
		. when('/Rbs/Website/Website/:websiteId/Menus/', { templateUrl : 'Rbs/Website/Menu/list.twig', reloadOnSearch : false })
		. when('/Rbs/Website/Website/:websiteId/Menus/:id/:LCID', { templateUrl : 'Rbs/Website/Menu/form.twig', reloadOnSearch : false })

		// ------

		// Website
		. when('/Rbs/Website/Website/', { templateUrl : 'Rbs/Website/Website/list.twig', reloadOnSearch : false })
		. when('/Rbs/Website/Website/:id/:LCID', { templateUrl : 'Rbs/Website/Website/form.twig', reloadOnSearch : false })
		. when('/Rbs/Website/Website/:id', { templateUrl : 'Rbs/Website/Website/form.twig', reloadOnSearch : false })

		// Navigation in topics
		. when('/Rbs/Website/nav/', { templateUrl : 'Rbs/Website/Topic/list.twig', reloadOnSearch : false })

		// Topic
		. when('/Rbs/Website/Topic/:id/:LCID', { templateUrl : 'Rbs/Website/Topic/form.twig', reloadOnSearch : false })
		. when('/Rbs/Website/Topic/:id/', { templateUrl : 'Rbs/Website/Topic/form.twig', reloadOnSearch : false })

		// Static pages
		. when('/Rbs/Website/StaticPage/', { templateUrl : 'Rbs/Website/StaticPage/list.twig', reloadOnSearch : false })
		. when('/Rbs/Website/StaticPage/:id', { templateUrl : 'Rbs/Website/StaticPage/form.twig', reloadOnSearch : false })
		. when('/Rbs/Website/StaticPage/:id/:LCID', { templateUrl : 'Rbs/Website/StaticPage/form.twig', reloadOnSearch : false })
		. when('/Rbs/Website/StaticPage/:id/:LCID/editor', { templateUrl : 'Rbs/Website/StaticPage/content-editor.twig', reloadOnSearch : false })
		. when('/Rbs/Website/StaticPage/:id/:LCID/translate-from/:fromLCID', { templateUrl : 'Rbs/Website/StaticPage/form.twig', reloadOnSearch : false })

		// Functional pages
		. when('/Rbs/Website/FunctionalPage/', { templateUrl : 'Rbs/Website/FunctionalPage/list.twig', reloadOnSearch : false })
		. when('/Rbs/Website/FunctionalPage/:id', { templateUrl : 'Rbs/Website/FunctionalPage/form.twig', reloadOnSearch : false })
		. when('/Rbs/Website/FunctionalPage/:id/:LCID', { templateUrl : 'Rbs/Website/FunctionalPage/form.twig', reloadOnSearch : false })
		. when('/Rbs/Website/FunctionalPage/:id/:LCID/editor', { templateUrl : 'Rbs/Website/FunctionalPage/content-editor.twig', reloadOnSearch : false })
		. when('/Rbs/Website/FunctionalPage/:id/:LCID/translate-from/:fromLCID', { templateUrl : 'Rbs/Website/FunctionalPage/form.twig', reloadOnSearch : false })

		;
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// StaticPages
			$delegate.register('Rbs_Website_StaticPage', {
				'form'  : '/Rbs/Website/StaticPage/:id/:LCID',
				'editor': '/Rbs/Website/StaticPage/:id/:LCID/editor',
				'list'  : '/Rbs/Website/StaticPage/:LCID',
				'i18n'  : '/Rbs/Website/StaticPage/:id/:LCID/translate-from/:fromLCID'
			});

			// FunctionalPages
			$delegate.register('Rbs_Website_FunctionalPage', {
				'form'  : '/Rbs/Website/FunctionalPage/:id/:LCID',
				'editor': '/Rbs/Website/FunctionalPage/:id/:LCID/editor',
				'list'  : '/Rbs/Website/FunctionalPage/:LCID',
				'i18n'  : '/Rbs/Website/FunctionalPage/:id/:LCID/translate-from/:fromLCID',
				'section-function': '/Rbs/Website/FunctionalPage/:id/:LCID/section-function/',
				'new-section-function': '/Rbs/Website/FunctionalPage/:id/:LCID/section-function/new'
			});

			// Topics
			$delegate.register('Rbs_Website_Topic', {
				'form': '/Rbs/Website/Topic/:id/:LCID',
				'list': '/Rbs/Website/Topic/:LCID',
				'tree': '/Rbs/Website/nav/?tn=:id',
				'functions': '/Rbs/Website/Topic/:id/Functions/'
			});

			// Websites
			$delegate.register('Rbs_Website_Website', {
				'form': '/Rbs/Website/Website/:id/:LCID',
				'list': '/Rbs/Website/Website/:LCID',
				'tree': '/Rbs/Website/nav/?tn=:id',
				'functions': '/Rbs/Website/Website/:id/Functions/',
				'menus': '/Rbs/Website/Website/:id/Menus/'
			});

			// Menus
			$delegate.register('Rbs_Website_Menu', {
				//'form': '/Rbs/Website/Menu/:id',
				'form': '/Rbs/Website/Website/:website/Menus/:id/:LCID',
				'list': '/Rbs/Website/Menu/:LCID'
			});

			return $delegate;

		}]);
	}]);

})();