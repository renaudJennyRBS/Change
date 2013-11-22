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
			'RbsChange.Device',
			ChangeMainMenuControllerFn
		]
	);

	function ChangeMainMenuControllerFn ($rootScope, $scope, $filter, $timeout, $location, Device) {

		var	$menu = jQuery('#rbs-change-menu'),
			$filterInput = $menu.find('input[type=text]').first();

		$scope.hasOtherModules = false;
		$scope.menu = __change.menu;
		angular.forEach($scope.menu.entries, function (entry) {
			if (!entry.section) {
				entry.section = 'other';
				$scope.hasOtherModules = true;
			}
		});

		$scope.$watch('filterModules', function (filter) {
			if (filter && angular.lowercase(filter.replace(/\s+/g, '')) === 'chucknorris') {
				$scope.chuckUrl = 'Rbs/Admin/img/chuck.jpg';
			}
			else {
				$scope.chuckUrl = null;
			}
			$scope.filterResults = $filter('filter')($scope.menu.entries, $scope.filterModules);
		});

		$scope.open = function () {
			$menu.addClass('show');
			if (!Device.isMultiTouch()) {
				$filterInput.focus();
			}
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

		$scope.go = function () {
			if ($scope.filterResults.length === 1) {
				this.close();
				$location.path($scope.filterResults[0].url);
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


		$timeout(function () {
			new mlPushMenu( document.getElementById( 'mp-menu' ), document.getElementById( 'mp-menu-trigger' ) );
		});

	}

})();