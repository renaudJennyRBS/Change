/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsTaskIndicator', ['RbsChange.UserTasks', function (UserTasks)
	{
		return {
			restrict : 'A',
			templateUrl: 'Rbs/Admin/js/directives/task-indicator.twig',
			replace : true,
			scope : true,

			link : function (scope)
			{
				function loadTasks ()
				{
					UserTasks.load().then(function (result) {
						scope.tasks = result;
					});
				}
				loadTasks();

				scope.reload = loadTasks;
			}
		};
	}]);

})();