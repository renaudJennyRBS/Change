/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @ngdoc service
	 * @name RbsChange.service:NotificationCenter
	 * @description Displays notifications to the user.
	 */
	app.provider('RbsChange.NotificationCenter', function RbsChangeNotificationCenterProvider()
	{

		this.$get = ['$rootScope', 'RbsChange.ArrayUtils', function ($rootScope, ArrayUtils)
		{

			var notificationCenter = {

				notifications: [],

				push : function (notification)
				{
					notification.level = notification.level || "info";

					if (notification.body !== null && notification.body !== undefined && angular.isArray(notification.body))
					{
						notification.body = this.constructBodyHtml(notification.body);
					}

					if (notification.id === null || notification.id === undefined)
					{
						notification.id = 'auto-' + (new Date()).getTime();
					}

					var existingNotificationIndex = this.getIndexOfNotificationById(notification.id);

					if (existingNotificationIndex !== null)
					{
						this.remove(existingNotificationIndex);
					}

					this.notifications.push(notification);
				},

				/**
				 * @ngdoc function
				 * @methodOf RbsChange.service:NotificationCenter
				 * @name RbsChange.service:NotificationCenter#info
				 *
				 * @description Displays an informational notification to the user.
				 *
				 * @param {String} title The notification's title.
				 * @param {String} body The notification's main contents.
				 * @param {String=} id Multiple notifications with the same ids will only be displayed once.
				 * @param {Integer=} timeout Timeout in millisecond after which the notification is removed from the screen.
				 */
				info : function (title, body, id, timeout)
				{
					this.push({
						'title': title,
						'body': body,
						'level': 'info',
						'id': id,
						'timeout' : timeout
					});
				},

				/**
				 * @ngdoc function
				 * @methodOf RbsChange.service:NotificationCenter
				 * @name RbsChange.service:NotificationCenter#warning
				 *
				 * @description Displays a warning notification to the user.
				 *
				 * @param {String} title The notification's title.
				 * @param {String} body The notification's main contents.
				 * @param {String=} id Multiple notifications with the same ids will only be displayed once.
				 * @param {Object=} context Object that can be used as a context.
				 */
				warning : function (title, body, id, context)
				{
					this.push({
						'title': title,
						'body': body,
						'level': 'warning',
						'context': context,
						'id': id
					});
				},

				/**
				 * @ngdoc function
				 * @methodOf RbsChange.service:NotificationCenter
				 * @name RbsChange.service:NotificationCenter#error
				 *
				 * @description Displays an error notification to the user.
				 *
				 * @param {String} title The notification's title.
				 * @param {String} body The notification's main contents.
				 * @param {String=} id Multiple notifications with the same ids will only be displayed once.
				 * @param {Object=} context Object that can be used as a context.
				 */
				error : function (title, body, id, context)
				{
					this.push({
						'title': title,
						'body': body,
						'level': 'error',
						'context': context,
						'id': id
					});
				},

				/**
				 * @ngdoc remove
				 * @methodOf RbsChange.service:NotificationCenter
				 * @name RbsChange.service:NotificationCenter#remove
				 *
				 * @description Removes the notification at the given `index`.
				 *
				 * @param {Integer} index Index of the notification to be removed.
				 */
				remove : function (index)
				{
					ArrayUtils.remove(this.notifications, index, index);
				},

				/**
				 * @ngdoc function
				 * @methodOf RbsChange.service:NotificationCenter
				 * @name RbsChange.service:NotificationCenter#clear
				 *
				 * @description Removes all the notifications.
				 */
				clear : function ()
				{
					ArrayUtils.clear(this.notifications);
				},

				constructBodyHtml : function (array)
				{
					return '<ul><li>' + array.join('</li><li>') + '</li></ul>';
				},

				/**
				 * @ngdoc function
				 * @methodOf RbsChange.service:NotificationCenter
				 * @name RbsChange.service:NotificationCenter#getIndexOfNotificationById
				 *
				 * @description Returns the index of a notification from its id.
				 *
				 * @param {String} id The notification ID.
				 */
				getIndexOfNotificationById : function (id)
				{
					for (var i = 0; i < this.notifications.length; i++)
					{
						if (this.notifications[i].id === id)
						{
							return i;
						}
					}
					return null;
				}

			};

			/**
			 * When the route changes, we need to clean up any cascading process.
			 */
			$rootScope.$on('$routeChangeSuccess', function ()
			{
				notificationCenter.clear();
			});

			return notificationCenter;

		}];

	});

})();