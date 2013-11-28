(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('notificationCenter', ['$filter', '$compile', 'RbsChange.NotificationCenter', '$timeout', function ($filter, $compile, NotificationCenter, $timeout) {

		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/notification-center.twig',

			link : function (scope, element) {
				scope.notifications = NotificationCenter.notifications;
			}

		};
	}]);

})();