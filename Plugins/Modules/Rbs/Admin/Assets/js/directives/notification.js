(function ($)
{

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsNotification', ['RbsChange.NotificationCenter', '$timeout', rbsNotificationDirective]);

	function rbsNotificationDirective(NotificationCenter, $timeout)
	{

		return {
			restrict: 'E',
			templateUrl: 'Rbs/Admin/js/directives/notification.twig',
			scope: {},

			link: function (scope, iElement, iAttrs)
			{
				var notificationIndex = NotificationCenter.getIndexOfNotificationById(iAttrs.notificationId);
				var notification = NotificationCenter.notifications[notificationIndex];
				var timeoutId;

				scope.notification = notification;

				if (notification.timeout !== undefined && notification.timeout !== null)
				{
					timeoutId = $timeout(function ()
					{
						scope.remove();
					}, notification.timeout);
				}

				scope.removeAll = function ()
				{
					NotificationCenter.clear();
				};

				scope.remove = function ()
				{
					NotificationCenter.remove(NotificationCenter.getIndexOfNotificationById(notification.id));
				};

				iElement.bind('$destroy', function() {
					if (timeoutId !== undefined && timeoutId !== null)
					{
						$timeout.cancel(timeoutId);
					}
				});
			}
		};
	}

})(window.jQuery);