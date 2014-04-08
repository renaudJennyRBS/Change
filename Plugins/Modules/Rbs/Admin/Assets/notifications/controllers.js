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

	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider
			.when('/notifications/',
			{
				templateUrl : 'Rbs/Admin/notifications/list.twig',
				reloadOnSearch : false
			});
	}]);

	/**
	 * @name Rbs_Admin_NotificationsController
	 */
	function ChangeAdminNotificationsController ($scope, $q, UserNotifications)
	{
		$scope.notifications = UserNotifications.getNotifications();

		$scope.dlExt = {
			markAsRead : function (data)
			{
				if (angular.isArray(data)) {
					var promises = [];
					angular.forEach(data, function (n) {
						promises.push(UserNotifications.markAsRead(n));
					});
					return $q.all(promises);
				} else {
					return UserNotifications.markAsRead(data);
				}
			}
		};

		$scope.reloadNotifications = UserNotifications.reload;

	}

	ChangeAdminNotificationsController.$inject = [
		'$scope', '$q',
		'RbsChange.UserNotifications'
	];
	app.controller('Rbs_Admin_NotificationsController', ChangeAdminNotificationsController);

})();