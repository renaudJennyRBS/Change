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

		$scope.reloadTasks = function (params)
		{
			var p = UserTasks.load(params);
			p.then(function (result) {
				$scope.tasks = result;
			});
			return p;
		};

		$scope.reloadTasks(null);


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

	ChangeAdminTasksController.$inject = [
		'$scope',
		'RbsChange.UserTasks'
	];
	app.controller('Rbs_Admin_TasksController', ChangeAdminTasksController);

})();