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
	function ChangeAdminTasksController ($scope, UserTasks)
	{
		$scope.tasks = UserTasks.getTasks();

		$scope.dlExt = {
			executeTask : function (data, actionName)
			{
				if (angular.isArray(data)) {
					var promises = [];
					angular.forEach(data, function (t) {
						promises.push(UserTasks.execute(t, actionName));
					});
					return $q.all(promises);
				} else {
					return UserTasks.execute(data, actionName);
				}
			}
		};

		$scope.reloadTasks = UserTasks.reload;
	}

	ChangeAdminTasksController.$inject = [
		'$scope',
		'RbsChange.UserTasks'
	];
	app.controller('Rbs_Admin_TasksController', ChangeAdminTasksController);

})();