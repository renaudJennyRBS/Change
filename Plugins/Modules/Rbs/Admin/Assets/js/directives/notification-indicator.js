/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsNotificationIndicator', ['RbsChange.UserNotifications', function (UserNotifications)
	{
		return {
			restrict : 'A',
			templateUrl: 'Rbs/Admin/js/directives/notification-indicator.twig',
			replace : true,
			scope : true,

			link : function (scope)
			{
				scope.notifications = UserNotifications.getNotifications();

				scope.markAsRead = function (notification, event)
				{
					event.stopPropagation();
					notification.status = 'loading';
					UserNotifications.markAsRead(notification);
				};

				scope.archive = function (notification, event)
				{
					event.stopPropagation();
					notification.status = 'loading';
					UserNotifications.archive(notification);
				};
			}
		};
	}]);

})();