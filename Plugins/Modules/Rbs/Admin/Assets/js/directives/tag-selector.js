(function ($) {

	"use strict";

	var	app = angular.module('RbsChange');


	app.directive('rbsAutoSizeInput', function () {

		var options = {
			maxWidth: 1000,
			comfortZone: 18
		};

		return {
			restrict : 'A',

			link : function (scope, elm) {

				var val = '',
					input = $(elm),
					testSubject = $('<tester/>').css({
						position: 'absolute',
						top: -9999,
						left: -9999,
						width: 'auto',
						fontSize: input.css('fontSize'),
						fontFamily: input.css('fontFamily'),
						fontWeight: input.css('fontWeight'),
						letterSpacing: input.css('letterSpacing'),
						whiteSpace: 'nowrap'
					}),
					check = function() {
						if (val === (val = input.val())) {return;}
						var escaped = val.replace(/&/g, '&amp;').replace(/\s/g,' ').replace(/</g, '&lt;').replace(/>/g, '&gt;');
						testSubject.html(escaped);
						input.width(Math.min(testSubject.width() + options.comfortZone, options.maxWidth));
					};

				testSubject.insertAfter(input);

				$(elm).bind('keydown keyup blur update', check);

			}
		};

	});


	app.directive('rbsTagSelector', ['$timeout', '$compile', 'RbsChange.ArrayUtils', 'RbsChange.REST', function ($timeout, $compile, ArrayUtils, REST) {

		var	counter = 0,
			availTags = null,
			autocompleteEl;

		function loadAvailTags () {
			if (availTags === null) {
				availTags = REST.tags.getList();
			}
			return availTags;
		}

		$('<div id="rbsTagSelectorAutocompleteList"></div>').css({
			'position' : 'absolute',
			'display'  : 'none'
		}).appendTo('body');
		autocompleteEl = $('#rbsTagSelectorAutocompleteList');

		return {
			restrict : 'E',
			//replace  : true,
			require  : 'ngModel',
			scope    : true,
			template :
				'<div class="tag-selector" ng-click="focus($event)">' +
					'<span ng-repeat="tag in tags">' +
						'<span ng-if="!tag.input" class="tag (= tag.color =)">(= tag.label =) <a href tabindex="-1" ng-click="removeTag($index)"><i class="icon-remove"></i></a></span>' +
						'<input type="text" rbs-auto-size-input="" ng-if="tag.input" ng-keyup="autocomplete($event)" ng-keydown="keydown($event, $index)"></span>' +
					'</span>' +
				'</div>',


			link : function (scope, elm, attrs, ngModel) {

				var inputIndex = -1;

				ngModel.$render = function ngModelRenderFn () {
					if (angular.isArray(ngModel.$viewValue)) {
						scope.tags = angular.copy(ngModel.$viewValue);
					} else {
						scope.tags = [];
					}
					if (inputIndex === -1) {
						scope.tags.push({'input': true});
					} else {
						scope.tags.splice(inputIndex, 0, {'input': true});
					}
					$timeout(update);
				};

				scope.availTags = loadAvailTags();

				function getInput() {
					return elm.find('input[type=text]');
				}

				function update () {
					var value = [];
					angular.forEach(scope.tags, function (tag, i) {
						if (tag.input) {
							inputIndex = i;
						} else {
							value.push(tag);
						}
					});

					ngModel.$setViewValue(value.length === 0 ? undefined : value);

					if (inputIndex === 0) {
						getInput().addClass('first');
					} else {
						getInput().removeClass('first');
					}
				}

				function backspace () {
					if (scope.tags.length > 1) {
						scope.tags.splice(inputIndex-1, 1);
						update();
					}
				}

				function add (value) {
					scope.tags.splice(inputIndex, 0, {
						'label' : value,
						'id'    : (++counter),
						'color' : (value === 'fred' ? "blue" : (value === 'ipad' ? "red" : ""))
					});
					update();
				}

				function moveinput (value, offset) {
					if (value.length === 0 && (inputIndex+offset) < (scope.tags.length)) {
						var r = ArrayUtils.move(scope.tags, inputIndex, inputIndex + offset);
						update();
						scope.focus();
						return r;
					}
					return false;
				}

				scope.removeTag = function (index) {
					scope.tags.splice(index, 1);
					update();
				};

				scope.focus = function () {
					$timeout(function () {
						getInput().focus();
					});
				};

				scope.keydown = function ($event, index) {
					var	input = getInput(),
						value = input.val().trim();
					inputIndex = index;

					switch ($event.keyCode) {

					// Backspace
					case 8 :
						if (value.length === 0 && !$event.shiftKey && !$event.metaKey) {
							backspace();
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Tab
					case 9 :
						if (value.length > 0) {
							add(value);
							input.val('');
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Coma
					case 188 :
						if (value.length > 0) {
							add(value);
							input.val('');
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Left arrow
					case 37 :
						if (moveinput(value, -1)) {
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Right arrow
					case 39 :
						if (moveinput(value, 1)) {
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;
					}

				};


				scope.add = function (tag) {
					autocompleteEl.hide();
					getInput().val('');
					scope.tags.splice(inputIndex, 0, tag);
					update();
					focus();
				};

				scope.autocomplete = function ($event) {
					var	input = getInput(),
						value = input.val().trim();
					console.log("value=", value);

					var suggestions = [];
					if (value.length) {
						angular.forEach(scope.availTags, function (tag) {
							if (angular.lowercase(tag.label).indexOf(angular.lowercase(value)) !== -1) {
								suggestions.push(tag);
							}
						});
					}
					scope.suggestions = suggestions;

					function buildSuggestionsList () {
						return '<a href ng-repeat="tag in suggestions" ng-click="add(tag)"><span class="tag (= tag.color =)">(= tag.label =)</span></a>';
					}

					if (suggestions.length) {
						$compile(buildSuggestionsList())(scope, function (clone) {
							autocompleteEl.empty();
							autocompleteEl.append(clone);
							autocompleteEl.css({
								'left' : input.offset().left + 'px',
								'top'  : (input.outerHeight() + input.offset().top)  + 'px'
							});
							autocompleteEl.show();
						});

					} else {
						autocompleteEl.hide();
					}

				};

			}
		};
	}]);

})(window.jQuery);