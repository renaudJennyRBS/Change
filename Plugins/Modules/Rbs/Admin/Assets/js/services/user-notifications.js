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

	app.service('RbsChange.UserNotifications', [ 'RbsChange.REST', '$q', 'RbsChange.User', 'RbsChange.Settings', function (REST, $q, User, Settings)
	{
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


		function loadNotifications (params)
		{
			var defer = $q.defer();

			Settings.ready().then(function()
			{
				REST.query(
					getNotificationQuery('new'),
					angular.extend({'column':['message']}, params)
				).then(function (data) {
					defer.resolve(data);
				});
			});

			return defer.promise;
		}

		// Public API
		return {
			load : loadNotifications
		};

	}]);

})();