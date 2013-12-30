(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModelTranslation('Rbs_Website_Website');
	__change.createEditorForModelTranslation('Rbs_Website_Topic');


	app.run(['$templateCache', function($templateCache) {
		$templateCache.put('picker-item-Rbs_Menu_Item.html', '(=item.title=)(=item.titleKey=)');
	}]);


	/**
	 * @name Rbs_Website_MainMenuController
	 */
	function ChangeWebsiteMainMenuController ($scope, $location, REST)
	{
		var unregisterUserLoginListener = null;


		function updateSelection (currentWebsiteId)
		{
			if ($scope.websites)
			{
				if (currentWebsiteId)
				{
					angular.forEach($scope.websites, function (ws) {
						if (ws.id === currentWebsiteId) {
							$scope.website = ws;
						}
					});
				}
				if (! $scope.website && $scope.websites && $scope.websites.length)
				{
					$scope.website = $scope.websites[0];
				}
			}
		}

		$scope.$on('$routeUpdate', function () {
			updateSelection(parseInt($location.search()['tn'], 10));
		});

		function loadSuccessFn (root)
		{
			REST.treeChildren(root.resources[0]).then(function (websites)
			{
				$scope.websites = websites.resources;
				updateSelection(parseInt($location.search()['tn'], 10));
				if (unregisterUserLoginListener) {
					unregisterUserLoginListener();
				}
			});
		}

		$scope.$on('Change:TreePathChanged', function (event, bcData) {
			$scope.website = bcData.website ? bcData.website : null;
			if ($scope.website) {
				updateSelection($scope.website.id);
			}
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
			/*$delegate.model('Rbs_Website_Website')
				.route('tree', 'Rbs/Website/nav/?tn=:id', 'Document/Rbs/Website/Topic/browse.twig')
				.route('functions', 'Rbs/Website/Website/:id/Functions/', 'Document/Rbs/Website/SectionPageFunction/list.twig')
				.route('menus', 'Rbs/Website/Website/:id/Menus/', 'Document/Rbs/Website/Menu/list.twig')
			;*/
			$delegate.model('Rbs_Website_Website')
				.route('tree', 'Rbs/Website/Browse/?website=:id&view=Structure', 'Document/Rbs/Website/Website/browse.twig')
				.route('functions', 'Rbs/Website/Browse/?website=:id&view=Functions', 'Document/Rbs/Website/Website/browse.twig')
				.route('menus', 'Rbs/Website/Browse/?website=:id&view=Menus', 'Document/Rbs/Website/Website/browse.twig')
				.route('properties', 'Rbs/Website/Browse/?website=:id&view=Properties', 'Document/Rbs/Website/Website/browse.twig')
			;

			$delegate.model('Rbs_Website_Topic')
				.route('tree', 'Rbs/Website/nav/?tn=:id', 'Document/Rbs/Website/Topic/browse.twig')
				.route('functions', 'Rbs/Website/Topic/:id/Functions/', 'Document/Rbs/Website/SectionPageFunction/list.twig')
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
				.route('new', 'Rbs/Website/Website/:website/Menus/new', 'Document/Rbs/Website/Menu/form.twig')
				.route('form', 'Rbs/Website/Website/:website/Menus/:id/:LCID', 'Document/Rbs/Website/Menu/form.twig')
			;

			return $delegate;
		}]);
	}]);


})();