(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		highlightMargin = 2, highlightBorder = 5,
		RICH_TEXT_BLOCK_NAMES = ['Rbs_Website_Richtext', 'Rbs_Mail_Richtext'],
		DEFAULT_GRID_SIZE = 12;

	//-------------------------------------------------------------------------
	//
	// Structure editor service.
	//
	// This service provides basic operations that are not attached to a
	// particular editor instance (there can be multiple editors is the same
	// page).
	//
	//-------------------------------------------------------------------------

	app.service('structureEditorService', ['$compile', '$timeout', function ($compile, $timeout) {
		var	self = this,
			dropTarget = null,
			highlighter,
			lastHighlight = null,
			unhighlightTimer = null;

		/**
		 * Initialize an editable zone for edition.
		 *
		 * @param scope
		 * @param zoneEl The element in which the editable content should be created.
		 * @param zoneObj The zone object definition.
		 * @param readonly
		 */
		this.initEditableZone = function (scope, zoneEl, zoneObj, readonly) {
			zoneEl.html('');

			zoneEl.addClass('editable-zone');

			zoneEl.attr('data-id', zoneObj.id);
			zoneEl.attr('data-grid', zoneObj.grid);

			angular.forEach(zoneObj.items, function (item) {
				self.initItem(scope, zoneEl, item, -1, readonly);
			});
		};

		this.getContentInfo = function (content) {
			if (angular.isString(content)) {
				content = JSON.parse(content);
			}
			// TODO Provide more information here
			var info = [];
			angular.forEach(content, function (zone) {
				info.push(zone.id);
			});
			return info;
		};

		this.getEditableZone = function (el) {
			return el.closest('.editable-zone');
		};

		this.initChildItems = function (scope, elm, item, readonly) {
			if (item.items) {
				angular.forEach(item.items, function (child) {
					self.initItem(scope, elm, child, -1, readonly);
				});
			} else {
				elm.addClass('empty');
				elm.html('vide');
			}
		};

		this.initItem = function (scope, container, item, atIndex, readonly) {
			var html = null, newEl;

			switch (item.type) {
				case 'block' :
					html = this.initBlock(item, readonly);
					break;
				case 'row' :
					html = this.initRow(item);
					break;
				case 'cell' :
					html = this.initCell(item);
					break;
				case 'block-chooser' :
					html = this.initBlockChooser(item);
					break;
				default :
					throw new Error("Unsupported item type '" + item.type + "'. Must be one of these: 'block', 'row', 'cell', 'block-chooser'.");
			}

			if (container.is('.empty')) {
				container.html('');
				container.removeClass('empty');
			}

			newEl = $(html);

			if (angular.isUndefined(atIndex) || atIndex === -1 || atIndex > container.children().length-1) {
				container.append(newEl);
			} else {
				$(container.children()[atIndex]).before(newEl);
			}
			$compile(newEl)(scope);

			return newEl;
		};

		this.initBlock = function (item, readonly) {
			if (!item.label) {
				item.label = item.name;
			}
			var attrName = RICH_TEXT_BLOCK_NAMES.indexOf(item.name) > -1 ? 'rbs-block-markdown-text' : 'rbs-block-template';
			return '<div ' + attrName + '="" ' + (readonly ? 'readonly="true" ' : '') + 'data-id="' + item.id + '" data-name="' + item.name + '" data-label="' + item.label + '" data-visibility="' + (item.visibility || '') + '">' + item.name + '</div>';
		};

		this.initRow = function (item) {
			return '<div rbs-row="" data-id="' + item.id + '" data-grid="' + item.grid + '" data-visibility="' + (item.visibility || '') + '"></div>';
		};

		this.initCell = function (item) {
			return '<div rbs-cell="" data-id="' + item.id + '" data-size="' + item.size + '"></div>';
		};

		this.initBlockChooser = function (item) {
			return '<div rbs-block-chooser="" data-id="' + item.id + '"></div>';
		};

		this.getColumnsInfo = function (row, gridSize) {
			var cols = [];
			gridSize = gridSize || DEFAULT_GRID_SIZE;
			row.children().each(function (index, el) {
				var i, span = 0, offset = 0;
				for (i=0 ; i<=gridSize ; i++) {
					if ($(el).is('.col-md-offset-'+i)) {
						offset = i;
					}
					if ($(el).is('.col-md-'+i)) {
						span = i;
					}
				}
				if (!span) {
					throw new Error("Bad column layout: column '" + index + "' should have a 'col-md-[1-" + gridSize + "]' class.");
				}
				cols.push({
					'id'         : $(el).data('id'),
					'span'       : span,
					'offset'     : offset,
					'childCount' : $(el).children().length
				});
			});
			return cols;
		};

		this.getColumnWidth = function (seCell) {
			var i, gridSize;

			gridSize = this.getEditableZone(seCell).data('grid');
			if (!gridSize) {
				throw new Error("Could not determine grid size in use for column " + seCell);
			}

			for (i=1 ; i<=gridSize ; i++) {
				if (seCell.is('.col-md-'+i)) {
					return i;
				}
			}

			throw new Error("Could not determine column size (missing 'col-md-*' class?) in grid of size " + gridSize);
		};

		this.applyColumnsWidth = function (row, columns, gridSize) {
			if (row.children().length !== columns.length) {
				throw new Error("Bad columns count: given " + columns.length + " columns but " + row.children().length + " columns exist in the row.");
			}

			gridSize = gridSize || DEFAULT_GRID_SIZE;

			// Apply 'span' and 'offset' on existing columns.
			row.children('[rbs-cell]').each(function (index, el) {

				// Find current 'span*' and 'offset*' classes and remove them.
				// - 'span*' is from 1 to `gridSize`
				// - 'offset*' is from 0 to `gridSize-1`
				// So we loop from 0 to `gridSize`, both included.
				var i, span = 0, offset = 0;
				for (i=0 ; i <= gridSize ; i++) {
					if ($(el).is('.col-md-offset-'+i)) {
						offset = i;
					}
					if ($(el).is('.col-md-'+i)) {
						span = i;
					}
				}
				$(el).removeClass('col-md-' + span);
				$(el).removeClass('col-md-offset-' + offset);

				// Add new 'span*' and 'offset*' classes.
				$(el).addClass('col-md-' + columns[index].span);
				$(el).attr('data-size', columns[index].span);
				if (columns[index].offset) {
					$(el).addClass('col-md-offset-' + columns[index].offset);
					$(el).attr('data-offset', columns[index].offset);
				} else {
					$(el).removeAttr('data-offset');
				}
			});
		};

		$('body').append(
			'<div id="structure-editor-highlighter-top"></div>' +
				'<div id="structure-editor-highlighter-right"></div>' +
				'<div id="structure-editor-highlighter-bottom"></div>' +
				'<div id="structure-editor-highlighter-left"></div>' +
				'<div id="structure-editor-highlighter-text"></div>'
		);

		highlighter = {
			'top'    : $('#structure-editor-highlighter-top'),
			'right'  : $('#structure-editor-highlighter-right'),
			'bottom' : $('#structure-editor-highlighter-bottom'),
			'left'   : $('#structure-editor-highlighter-left'),
			'text'   : $('#structure-editor-highlighter-text')
		};

		this.highlightDropTarget = function highlightDropTarget (el) {
			if (el.is('[rbs-cell]')) {
				var	columnIndex = el.index();
				this.highlightBlock(
					el,
					"Col. " + (columnIndex + 1) + ' (<i class="icon-resize-horizontal"></i> ' + this.getColumnWidth(el) + ")",
					true // use parent's height (row)
				);
				dropTarget = el;
			} else if (el.is('.editable-zone')) {
				this.highlightBlock(el, el.data('editable-zone-id'));
				dropTarget = el;
			}
		};

		this.unhighlightDropTarget = function unhighlightDropTarget (el) {
			if (dropTarget === el) {
				this.highlightBlock(null);
			}
		};

		/**
		 * Highlights an element in the editor.
		 *
		 * @param el
		 * @param text Optional text.
		 * @param useParentHeight If true, use parent's height instead of the default one (el.innerHeight()).
		 */
		this.highlightBlock = function highlightBlock (el, text, useParentHeight) {
			if (unhighlightTimer) {
				$timeout.cancel(unhighlightTimer);
			}

			highlighter.top.removeClass('done');
			highlighter.right.removeClass('done');
			highlighter.bottom.removeClass('done');
			highlighter.left.removeClass('done');
			highlighter.text.removeClass('done');

			highlighter.top.hide();
			highlighter.right.hide();
			highlighter.bottom.hide();
			highlighter.left.hide();
			highlighter.text.hide();

			if (lastHighlight !== null) {
				lastHighlight.el.removeClass('highlighted');
			}

			if (el) {
				el = $(el);
				el.addClass('highlighted');

				var	x, y, w, h, offset;

				offset = el.offset();
				x = offset.left;
				y = useParentHeight ? el.parent().offset().top + highlightMargin : offset.top;
				w = el.innerWidth();
				h = useParentHeight ? el.parent().outerHeight() - (highlightMargin * 2) : el.outerHeight();

				this.highlightZone(x, y, w, h, text);

				lastHighlight = {
					'el' : el,
					'text' : text,
					'useParentHeight' : useParentHeight
				};
			} else {
				lastHighlight = null;
			}
		};

		this.updateHighlight = function updateHighlight () {
			if (lastHighlight === null) {
				this.highlightBlock(null);
			} else {
				this.highlightBlock(lastHighlight.el, lastHighlight.text, lastHighlight.useParentHeight);
			}
		};

		this.terminateHighlight = function terminateHighlight () {
			var self = this;

			this.updateHighlight();
			highlighter.top.addClass('done');
			highlighter.right.addClass('done');
			highlighter.bottom.addClass('done');
			highlighter.left.addClass('done');
			highlighter.text.addClass('done');

			unhighlightTimer = $timeout(function () {
				self.highlightBlock(null);
				unhighlightTimer = null;
			}, 1000);

			return unhighlightTimer;
		};

		this.highlightZone = function highlightZone (x, y, w, h, text) {
			var x1, y1, x2, y2, ww, hh;

			x1 = x - highlightMargin - highlightBorder;
			y1 = y - highlightMargin - highlightBorder + 1;
			x2 = x + w + highlightMargin;
			y2 = y + h + highlightMargin - 1;
			ww = w + (highlightMargin + highlightBorder) * 2;
			hh = h + (highlightMargin + highlightBorder - 1) * 2;

			highlighter.top.css({
				'top'    : y1 + 'px',
				'left'   : x1 + 'px',
				'width'  : ww + 'px'
			}).show();

			highlighter.bottom.css({
				'top'    : y2 + 'px',
				'left'   : x1 + 'px',
				'width'  : ww + 'px'
			}).show();

			highlighter.right.css({
				'top'    : y1 + 'px',
				'left'   : x2 + 'px',
				'height' : hh + 'px'
			}).show();

			highlighter.left.css({
				'top'    : y1 + 'px',
				'left'   : x1 + 'px',
				'height' : hh + 'px'
			}).show();

			if (text) {
				highlighter.text.css({
					'top'  : (y1 - highlighter.text.outerHeight() + 4) + 'px',
					'left' : x + 'px'
				}).html(text).show();
			} else {
				highlighter.text.hide();
			}
		};
	}]);
})(window.jQuery);