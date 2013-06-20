(function ($) {

	"use strict";

	var	app = angular.module('RbsChange');


	/**
	 * @name rbsAutoSizeInput
	 * <input class="rbs-auto-size-input" ... />
	 */
	app.directive('rbsAutoSizeInput', function () {

		var options = {
			maxWidth: 1000,
			comfortZone: 18
		};

		return {
			restrict : 'AC',

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


	/**
	 * @name rbsTagSelector
	 * <rbs-tag-selector ng-model="document.tags"></rbs-tag-selector>
	 */
	app.directive(
		'rbsTagSelector',
		[
			'$timeout', '$compile', 'RbsChange.ArrayUtils', 'RbsChange.REST', 'RbsChange.i18n',
			rbsTagSelectorFn
		]
	);

	function rbsTagSelectorFn ($timeout, $compile, ArrayUtils, REST, i18n) {

		var	autocompleteEl;

		function loadAvailTags () {
			return REST.tags.getList();
		}

		// Popover element that shows suggestions.
		$('<div id="rbsTagSelectorAutocompleteList"></div>').css({
			'position' : 'absolute',
			'display'  : 'none'
		}).appendTo('body');
		autocompleteEl = $('#rbsTagSelectorAutocompleteList');

		return {
			restrict : 'E',
			replace  : true,
			require  : 'ngModel',
			scope    : true,
			template :
				'<div class="tag-selector" ng-mousedown="focus($event)" ng-swipe-left="moveLeft()" ng-swipe-right="moveRight()">' +
					'<button class="btn btn-mini btn-inverse pull-right" title="' + i18n.trans('m.rbs.tag.admin.js.show-hide-all-tags | ucf') + '" type="button" ng-click="showAll=!showAll">' +
						'<i ng-class="{true:\'icon-chevron-up\',false:\'icon-chevron-down\'}[showAll]"></i>' +
					'</button>' +
					'<span ng-repeat="tag in tags">' +
						'<span ng-if="! tag.input" rbs-tag="tag" on-remove="removeTag($index)"></span>' +
						'<input autocapitalize="off" autocomplete="off" autocorrect="off" type="text" rbs-auto-size-input="" ng-if="tag.input" ng-keyup="autocomplete()" ng-keydown="keydown($event, $index)"></span>' +
					'</span>' +
					'<div class="all-tags clearfix" ng-show="showAll">' +
						'<h6 ng-pluralize count="availTags.length" when="' + i18n.trans('m.rbs.tag.admin.js.available-tags-pluralize') + '"></h6>' +
						'<a href ng-repeat="tag in availTags | orderBy:\'label\'" ng-click="appendTag(tag)"><span ng-class="{true:\'opacity-half\'}[isUsed(tag)]" class="tag (= tag.color =)">(= tag.label =)</span></a>' +
					'</div>' +
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
				scope.showAll = false;

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

				function findTag (label) {
					var i;
					for (i=0 ; i<scope.availTags.length ; i++) {
						if (angular.lowercase(scope.availTags[i].label) === angular.lowercase(label)) {
							return scope.availTags[i];
						}
					}
					return null;
				}

				function add (value) {
					var tag = findTag(value);
					if (!tag) {
						tag = createTemporaryTag(value);
					}
					appendTag(tag);
				}

				function createTemporaryTag (value) {
					return {
						'label'   : value,
						'unsaved' : true
					};
				}

				function appendTag (tag) {
					if (!scope.isUsed(tag)) {
						scope.tags.splice(inputIndex, 0, tag);
						update();
					}
				}
				scope.appendTag = appendTag;

				function moveInput (value, offset) {
					if (value.length === 0 && (inputIndex+offset) < (scope.tags.length)) {
						var r = ArrayUtils.move(scope.tags, inputIndex, inputIndex + offset);
						update();
						scope.focus();
						return r;
					}
					return false;
				}

				scope.isUsed = function (tag) {
					var i;
					for (i=0 ; i<scope.tags.length ; i++) {
						if (angular.lowercase(scope.tags[i].label) === angular.lowercase(tag.label)) {
							return true;
						}
					}
					return false;
				};

				scope.removeTag = function (index) {
					scope.tags.splice(index, 1);
					update();
				};

				scope.focus = function () {
					$timeout(function () {
						getInput().focus();
					});
				};

				scope.moveLeft = function () {
					moveInput(getInput().val().trim(), -1);
				};
				scope.moveRight = function () {
					moveInput(getInput().val().trim(), +1);
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
					// Coma
					case 188 :
						if (value.length > 0) {
							add(value);
							input.val('');
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Enter
					case 13 :
						if (scope.suggestions.length) {
							$event.preventDefault();
							$event.stopPropagation();
							scope.autocompleteAdd(scope.suggestions[0]);
						} else if (value.length > 0) {
							add(value);
							input.val('');
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Left arrow
					case 37 :
						if (moveInput(value, -1)) {
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;

					// Right arrow
					case 39 :
						if (moveInput(value, 1)) {
							$event.preventDefault();
							$event.stopPropagation();
						}
						break;
					}

				};


				scope.autocompleteAdd = function (tag) {
					autocompleteEl.hide();
					getInput().val('');
					appendTag(tag);
					focus();
				};

				scope.autocomplete = function () {
					var	input = getInput(),
						value = input.val().trim(),
						suggestions = [];

					if (value.length) {
						value = angular.lowercase(value);
						angular.forEach(scope.availTags, function (tag) {
							var label = angular.lowercase(tag.label), p;
							if (ArrayUtils.inArray(tag, scope.tags) === -1) {
								p = label.indexOf(value);
								if (p === 0) {
									// Tag begins with the entered value?
									// Add it at the beginning of the autocomplete list.
									suggestions.unshift(tag);
								} else if (p > 0) {
									// Tag contains the entered value?
									// Add it at the end of the autocomplete list.
									suggestions.push(tag);
								}
							}
						});
					}
					scope.suggestions = suggestions;

					function buildSuggestionsList () {
						return '<a href ng-repeat="tag in suggestions" ng-click="autocompleteAdd(tag)"><span class="tag (= tag.color =)">(= tag.label =)</span></a><br/><small><em>' + i18n.trans('m.rbs.tag.admin.js.enter-selects-first-tag | ucf') + '</em></small>';
					}

					if (suggestions.length) {
						$compile(buildSuggestionsList())(scope, function (clone) {
							autocompleteEl.empty();
							autocompleteEl.append(clone);
							autocompleteEl.css({
								'left' : input.offset().left + 'px',
								'top'  : (input.outerHeight() + input.offset().top + 3)  + 'px'
							});
							autocompleteEl.show();
						});
					} else {
						autocompleteEl.hide();
					}

				};

			}
		};
	}

})(window.jQuery);