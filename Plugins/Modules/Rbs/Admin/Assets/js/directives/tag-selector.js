(function ($) {

	"use strict";

	var	app = angular.module('RbsChange');

	app.directive('rbsTagSelector', ['RbsChange.ArrayUtils', '$timeout', function (ArrayUtils, $timeout) {

		var counter = 0;

		return {
			restrict : 'E',
			replace  : true,
			template :
				'<div class="tag-selector" ng-click="focus()">' +
					'<span ng-repeat="tag in tags">' +
						'<span ng-if="!tag.input" class="tag (= tag.color =)">(= tag.label =) <a href tabindex="-1" ng-click="removeTag($index)"><i class="icon-remove"></i></a></span>' +
						'<span ng-if="tag.input" class="typed">(= typed =)<input type="text" ng-keydown="keydown($event, $index)" ng-keyup="update($event, $index)"/></span>' +
					'</span>' +
				'</div>',

			// Create isolated scope.
			scope : {
				items : '='
			},

			link : function (scope, elm, attrs) {

				scope.typed = '';
				scope.tags = [{
					'input':true
				}];

				var inputIndex = 0;

				function backspace () {
					if (scope.typed.length) {
						scope.typed = scope.typed.substring(0, scope.typed.length-1);
					} else if (scope.tags.length > 1) {
						scope.tags.splice(inputIndex-1, 1);
					}
				}

				function remove () {
					if (scope.typed.length) {
						scope.typed = scope.typed.substring(0, scope.typed.length-1);
					} else if (scope.tags.length > 1) {
						scope.tags.splice(inputIndex+1, 1);
					}
				}

				function add () {
					scope.tags.splice(inputIndex, 0, {
						'label': scope.typed.trim(),
						'id'   : (++counter),
						'color': (scope.typed === 'fred' ? "blue" : (scope.typed === 'ipad' ? "red" : ""))
					});
					scope.typed = '';
				}

				scope.removeTag = function (index) {
					scope.tags.splice(index, 1);
				};

				scope.focus = function () {
					$timeout(function () {
						elm.find('input[type=text]').focus();
					});
				};

				function moveinput (offset) {
					if (scope.typed.length === 0 && (inputIndex+offset) < (scope.tags.length)) {
						var r = ArrayUtils.move(scope.tags, inputIndex, inputIndex+offset);
						scope.focus();
						return r;
					}
					return false;
				}

				scope.update = function ($event, index) {
					inputIndex = index;
					var input = elm.find('input[type=text]');
					scope.typed += input.val();
					input.val('');
				};

				scope.keydown = function ($event, index) {
					inputIndex = index;
					var input = elm.find('input[type=text]');

					console.log("update tag selector: ", input.val().trim(), $event.keyCode);
					switch ($event.keyCode) {
						case 8 :
							backspace();
							$event.preventDefault();
							$event.stopPropagation();
							break;
						case 46 :
							remove();
							$event.preventDefault();
							$event.stopPropagation();
							break;
						case 9 :
							if (scope.typed.length) {
								add();
								$event.preventDefault();
								$event.stopPropagation();
							}
							break;
						case 188 :
							add();
							$event.preventDefault();
							$event.stopPropagation();
							break;
						case 37 :
							moveinput(-1);
							$event.preventDefault();
							$event.stopPropagation();
							break;
						case 39 :
							moveinput(1);
							$event.preventDefault();
							$event.stopPropagation();
							break;
					}
				};

			}
		};
	}]);

})(window.jQuery);