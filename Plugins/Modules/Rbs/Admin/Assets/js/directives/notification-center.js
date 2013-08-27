(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('notificationCenter', ['$filter', '$compile', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', function ($filter, $compile, NotificationCenter, ErrorFormatter) {

		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/notification-center.html',

			link : function (scope) {
				scope.notifications = NotificationCenter.notifications;

				scope.formatErrorMessage = function (notification) {
					var html = ErrorFormatter.format(notification.body, notification.context);
					return html;
				};

				scope.remove = function (index) {
					NotificationCenter.remove(index);
				};
			}

		};
	}]);

})();