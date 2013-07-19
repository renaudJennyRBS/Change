(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * FIXME
	 * This does not work well as a field in an Editor:
	 * when coming back on the form, the value is not always set.
	 */
	app.directive('rbsDocumentSelect', ['RbsChange.REST', function (REST) {
		return {
			restrict : 'E',
			require  : 'ngModel',
			template : '<select ng-options="doc.label for doc in documents"></select>',
			replace  : true,
			scope    : false,

			link     : function (scope, elm, attrs) {
				REST.collection(attrs.model).then(function (docs) {
					scope.documents = docs.resources;
				});
			}
		};
	}]);

})();