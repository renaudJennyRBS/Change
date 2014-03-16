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
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-model-selector
	 * @restrict E
	 *
	 * @description
	 * Displays a listbox to select a Document Model.
	 *
	 * @param {Object} model The selected Document Model.
	 * @param {Object} filter JSON object to filter the Document Models.
	 *
	 * @example
	 * <pre>
	 *     <rbs-model-selector
	 *        model="selectedModel"
	 *        filter="{publishable:true, abstract:false}">
	 *     </rbs-model-selector>
	 * </pre>
	 */
	app.directive('rbsModelSelector', ['RbsChange.Models', 'RbsChange.ArrayUtils', function (Models, ArrayUtils)
	{
		return {
			restrict : 'E',
			template : '<select ng-model="model" ng-options="m.label group by m.plugin for m in models | filter:filter:comparator | orderBy:[\'plugin\',\'label\']"></select>',
			replace : true,
			scope : {
				model : '=',
				filter : '@'
			},

			link : function (scope, elm, attrs)
			{
				scope.models = Models.getAll();
				scope.filter = {};
				scope.comparator = function (expected, actual)
				{
					if (angular.isArray(actual))
					{
						return ArrayUtils.inArray(expected, actual) != -1;
					}
					else if (actual === null)
					{
						return true;
					}
					return expected == actual;
				};
				attrs.$observe('filter', function (value) {
					scope.filter = scope.$eval(attrs.filter);
				});
			}
		};
	}]);

})();