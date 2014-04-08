/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @ngdoc service
	 * @name RbsChange.service:UserNotifications
	 *
	 * @description Provides methods to deal with user Notifications.
	 */
	app.service('RbsChange.UserNotifications', [ 'RbsChange.REST', '$q', 'RbsChange.User', 'RbsChange.Settings', '$timeout', function (REST, $q, User, Settings, $timeout)
	{
		var notifications = {};

		function getNotificationQuery (status)
		{
			return {
				'model': 'Rbs_Notification_Notification',
				'where': {
					'and': [
						{
							'op': 'eq',
							'lexp': {
								'property': 'userId'
							},
							'rexp': {
								'value': User.get().id
							}
						},
						{
							'op': 'eq',
							'lexp': {
								'property': 'status'
							},
							'rexp': {
								'value': status
							}
						}
					]
				}
			};
		}

		/**
		 * @ngdoc function
		 * @methodOf RbsChange.service:UserNotifications
		 * @name RbsChange.service:UserNotifications#load
		 *
		 * @description
		 * Loads the Notifications for the current user.
		 *
		 * @param {Object=} Optional parameters.
		 * @returns {Promise} Promise resolved when the Notifications are loaded.
		 */
		function load (params)
		{
			REST.query(
				getNotificationQuery('new'),
				angular.extend({'column':['message','status','code']}, params)
			).then(function (data) {
				notifications.pagination = data.pagination;
				notifications.resources = data.resources;
			});
			$timeout(load, 1000*60);
		}

		/**
		 * @ngdoc function
		 * @methodOf RbsChange.service:UserNotifications
		 * @name RbsChange.service:UserNotifications#markAsRead
		 *
		 * @description
		 * Marks the given Notification as read.
		 *
		 * @param {Document} notification Notification.
		 * @returns {Promise} Promise resolved when the Notification is marked as read.
		 */
		function markAsRead (notification)
		{
			var n = angular.copy(notification);
			n.status = 'read';
			REST.save(n).then(function(){load();});
		}

		Settings.ready().then(load);

		// Public API
		return {
			markAsRead : markAsRead,
			getNotifications : function ()
			{
				return notifications;
			},
			reload : load
		};

	}]);

})();