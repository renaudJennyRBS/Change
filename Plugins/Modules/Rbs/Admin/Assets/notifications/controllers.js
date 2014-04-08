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
	function ChangeAdminNotificationsController ($scope, UserNotifications)
	{

		$scope.reloadNotifications = function (params)
		{
			var p = UserNotifications.load(params);
			p.then(function (result) {
				$scope.notifications = result;
			});
			return p;
		};

		$scope.reloadNotifications(null);


		$scope.clipboardList = {
			'removeFromClipboard': function ($docs) {
				angular.forEach($docs, function (doc) {
					// TODO
				});
			},
			'clearClipboard': function () {

			}
		};


	}

	ChangeAdminNotificationsController.$inject = [
		'$scope',
		'RbsChange.UserNotifications'
	];
	app.controller('Rbs_Admin_NotificationsController', ChangeAdminNotificationsController);

})();