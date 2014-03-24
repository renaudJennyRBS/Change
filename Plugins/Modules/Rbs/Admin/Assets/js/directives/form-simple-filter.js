(function() {
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsFormSimpleFilter', ['RbsChange.i18n', '$timeout', function(i18n, $timeout) {
		return {
			restrict: 'A',
			templateUrl : 'Rbs/Admin/js/directives/form-simple-filter.twig',
			scope: {
				'filter': '=',
				'onEnter': '&',
				'onTab': '&',
				'onEscape': '&'
			},

			link: function(scope, elm) {
				function applyValue() {
					$timeout(function() {
						scope.filter = scope.internalFilter;
					}, 250);
				}

				elm.attr('novalidate', '');

				scope.clear = function() {
					scope.internalFilter = '';
					elm.find('input').focus();
					applyValue();
				};

				scope.keyup = function($event) {
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
							}
							else {
								scope.onEscape();
							}
						}
						return;
					}
					applyValue();
				};

				scope.keydown = function($event) {
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



