(function() {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsWebsiteManageTrackers(scope) {
		function refreshScope() {
			scope.isChosen = __change.rbsWebsiteTrackersManager.isChosen();
			scope.isAllowed = __change.rbsWebsiteTrackersManager.isAllowed();
		}
		refreshScope();

		scope.optIn = function () {
			__change.rbsWebsiteTrackersManager.optIn();
			refreshScope();
		};
		scope.optOut = function () {
			__change.rbsWebsiteTrackersManager.optOut();
			refreshScope();
		}
	}

	rbsWebsiteManageTrackers.$inject = ['$scope'];
	app.controller('rbsWebsiteManageTrackers', rbsWebsiteManageTrackers);
})();