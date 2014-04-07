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

	app.service('RbsChange.UserTasks', [ 'RbsChange.REST', '$q', function (REST, $q)
	{
		function loadTasks (params)
		{
			var defer = $q.defer();

			REST.call(
				REST.getBaseUrl('admin/currentTasks/'),
				angular.extend({'column': ['document', 'taskCode', 'status']}, params),
				REST.collectionTransformer()
			).then(function (result) {
				defer.resolve(result);
			});

			return defer.promise;
		}

		// Public API
		return {
			load : loadTasks
		};

	}]);

})();