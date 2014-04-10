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
			.when('/tasks/',
			{
				templateUrl : 'Rbs/Admin/tasks/list.twig',
				reloadOnSearch : false
			});
	}]);

	/**
	 * @name Rbs_Admin_TasksController
	 */
	function ChangeAdminTasksController ($scope, $q, UserTasks, i18n)
	{
		$scope.tasks = UserTasks.getTasks();

		$scope.dlExt =
		{
			executeTask : function (data, actionName)
			{
				if (angular.isArray(data)) {
					var promises = [];
					angular.forEach(data, function (t) {
						// When in batch processing, the only allowed action is 'execute'.
						promises.push(UserTasks.execute(t, 'execute', null, false));
					});
					var p = $q.all(promises);
					p.then(function () {UserTasks.reload()});
					return p;
				} else {
					return UserTasks.execute(data, actionName, null, true);
				}
			},

			rejectTask : function (data)
			{
				var task, reason;
				if (angular.isArray(data)) {
					task = data[0];
				} else {
					task = data;
				}
				reason = window.prompt(i18n.trans("m.rbs.admin.admin.please_indicate_reject_reason"));
				if (reason && reason.length) {
					return UserTasks.reject(task, reason);
				}
			}
		};

		$scope.reloadTasks = UserTasks.reload;
	}

	ChangeAdminTasksController.$inject = [
		'$scope', '$q',
		'RbsChange.UserTasks',
		'RbsChange.i18n'
	];
	app.controller('Rbs_Admin_TasksController', ChangeAdminTasksController);

})();