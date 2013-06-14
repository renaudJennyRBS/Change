(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 *
	 */
	app.directive('rbsFormSimpleFilter', ['RbsChange.i18n', function (i18n) {
		return {
			restrict : 'C',

			template :
				'<button type="button" ng-click="clear()"><i class="icon-remove-circle icon-large"></i></button>' +
				'<input autocapitalize="off" autocomplete="off" autocorrect="off" placeholder="' + i18n.trans('m.rbs.admin.admin.js.search | ucf | etc') + '" type="text" ng-model="filter"/>',

			scope : {
				'filter' : '='
			},

			link : function (scope, elm) {
				elm.attr('novalidate', '');
				scope.clear = function () {
					scope.filter = '';
					elm.find('input').focus();
				};
			}
		};
	}]);

})();



