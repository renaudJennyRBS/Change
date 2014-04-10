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
		var notifications = {},
			allNotifications = {};

		function getNotificationQuery (status)
		{
			var query = {
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
						}
					]
				},
				'order': [{
					"property": "creationDate",
					"order": "desc"
				}]
			};

			if (status) {
				query.where.and.push(
					{
						'op': 'eq',
						'lexp': {
							'property': 'status'
						},
						'rexp': {
							'value': status
						}
					}
				);
			} else {
				query.where.and.push(
					{
						'op': 'neq',
						'lexp': {
							'property': 'status'
						},
						'rexp': {
							'value': 'deleted'
						}
					}
				);
			}

			return query;
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
		function loadNewNotifications (params)
		{
			REST.query(
				getNotificationQuery('new'),
				angular.extend({'column':['message','status','code']}, params)
			).then(function (data) {
				notifications.pagination = data.pagination;
				notifications.resources = data.resources;
			});
			$timeout(loadNewNotifications, 1000*60);
		}

		function loadAllNotifications (params)
		{
			REST.query(
					getNotificationQuery(),
					angular.extend({'column':['message','status','code']}, params)
				).then(function (data) {
					allNotifications.pagination = data.pagination;
					allNotifications.resources = data.resources;
				});
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
		function markAsRead (notification, reload)
		{
			var n = angular.copy(notification);
			n.status = 'read';
			var p = REST.save(n);
			if (reload === true || angular.isUndefined(reload)) {
				p.then(function () {
					loadNewNotifications();
					loadAllNotifications();
				});
			}
			return p;
		}

		/**
		 * @ngdoc function
		 * @methodOf RbsChange.service:UserNotifications
		 * @name RbsChange.service:UserNotifications#archive
		 *
		 * @description
		 * Marks the given Notification as deleted.
		 *
		 * @param {Document} notification Notification.
		 * @returns {Promise} Promise resolved when the Notification is marked as deleted.
		 */
		function archive (notification, reload)
		{
			var n = angular.copy(notification);
			n.status = 'deleted';
			var p = REST.save(n);
			if (reload === true || angular.isUndefined(reload)) {
				p.then(function () {
					loadNewNotifications();
					loadAllNotifications();
				});
			}
			return p;
		}

		Settings.ready().then(loadNewNotifications);

		// Public API
		return {
			markAsRead : markAsRead,
			archive : archive,

			reload : loadNewNotifications,
			reloadAll : loadAllNotifications,

			getNotifications : function ()
			{
				return notifications;
			},

			getAllNotifications : function ()
			{
				if (angular.isUndefined(allNotifications.resources)) {
					loadAllNotifications();
				}
				return allNotifications;
			}
		};

	}]);

})();