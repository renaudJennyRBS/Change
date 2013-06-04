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
		$scope.modules = [
			{
				"label"  : "Sites et pages",
				"url"    : "Rbs/Website",
				"pinned" : true
			},
			{
				"label"  : "Thèmes",
				"url"    : "Rbs/Theme"
			},
			{
				"label"  : "Médiathèque",
				"url"    : "Rbs/Media",
				"pinned" : true
			},
			{
				"label"  : "Catalogue",
				"url"    : "Rbs/Catalog"
			}
		];

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