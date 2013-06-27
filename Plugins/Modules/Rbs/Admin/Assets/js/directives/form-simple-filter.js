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
				'<input autocapitalize="off" autocomplete="off" autocorrect="off" placeholder="' + i18n.trans('m.rbs.admin.admin.js.search | ucf | etc') + '" type="text" ng-keyup="keydown($event)" ng-model="filter"/>',

			scope : {
				'filter'   : '=',
				'onEnter'  : '&',
				'onEscape' : '&'
			},

			link : function (scope, elm) {
				elm.attr('novalidate', '');

				scope.clear = function () {
					scope.filter = '';
					elm.find('input').focus();
				};

				scope.keydown = function ($event) {
					// Enter
					if ($event.keyCode === 13) {
						$event.stopPropagation();
						$event.preventDefault();
						scope.onEnter();
						scope.clear();
					}
					// Escape
					else if ($event.keyCode === 27) {
						$event.stopPropagation();
						$event.preventDefault();
						if (scope.filter.length) {
							scope.clear();
						} else {
							scope.onEscape();
						}
					}
				};
			}
		};
	}]);

})();



