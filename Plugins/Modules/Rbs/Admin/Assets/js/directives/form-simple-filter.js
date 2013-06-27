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
				'<input autocapitalize="off" autocomplete="off" autocorrect="off" placeholder="' + i18n.trans('m.rbs.admin.admin.js.search | ucf | etc') + '" type="text" ng-keyup="keyup($event)" ng-keydown="keydown($event)" ng-model="filter"/>',

			scope : {
				'filter'   : '=',
				'onEnter'  : '&',
				'onTab'    : '&',
				'onEscape' : '&'
			},

			link : function (scope, elm) {
				elm.attr('novalidate', '');

				scope.clear = function () {
					scope.filter = '';
					elm.find('input').focus();
				};

				scope.keyup = function ($event) {
					switch ($event.keyCode) {
						// ENTER
						case 13 :
							$event.stopPropagation();
							$event.preventDefault();
							scope.onEnter();
							scope.clear();
							break;

						// ESC
						case 27 :
							$event.stopPropagation();
							$event.preventDefault();
							if (scope.filter.length) {
								scope.clear();
							} else {
								scope.onEscape();
							}
					}
				};

				scope.keydown = function ($event) {
					switch ($event.keyCode) {
						// TAB
						case 9 :
							$event.stopPropagation();
							$event.preventDefault();
							scope.onTab();
							scope.clear();
							break;
					}
				};
			}
		};
	}]);

})();



