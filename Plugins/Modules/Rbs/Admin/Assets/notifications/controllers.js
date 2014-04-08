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
		$scope.view = 'unread';

		$scope.$watch('view', function (view)
		{
			if (view === 'unread') {
				$scope.notifications = UserNotifications.getNotifications();
				$scope.reloadNotifications = UserNotifications.reload;
			} else if (view === 'all') {
				$scope.notifications = UserNotifications.getAllNotifications();
				$scope.reloadNotifications = UserNotifications.reloadAll;
			}
		});

		$scope.dlExt = {
			markAsRead : function (data)
			{
				if (angular.isArray(data)) {
					var promises = [];
					angular.forEach(data, function (n) {
						promises.push(UserNotifications.markAsRead(n, false));
					});
					var p = $q.all(promises);
					p.then($scope.reloadNotifications);
					return p;
				} else {
					return UserNotifications.markAsRead(data, true);
				}
			},

			archive : function (data)
			{
				if (angular.isArray(data)) {
					var promises = [];
					angular.forEach(data, function (n) {
						promises.push(UserNotifications.archive(n, false));
					});
					var p = $q.all(promises);
					p.then($scope.reloadNotifications);
					return p;
				} else {
					return UserNotifications.archive(data, true);
				}
			}
		};
	}

	ChangeAdminNotificationsController.$inject = [
		'$scope', '$q',
		'RbsChange.UserNotifications'
	];
	app.controller('Rbs_Admin_NotificationsController', ChangeAdminNotificationsController);

})();