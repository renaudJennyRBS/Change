(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('notificationCenter', ['$filter', '$compile', 'RbsChange.NotificationCenter', function ($filter, $compile, NotificationCenter) {

		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/notification-center.twig',

			link : function (scope) {
				scope.notifications = NotificationCenter.notifications;
			}

		};
	}]);

})();