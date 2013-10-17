(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 *
	 */
	app.directive('rbsFormSimpleFilter', ['RbsChange.i18n', '$timeout', function (i18n, $timeout) {
		return {
			restrict : 'C',

			template :
				'<button type="button" ng-click="clear()"><i class="icon-remove-circle icon-large"></i></button>' +
				'<input class="form-control" autocapitalize="off" autocomplete="off" autocorrect="off" placeholder="' + i18n.trans('m.rbs.admin.admin.js.search | ucf | etc') + '" type="text" ng-keyup="keyup($event)" ng-keydown="keydown($event)" ng-model="internalFilter"/>',

			scope : {
				'filter'   : '=',
				'onEnter'  : '&',
				'onTab'    : '&',
				'onEscape' : '&'
			},

			link : function (scope, elm) {

				function applyValue() {
					$timeout(function () {
						scope.filter = scope.internalFilter;
					}, 250);
				}

				elm.attr('novalidate', '');

				scope.clear = function () {
					scope.internalFilter = '';
					elm.find('input').focus();
					applyValue();
				};

				scope.keyup = function ($event) {
					if ($event.keyCode === 13 || $event.keyCode === 27) {
						$event.stopPropagation();
						$event.preventDefault();
						if ($event.keyCode === 13) {
							scope.onEnter();
							scope.clear();
						}
						else {
							if (scope.internalFilter.length) {
								scope.clear();
							} else {
								scope.onEscape();
							}
						}
						return;
					}
					applyValue();
				};

				scope.keydown = function ($event) {
					if ($event.keyCode === 9 && elm.is('[on-tab]')) {
						$event.stopPropagation();
						$event.preventDefault();
						scope.onTab();
						scope.clear();
						return;
					}
					applyValue();
				};
			}
		};
	}]);

})();



