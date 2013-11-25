(function () {

	"use strict";

	angular.module('RbsChange').controller(
		'Rbs_MainMenu_Controller',
		[
			'$scope',
			'$timeout',
			ChangeMainMenuControllerFn
		]
	);

	function ChangeMainMenuControllerFn ($scope, $timeout)
	{
		$scope.hasOtherModules = false;
		$scope.menu = __change.menu;

		$timeout(function () {
			new mlPushMenu( document.getElementById( 'mp-menu' ), document.getElementById( 'mp-menu-trigger' ) );
		});
	}

})();