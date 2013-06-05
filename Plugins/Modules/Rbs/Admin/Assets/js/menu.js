(function () {

	"use strict";

	angular.module('RbsChange').controller(
		'Rbs_MainMenu_Controller',
		[
			'$rootScope',
			'$scope',
			'$filter',
			'$timeout',
			'$location',
			ChangeMainMenuControllerFn
		]
	);

	function ChangeMainMenuControllerFn ($rootScope, $scope, $filter, $timeout, $location) {

		var	$menu = jQuery('#change-menu'),
			$filterInput = $menu.find('input.search-query').first();

		// TODO Load modules from the server.
		if (!__change.menu) {
			__change.menu = {
				"sections" : [
					{
						"code" : "cms",
						"label": "CMS"
					},
					{
						"code" : "ecommerce",
						"label": "E-commerce"
					}
				],
				"entries" : [
					{
						"label"  : "Sites et pages",
						"url"    : "Rbs/Website",
						"section": "cms"
					},
					{
						"label"  : "Thèmes",
						"url"    : "Rbs/Theme",
						"section": "cms"
					},
					{
						"label"  : "Médiathèque",
						"url"    : "Rbs/Media",
						"section": "cms"
					},
					{
						"label"  : "Catalogue",
						"url"    : "Rbs/Catalog",
						"section": "ecommerce"
					},
					{
						"label"  : "Zones géographiques",
						"url"    : "Rbs/Geo"
						"section": "ecommerce"
					}
				]
			};
		}

		$scope.hasOtherModules = false;
		$scope.menu = __change.menu;
		angular.forEach($scope.menu.entries, function (entry) {
			if (!entry.section) {
				entry.section = 'other';
				$scope.hasOtherModules = true;
			}
		});

		$scope.open = function () {
			$menu.addClass('show');
			$filterInput.focus();
		};

		$scope.close = function () {
			$scope.filterModules = '';
			$menu.removeClass('show');
			$filterInput.blur();
		};

		$scope.toggle = function () {
			if ($menu.is('.show')) {
				this.close();
			} else {
				this.open();
			}
		};

		$scope.clear = function () {
			$scope.filterModules = '';
			$filterInput.focus();
		};

		$scope.filterKeydown = function ($event) {
			if ($event.keyCode === 27) {
				if (!$scope.filterModules || !$scope.filterModules.length) {
					this.close();
				} else {
					$timeout(function () {
						$scope.filterModules = '';
					});
				}
			}
		};

		$scope.go = function () {
			var found = $filter('filter')(this.modules, $scope.filterModules);
			if (found.length === 1) {
				this.close();
				$location.path(found[0].url);
			}
		};

		// Add shortcut methods to the $rootScope.
		$rootScope.menu = {
			"open": function () {
				$scope.open();
			},
			"close": function () {
				$scope.close();
			},
			"toggle": function () {
				$scope.toggle();
			}
		};

		// Add keyboard shortcut to invoke the main menu (Ctrl+Alt+M for the moment).
		// TODO Add possibility to configure the keyboard shortcut?
		jQuery('body').on('keydown', function (ev) {
			if (ev.ctrlKey && ev.altKey && ev.keyCode === 77) {
				$scope.toggle();
			}
		});

	}

})();