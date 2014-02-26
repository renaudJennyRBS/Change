(function ($) {
	"use strict";

	var	app = angular.module('RbsChange');


	app.run(['$templateCache', function ($templateCache)
	{
		if (! $templateCache.get('picker-item-default.html')) {
			$templateCache.put('picker-item-default.html', '(= item.label =)');
		}
	}]);


	app.directive('rbsTokenList', ['RbsChange.ArrayUtils', '$filter', '$templateCache', function (ArrayUtils, $filter, $templateCache)
	{
		return {
			restrict : 'E',
			replace  : true,
			templateUrl : 'Rbs/Admin/js/directives/token-list.twig',

			// Create isolated scope.
			scope : {
				items : '=',
				openItem : '='
			},

			compile : function (tElement, tAttrs)
			{
				if (tAttrs.rows === 'true') {
					tElement.addClass('rows');
				}

				return function linkFn (scope, elm, attrs)
				{
					var $el = $(elm),
						lastSelectedItemIndex = -1,
						dragging, isHandle, startIndex, stopIndex,
						placeholder = $('<li class="sortable-placeholder"></li>');

					scope.readonly = attrs.readonly ? true : false;
					scope.disableReordering = attrs.disableReordering ? true : false;


					scope.getItemTemplateName = function (item) {
						if (attrs.itemTemplate) {
							return attrs.itemTemplate
						}
						if (!item || !item.model) {
							return 'picker-item-default.html';
						}

						var tplName = 'picker-item-' + item.model + '.html';
						return $templateCache.get(tplName) ? tplName : 'picker-item-default.html';
					};


					scope.remove = function (index) {
						ArrayUtils.remove(scope.items, index);
					};


					scope.itemClicked = function (index, event) {
						if (scope.readonly) {
							return;
						}
						if ( ! event.metaKey ) {
							if (event.shiftKey && lastSelectedItemIndex !== -1) {
								var from = Math.min(lastSelectedItemIndex, index);
								var to = Math.max(lastSelectedItemIndex, index);
								for (var i=from ; i<=to ; i++) {
									scope.items[i].$selected = true;
								}
							} else {
								scope.clearSelected();
								scope.items[index].$selected = ! scope.items[index].$selected;
							}
						} else {
							scope.items[index].$selected = ! scope.items[index].$selected;
						}
						$el.find('li:eq(' + index + ') a.delete').focus();
						if (scope.items[index].$selected) {
							lastSelectedItemIndex = index;
						} else {
							lastSelectedItemIndex = -1;
						}
					};


					scope.clearSelected = function () {
						angular.forEach(scope.items, function (item) {
							item.$selected = false;
						});
					};


					scope.deleteSelected = function () {
						var i;
						if (scope.readonly) {
							return;
						}
						for (i = scope.items.length-1 ; i >= 0 ; i--) {
							if (scope.items[i].$selected) {
								scope.remove(i);
							}
						}
					};


					// Enable drag and drop to reorder the items.
					if ( ! scope.disableReordering ) {
						$(elm).on({
							'mousedown': function() {
								isHandle = true;
							},
							'mouseup': function() {
								isHandle = false;
							}
						}, 'i.icon-reorder');

						$(elm).on({
							'dragstart': function (e) {
								if (!isHandle) {
									return false;
								}
								isHandle = false;
								var dt = e.originalEvent.dataTransfer;
								dt.effectAllowed = 'move';
								dragging = $(this);
								dragging.addClass('sortable-dragging');
								startIndex = dragging.index();
								dt.setData('Text', dragging.text());
							},

							'dragend': function () {
								dragging.removeClass('sortable-dragging').show();
								stopIndex = placeholder.index();
								placeholder.detach();
								if (stopIndex > startIndex) {
									stopIndex--;
								}
								if (startIndex !== stopIndex) {
									scope.$apply(function () {
										ArrayUtils.move(scope.items, startIndex, stopIndex);
									});
								}
								dragging = null;
							}
						}, 'li[data-id]');

						$(elm).on({
							'dragenter': function (e) {
								e.preventDefault();
								e.originalEvent.dataTransfer.dropEffect = 'move';
							},

							'dragover': function (e) {
								e.preventDefault();
								e.originalEvent.dataTransfer.dropEffect = 'move';

								if (dragging) {
									if ( ! $(this).is(placeholder) ) {
										dragging.hide();
										placeholder.height(dragging.height());
										$(this)[placeholder.index() < $(this).index() ? 'after' : 'before'](placeholder);
										if (placeholder.index() > startIndex) {
											placeholder.html(placeholder.index());
										} else {
											placeholder.html(placeholder.index()+1);
										}
									}
								}
							},

							'drop': function (e) {
								e.stopPropagation();
								e.preventDefault();
								placeholder.after(dragging);
							}
						}, 'li[data-id], li.sortable-placeholder');
					}

					if ( ! scope.readonly ) {
						$el.on('keydown', 'li a.delete', function (event) {
							if (event.keyCode === 46 || event.keyCode === 8) { // delete or back key
								scope.$apply(function () {
									scope.deleteSelected();
								});
							} else if (! attrs.disableReordering && (event.keyCode === 38 || event.keyCode === 40)) {
								var selected = -1, nb = 0, i;
								for (i = 0 ; i < scope.items.length ; i++) {
									if (scope.items[i].$selected) {
										selected = i;
										nb++;
									}
								}
								// Move items only if one is selected
								if (nb === 1 && selected !== -1) {
									if (event.keyCode === 38) { // top
										if (selected > 0) {
											ArrayUtils.move(scope.items, selected, selected-1);
											selected--;
										}
									} else if (event.keyCode === 40) { // bottom
										if (selected < (scope.items.length-1)) {
											ArrayUtils.move(scope.items, selected, selected+1);
											selected++;
										}
									}
									scope.$apply();
									$el.find('li:eq(' + selected + ') a.delete').focus();
								}
							}
						});
					}
				}; // End of link function
			} // End of compile function
		}; // End of Directive object
	}]);
})(window.jQuery);