(function ($) {

	"use strict";

	var	//blockPropertiesLink,
		//blockPropertiesLinkBorder,
		dropZoneIndicator,
		blockPropertiesPopup,
		blockPropertiesPopupShown = false,
		lastSelectedBlock = null,

		app = angular.module('RbsChange'),

		forEach = angular.forEach,

		DEFAULT_GRID_SIZE = 12,
		RICH_TEXT_BLOCK_NAME = 'Rbs_Website_Richtext',
		ADVANCED_TEXT_BLOCK_NAME = 'Rbs_Website_FormattedText',

		highlightMargin = 2,
		highlightBorder = 5;

	$('body').append(
		'<div id="structure-editor-block-properties-popup" class="dockable" style="display:none;"></div>' +
		'<div id="structure-editor-dropzone-indicator"><span class="content"></span><i class="icon-arrow-right"></i></div>'
	);
	dropZoneIndicator = $('#structure-editor-dropzone-indicator');

	blockPropertiesPopup = $('#structure-editor-block-properties-popup');

	/**
	 * Set the position of the editor for the settings of the selected block.
	 *
	 * @param blockEl
	 */
	function positionBlockSettingsEditor (blockEl) {

		if (blockEl === null && lastSelectedBlock !== null) {
			blockEl = lastSelectedBlock;
		}

		if (blockEl === null) {
			return;
		}

		blockPropertiesPopup.show();
		blockPropertiesPopupShown = true;

		lastSelectedBlock = blockEl;
	}


	function closeBlockSettingsEditor () {
		blockPropertiesPopup.hide();
		blockPropertiesPopupShown = false;
	}



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
		 */
		this.initEditableZone = function initEditableZone (scope, zoneEl, zoneObj, readonly) {
			zoneEl.html('');

			zoneEl.addClass(zoneObj.gridMode === 'fixed' ? 'container' : 'container-fluid');
			zoneEl.addClass('editable-zone');

			zoneEl.attr('data-id', zoneObj.id);
			zoneEl.attr('data-grid', zoneObj.grid);
			zoneEl.attr('data-grid-mode', zoneObj.gridMode);

			forEach(zoneObj.items, function (item) {
				self.initItem(scope, zoneEl, item, -1, readonly);
			});
		};


		this.getContentInfo = function (content) {
			if (angular.isString(content)) {
				content = JSON.parse(content);
			}
			// TODO Provide more information here
			var info = [];
			forEach(content, function (zone) {
				info.push(zone.id);
			});
			return info;
		};


		this.getEditableZone = function getEditableZone (el) {
			return el.closest('.editable-zone');
		};


		this.initChildItems = function (scope, elm, item, readonly) {
			if (item.items) {
				forEach(item.items, function (child) {
					self.initItem(scope, elm, child, -1, readonly);
				});
			} else {
				elm.addClass('empty');
				elm.html('vide');
			}
		};


		this.initItem = function initItem (scope, container, item, atIndex, readonly) {
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


		this.initBlock = function initBlock (item, readonly) {
			if (!item.label) {
				item.label = item.name;
			}
			if (item.name === RICH_TEXT_BLOCK_NAME) {
				return this.initRichText(item, readonly);
			} else {
				var className = 'se-block-template';
				return '<div class="' + className + '" data-id="' + item.id + '" data-name="' + item.name + '" data-label="' + item.label + '" data-visibility="' + (item.visibility || '') + '">' + item.name + '</div>';
			}
		};

		this.initRichText = function initRichText (item, readonly) {
			return '<div class="se-markdown-text" ' + (readonly ? 'readonly="true" ' : '') + 'data-id="' + item.id + '" data-name="' + item.name + '" data-visibility="' + (item.visibility || '') + '"></div>';
		};

		this.initRow = function initRow (item) {
			return '<div class="se-row" data-id="' + item.id + '" data-grid="' + item.grid + '" data-visibility="' + (item.visibility || '') + '"></div>';
		};

		this.initCell = function initCell (item) {
			return '<div class="se-cell" data-id="' + item.id + '" data-size="' + item.size + '"></div>';
		};

		this.initBlockChooser = function initCell (item) {
			return '<div class="se-block-chooser" data-id="' + item.id + '"></div>';
		};


		this.getColumnsInfo = function getColumnsInfo (row, gridSize) {
			var cols = [];
			gridSize = gridSize || DEFAULT_GRID_SIZE;
			row.children().each(function (index, el) {
				var i, span = 0, offset = 0;
				for (i=0 ; i<=gridSize ; i++) {
					if ($(el).is('.offset'+i)) {
						offset = i;
					}
					if ($(el).is('.span'+i)) {
						span = i;
					}
				}
				if (!span) {
					throw new Error("Bad column layout: column '" + index + "' should have a 'span[1-" + gridSize + "]' class.");
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


		this.getColumnWidth = function getColumnWidth (seCell) {
			var i, gridSize;

			gridSize = this.getEditableZone(seCell).data('grid');
			if (!gridSize) {
				throw new Error("Could not determine grid size in use for column " + seCell);
			}

			for (i=1 ; i<=gridSize ; i++) {
				if (seCell.is('.span'+i)) {
					return i;
				}
			}

			throw new Error("Could not determine column size (missing 'span*' class?) in grid of size " + gridSize);
		};


		this.applyColumnsWidth = function applyColumnsWidth (row, columns, gridSize) {
			if (row.children().length !== columns.length) {
				throw new Error("Bad columns count: given " + columns.length + " columns but " + row.children().length + " columns exist in the row.");
			}

			gridSize = gridSize || DEFAULT_GRID_SIZE;

			// Apply 'span' and 'offset' on existing columns.
			row.children('.se-cell').each(function (index, el) {

				// Find current 'span*' and 'offset*' classes and remove them.
				// - 'span*' is from 1 to `gridSize`
				// - 'offset*' is from 0 to `gridSize-1`
				// So we loop from 0 to `gridSize`, both included.
				var i, span = 0, offset = 0;
				for (i=0 ; i <= gridSize ; i++) {
					if ($(el).is('.offset'+i)) {
						offset = i;
					}
					if ($(el).is('.span'+i)) {
						span = i;
					}
				}
				$(el).removeClass('span' + span);
				$(el).removeClass('offset' + offset);

				// Add new 'span*' and 'offset*' classes.
				$(el).addClass('span' + columns[index].span);
				$(el).attr('data-size', columns[index].span);
				if (columns[index].offset) {
					$(el).addClass('offset' + columns[index].offset);
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

			if (el.is('.se-cell')) {
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
			var	x1, y1, x2, y2, ww, hh;

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



	//-------------------------------------------------------------------------
	//
	// Structure editor.
	//
	//-------------------------------------------------------------------------

	app.directive('structureEditor', ['$timeout', '$compile', 'RbsChange.Workspace', 'RbsChange.MainMenu', 'structureEditorService', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.NotificationCenter', function ($timeout, $compile, Workspace, MainMenu, structureEditorService, ArrayUtils, Utils, NotificationCenter) {

		return {

			"restrict"   : 'E',
			"require"    : ['ngModel', 'structureEditor'],
			"scope"      : true,
			"transclude" : true,
			"template"   :
				'<div class="btn-toolbar">' +
					'<div class="btn-group" ng-if="! readOnly">' +
						'<button type="button" ng-disabled="!undoData.length" class="btn" ng-click="undo(0)"><i class="icon-undo"></i> Défaire</button>' +
						'<button type="button" ng-disabled="!undoData.length" class="btn dropdown-toggle" data-toggle="dropdown">' +
							'<span class="caret"></span>' +
						'</button>' +
						'<ul class="dropdown-menu" data-role="undo-menu">' +
							'<li data-ng-repeat="entry in undoData"><a href="javascript:;" ng-click="undo($index)"><span class="muted">{{entry.date | date:\'mediumTime\'}}</span> <i class="{{entry.icon}}"></i> {{entry.label}} {{entry.item.label}}</a></li>' +
						'</ul>' +
					'</div>' +
					'<button ng-if="readOnly" type="button" disabled="disabled" class="btn">Lecture seule</button>' +
					'<div class="btn-group" ng-transclude></div>' +
					'<button type="button" class="btn pull-right">{{editorWidth}} &times; {{editorHeight}}</button>' +
				'</div>' +
				'<div class="rich-text-input-selectors-container"></div>' +
				'<div id="se-picker-container"></div>' +
				//'<pre>(= items | json =)</pre>' +
				'<div class="structure-editor"></div>',


			/**
			 * structureEditor.controller
			 *
			 * This Controller manages all the operations on the blocks inside the editor:
			 * creation, selection, settings, ...
			 *
			 * @param $scope
			 * @param $element
			 * @param $attrs
			 */
			"controller" : function seControllerFn ($scope, $element, $attrs) {
				var	selectedBlock = null,
					self = this,
					draggedEl, containerOfDraggedEl,
					dropTarget, dropPosition = -1,
					lastIndicatorY = 0;


				this.isReadOnly = function () {
					return $attrs.readonly === 'true';
				};
				$scope.readOnly = this.isReadOnly();

				/**
				 * Selects a block.
				 *
				 * @param blockEl
				 * @param params
				 * @returns {*}
				 */
				this.selectBlock = function selectBlock (blockEl, params) {
					if (this.isReadOnly()) {
						return;
					}
					var shouldUpdate = selectedBlock !== blockEl;
					if (shouldUpdate) {
						if (selectedBlock !== null) {
							selectedBlock.removeClass('active');
						}
						selectedBlock = blockEl;
						selectedBlock.addClass('active');
					}

					if (! blockPropertiesPopupShown || shouldUpdate) {
						this.showBlockSettingsEditor(selectedBlock, params);
					}

					return blockEl;
				};


				/**
				 * Returns the selected block.
				 *
				 * @returns {null}
				 */
				this.getSelectedBlock = function getSelectedBlock () {
					return selectedBlock;
				};


				/**
				 * Shows the block's settings editor.
				 *
				 * @param blockEl
				 * @param params
				 */
				this.showBlockSettingsEditor = function showBlockSettingsEditor (blockEl, params) {
					var	elName = null,
						html,
						item = this.getItemById(blockEl.data('id')),
						shouldFocus = true;

					if (blockEl.is('.se-markdown-text')) {
						//elName = "se-rich-text-settings";
						shouldFocus = false;
					} else if (blockEl.is('.se-formatted-text')) {
						elName = "se-formatted-text-settings";
						shouldFocus = false;
					} else if (blockEl.is('.se-block-document')) {
						elName = "se-block-document-settings";
					} else if (blockEl.is('.se-row')) {
						elName = "se-row-settings";
					} else if (blockEl.is('.se-block-chooser')) {
						elName = "se-block-chooser-settings";
					}

					blockEl.scope().controller = this;

					html = '<div class="se-block-settings-editor" data-id="' + blockEl.data('id') + '" data-label="' + item.label + '"';
					if (elName) {
						html += '><div class="' + elName + '" data-id="' + blockEl.data('id') + '"';
					}
					forEach(params, function (value, name) {
						html += ' data-' + name + '="' + value + '"';
					});
					html += '></div>';
					if (elName) {
						html += '</div>';
					}

					blockPropertiesPopup.html(html);
					blockPropertiesPopup.attr('data-title', item.name === 'Rbs_Website_Richtext' ? "Texte WYSIWYG" : item.label);
					$compile(blockPropertiesPopup)(blockEl.scope());
					positionBlockSettingsEditor(blockEl);
					if (shouldFocus) {
						$timeout(function () {
							var inputs = blockPropertiesPopup.find('input[type="text"]');
							if (inputs.length) {
								inputs[0].focus();
							}
						});
					}
				};


				/**
				 * Gets an item from its ID.
				 *
				 * @param id
				 * @returns {*|null}
				 */
				this.getItemById = function getItemById (id) {
					return $scope.items[id] || null;
				};


				/**
				 * Creates a block in the given container, with the given `item` object (if any) and insert it
				 * at the given `atIndex`.
				 *
				 * @param container
				 * @param item
				 * @param atIndex
				 * @returns {*}
				 */
				this.createBlock = function createItem (container, item, atIndex) {
					var itemCell, itemChooser, block;

					if (!item) {
						item = this.registerNewItem("block-chooser");
					} else {
						item = this.registerNewItem(item);
					}

					// If item is a container:
					if (item.type === 'cell') {

						itemChooser = this.registerNewItem("block-chooser");
						item.items = [ itemChooser ];

					} else if (item.type === 'row') {

						item.grid = item.grid || DEFAULT_GRID_SIZE;
						if ( ! item.items || item.items.length === 0 ) {
							itemCell = this.registerNewItem("cell", {'size' : DEFAULT_GRID_SIZE});
							item.items = [ itemCell ];
							itemChooser = this.registerNewItem("block-chooser");
							itemCell.items = [ itemChooser ];
						}

					}

					block = structureEditorService.initItem(
						$scope,
						container,
						item,
						atIndex,
						this.isReadOnly()
					);

					console.log("create block: ", $element.attr('readonly') === 'true');

					if (angular.isFunction(block.scope().initItem)) {
						block.scope().initItem(item);
					}

					return block;
				};


				/**
				 * Creates a new block after the selected one.
				 */
				this.newBlockAfter = function newBlockAfter () {
					this.selectBlock(this.createBlock(selectedBlock.parent(), null, -1));
				};


				/**
				 * Creates a new block before the selected one.
				 */
				this.newBlockBefore = function newBlockAfter () {
					this.selectBlock(
						this.createBlock(
							selectedBlock.parent(),
							null,
							selectedBlock.index()
						)
					);
				};


				/**
				 * Registers a new item in the items registry (scope.items).
				 *
				 * @param item
				 * @param defaults
				 * @returns {*}
				 */
				this.registerNewItem = function (item, defaults) {
					if (angular.isString(item)) {
						item = angular.extend(
							{'type': item},
							defaults
						);
					}

					// Check if an item with this ID is already registered.
					if (item.id && $scope.items[item.id]) {
						throw new Error("Could not register item " + item.id + ": another item is registered with the same ID (" + $scope.items[item.id].type + ").");
					}

					// Assign new unique ID and register the item.
					item.id = $scope.getNextBlockId();
					$scope.items[item.id] = item;

					return item;
				};


				/**
				 * Creates a new block on the left or on the right on the selected block.
				 *
				 * @param where "left" or "right"
				 */
				this.newBlockSideways = function newBlockLeft (where) {
					var	block = this.getSelectedBlock(),
						item = $scope.items[block.data('id')],
						newLeftCellItem, newRightCellItem, newBlockChooserItem;

					if (angular.isFunction(block.scope().saveItem)) {
						block.scope().saveItem(item);
					}

					// Create left cell.
					newLeftCellItem = this.registerNewItem("cell", {'size' : DEFAULT_GRID_SIZE / 2});

					// Create right cell.
					newRightCellItem = this.registerNewItem("cell", {'size' : DEFAULT_GRID_SIZE / 2});

					// Create block chooser.
					newBlockChooserItem = this.registerNewItem("block-chooser");

					if (where === 'left') {
						newLeftCellItem.items = [ newBlockChooserItem ];
						newRightCellItem.items = [ item ];
					} else {
						newLeftCellItem.items = [ item ];
						newRightCellItem.items = [ newBlockChooserItem ];
					}

					this.createBlock(block.parent(), {
						'type'  : 'row',
						'items' : [ newLeftCellItem, newRightCellItem ]
					}, selectedBlock.index());
					block.remove();

					this.selectBlock(this.getBlockByItem(newBlockChooserItem));
				};


				/**
				 * Returns a block element from its corresponding item.
				 *
				 * @param item
				 * @returns {*}
				 */
				this.getBlockByItem = function (item) {
					if (angular.isObject(item) && item.id) {
						item = item.id;
					}
					return $element.find('[data-id="' + item + '"]').first();
				};


				/**
				 * Removes an item from the items registry.
				 *
				 * @param item
				 */
				this.removeItem = function removeItem (item) {
					this.removeBlock($element.find('[data-id="' + item.id + '"]'));
				};


				/**
				 * Removes the given block.
				 *
				 * @param block
				 */
				this.removeBlock = function removeBlock (block) {
					var	id = block.data('id'),
						wasSelected = (block === selectedBlock),
						parent = block.parent();

					delete $scope.items[id];
					block.remove();

					// Close block's settings if the removed block was selected.
					if (wasSelected) {
						closeBlockSettingsEditor();
					}

					// If the parent container of the removed block is now empty,
					// append a Block Chooser.
					if (parent.children().length === 0) {
						this.selectBlock( this.createBlock(parent, { 'type' : 'block-chooser' }) );
					}
				};


				// Utility functions -----------------------------------------------------------------------------------

				this.isInColumnLayout = function isInColumnLayout (el) {
					return el.closest('.se-cell').length === 1;
				};

				this.isRichText = function isInColumnLayout (el) {
					return el.is('.se-markdown-text');
				};


				this.selectParentRow = function (block) {
					this.selectBlock(
						block.closest('.se-row'),
						{ 'highlight-column': block.closest('.se-cell').index() }
					);
				};


				function isContainer (block) {
					return block.is('.se-row') || block.is('.se-cell');
				}


				function parseEditableZone (zoneEl, output) {
					var id = zoneEl.data('id'), zoneItem;
					zoneItem = {
						'id'      : id,
						'grid'    : zoneEl.data('grid'),
						'gridMode': zoneEl.data('gridMode'),
						'type'    : 'container',
						'items'   : []
					};
					output[id] = zoneItem;

					parseChildBlocks(zoneEl, zoneItem.items);
				}


				function parseChildBlocks (parentBlockEl, parentItems) {
					parentBlockEl.children('[data-id]').each(function (index, block) {
						var	blockEl = $(block),
							id = blockEl.data('id'),
							item = $scope.items[id];

						if (item.type !== 'block-chooser') {
							if (angular.isFunction(blockEl.scope().saveItem)) {
								blockEl.scope().saveItem(item);
							}

							parentItems.push(item);

							if (isContainer(blockEl)) {
								// Clear any existing items and rebuild the array in parseChildBlocks().
								item.items = [];
								parseChildBlocks(blockEl, item.items);
							}
						} else {
							isValid = false;
						}
					});
				}


				// Changes detection and notification ------------------------------------------------------------------
				var isValid = true;
				$scope.generateJSON = function () {

					var output = {};

					isValid = true;

					// Find editable zones
					$element.find('[data-editable-zone-id]').each(function (index, zoneEl) {
						parseEditableZone($(zoneEl), output);
					});

					$scope.contentChanged(output, isValid);
					return output;
				};

				var operationIcons = {
					'move': 'icon-move',
					'changeSettings': 'icon-cog',
					'changeText': 'icon-edit',
					'create': 'icon-plus-sign',
					'remove': 'icon-remove-sign',
					'resize': 'icon-resize-full',
					'visibility': 'icon-eye-open'
				};

				this.notifyChange = function (operation, elementName, element, data) {
					var self = this;
					$timeout(function () {
						var	output = $scope.generateJSON(),
							item = self.getItemById(element.data('id'));
						$scope.undoData.unshift({
							'label': (operation + ' ' + elementName),
							'item' : item,
							'data' : output,
							'date' : new Date(),
							'icon' : operationIcons[operation]
						});
					});
				};


				// Drag'n'drop -----------------------------------------------------------------------------------------

				// Draggable elements

				if (! this.isReadOnly())
				{
					$($element).on({

						'dragstart': function (e) {
							draggedEl = $(this);
							draggedEl.addClass('dragged');
							containerOfDraggedEl = draggedEl.parent();

							e.dataTransfer.setData('Text', draggedEl.data('id'));
							e.dataTransfer.effectAllowed = "copyMove";
						},

						'dragend': function () {
							draggedEl.removeClass('dragged');
						}

					}, '.block');

					// Droppable elements

					$($element).on({

						'dragenter': function (e) {
							e.preventDefault();
							e.stopPropagation();

							if (e.dataTransfer.getData('Text') !== $(this).data('id')) {
								structureEditorService.highlightDropTarget($(this));
							}
						},

						'dragleave': function (e) {
							e.preventDefault();
							e.stopPropagation();

							structureEditorService.unhighlightDropTarget($(this));
							dropZoneIndicator.hide();
						},

						'dragover': function (e) {
							e.dataTransfer.dropEffect = "move";

							e.preventDefault();
							e.stopPropagation();

							if (e.dataTransfer.getData('Text') !== $(this).data('id')) {
								var	mouseY = e.originalEvent.pageY,
									sameParent = containerOfDraggedEl.data('id') === $(this).data('id'),
									indicatorY = 0, i, midY, childEl, last, finalDropPosition;

								// Reset indicator position if drop zone has changed
								// so that it displays at the right position.
								if (dropTarget !== $(this)) {
									lastIndicatorY = -1;
								}

								dropTarget = $(this);
								dropPosition = -1;

								// Loop through all the children of the hovered element to determine
								// between which blocks the dragged block should be inserted.

								for (i=0 ; i<dropTarget.children().length && dropPosition === -1 ; i++) {
									childEl = $(dropTarget.children()[i]);
									midY = childEl.offset().top + (childEl.outerHeight() / 2);
									if (mouseY < midY) {
										finalDropPosition = dropPosition = i;
										indicatorY = childEl.offset().top;
									}
								}

								if (dropPosition === -1) {
									if (dropTarget.children().length) {
										last = dropTarget.children().last();
										indicatorY = last.offset().top + last.outerHeight();
									} else {
										indicatorY = dropTarget.offset().top;
									}
									finalDropPosition = dropTarget.children().length;
									if (sameParent) {
										finalDropPosition--;
									}
								} else if (sameParent && dropPosition > draggedEl.index()) {
									finalDropPosition--;
								}

								if (lastIndicatorY !== indicatorY) {
									dropZoneIndicator.find('.content').html(finalDropPosition + 1);
									dropZoneIndicator.css({
										'left' : (dropTarget.offset().left - dropZoneIndicator.outerWidth() - 2) + 'px',
										'top'  : (indicatorY - dropZoneIndicator.outerHeight()/2) + 'px'
									}).show();
									lastIndicatorY = indicatorY;
								}
							}
						},

						'drop': function (e) {
							e.preventDefault();
							e.stopPropagation();

							dropZoneIndicator.hide();

							if (containerOfDraggedEl.data('id') !== $(this).data('id') || draggedEl.index() !== dropPosition) {
								if (dropTarget.is('.empty')) {
									dropTarget.html('');
									dropTarget.removeClass('empty');
								}

								if (dropPosition === -1) {
									dropTarget.append(draggedEl);
								} else {
									$(dropTarget.children()[dropPosition]).before(draggedEl);
								}

								draggedEl.addClass('just-dragged');
								structureEditorService.terminateHighlight().then(
									function () {
										draggedEl.removeClass('just-dragged');
									},
									function () {
										draggedEl.removeClass('just-dragged');
									}
								);
								positionBlockSettingsEditor(null); // update

								if (containerOfDraggedEl.children().length === 0) {
									self.createBlock(containerOfDraggedEl);
								}

								self.notifyChange("move", "block", draggedEl, {'from': containerOfDraggedEl, 'to': dropTarget});
							} else {
								structureEditorService.highlightBlock(null);
							}
						}

					}, '.block-container');

					// Prevent drop on se-row (temporary?).

					$($element).on({
						'dragenter': function (e) {
							e.preventDefault();
							e.stopPropagation();
						},
						'dragover': function (e) {
							e.preventDefault();
							e.stopPropagation();
						}
					}, '.se-row');
				}

			},


			/**
			 * structureEditor.link
			 *
			 * @param scope
			 * @param elm
			 * @param attrs
			 * @param ngModel
			 */
			"link" : function seLinkFn (scope, elm, attrs, ctrls) {

				var	ngModel = ctrls[0],
					ctrl = ctrls[1];

				function getDefaultEmptyContent () {
					var content = {}, blockId = 0;
					elm.find('[data-editable-zone-id]').each(function (index, el) {
						var	zone = $(el),
							zoneId = zone.attr('data-editable-zone-id');

						content[zoneId] = {
							"id"   : zoneId,
							"grid" : DEFAULT_GRID_SIZE,
							"gridMode" : "fluid",
							"type" : "container",
							"items" : [
								{
									"id"  : ++blockId,
									"type": "block-chooser"
								}
							]
						};
					});

					return content;
				}

				scope.items = {};
				scope.blockIdCounter = 0;
				scope.undoData = [];

				scope.getNextBlockId = function () {
					return ++scope.blockIdCounter;
				};

				function layoutLoaded () {
					return elm.find('.structure-editor').children().length > 0;
				}

				var originalValue, templateData;

				attrs.$observe('template', function watchLayout (template) {
					if (template) {
						var tpl = JSON.parse(template);
						templateData = tpl.data;
						elm.find('.structure-editor').html(tpl.html);
						ngModel.$render();
					}
				});


				if (ngModel) {

					scope.undo = function (index) {
						closeBlockSettingsEditor();
						ngModel.$setViewValue(index < (scope.undoData.length-1) ? scope.undoData[index+1].data : originalValue);
						ngModel.$render();
						ArrayUtils.remove(scope.undoData, 0, index);
					};

					// Specify how UI should be updated
					ngModel.$render = function() {

						var	pageContent,
							newZones = [];

						// Parse all the hierarchical items and put them in a flat, ID-indexed array.
						function registerItems (container) {
							if (container.items) {
								forEach(container.items, function (item) {
									scope.items[item.id] = item;
									scope.blockIdCounter = Math.max(item.id, scope.blockIdCounter);
									registerItems(item);
								});
							}
						}

						if (layoutLoaded()) {
							if (angular.isDefined(ngModel.$viewValue) && angular.isUndefined(originalValue)) {
								originalValue = ngModel.$viewValue;
							}

							try {
								pageContent = ngModel.$viewValue ? ngModel.$viewValue : getDefaultEmptyContent();
							} catch (e) {
								console.log("Got error: " + e);
								pageContent = getDefaultEmptyContent();
							}

							// Create a container for each editable zone.
							forEach(templateData, function (tplZone) {
								var	zone, editableZone;

								// Check if the zone exists in the page.
								zone = pageContent[tplZone.id];
								if (zone) {
									editableZone = $(elm).find('[data-editable-zone-id="' + tplZone.id + '"]');
									if (editableZone.length) {
										registerItems(zone);
										editableZone.addClass('block-container');
										structureEditorService.initEditableZone(
											scope,
											editableZone,
											zone,
											ctrl.isReadOnly()
										);
									} else {
										NotificationCenter.error("Bad template configuration", "Could not find editable zone '" + zone.id + "' in page template.");
									}
								} else {

									if (tplZone.type === 'container') {
										// Store the zones that are not found in the page to add them later.
										newZones.push(angular.copy(tplZone));
									}

								}
							});



							forEach(newZones, function (zone) {

								var editableZone = $(elm).find('[data-editable-zone-id="' + zone.id + '"]');

								if (editableZone.length) {
									zone.items = [{
										"id"   : scope.getNextBlockId(),
										"type" : "block-chooser"
									}];

									registerItems(zone);
									editableZone.addClass('block-container');
									structureEditorService.initEditableZone(
										scope,
										editableZone,
										zone,
										ctrl.isReadOnly()
									);
								}

							});

							resizeHandler();
							Workspace.pin(blockPropertiesPopup);
						}

					};

					scope.contentChanged = function (newContent, isValid) {
						ngModel.$setViewValue(newContent);
						ngModel.$setValidity("content", isValid);
 					};

				}

				// Resize handler --------------------------------------------------------------------------------------

				function resizeHandler () {
					var editor = $(elm).find('.structure-editor').first();
					$timeout(function () {
						scope.editorWidth = editor.width();
						scope.editorHeight = editor.height();
					});
					structureEditorService.updateHighlight();
				}

				Workspace.addResizeHandler("StructureEditor", resizeHandler);

				scope.$on('Change:FullScreen:On', resizeHandler);
				scope.$on('Change:FullScreen:Off', resizeHandler);

				resizeHandler();

				scope.$on('$destroy', function () {
					closeBlockSettingsEditor();
				});

			}

		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Row (se-row).
	//
	//-------------------------------------------------------------------------

	app.directive('seRow', ['structureEditorService', function (structureEditorService) {

		return {

			"restrict"   : 'C',
			"require"    : "^structureEditor",
			"scope"      : {}, // isolated scope is required

			"link" : function seRowLinkFn (scope, elm, attrs, ctrl) {
				var item = ctrl.getItemById(elm.data('id'));

				if ($(elm).closest('.editable-zone').data('gridMode') === 'fixed') {
					elm.addClass('row');
				} else {
					elm.addClass('row-fluid');
				}

				elm.click(function (event) {
					event.stopPropagation();
					ctrl.selectBlock(elm);
				});

				structureEditorService.initChildItems(scope, elm, item, ctrl.isReadOnly());
			}
		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Row settings (se-row-settings).
	//
	//-------------------------------------------------------------------------

	app.directive('seRowSettings', ['structureEditorService', '$timeout', 'RbsChange.Dialog', function (structureEditorService, $timeout, Dialog) {

		return {

			"restrict" : 'C',
			"scope"    : true,
			"template" :
				'<button type="button" ng-click="setEqualColumns()" class="btn btn-small btn-block" ng-disabled="columns.length < 2 || ! equalSize">Répartir équitablement<span ng-show="equalSize"> ({{gridSize/equalSize}} x {{equalSize}})</span></button>' +
				'<div ng-repeat="col in columns" style="display:inline-block">' +
					'<div class="column-info" ng-class="{\'active\': highlightedColIndex == $index}" ng-click="highlightColumn($index)">' +
						'<div class="btn-group pull-right">' +
							'<button type="button" class="btn btn-small" ng-click="deleteColumn($index, $event)" title="Supprimer"><i class="icon-trash"></i></button>' +
						'</div>' +
						'<h5>Colonne {{$index+1}}</h5>' +
						'<div class="btn-group">' +
							'<button type="button" class="btn btn-mini" disabled="disabled" ng-pluralize count="col.childCount" when="{\'0\':\'Aucun bloc\', \'one\': \'Un bloc\', \'other\': \'{} blocs\'}"></button>' +
							'<button type="button" class="btn btn-mini" ng-click="addBlockInColumn($index, $event)" title="Ajouter un bloc dans cette colonne"><i class="icon-plus"></i></button>' +
						'</div>' +
						'<div class="param clearfix">' +
							'<button type="button" class="btn btn-small" ng-click="reduceColumn($index, $event)" ng-disabled="col.span == 1" title="Réduire"><i class="icon-minus"></i></button>' +
							'<div class="text">Largeur={{col.span}}</div>' +
							'<button type="button" class="btn btn-small" ng-click="expandColumn($index, $event)" ng-disabled="! canExpandColumn($index)" title="Agrandir"><i class="icon-plus"></i></button>' +
						'</div>' +
						'<div class="param clearfix">' +
							'<button type="button" class="btn btn-small" ng-click="moveColumnLeft($index, $event)" title="Décaler à gauche" ng-disabled="col.offset == 0"><i class="icon-arrow-left"></i></button>' +
							'<div class="text">Décalage={{col.offset}}</div>' +
							'<button type="button" class="btn btn-small" ng-click="moveColumnRight($index, $event)" title="Décaler à droite" ng-disabled="! canMoveColumnRight($index)"><i class="icon-arrow-right"></i></button>' +
						'</div>' +
					'</div>' +
					'<button type="button" class="btn btn-small" ng-click="insertColumn($index, $event)" ng-mouseover="highlightNewColumn($index)" ng-mouseout="unhighlightNewColumn()" ng-disabled="! canInsertColumn($index)">' +
						'<i class="icon-plus-sign"></i>' +
					'</button>' +
				'</div>',

			"link" : function seRowSettingsLinkFn (scope, elm, attrs) {
				var	ctrl = scope.controller,
					rowEl = ctrl.getSelectedBlock(),
					gridSize = attrs.gridSize || DEFAULT_GRID_SIZE,
					previouslyHighlightedColIndex = -1;

				if (rowEl.children().length === 0) {
					ctrl.selectBlock(ctrl.createBlock(rowEl, {'type': 'cell', 'size': DEFAULT_GRID_SIZE}));
				}

				scope.gridSize = gridSize;
				scope.totalColumns = 0;
				scope.highlightedColIndex = attrs.highlightColumn || -1;

				scope.highlightColumn = function (index) {
					if (angular.isDefined(index)) {
						if (index === scope.highlightedColIndex) {
							scope.highlightedColIndex = -1;
						} else {
							scope.highlightedColIndex = Math.min(index, rowEl.children().length - 1);
						}
					}
					if (scope.highlightedColIndex !== -1) {
						try {
							structureEditorService.highlightBlock(
								rowEl.children()[scope.highlightedColIndex],
								"Col. " + (scope.highlightedColIndex + 1) + ' (<i class="icon-resize-horizontal"></i> ' + scope.columns[scope.highlightedColIndex].span + ")",
								true
							);
						} catch (e) {
							// FIXME
						}
					} else {
						structureEditorService.highlightBlock(null);
					}
				};

				scope.highlightNewColumn = function (index) {
					previouslyHighlightedColIndex = scope.highlightedColIndex;
					scope.highlightedColIndex = -1;

					var col, nextCol, x, y, w, h, newColSpan;

					col = $(rowEl.children()[index]);
					nextCol = col.next();

					x = col.offset().left + col.outerWidth();
					y = rowEl.offset().top + highlightMargin;
					w = (nextCol.length ? nextCol.offset().left : rowEl.offset().left + rowEl.outerWidth()) - x;
					h = rowEl.outerHeight() - (highlightMargin * 2);

					if (scope.totalColumns < gridSize) {
						newColSpan = gridSize - scope.totalColumns;
					} else {
						newColSpan = nextCol.length ? parseInt(nextCol.attr('data-offset') || 1, 10) : 1;
					}

					structureEditorService.highlightZone(x, y, w, h, '<i class="icon-arrow-down"></i> <i class="icon-plus"></i> Col. (<i class="icon-resize-horizontal"></i> ' + newColSpan + ')');
				};

				scope.unhighlightNewColumn = function () {
					scope.highlightColumn(previouslyHighlightedColIndex);
					previouslyHighlightedColIndex = -1;
				};


				scope.setEqualColumns = function () {
					forEach(scope.columns, function (column) {
						column.offset = 0;
						column.span = scope.equalSize;
					});
					ctrl.notifyChange("resize", "allColumns", rowEl, {'size': scope.equalSize});
				};


				scope.addBlockInColumn = function (index, $event) {
					if (index === scope.highlightedColIndex) {
						$event.stopPropagation();
					}

					var colIndex = index, newBlock, cell = $(rowEl.children()[index]);
					newBlock = ctrl.createBlock(cell);
					scope.columns[index].childCount++;
					index = scope.highlightedColIndex;
					scope.highlightedColIndex = -1;
					scope.highlightColumn(index);
					ctrl.notifyChange("create", "block", newBlock, {'column': colIndex});
				};


				// Move column to right

				scope.canMoveColumnRight = function (index) {
					return scope.totalColumns < gridSize
						|| (scope.columns[index+1] && scope.columns[index+1].offset >= 1);
				};

				scope.moveColumnRight = function (index, $event) {
					if (index === scope.highlightedColIndex) {
						$event.stopPropagation();
					}

					var	offset = 0,
						isLastCol = (index === scope.columns.length -1);

					if (isLastCol && scope.totalColumns < gridSize) {
						offset = $event.altKey ? gridSize - scope.totalColumns : 1;
					} else {
						if (scope.columns[index+1] && scope.columns[index+1].offset >= 1) {
							offset = $event.altKey ? scope.columns[index+1].offset : 1;
							scope.columns[index+1].offset -= offset;
						} else if (scope.totalColumns < gridSize) {
							offset = $event.altKey ? gridSize - scope.totalColumns : 1;
						}
					}

					scope.columns[index].offset += offset;
					ctrl.notifyChange("move", "column", rowEl, {'location': 'right'});
				};


				// Move column to left

				scope.moveColumnLeft = function (index, $event) {
					if (index === scope.highlightedColIndex) {
						$event.stopPropagation();
					}

					var offset = $event.altKey ? scope.columns[index].offset : 1;
					scope.columns[index].offset -= offset;
					if (scope.columns[index + 1]) {
						scope.columns[index + 1].offset += offset;
					}
					ctrl.notifyChange("move", "column", rowEl, {'location': 'left'});
				};


				// Expand column

				scope.canExpandColumn = function (index) {
					return scope.totalColumns < gridSize
						|| (scope.columns[index+1] && scope.columns[index+1].offset >= 1)
						|| (scope.columns[index].offset >= 1);
				};

				scope.expandColumn = function (index, $event) {
					if (index === scope.highlightedColIndex) {
						$event.stopPropagation();
					}

					var	expandSize = 0,
						offsetToRemove = 0,
						nextOffsetToRemove = 0,
						column = scope.columns[index],
						nextColumn = scope.columns[index + 1],
						prevSpan = scope.columns[index].span;

					// If the Shift key is pressed,
					// find all the offsets that can be removed in favour of the column width.
					if ($event.altKey) {
						offsetToRemove = column.offset || 0;
						if (nextColumn) {
							nextOffsetToRemove = nextColumn.offset || 0;
						}
						if (scope.totalColumns < gridSize) {
							expandSize = gridSize - scope.totalColumns;
						}
					} else {
						// Use empty space if any.
						if (scope.totalColumns < gridSize) {
							expandSize = 1;
						} else if (column.offset >= 1) {
							// Can we reduce the offset of the column?
							offsetToRemove = 1;
						} else if (nextColumn.offset >= 1) {
							// Can we reduce the offset of the next column?
							nextOffsetToRemove = 1;
						}
					}

					if (offsetToRemove) {
						scope.columns[index].offset -= offsetToRemove;
						expandSize += offsetToRemove;
					}
					if (nextOffsetToRemove) {
						scope.columns[index+1].offset -= nextOffsetToRemove;
						expandSize += nextOffsetToRemove;
					}


					scope.columns[index].span += expandSize;

					ctrl.notifyChange("resize", "column", rowEl, {'from': prevSpan, 'to': scope.columns[index].span});
				};


				// Reduce column

				scope.reduceColumn = function (index, $event) {
					if (index === scope.highlightedColIndex) {
						$event.stopPropagation();
					}

					scope.columns[index].span--;
					if (scope.columns[index+1]) {
						scope.columns[index+1].offset++;
					}

					ctrl.notifyChange("resize", "column", rowEl, {'from': scope.columns[index].span+1, 'to': scope.columns[index].span});
				};


				// Insert column

				scope.canInsertColumn = function (index) {
					return scope.canMoveColumnRight(index);
				};

				scope.insertColumn = function (index, $event) {
					if (index === scope.highlightedColIndex) {
						$event.stopPropagation();
					}

					var size = 0;
					index++;

					if (scope.columns[index] && scope.columns[index].offset >= 1) {
						size = scope.columns[index].offset;
						$(rowEl.children().get(index)).removeClass("offset"+scope.columns[index].offset).removeAttr('data-offset');
					} else if (scope.totalColumns < gridSize) {
						size = gridSize - scope.totalColumns;
					}

					if (size) {
						ctrl.createBlock(
							rowEl,
							{
								'size'   : size,
								'offset' : 0,
								'type'   : 'cell'
							},
							index
						);

						$timeout(function () {
							scope.highlightColumn(index);
							scope.columns = structureEditorService.getColumnsInfo(rowEl, gridSize);
							ctrl.notifyChange("create", "column", rowEl, {'location': index});
						});
					} else {
						throw new Error("Could not insert column because its size would be 0.");
					}
				};


				// Delete column

				scope.deleteColumn = function (index, $event) {
					$event.stopPropagation();

					Dialog.confirmLocal(
						rowEl.children().get(index),
						"Supprimer la colonne ?",
						"<strong>Vous êtes sur le point de supprimer la colonne ci-dessous.</strong><br/><br/>Tous les blocs qu'elle contient (" + scope.columns[index].childCount + ") seront également supprimés de la page.",
						{"placement": "top"}
					).then(function () {
						if (index === scope.highlightedColIndex) {
							scope.highlightedColIndex = -1;
						}
						ctrl.removeItem(scope.columns[index]);
						scope.$apply(function () {
							scope.columns.splice(index, 1);
							ctrl.notifyChange("remove", "column", rowEl, {'location': index});
						});
						if (scope.columns.length === 0) {
							scope.insertColumn(0);
						}
					});
				};


				// ---

				scope.$watch(
					'columns',
					function (columns) {
						scope.totalColumns = 0;
						if (columns) {
							if (gridSize % columns.length === 0) {
								scope.equalSize = gridSize / columns.length;
							} else {
								scope.equalSize = 0;
							}
							forEach(columns, function (col) {
								scope.totalColumns += col.span + col.offset;
							});
							structureEditorService.applyColumnsWidth(rowEl, columns);
							scope.highlightColumn();
						}
					},
					true
				);

				$timeout(function () {
					scope.columns = structureEditorService.getColumnsInfo(rowEl);
				});

			}

		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Column (se-cell).
	//
	//-------------------------------------------------------------------------

	app.directive('seCell', ['structureEditorService', function (structureEditorService) {

		return {

			"restrict"   : 'C',
			"template"   : '<div class="{{span}} {{offset}} block-container"></div>',
			"replace"    : true,
			"require"    : "^structureEditor",
			"scope"      : {}, // isolated scope is required

			"link" : function seRowLinkFn (scope, elm, attrs, ctrl) {
				var item = ctrl.getItemById(elm.data('id'));

				scope.span = 'span' + item.size;
				if (item.offset) {
					scope.offset = 'offset' + item.offset;
				}

				scope.saveItem = function (item) {
					if (item) {
						item.size = parseInt(elm.attr('data-size'), 10);
						item.offset = parseInt(elm.attr('data-offset'), 10) || 0;
					}
				};

				structureEditorService.initChildItems(scope, elm, item, ctrl.isReadOnly());
			}
		};

	}]);



	var messages = {
		NewBlock: "Nouveau bloc",
		Text: "Ajouter une zone de texte",
		Block: "Ajouter un bloc",
		Document: "Ajouter un contenu",
		Columns2Tooltip: "Ajouter une zone divisée en 2 colonnes",
		Columns3Tooltip: "Ajouter une zone divisée en 3 colonnes",
		columns: "colonnes",
		DeleteBlock: "Supprimer le bloc",
		DeleteColumns: "Supprimer la zone divisée en colonnes",
		MoveBlock: "Déplacer le bloc",
		InsertBlockLeft: "Insérer un bloc à gauche",
		InsertBlockRight: "Insérer un bloc à droite",
		InsertBlockTop: "Insérer un bloc au-dessus",
		InsertBlockBottom: "Insérer un bloc en-dessous",
		ThisBlockHasNoParameters: "<em class=\"muted\">Ce bloc n'a pas de paramètre.</em>",
		UneditableBlockSuffix: " (bloc non modifiable)",
		InsertBlockLabel: "Insérer un bloc",
		InsertBlockAboveColumns: "Insérer un bloc au-dessus de la zone divisée en colonnes",
		InsertBlockBelowColumns: "Insérer un bloc en-dessous de la zone divisée en colonnes"
	};


	//-------------------------------------------------------------------------
	//
	// Block settings editor.
	//
	//-------------------------------------------------------------------------


	app.directive('seBlockSettingsEditor', ['structureEditorService', 'RbsChange.Workspace', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.REST', '$rootScope', 'RbsChange.Dialog', function (structureEditorService, Workspace, ArrayUtils, Utils, REST, $rootScope, Dialog) {

		var blockPropertiesCache = {};

		return {

			"restrict"   : 'C',
			"transclude" : true,
			"template"   :
				'<div class="btn-toolbar">' +
					'<div class="btn-group">' +
						'<button class="btn btn-small" disabled="disabled"><i class="icon-plus"></i></button>' +
						'<button class="btn btn-small" ng-show="canInsertSideways()" ng-click="newBlockLeft()" title="' + messages.InsertBlockLeft + '"><i class="icon-arrow-left"></i></button>' +
						'<button class="btn btn-small" ng-show="canInsertSideways()" ng-click="newBlockRight()" title="' + messages.InsertBlockRight + '"><i class="icon-arrow-right"></i></button>' +
						'<button class="btn btn-small" ng-click="newBlockAfter()" title="' + messages.InsertBlockBottom + '"><i class="icon-arrow-down"></i></button>' +
						'<button class="btn btn-small" ng-click="newBlockBefore()" title="' + messages.InsertBlockTop + '"><i class="icon-arrow-up"></i></button>' +
					'</div>' +
					'<button class="btn btn-small btn-danger pull-right" type="button" ng-click="removeBlock()" title="' + messages.DeleteBlock + '"><i class="icon-trash"></i></button>' +
				'</div>' +
				'<div class="btn-toolbar">' +
					'<h6>Visibilité</h6>' +
					'<div style="text-align: center;">' +
						'<button class="btn btn-mini" ng-click="toggleVisibility(\'D\')" ng-class="{\'btn-success active\': isVisibleFor(\'D\')}">Ordinateurs</button>' +
						'<button class="btn btn-mini" ng-click="toggleVisibility(\'T\')" ng-class="{\'btn-success active\': isVisibleFor(\'T\')}">Tablettes</button>' +
						'<button class="btn btn-mini" ng-click="toggleVisibility(\'P\')" ng-class="{\'btn-success active\': isVisibleFor(\'P\')}">Mobiles</button>' +
					'</div>' +
				'</div>' +
				'<div class="btn-toolbar" ng-show="isInColumnLayout()">' +
					'<button class="btn btn-small pull-right" ng-click="selectParentRow()"><i class="icon-columns"></i> Paramétrer</button>' +
					'<h6>Colonnes</h6>' +
				'</div>' +
				'<form ng-submit="submit()" novalidate name="block_properties_form" class="form-{{formDirection}}">' +
					'<div class="control-group" ng-hide="isRichText()">' +
						'<label class="control-label" for="block_{{item.id}}_label">Libellé du bloc</label>' +
						'<div class="controls">' +
							'<input class="input-block-level" id="block_{{item.id}}_label" type="text" ng-model="item.label" placeholder="Nom du bloc"/>' +
						'</div>' +
					'</div>' +
					'<div class="control-group" ng-repeat="param in blockParameters" ng-class="{true:\'required\'}[param.required]" ng-hide="isRichText()">' +
						'<label class="control-label" for="block_{{item.id}}_param_{{param.name}}">{{param.label}}</label>' +
						'<div ng-switch="param.type" class="controls">' +
							'<input id="block_{{item.id}}_param_{{param.name}}" name="{{param.name}}" ng-switch-when="Integer" type="number" required="{{param.required}}" class="input-small" ng-model="item.parameters[param.name]"/>' +

							// FIXME allowedModelNames
							'<div ng-switch-when="DocumentId" class="document-picker-single" input-css-class="input-small" ng-model="item.parameters[param.name]" embed-in="#se-picker-container" allow-creation="false" allow-edition="false" accepted-model="(= param.allowedModelsNames[0] =)"></div>' +

							'<input id="block_{{item.id}}_param_{{param.name}}" name="{{param.name}}" ng-switch-when="String" type="text" required="{{param.required}}" class="input-block-level" ng-model="item.parameters[param.name]"/>' +
							'<switch id="block_{{item.id}}_param_{{param.name}}" name="{{param.name}}" ng-switch-when="Boolean" ng-model="item.parameters[param.name]"/>' +
							'<select id="block_{{item.id}}_param_{{param.name}}" name="{{param.name}}" ng-switch-when="Collection" ng-model="item.parameters[param.name]" class="input-block-level" rbs-items-from-collection="{{param.collectionCode}}"></select>' +
						'</div>' +
					'</div>' +
					'<div ng-transclude=""></div>' +
					'<div class="form-actions">' +
						'<button type="button" class="btn" ng-disabled="! hasChanged()" ng-click="revert()">Annuler</button> ' +
						'<button type="submit" class="btn btn-primary" ng-disabled="! hasChanged() || block_properties_form.$invalid" ng-click="submit()">Valider</button>' +
					'</div>' +
				'</form>',

			/**
			 * The Scope here is the same Scope as the one of the current (selected) Block.
			 *
			 * @param scope
			 * @param element
			 * @param attrs
			 */
			"link" : function (scope, element, attrs) {

				structureEditorService.highlightBlock(null);

				scope.formDirection = blockPropertiesPopup.is('.pinned') ? 'vertical' : 'horizontal';

				scope.originalItem = scope.controller.getItemById(element.data('id'));
				if ( ! scope.originalItem.parameters ) {
					scope.originalItem.parameters = {};
				}
				scope.item = angular.copy(scope.originalItem);


				// Put default values in the block's parameters
				function fillDefaultValues (parameters) {
					scope.blockParameters = angular.copy(parameters);
					forEach(parameters, function (param) {
						if (angular.isUndefined(scope.originalItem.parameters[param.name])) {
							scope.originalItem.parameters[param.name] = param.defaultValue;
						}
					});
					scope.item = angular.copy(scope.originalItem);
				}

				if (scope.item.name && scope.item.name !== 'row' && scope.item.name !== 'cell') {
					// Load block's parameters and store them
					if (scope.item.name in blockPropertiesCache) {
						fillDefaultValues(blockPropertiesCache[scope.item.name]);
					} else {
						// Load block properties.
						REST.blockInfo(scope.item.name).then(function (blockInfo) {
							forEach(blockInfo.parameters, function (param) {
								param.label = param.label || param.name;
							});
							blockPropertiesCache[scope.item.name] = blockInfo.parameters;
							fillDefaultValues(blockPropertiesCache[scope.item.name]);
						});
					}
				}


				// Block visibility options -----------------------------------


				scope.isVisibleFor = function (device) {
					return ! scope.item.visibility || scope.item.visibility.indexOf(device) !== -1;
				};

				scope.toggleVisibility = function (device) {
					var	value = ! scope.isVisibleFor(device),
						splat,
						block,
						originalValue = ''+scope.item.visibility;

					if (scope.item.visibility) {

						if (scope.item.visibility.length > 3) {
							scope.item.visibility = scope.item.visibility.substr(0, 3);
						}
						splat = scope.item.visibility.split('');
						if (ArrayUtils.inArray(device, splat) !== -1 && ! value) {
							ArrayUtils.removeValue(splat, device);
						} else if (ArrayUtils.inArray(device, splat) === -1 && value) {
							splat.push(device);
						}
						splat.sort();
						scope.item.visibility = splat.join('');
					} else {
						if (value) {
							scope.item.visibility = device;
						} else {
							switch (device) {
							case 'P' :
								scope.item.visibility = 'DT';
								break;
							case 'T' :
								scope.item.visibility = 'DP';
								break;
							case 'D' :
								scope.item.visibility = 'PT';
								break;
							}
						}
					}

					block = scope.controller.getSelectedBlock();
					block.attr('data-visibility', scope.item.visibility);
					scope.controller.notifyChange("visibility", "block", block, {'from': originalValue, 'to': scope.item.visibility});
				};

				scope.canInsertSideways = function () {
					var block = scope.controller.getSelectedBlock();
					return ! block.is('.se-row') && ! scope.controller.isInColumnLayout(block);
				};

				scope.isInColumnLayout = function () {
					var block = scope.controller.getSelectedBlock();
					return scope.controller.isInColumnLayout(block);
				};

				scope.isRichText = function () {
					var block = scope.controller.getSelectedBlock();
					return scope.controller.isRichText(block);
				};

				scope.selectParentRow = function () {
					var block = scope.controller.getSelectedBlock();
					scope.controller.selectParentRow(block);
				};

				scope.newBlockBefore = function () {
					scope.controller.newBlockBefore();
				};

				scope.newBlockAfter = function () {
					scope.controller.newBlockAfter();
				};

				scope.newBlockLeft = function () {
					scope.controller.newBlockSideways('left');
				};

				scope.newBlockRight = function () {
					scope.controller.newBlockSideways('right');
				};

				scope.removeBlock = function () {
					var block = scope.controller.getSelectedBlock();
					Dialog.confirmLocal(
						block,
						"Supprimer le bloc ?",
						"<strong>Vous êtes sur le point de supprimer le bloc ci-dessous.</strong>",
						{"placement": "top"}
					).then(function () {
						scope.controller.removeBlock(block);
						scope.controller.notifyChange("remove", "block", block);
					});
				};

				scope.hasChanged = function () {
					return ! angular.equals(scope.item, scope.originalItem);
				};

				scope.revert = function () {
					scope.item = angular.copy(scope.originalItem);
					var block = scope.controller.getSelectedBlock();
					block.attr('data-visibility', scope.item.visibility);
				};

				scope.submit = function () {

					// Transform Documents into a lightweight representation (id, model, label, LCID).
					angular.forEach(scope.item.parameters, function (value, name) {
						if (Utils.isDocument(value)) {
							scope.item.parameters[name] = Utils.simpleRepresentation(value);
						}
					});

					angular.extend(scope.originalItem, scope.item);

					// Remove empty values.
					angular.forEach(scope.originalItem.parameters, function (value, name) {
						if (scope.item.parameters[name] === null || angular.isUndefined(scope.item.parameters[name])) {
							delete scope.originalItem.parameters[name];
						}
					});

					scope.item = angular.copy(scope.originalItem);
					scope.controller.notifyChange("changeSettings", "block", element);
				};

				$rootScope.$on('Change:Workspace:Pinned', function (event, el) {
					if (el.is('#structure-editor-block-properties-popup')) {
						scope.formDirection = 'vertical';
					}
				});
				$rootScope.$on('Change:Workspace:Unpinned', function (event, el) {
					if (el.is('#structure-editor-block-properties-popup')) {
						scope.formDirection = 'horizontal';
					}
				});

			}

		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Block template.
	//
	//-------------------------------------------------------------------------

	app.directive('seBlockTemplate', [ function () {

		return {

			"restrict" : 'C',
			"scope"    : {}, // isolated scope is required
			"require"  : '^structureEditor',
			"replace"  : true,

			"template" :
				'<div draggable="true" class="block btn btn-block btn-settings" ng-click="selectBlock($event)">' +
					'<i class="icon-th-large"></i> <span ng-bind-html="item.label | niceBlockName"></span>' +
				'</div>',

			"link" : function seBlockTemplateLinkFn (scope, element, attrs, ctrl) {
				scope.item = ctrl.getItemById(element.data('id'));

				scope.selectBlock = function ($event) {
					$event.stopPropagation();
					ctrl.selectBlock(element);
				};

			}

		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Block chooser.
	//
	//-------------------------------------------------------------------------

	app.directive('seBlockChooser', [ function () {

		return {

			"restrict" : 'C',
			"scope"    : true,
			"require"  : '^structureEditor',
			"replace"  : true,

			"template" :
				'<div draggable="true" class="block" ng-click="selectBlock($event)">' +
					'<i class="icon-question-sign"></i> Nouv. bloc' +
				'</div>',

			"link" : function seBlockChooserLinkFn (scope, element, attrs, ctrl) {

				element.attr('data-label', "Nouveau bloc");

				scope.selectBlock = function ($event) {
					$event.stopPropagation();
					ctrl.selectBlock(element);
				};

			}

		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Block chooser settings.
	//
	//-------------------------------------------------------------------------


	app.directive('seBlockChooserSettings', ['RbsChange.REST', 'RbsChange.ArrayUtils', function (REST, ArrayUtils) {

		var loadedModules, loadedBlocks;

		return {

			"restrict" : 'C',
			"template" :
				'<header>Choisissez le type de bloc à ajouter :</header>' +

				'<select ng-options="mod for mod in modules" ng-model="selectedModule" class="input-block-level"></select>' +
				'<button class="btn btn-block" type="button" ng-click="makeBlock($event, block)" ng-repeat="block in blocks[selectedModule]">' +
					'<i class="{{block.name}} icon-large"></i> {{block.label}}' +
				'</button>',

			"link" : function seBlockChooserSettingsLinkFn (scope, element, attrs) {
				var	ctrl = scope.controller,
					blockEl = ctrl.getSelectedBlock();

				scope.allowColumns = ! ctrl.isInColumnLayout(blockEl);

				if (loadedModules) {

					scope.modules = loadedModules;
					scope.blocks = loadedBlocks;
					scope.selectedModule = scope.modules[0];

				} else {

					REST.blocks().then(function (allBlocks) {
						var blocks = {}, modules = [];

						forEach(allBlocks, function (block, name) {
							var splat = name.split('_'),
								module = splat[0] + ' ' + splat[1];
							if (ArrayUtils.inArray(module, modules) === -1) {
								modules.push(module);
								blocks[module] = [];
							}
							blocks[module].push(block);
						});

						scope.selectedModule = modules[0];
						scope.modules = loadedModules = modules;
						scope.blocks = loadedBlocks = blocks;
					});

				}

				function replaceItem (item) {
					var createdEl = ctrl.createBlock(blockEl.parent(), item, blockEl.index());
					ctrl.removeBlock(blockEl);
					return createdEl;
				}

				scope.makeBlock = function ($event, blockDef) {
					$event.stopPropagation();
					var block = ctrl.selectBlock(replaceItem({
						'type' : 'block',
						'name' : blockDef.name
					}));
					ctrl.notifyChange("create", blockDef.label, block);
				};
			}

		};

	}]);



	//-------------------------------------------------------------------------
	//
	// Block Document.
	//
	//-------------------------------------------------------------------------

	app.directive('seBlockDocument', [ function () {

		return {

			"restrict" : 'C',
			"scope"    : {}, // isolated scope is required
			"require"  : '^structureEditor',
			"replace"  : true,

			"template" :
				'<div draggable="true" class="block btn btn-block btn-settings" block-label="{{item.label}}" block-type="document" ng-click="selectBlock($event)">' +
					'<i class="icon-file"></i> <span ng-bind-html="item.label | niceBlockName"></span>' +
				'</div>',

			"link" : function seBlockDocumentLinkFn (scope, element, attrs, ctrl) {
				scope.item = ctrl.getItemById(element.data('id'));

				scope.selectBlock = function ($event) {
					$event.stopPropagation();
					ctrl.selectBlock(element);
				};
			}

		};

	}]);


	app.filter('niceBlockName', function () {
		return function (input) {
			return input.replace(/_/g, '<span style="font-size:0;display:inline-block;"> </span>_');
		};
	});


	app.directive('seBlockDocumentSettings', [ function () {

		return {

			"restrict" : 'C',
			"replace"  : true,
			"scope"    : true,

			"template" :
				'<form ng-submit="submit()">' +
					'<label for ="block_{{block}}_label">Libellé du bloc</label>' +
					'<input class="input-block-level" id="block_{{block}}_label" type="text" ng-model="block.label" placeholder="Nom du bloc"/>' +
					'<div class="parameters"></div>'+
					'<div class="form-actions">' +
						'<button type="button" class="btn" ng-disabled="! hasChanged()" ng-click="revert()">Annuler</button> ' +
						'<button type="submit" class="btn btn-primary" ng-disabled="! hasChanged()" ng-click="submit()">Valider</button>' +
					'</div>' +
				'</form>',

			"link" : function seBlockDocumentLinkFn (scope, element, attrs) {
				var originalItem = scope.controller.getItemById(element.data('id'));
				scope.block = angular.copy(originalItem);

				scope.hasChanged = function () {
					return ! angular.equals(scope.block, originalItem);
				};

				scope.revert = function () {
					scope.block = angular.copy(originalItem);
				};

				scope.submit = function () {
					angular.extend(originalItem, scope.block);
					scope.controller.notifyChange("changeSettings", "block", element);
				};
			}

		};

	}]);


	//-------------------------------------------------------------------------
	//
	// Markdown text.
	//
	//-------------------------------------------------------------------------

	app.directive('seMarkdownText', [ function () {

		return {

			"restrict"   : 'C',
			"scope"      : {
				// isolated scope is required
				readonly: '@'
			},
			"require"    : '^structureEditor',
			"transclude" : true,
			"replace"    : true,
			"template"   : '<div class="block" ng-click="selectBlock($event)"><rbs-rich-text-input ng-readonly="readonly" use-tabs="false" ng-model="text" profile="Website"></rbs-rich-text-input></div>',

			"link" : function seRichTextLinkFn (scope, element, attrs, ctrl) {
				element.attr('block-label', "Markdown");
				element.attr('block-type', "rich-text");

				var item = ctrl.getItemById(element.data('id'));
				if (! item.parameters) {
					item.parameters = {
						'contentType' : 'Markdown',
						'content' : ''
					};
				}
				scope.text = item.parameters.content;

				scope.$watch('text', function (text, old) {
					if (text !== old) {
						scope.saveItem(item);
					}
				});

				scope.selectBlock = function ($event) {
					$event.stopPropagation();
					ctrl.selectBlock(element);
				};


				scope.initItem = function (item) {
					item.parameters = {
						'contentType': 'Markdown'
					};
				};

				scope.saveItem = function (item) {
					if (item) {
						angular.extend(item.parameters, {'content': scope.text});
					}
				};
			}

		};

	}]);


	app.directive('rbsStructureViewer', function ()
	{
		return {
			'restrict' : 'E',
			'template' :
				'<div ng-repeat="zone in zones">' +
					'<h4>(= zone.id =)</h4>' +
					'<pre ng-repeat="block in zone.blocks">(= block.parameters.content =)</pre>' +
				'</div>',
			'scope' : {
				'content' : '='
			},

			'link' : function structureViewerLink (scope, iElement, iAttrs)
			{
				function findBlocks (container, blocks) {
					angular.forEach(container.items, function (item) {
						if (item.type === 'block') {
							blocks.push(item);
						}
						else {
							findBlocks(item, blocks);
						}
					});
				}

				scope.$watch('content', function (content, old)
				{
					if (content !== old) {
						var zones = [];
						// Loop through the editable zones.
						angular.forEach(content, function (zone) {
							var blocks = [];
							findBlocks(zone, blocks);
							zones.push({"id": zone.id, "blocks": blocks});
						});
						scope.zones = zones;
					}
				});
			}
		};
	});


})(window.jQuery);