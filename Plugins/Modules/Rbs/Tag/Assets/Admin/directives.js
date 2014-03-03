(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		TAG_COLORS = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray'];


	/**
	 * <rbs-tag-color-selector/>
	 */
	app.directive('rbsTagColorSelector', function () {

		return {
			restrict : 'E',
			require  : 'ngModel',
			scope    : true,
			template :
				'<a ng-repeat="color in colors" class="tag tag-color-setter (=color=)" href ng-click="setColor($event, color)">' +
				'<i ng-class="{true:\'icon-circle\', false:\'icon-circle-blank\'}[selectedColor == color]" class="(= iconSize =)"></i>' +
				'</a>',

			link : function (scope, elm, attrs, ngModel) {
				scope.colors = TAG_COLORS;

				ngModel.$render = function () {
					scope.selectedColor = ngModel.$viewValue;
				};

				scope.iconSize = attrs.iconSize || 'icon-large';

				scope.setColor = function ($event, color) {
					ngModel.$setViewValue(color);
					ngModel.$render();
				};
			}
		};
	});


	/**
	 * <rbs-tag/>
	 */
	app.directive('rbsTag', ['RbsChange.i18n', function (i18n) {
		return {
			restrict : 'A',
			replace  : true,
			template :
				'<span draggable="true" class="tag (= rbsTag.color =)">' +
					'<i class="icon-user" ng-if="rbsTag.userTag" title="' + i18n.trans('m.rbs.tag.adminjs.usertag_text | ucf') + '"></i> ' +
					'<i class="icon-exclamation-sign" ng-if="rbsTag.unsaved" title="' + i18n.trans('m.rbs.tag.adminjs.tag_not_saved | ucf') + '"></i> ' +
					'(= rbsTag.label =)' +
					'<a ng-if="canBeRemoved" href tabindex="-1" class="tag-delete" ng-click="remove()"><i class="icon-remove"></i></a>' +
				'</span>',

			scope : {
				'rbsTag'   : '=',
				'onRemove' : '&'
			},

			link : function (scope, elm) {
				elm.addClass('tag');
				scope.canBeRemoved = elm.is('[on-remove]');
				scope.remove = function () {
					scope.onRemove();
				};
				scope.$watch('rbsTag', function (value) {
					if (value) {
						elm.addClass(value.color);
						if (value.unsaved) {
							elm.addClass('new');
						} else {
							elm.removeClass('new');
						}
					}
				}, true);

				function buildDnDRepresentation (tag) {
					return JSON.stringify({
						"id" : tag.id,
						"label" : tag.label,
						"color" : tag.color,
						"userTag" : tag.userTag
					});
				}

				elm.on({
					'dragstart.rbs.tag': function (e) {
						e.dataTransfer.setData('Rbs/Tag', buildDnDRepresentation(scope.rbsTag));
						e.dataTransfer.effectAllowed = "copy";
					}
				});

			}
		};
	}]);

	/**
	 * <input rbs-auto-size-input="" ... />
	 *
	 * Used in <rbs-tag-selector/>
	 */
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


	/**
	 * <rbs-tag-selector document="document"></rbs-tag-selector>
	 */
	app.directive(
		'rbsAsideTagSelector',
		[
			'$timeout', '$q', '$compile', 'RbsChange.ArrayUtils', 'RbsChange.TagService', 'RbsChange.i18n',
			rbsTagSelectorFn
		]
	);

	function rbsTagSelectorFn ($timeout, $q, $compile, ArrayUtils, TagService, i18n) {

		var	autocompleteEl;

		function loadAvailTags () {
			return TagService.getList();
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
			scope    : {'document': '='},
			templateUrl : 'Rbs/Tag/rbs-aside-tag-selector.twig',


			link : function (scope, elm, attrs, ngModel) {

				var inputIndex = -1,
					tempTagCounter = 0;

				scope.tags = [{'input': true}];
				scope.unsavedTags = [];

				scope.$watch('document', function (doc)
				{
					if (doc && doc.id > 0 && doc.model)
					{
						doc.loadTags().then(function(tags) {
							scope.tags = tags;
							if (inputIndex === -1) {
								scope.tags.push({'input': true});
							} else {
								scope.tags.splice(inputIndex, 0, {'input': true});
							}
						});
					}
				});

				scope.availTags = loadAvailTags();
				scope.showAll = false;

				scope.toggleShowAll = function ($event) {
					scope.showAll = ! scope.showAll;
					if (scope.showAll && $event.shiftKey) {
						scope.availTags = loadAvailTags();
					}
				};


				function getInput() {
					return elm.find('input[type=text]');
				}


				function update (doNotSave)
				{
					var tags = [],
						unsavedPromises = [];

					if (! doNotSave) {
						scope.busy = true;
					}

					angular.forEach(scope.tags, function (tag, i) {
						if (tag.input) {
							inputIndex = i;
						} else if (tag.unsaved) {
							unsavedPromises.push(TagService.create(tag));
						} else {
							tags.push(tag);
						}
					});

					function save () {
						TagService.setDocumentTags(scope.document, tags).then(
							function () {
								scope.busy = false;
							},
							function () {
								scope.busy = false;
							}
						);
					}

					if (unsavedPromises.length) {
						$q.all(unsavedPromises).then(function () {
							tags = [];
							angular.forEach(scope.tags, function (tag) {
								if (! tag.input && ! tag.unsaved) {
									tags.push(tag);
								}
							});
							save();
						});
					}
					else if (! doNotSave) {
						save();
					}
				}


				function backspace () {
					if (scope.tags.length > 1) {
						scope.tags[inputIndex-1].used = false;
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
						'id'      : --tempTagCounter,
						'label'   : value,
						'unsaved' : true,
						'userTag' : true
					};
				}


				function appendTag (tag) {
					if (!scope.isUsed(tag)) {
						scope.tags.splice(inputIndex, 0, tag);
						tag.used = true;
						update();
					}
				}
				scope.appendTag = appendTag;


				function moveInput (value, offset) {
					if (value.length === 0 && (inputIndex+offset) < (scope.tags.length)) {
						var r = ArrayUtils.move(scope.tags, inputIndex, inputIndex + offset);
						update(true);
						scope.focus();
						return r;
					}
					return false;
				}

				scope.isUsed = function (tag) {
					var i;
					for (i=0 ; i<scope.tags.length ; i++) {
						if (! scope.tags[i].input && scope.tags[i].id === tag.id) {
							return true;
						}
					}
					return false;
				};

				scope.removeTag = function (index) {
					scope.tags[index].used = false;
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

						// Down arrow
						case 40 :
							if ($event.ctrlKey) {
								$event.preventDefault();
								$event.stopPropagation();
								scope.showAll = true;
							}
							break;

						// Up arrow
						case 38 :
							if ($event.ctrlKey) {
								$event.preventDefault();
								$event.stopPropagation();
								scope.showAll = false;
							}
							break;

						// Ctrl+R
						case 82 :
							if ($event.ctrlKey) {
								scope.availTags = loadAvailTags();
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
						return '<a href ng-repeat="tag in suggestions" ng-click="autocompleteAdd(tag)"><span rbs-tag="tag"></span></a><br/><small><em>' + i18n.trans('m.rbs.tag.adminjs.enter_selects_first_tag | ucf') + '</em></small>';
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

	app.directive('rbsDocumentFilterTags', ['RbsChange.TagService', function(TagService) {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Tag/document-filter-tags.twig',
			scope: {
				filter : '='
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

				scope.tags = null;
				scope.availTags = null;
				if (!scope.filter.parameters.hasOwnProperty('tagIds')) {
					scope.filter.parameters.tagIds = [];
				}

				scope.showAll = scope.filter.parameters.tagIds.length == 0;

				scope.isConfigured = function() {
					return (scope.filter.parameters.tagIds && scope.filter.parameters.tagIds.length > 0);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});

				scope.$watchCollection('tags', function(value) {
					if (angular.isArray(value)){
						scope.filter.parameters.tagIds = [];
						angular.forEach(value, function(tagDoc) {
							if (tagDoc && tagDoc.id) {
								scope.filter.parameters.tagIds.push(tagDoc.id);
							}
						})
					}
				});

				if (!scope.filterDefinition)
				{
					scope.availTags = [];
					scope.showAll = false;
					return;
				}

				function initializeAvailTags(availTags) {
					var tags = [], i;
					scope.availTags = availTags;
					angular.forEach(scope.filter.parameters.tagIds, function(id) {
						for (i = 0; i < availTags.length; i++) {
							if (availTags[i].id == id) {
								tags.push(availTags[i]);
								break;
							}
						}
					});
					scope.tags = tags;
				}

				if (scope.filterDefinition.hasOwnProperty('availTags')) {
					initializeAvailTags(scope.filterDefinition.availTags);
				} else {
					scope.filterDefinition.availTags = TagService.getList({resolve: initializeAvailTags});
				}

				scope.toggleShowAll = function ($event) {
					scope.showAll = ! scope.showAll;
					if (scope.showAll && $event.shiftKey) {
						scope.filterDefinition.availTags = scope.availTags = TagService.getList();
					}
				};

				scope.isUsed = function (tag) {
					var i;
					for (i=0 ; i < scope.tags.length ; i++) {
						if (!scope.tags[i].id === tag.id) {
							return true;
						}
					}
					return false;
				};

				scope.appendTag = function(tag) {
					if (!scope.isUsed(tag)) {
						scope.tags.push(tag);
						tag.used = true;
					}
				};

				scope.removeTag = function (index) {
					scope.tags[index].used = false;
					scope.tags.splice(index, 1);
				};
			}
		};
	}]);

})(window.jQuery);