/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsTaskIndicator', ['RbsChange.UserTasks', 'RbsChange.i18n', function (UserTasks, i18n)
	{
		return {
			restrict : 'A',
			templateUrl: 'Rbs/Admin/js/directives/task-indicator.twig',
			replace : true,
			scope : true,

			link : function (scope)
			{
				scope.tasks = UserTasks.getTasks();

				scope.executeTask = function ($event, task, actionName)
				{
					$event.stopPropagation();
					var t = angular.copy(task);
					task.loading = true;
					UserTasks.execute(t, actionName);
				};

				scope.rejectTask = function ($event, task)
				{
					$event.stopPropagation();
					var t = angular.copy(task),
						reason;
					task.loading = true;
					reason = window.prompt(i18n.trans("m.rbs.admin.admin.please_indicate_reject_reason"));
					if (reason && reason.length) {
						return UserTasks.reject(t, reason);
					}
				};

			}
		};
	}]);

})();