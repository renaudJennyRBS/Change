(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 */
	app.directive('modelSelect', ['RbsChange.REST', function (REST) {
		return {
			restrict : 'E',
			require  : 'ng-model',

			template : '<select ng-options="doc.label for doc in documents"></select>',
			replace  : true,

			scope    : true,

			link     : function (scope, elm, attrs) {
				REST.collection(attrs.changeModel).then(function (docs) {
					scope.documents = docs.resources;
				});
			}
		};
	}]);

})();