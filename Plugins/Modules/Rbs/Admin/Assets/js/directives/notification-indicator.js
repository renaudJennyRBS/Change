/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsNotificationIndicator', ['RbsChange.Settings', 'RbsChange.UserNotifications', function (Settings, UserNotifications)
	{
		return {
			restrict : 'A',
			templateUrl: 'Rbs/Admin/js/directives/notification-indicator.twig',
			replace : true,
			scope : true,

			link : function (scope)
			{
				function loadNotifications ()
				{
					UserNotifications.load().then(function (result) {
						scope.notifications = result;
					});
				}
				loadNotifications();

				scope.reload = loadNotifications;
			}
		};
	}]);

})();