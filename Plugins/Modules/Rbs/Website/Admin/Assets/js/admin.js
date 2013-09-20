(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorsForLocalizedModel('Rbs_Website_Website');
	__change.createEditorForModelTranslation('Rbs_Website_Topic');


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


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Website_Website')
				.route('tree', 'Rbs/Website/nav/?tn=:id', 'Rbs/Website/Topic/list.twig')
				.route('functions', 'Rbs/Website/Website/:id/Functions/', 'Rbs/Website/SectionPageFunction/list.twig')
				.route('menus', 'Rbs/Website/Website/:id/Menus/', 'Rbs/Website/Menu/list.twig')
			;

			$delegate.model('Rbs_Website_Topic')
				.route('tree', 'Rbs/Website/nav/?tn=:id', 'Rbs/Website/Topic/list.twig')
				.route('functions', 'Rbs/Website/Topic/:id/Functions/', 'Rbs/Website/SectionPageFunction/list.twig')
			;

			$delegate.model('Rbs_Website')
				.route('home', 'Rbs/Website', { 'redirectTo': 'Rbs/Website/Website/'})
			;

			$delegate.routesForLocalizedModels([
				'Rbs_Website_Website',
				'Rbs_Website_Topic',
				'Rbs_Website_StaticPage',
				'Rbs_Website_FunctionalPage',
				'Rbs_Website_Menu'
			]);

			$delegate.model('Rbs_Website_Menu')
				.route('new', 'Rbs/Website/Website/:website/Menus/new', 'Rbs/Website/Menu/form.twig')
				.route('form', 'Rbs/Website/Website/:website/Menus/:id/:LCID', 'Rbs/Website/Menu/form.twig')
			;

			return $delegate;
		}]);
	}]);


})();