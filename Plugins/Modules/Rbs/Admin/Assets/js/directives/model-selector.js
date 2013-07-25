(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsModelSelector', ['RbsChange.REST', function (REST) {
		return {
			restrict : 'A',
			require  : 'ngModel',
			template : '<select ng-options="m.label for m in models"></select>',
			replace  : true,
			scope    : true,

			link : function (scope, elm, attrs) {
				if (attrs.rbsModelSelect === 'publishable') {
					REST.call(REST.getBaseUrl('Rbs/PublishableModels')).then(function (models) {
						scope.models = models;
					});
				} else {
					throw new Error("Directive 'rbs-model-select' only works with publishable models for now.");
				}
			}
		};
	}]);

})();