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
	 * @name RbsChange.service:UserTasks
	 *
	 * @description Provides methods to deal with user tasks (Task in the workflow system).
	 */
	app.service('RbsChange.UserTasks', [ 'RbsChange.REST', '$q', '$http', 'RbsChange.Settings', '$timeout', function (REST, $q, $http, Settings, $timeout)
	{
		var tasks = {};

		/**
		 * @ngdoc function
		 * @methodOf RbsChange.service:UserTasks
		 * @name RbsChange.service:UserTasks#load
		 *
		 * @description
		 * Loads the Tasks for the current user.
		 *
		 * @param {Object=} Optional parameters.
		 * @returns {Promise} Promise resolved when the Tasks are loaded.
		 */
		function load (params)
		{
			REST.call(
				REST.getBaseUrl('admin/currentTasks/'),
				angular.extend({'column': ['document', 'taskCode', 'status']}, params),
				REST.collectionTransformer()
			).then(function (data) {
				tasks.pagination = data.pagination;
				tasks.resources = data.resources;
			});
			$timeout(load, 1000*60);
		}

		Settings.ready().then(load);

		/**
		 * @ngdoc function
		 * @methodOf RbsChange.service:UserTasks
		 * @name RbsChange.service:UserTasks#execute
		 *
		 * @description
		 * Execute the given Task.
		 *
		 * @param {Document} task The Task Document to execute.
		 * @param {String=} actionName Name of the action to execute. Defaults to 'execute'.
		 * @param {Object=} params Optional parameters.
		 * @returns {Promise} Promise resolved when the action is successfully executed.
		 */
		function execute (task, actionName, params)
		{
			actionName = actionName || 'execute';
			if (task && task.META$ && task.META$.actions && task.META$.actions[actionName]) {
				var p = $http.post(
					task.META$.actions[actionName].href,
					params,
					REST.getHttpConfig(REST.resourceTransformer())
				);
				p.then(function(){load();});
				return p;
			}
			var defer = $q.defer();
			defer.reject('Bad Task configuration');
			return defer.promise;
		}

		// Public API
		return {
			getTasks : function ()
			{
				return tasks;
			},
			execute : execute
		};

	}]);

})();