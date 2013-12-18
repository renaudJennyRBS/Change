(function ($) {

	"use strict";

	var	dropZoneIndicator,
		blockPropertiesPopup,
		blockPropertiesPopupShown = false,
		lastSelectedBlock = null,

		app = angular.module('RbsChange'),

		forEach = angular.forEach,

		DEFAULT_GRID_SIZE = 12,
		RICH_TEXT_BLOCK_NAME = 'Rbs_Website_Richtext',

		highlightMargin = 2,
		highlightBorder = 5;

	$('body').append(
		'<div id="structure-editor-dropzone-indicator"><span class="content"></span><i class="icon-arrow-right"></i></div>'
	);
	dropZoneIndicator = $('#structure-editor-dropzone-indicator');

	blockPropertiesPopup = $('#rbsStructureEditorBlockPropertiesPopup');


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
		console.log(blockPropertiesPopup);
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
		 * @param readonly
		 */
		this.initEditableZone = function (scope, zoneEl, zoneObj, readonly) {
			zoneEl.html('');

			zoneEl.addClass('editable-zone');

			zoneEl.attr('data-id', zoneObj.id);
			zoneEl.attr('data-grid', zoneObj.grid);

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

		this.getEditableZone = function (el) {
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
			var className = (item.name === RICH_TEXT_BLOCK_NAME) ? 'rbs-block-markdown-text' : 'rbs-block-template';
			return '<div class="' + className + '" ' + (readonly ? 'readonly="true" ' : '') + 'data-id="' + item.id + '" data-name="' + item.name + '" data-label="' + item.label + '" data-visibility="' + (item.visibility || '') + '">' + item.name + '</div>';
		};

		this.initRow = function (item) {
			return '<div class="rbs-row" data-id="' + item.id + '" data-grid="' + item.grid + '" data-visibility="' + (item.visibility || '') + '"></div>';
		};

		this.initCell = function (item) {
			return '<div class="rbs-cell" data-id="' + item.id + '" data-size="' + item.size + '"></div>';
		};

		this.initBlockChooser = function (item) {
			return '<div class="rbs-block-chooser" data-id="' + item.id + '"></div>';
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
			row.children('.rbs-cell').each(function (index, el) {

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
			if (el.is('.rbs-cell')) {
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


	//-------------------------------------------------------------------------
	//
	// Structure editor.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsStructureEditor', ['$timeout', '$compile', 'RbsChange.Workspace', 'RbsChange.MainMenu', 'structureEditorService', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.NotificationCenter', function ($timeout, $compile, Workspace, MainMenu, structureEditorService, ArrayUtils, Utils, NotificationCenter) {
		return {
			"restrict"   : 'E',
			"require"    : ['ngModel', 'rbsStructureEditor'],
			"scope"      : true,
			"transclude" : true,
			"templateUrl": 'Rbs/Admin/js/directives/structure-editor.twig',

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
			"controller" : function ($scope, $element, $attrs) {
				var	selectedBlock = null,
					selectedBlockId = -1,
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
				this.selectBlock = function (blockEl, params) {
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

					selectedBlockId = blockEl.data('id');
					return blockEl;
				};


				this.reselectBlock = function () {
					selectedBlock = null;
					if (selectedBlockId !== -1) {
						this.selectBlock(this.getBlockByItem(selectedBlockId));
					}
				};


				/**
				 * Returns the selected block.
				 *
				 * @returns {null}
				 */
				this.getSelectedBlock = function () {
					return selectedBlock;
				};


				/**
				 * Shows the block's settings editor.
				 *
				 * @param blockEl
				 * @param params
				 */
				this.showBlockSettingsEditor = function (blockEl, params) {
					var	html;
					var item = this.getItemById(blockEl.data('id'));

					if (item === null) {
						blockPropertiesPopup = $('#rbsStructureEditorBlockPropertiesPopup');
						blockPropertiesPopup.html(blockPropertiesPopup.attr('data-no-block-text'));
						blockPropertiesPopup.attr('data-title', '');
						positionBlockSettingsEditor(blockEl);
						return;
					}
					if (blockEl.is('.rbs-row')) {
						html = '<div class="rbs-row-settings" data-id="' + blockEl.data('id') + '"';
					}
					else {
						html = '<div class="rbs-block-settings-editor" data-id="' + blockEl.data('id') + '" data-label="' + item.label + '"';
					}
					forEach(params, function (value, name) {
						html += ' data-' + name + '="' + value + '"';
					});
					html += '></div>';

					var blockScope = blockEl.isolateScope() || blockEl.scope();
					blockScope.editorCtrl = this;

					blockPropertiesPopup = $('#rbsStructureEditorBlockPropertiesPopup');
					blockPropertiesPopup.html(html);
					blockPropertiesPopup.attr('data-title', item.name === 'Rbs_Website_Richtext' ? '' : item.label);
					$compile(blockPropertiesPopup)(blockScope);
					positionBlockSettingsEditor(blockEl);
				};


				/**
				 * Gets an item from its ID.
				 *
				 * @param id
				 * @returns {*|null}
				 */
				this.getItemById = function (id) {
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
				this.createBlock = function (container, item, atIndex) {
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

					if (angular.isFunction(block.scope().initItem)) {
						block.scope().initItem(item);
					}

					return block;
				};


				/**
				 * Creates a new block after the selected one.
				 */
				this.newBlockAfter = function () {
					this.selectBlock(this.createBlock(selectedBlock.parent(), null, selectedBlock.index()+1));
				};

				/**
				 * Creates a new block after each other.
				 */
				this.newBlockBottom = function () {
					this.selectBlock(this.createBlock(selectedBlock.parent(), null, -1));
				};

				/**
				 * Creates a new block before the selected one.
				 */
				this.newBlockBefore = function () {
					this.selectBlock(this.createBlock(selectedBlock.parent(), null, selectedBlock.index()));
				};

				/**
				 * Creates a new block before each other.
				 */
				this.newBlockTop = function () {
					this.selectBlock(this.createBlock(selectedBlock.parent(), null, 0));
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
				this.newBlockSideways = function (where) {
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
				this.removeItem = function (item) {
					this.removeBlock($element.find('[data-id="' + item.id + '"]'));
				};


				/**
				 * Removes the given block.
				 *
				 * @param block
				 */
				this.removeBlock = function (block) {
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

				this.isInColumnLayout = function (el) {
					return el.closest('.rbs-cell').length === 1;
				};

				this.isRichText = function (el) {
					return el.is('.rbs-block-markdown-text');
				};


				this.selectParentRow = function (block) {
					this.selectBlock(
						block.closest('.rbs-row'),
						{ 'highlight-column': block.closest('.rbs-cell').index() }
					);
				};


				function isContainer (block) {
					return block.is('.rbs-row') || block.is('.rbs-cell');
				}


				function parseEditableZone (zoneEl, output) {
					var id = zoneEl.data('id'), zoneItem;
					zoneItem = {
						'id'      : id,
						'grid'    : zoneEl.data('grid'),
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
							//isValid = false;
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

				var undoDataItemId = 0,
					operationIcons = {
					'move': 'icon-move',
					'changeSettings': 'icon-cog',
					'changeText': 'icon-edit',
					'create': 'icon-plus-sign',
					'remove': 'icon-remove-sign',
					'resize': 'icon-resize-full',
					'visibility': 'icon-eye-open'
				};

				this.notifyChange = function (operation, elementName, element) {
					var self = this;
					$timeout(function () {
						var	output = $scope.generateJSON(),
							item = self.getItemById(element.data('id'));
						$scope.undoData.unshift({
							'id'   : undoDataItemId++,
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
							draggedEl = $(this).closest('.block-draggable');
							console.log(draggedEl);
							draggedEl.addClass('dragged');
							containerOfDraggedEl = draggedEl.parent();

							e.dataTransfer.setData('Text', draggedEl.data('id'));
							e.dataTransfer.effectAllowed = "copyMove";
						},

						'dragend': function () {
							draggedEl.removeClass('dragged');
						}

					}, '.block-handle');

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

					// Prevent drop on rbs-row (temporary?).
					$($element).on({
						'dragenter': function (e) {
							e.preventDefault();
							e.stopPropagation();
						},
						'dragover': function (e) {
							e.preventDefault();
							e.stopPropagation();
						}
					}, '.rbs-row');
				}
			},


			/**
			 * structureEditor.link
			 *
			 * @param scope
			 * @param elm
			 * @param attrs
			 * @param ctrls
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
										if (!zone.hasOwnProperty('items') || zone.items.length == 0) {
											zone.items = [{
												"id"   : scope.getNextBlockId(),
												"type" : "block-chooser"
											}];
										}

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

							ctrl.reselectBlock();
							resizeHandler();
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

				resizeHandler();

				scope.$on('$destroy', function () {
					closeBlockSettingsEditor();
				});
			}
		};
	}]);



	//-------------------------------------------------------------------------
	//
	// Row (rbs-row).
	//
	//-------------------------------------------------------------------------

	app.directive('rbsRow', ['structureEditorService', function (structureEditorService) {
		return {
			"restrict"   : 'C',
			"require"    : "^rbsStructureEditor",
			"scope"      : {}, // isolated scope is required

			"link" : function seRowLinkFn (scope, elm, attrs, ctrl) {
				var item = ctrl.getItemById(elm.data('id'));

				elm.addClass('row');

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
	// Row settings (rbs-row-settings).
	//
	//-------------------------------------------------------------------------

	app.directive('rbsRowSettings', ['structureEditorService', '$timeout', 'RbsChange.Dialog', 'RbsChange.i18n', function (structureEditorService, $timeout, Dialog, i18n) {
		return {
			"restrict" : 'C',
			"scope"    : true,
			templateUrl : 'Rbs/Admin/js/directives/structure-editor-row-settings.twig',

			"link" : function seRowSettingsLinkFn (scope, elm, attrs) {
				var	ctrl = scope.editorCtrl,
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
						i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_column | ucf'),
						i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_column_confirm | ucf', {CHILDCOUNT: scope.columns[index].childCount}),
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

				// New blocks.
				scope.newBlockBefore = function () {
					ctrl.newBlockBefore();
				};

				scope.newBlockAfter = function () {
					ctrl.newBlockAfter();
				};

				scope.removeBlock = function () {
					var block = ctrl.getSelectedBlock();
					Dialog.confirmLocal(
						block,
						i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block_group | ucf'),
						"<strong>" + i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block_group_confirm | ucf') + "</strong>",
						{"placement": "top"}
					).then(function () {
							ctrl.removeBlock(block);
							ctrl.notifyChange("remove", "block", block);
						});
				};

				$timeout(function () {
					scope.columns = structureEditorService.getColumnsInfo(rowEl);
				});
			}
		};
	}]);


	//-------------------------------------------------------------------------
	//
	// Column (rbs-cell).
	//
	//-------------------------------------------------------------------------

	app.directive('rbsCell', ['structureEditorService', function (structureEditorService) {
		return {
			"restrict"   : 'C',
			"template"   : '<div class="{{span}} {{offset}} block-container"></div>',
			"replace"    : true,
			"scope"      : {}, // isolated scope is required
			"require"    : '^rbsStructureEditor',

			"link" : function seRowLinkFn (scope, elm, attrs, ctrl) {
				var item = ctrl.getItemById(elm.data('id'));

				scope.span = 'col-md-' + item.size;
				if (item.offset) {
					scope.offset = 'col-md-offset-' + item.offset;
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


	//-------------------------------------------------------------------------
	//
	// Block selector.
	//
	//-------------------------------------------------------------------------


	app.directive('rbsBlockSelector', ['RbsChange.REST', function (REST) {
		var blockList = [], loading = false;

		function loadBlockList () {
			if (! blockList.length && ! loading) {
				loading = true;
				REST.call(REST.getBaseUrl('admin/blockList/')).then(function (blockData)
				{
					angular.forEach(blockData, function (pluginBlocks, pluginLabel)
					{
						angular.forEach(pluginBlocks, function (block)
						{
							block.plugin = pluginLabel;
							blockList.push(block);
						});
					});
					loading = false;
				});
			}
			return blockList;
		}

		function getBlockByName (name) {
			for (var i=0 ; i<blockList.length ; i++) {
				if (blockList[i].name === name) {
					return blockList[i];
				}
			}
			return null;
		}

		return {
			restrict : 'E',
			template : '<select class="form-control" ng-model="block" ng-required="required" ng-options="block.label group by block.plugin for block in blocks"></select>',
			scope : { block : '=', selected : '@', required : '@' },

			link : function (scope)
			{
				scope.blocks = loadBlockList();

				function updateSelection () {
					if (scope.selected && scope.blocks.length > 0 && (! scope.block || scope.block.name !== scope.selected)) {
						scope.block = getBlockByName(scope.selected);
					}
				}

				function onSelectBlock() {
					scope.$emit('blockSelected', {name: (scope.block ? scope.block.name : '')});
				}

				scope.$watchCollection('blocks', updateSelection);
				scope.$watch('selected', updateSelection, true);
				scope.$watch('block', onSelectBlock, true);
			}
		};
	}]);


	//-------------------------------------------------------------------------
	//
	// Block settings editor.
	//
	//-------------------------------------------------------------------------


	app.directive('rbsBlockSettingsEditor', ['structureEditorService', 'RbsChange.Workspace', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.REST', '$rootScope', 'RbsChange.Dialog', '$timeout', '$http', '$compile', 'RbsChange.i18n', function (structureEditorService, Workspace, ArrayUtils, Utils, REST, $rootScope, Dialog, $timeout, $http, $compile, i18n) {
		return {
			"restrict" : 'C',
			"transclude" : true,
			"scope" : true,
			"templateUrl" : 'Rbs/Admin/js/directives/structure-editor-block-settings.twig',

			"link" : function (scope, element) {
				var ctrl = scope.editorCtrl;

				structureEditorService.highlightBlock(null);

				scope.formValues = {};
				scope.formDirection = 'vertical';
				scope.blockParametersLoading = false;

				scope.block = ctrl.getItemById(element.data('id'));
				if (!scope.block.parameters) {
					scope.block.parameters = {};
					// If there is a block, load default parameters and open parameterize panel.
					if (scope.block.name) {
						scope.blockParametersLoading = true;
						REST.blockInfo(scope.block.name).then(function (blockInfo) {
							angular.forEach(blockInfo.parameters, function(parameter) {
								if (parameter.hasOwnProperty('defaultValue')) {
									scope.block.parameters[parameter.name] = parameter.defaultValue;
								}
							});
							scope.blockParametersLoading = false;
							finalizeParameters();
						});
					}
				}
				finalizeParameters();

				function finalizeParameters() {
					scope.blockParameters = scope.block.parameters;
					if (!scope.blockParameters.hasOwnProperty('TTL')) {
						scope.blockParameters.TTL = 60;
					}
				}

				function replaceItem (item) {
					var block = ctrl.getSelectedBlock(),
						createdEl = ctrl.createBlock(block.parent(), item, block.index());
					ctrl.removeBlock(block);
					return createdEl;
				}

				function onBlockTypeChanged(blockType) {
					if (!scope.block.name) {
						var block = replaceItem({
							'type': 'block',
							'name': blockType.name
						});
						ctrl.notifyChange("create", blockType.label, block);
						$timeout(function () { ctrl.selectBlock(block); });
					}
				}

				scope.$watch('blockType', function (blockType, old) {
					if (blockType && blockType !== old) {
						if (blockType.name == 'Rbs_Website_Richtext') {
							onBlockTypeChanged(blockType);
						}
						else {
							$http.get(blockType.template).success(function (html) {
								$compile(html)(scope, function (clone) {
									element.find('[data-role="blockParametersContainer"]').append(clone);
									onBlockTypeChanged(blockType);
								});
							});
						}
					}
				}, true);

				// Block TTL options ------------------------------------------

				scope.hasTTL = function(seconds) {
					return scope.blockParameters.TTL == seconds;
				};

				scope.setTTL = function(seconds) {
					scope.blockParameters.TTL = seconds;
				};


				// Block visibility options -----------------------------------

				scope.isVisibleFor = function (device) {
					if (!scope.block) {
						return false;
					}
					if (device == 'raw') {
						return (scope.block.visibility == 'raw');
					} else if (scope.block.visibility == 'raw') {
						return false;
					}
					return (! scope.block.visibility || scope.block.visibility.indexOf(device) !== -1);
				};

				scope.toggleVisibility = function (device) {
					var	value = ! scope.isVisibleFor(device),
						splat,
						block,
						originalValue = ''+scope.block.visibility;

					if (device == 'raw') {
						if (value) {
							scope.block.visibility = device;
						} else {
							delete scope.block.visibility;
						}
					} else if (scope.block.visibility == 'raw') {
						delete scope.block.visibility;
					} else {
						if (scope.block.visibility) {
							splat = scope.block.visibility.split('');
							if (ArrayUtils.inArray(device, splat) !== -1 && ! value) {
								ArrayUtils.removeValue(splat, device);
							} else if (ArrayUtils.inArray(device, splat) === -1 && value) {
								splat.push(device);
							}
							splat.sort();
							scope.block.visibility = splat.join('');
						} else {
							if (value) {
								scope.block.visibility = device;
							} else {
								switch (device) {
									case 'X' :
										scope.block.visibility = 'SML';
										break;
									case 'S' :
										scope.block.visibility = 'XML';
										break;
									case 'M' :
										scope.block.visibility = 'XSL';
										break;
									case 'L' :
										scope.block.visibility = 'XSM';
										break;
								}
							}
						}
					}

					block = ctrl.getSelectedBlock();
					block.attr('data-visibility', scope.block.visibility);
					ctrl.notifyChange("visibility", "block", block, {'from': originalValue, 'to': scope.block.visibility});
				};

				scope.canInsertSideways = function () {
					var block = ctrl.getSelectedBlock();
					return ! block.is('.rbs-row') && ! ctrl.isInColumnLayout(block);
				};

				scope.isInColumnLayout = function () {
					var block = ctrl.getSelectedBlock();
					return ctrl.isInColumnLayout(block);
				};

				scope.isRichText = function () {
					var block = ctrl.getSelectedBlock();
					return ctrl.isRichText(block);
				};

				scope.selectParentRow = function () {
					var block = ctrl.getSelectedBlock();
					ctrl.selectParentRow(block);
				};

				scope.newBlockBefore = function () {
					ctrl.newBlockBefore();
				};

				scope.newBlockAfter = function () {
					ctrl.newBlockAfter();
				};

				scope.newBlockTop = function () {
					ctrl.newBlockTop();
				};

				scope.newBlockBottom = function () {
					ctrl.newBlockBottom();
				};

				scope.newBlockLeft = function () {
					ctrl.newBlockSideways('left');
				};

				scope.newBlockRight = function () {
					ctrl.newBlockSideways('right');
				};

				scope.removeBlock = function () {
					var block = ctrl.getSelectedBlock();
					console.log(block);
					if (block.hasClass('rbs-block-chooser')) {
						ctrl.removeBlock(block);
						ctrl.notifyChange("remove", "block", block);
					}
					else {
						Dialog.confirmLocal(
							block,
							i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block | ucf'),
							"<strong>" + i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block_confirm | ucf') + "</strong>",
							{"placement": "top"}
						).then(function () {
							ctrl.removeBlock(block);
							ctrl.notifyChange("remove", "block", block);
						});
					}
				};
			}
		};
	}]);


	//-------------------------------------------------------------------------
	//
	// Block template.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsBlockTemplate', [ function () {
		return {
			"restrict" : 'C',
			"scope"    : {}, // isolated scope is required
			"require"  : '^rbsStructureEditor',
			"replace"  : true,
			"template" :
				'<div draggable="true" class="block btn btn-block btn-settings break-word block-draggable block-handle" ng-click="selectBlock($event)">' +
					'<i class="icon-th-large"></i> <span ng-bind-html="item.label"></span><div><small>(= item.parameters | json =)</small></div>' +
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

	app.directive('rbsBlockChooser', [ function () {
		return {
			"restrict" : 'C',
			"scope"    : true,
			"require"  : '^rbsStructureEditor',
			"replace"  : true,
			"templateUrl" : 'Rbs/Admin/js/directives/structure-editor-block-chooser.twig',

			"link" : function seBlockChooserLinkFn (scope, element, attrs, ctrl) {
				scope.selectBlock = function ($event) {
					$event.stopPropagation();
					ctrl.selectBlock(element);
				};
			}
		};
	}]);


	//-------------------------------------------------------------------------
	//
	// Markdown text.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsBlockMarkdownText', [ function () {
		return {
			"restrict"   : 'C',
			"scope"      : {
				// isolated scope is required
				readonly: '@'
			},
			"require"    : '^rbsStructureEditor',
			"transclude" : true,
			"replace"    : true,
			"template"   : '<div class="block block-draggable" ng-click="selectBlock($event)"><rbs-rich-text-input data-draggable="true" ng-readonly="readonly" use-tabs="false" ng-model="input.text" profile="Website"></rbs-rich-text-input></div>',

			"link" : function seRichTextLinkFn (scope, element, attrs, ctrl) {
				element.attr('block-label', "Markdown");
				element.attr('block-type', "rich-text");

				scope.initItem = function (item) {
					item.parameters = {
						contentType: 'Markdown',
						content : ''
					};
				};

				var item = ctrl.getItemById(element.data('id'));
				if (! item.parameters) {
					scope.initItem(item);
				}
				scope.input = {text: item.parameters.content};

				scope.$watch('input.text', function (text, old) {
					if (text !== old) {
						scope.saveItem(item);
					}
				});

				scope.selectBlock = function ($event) {
					//$event.stopPropagation();
					ctrl.selectBlock(element);
				};

				scope.saveItem = function (item) {
					if (item) {
						angular.extend(item.parameters, {content: scope.input.text});
					}
				};
			}
		};
	}]);


	app.directive('rbsStructureViewer', function () {
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

			'link' : function structureViewerLink (scope, iElement, iAttrs) {
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

				scope.$watch('content', function (content, old) {
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