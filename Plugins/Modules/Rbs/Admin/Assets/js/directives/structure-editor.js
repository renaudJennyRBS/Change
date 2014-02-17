(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		dropZoneIndicator,
		blockPropertiesPopup,
		blockPropertiesPopupShown = false,
		lastSelectedBlock = null,

		DEFAULT_GRID_SIZE = 12,
		RICH_TEXT_BLOCK_NAMES = ['Rbs_Website_Richtext', 'Rbs_Mail_Richtext'],
		MAIL_SUITABLE_MODELS = ['Rbs_Mail_Mail'];

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
	function positionBlockSettingsEditor(blockEl) {
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

	function closeBlockSettingsEditor() {
		blockPropertiesPopup.hide();
		blockPropertiesPopupShown = false;
	}

	//-------------------------------------------------------------------------
	// rbs-structure-editor
	// Structure editor.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsStructureEditor',
		['$timeout', '$compile', 'RbsChange.Workspace', 'RbsChange.MainMenu', 'structureEditorService', 'RbsChange.ArrayUtils',
			'RbsChange.Utils', 'RbsChange.NotificationCenter', 'RbsChange.Navigation',
			function ($timeout, $compile, Workspace, MainMenu, structureEditorService, ArrayUtils, Utils, NotificationCenter, Navigation) {
				return {
					"restrict": 'E',
					"require": ['ngModel', 'rbsStructureEditor'],
					"scope": true,
					"transclude": true,
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
					"controller": function ($scope, $element, $attrs) {
						var selectedBlock = null,
							selectedBlockId = -1,
							self = this,
							draggedEl, containerOfDraggedEl,
							dropTarget, dropPosition = -1,
							lastIndicatorY = 0;

						this.isReadOnly = function () {
							return $attrs.readonly === 'true';
						};

						$scope.readOnly = this.isReadOnly();

						function initContextData() {
							var currentContext = Navigation.getCurrentContext();
							if (currentContext) {
								var data = currentContext.savedData('pageEditor');
								if (angular.isObject(data)) {
									$scope.contextData = data;
									selectedBlockId = data.blockId;
								}
							}
							return null;
						}

						//Init from context
						initContextData();

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

							if (!blockPropertiesPopupShown || shouldUpdate) {
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

						this.blockById = function (blockId) {
							if (blockId === undefined) {
								return selectedBlockId;
							} else {
								selectedBlockId = blockId;
								this.reselectBlock();
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
							var html;
							var item = this.getItemById(blockEl.data('id'));

							if (item === null) {
								blockPropertiesPopup = $('#rbsStructureEditorBlockPropertiesPopup');
								blockPropertiesPopup.html(blockPropertiesPopup.attr('data-no-block-text'));
								blockPropertiesPopup.attr('data-title', '');
								positionBlockSettingsEditor(blockEl);
								return;
							}
							if (blockEl.is('[rbs-row]')) {
								html = '<div rbs-row-settings="" data-id="' + blockEl.data('id') + '"';
							}
							else {
								var isMailSuitable = MAIL_SUITABLE_MODELS.indexOf($scope.document.model) > -1;
								html = '<div rbs-block-settings-editor="" data-id="' + blockEl.data('id') + '" data-label="' +
									item.label + '" mail-suitable="' + isMailSuitable + '"';
							}
							angular.forEach(params, function (value, name) {
								html += ' data-' + name + '="' + value + '"';
							});
							html += '></div>';

							var blockScope = blockEl.isolateScope() || blockEl.scope();
							blockScope.editorCtrl = this;

							blockPropertiesPopup = $('#rbsStructureEditorBlockPropertiesPopup');
							blockPropertiesPopup.html(html);
							blockPropertiesPopup.attr('data-title',
								RICH_TEXT_BLOCK_NAMES.indexOf(item.name) > -1 ? '' : item.label);
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

							} else {
								if (item.type === 'row') {

									item.grid = item.grid || DEFAULT_GRID_SIZE;
									if (!item.items || item.items.length === 0) {
										itemCell = this.registerNewItem("cell", {'size': DEFAULT_GRID_SIZE});
										item.items = [ itemCell ];
										itemChooser = this.registerNewItem("block-chooser");
										itemCell.items = [ itemChooser ];
									}

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
							this.selectBlock(this.createBlock(selectedBlock.parent(), null, selectedBlock.index() + 1));
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
								throw new Error("Could not register item " + item.id +
									": another item is registered with the same ID (" + $scope.items[item.id].type + ").");
							}

							// Add substitution variables if it's a rich text block.
							if (item.type === 'block'
									&& (item.name === 'Rbs_Mail_Richtext' || item.name === 'Rbs_Website_Richtext')) {
								item.substitutionVariables = $attrs.substitutionVariables;
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
							var block = this.getSelectedBlock(),
								item = $scope.items[block.data('id')],
								newLeftCellItem, newRightCellItem, newBlockChooserItem;

							if (angular.isFunction(block.scope().saveItem)) {
								block.scope().saveItem(item);
							}

							// Create left cell.
							newLeftCellItem = this.registerNewItem("cell", {'size': DEFAULT_GRID_SIZE / 2});

							// Create right cell.
							newRightCellItem = this.registerNewItem("cell", {'size': DEFAULT_GRID_SIZE / 2});

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
								'type': 'row',
								'items': [ newLeftCellItem, newRightCellItem ]
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
							var id = block.data('id'),
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
								this.selectBlock(this.createBlock(parent, { 'type': 'block-chooser' }));
							}
						};

						// Utility functions -----------------------------------------------------------------------------------

						this.isInColumnLayout = function (el) {
							return el.closest('[rbs-cell]').length === 1;
						};

						this.isRichText = function (el) {
							return el.is('[rbs-block-markdown-text]');
						};

						this.selectParentRow = function (block) {
							this.selectBlock(
								block.closest('[rbs-row]'),
								{ 'highlight-column': block.closest('[rbs-cell]').index() }
							);
						};

						function isContainer(block) {
							return block.is('[rbs-row]') || block.is('[rbs-cell]');
						}

						function parseEditableZone(zoneEl, output) {
							var id = zoneEl.data('id'), zoneItem;
							zoneItem = {
								'id': id,
								'grid': zoneEl.data('grid'),
								'type': 'container',
								'items': []
							};
							output[id] = zoneItem;

							parseChildBlocks(zoneEl, zoneItem.items);
						}

						function parseChildBlocks(parentBlockEl, parentItems) {
							parentBlockEl.children('[data-id]').each(function (index, block) {
								var blockEl = $(block),
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
								var output = $scope.generateJSON(),
									item = self.getItemById(element.data('id'));
								$scope.undoData.unshift({
									'id': undoDataItemId++,
									'label': (operation + ' ' + elementName),
									'item': item,
									'data': output,
									'date': new Date(),
									'icon': operationIcons[operation]
								});
							});
						};

						// Drag'n'drop -----------------------------------------------------------------------------------------

						// Draggable elements

						if (!this.isReadOnly()) {
							$($element).on({
								'dragstart': function (event) {
									draggedEl = $(this).closest('.block-draggable');
									draggedEl.addClass('dragged');
									containerOfDraggedEl = draggedEl.parent();

									event.originalEvent.dataTransfer.setData('Text', draggedEl.data('id'));
									event.originalEvent.dataTransfer.effectAllowed = 'copyMove';
								},

								'dragend': function () {
									draggedEl.removeClass('dragged');
								}

							}, '.block-handle');

							// Droppable elements

							$($element).on({
								'dragenter': function (event) {
									event.preventDefault();
									event.stopPropagation();

									if (event.originalEvent.dataTransfer.getData('Text') !== $(this).data('id')) {
										structureEditorService.highlightDropTarget($(this));
									}
								},

								'dragleave': function (event) {
									event.preventDefault();
									event.stopPropagation();

									structureEditorService.unhighlightDropTarget($(this));
									dropZoneIndicator.hide();
								},

								'dragover': function (event) {
									event.preventDefault();
									event.stopPropagation();

									if (event.originalEvent.dataTransfer.getData('Text') !== $(this).data('id')) {
										var mouseY = event.originalEvent.pageY,
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
										for (i = 0; i < dropTarget.children().length && dropPosition === -1; i++) {
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
										} else {
											if (sameParent && dropPosition > draggedEl.index()) {
												finalDropPosition--;
											}
										}

										if (lastIndicatorY !== indicatorY) {
											dropZoneIndicator.find('.content').html(finalDropPosition + 1);
											dropZoneIndicator.css({
												'left': (dropTarget.offset().left - dropZoneIndicator.outerWidth() - 2) + 'px',
												'top': (indicatorY - dropZoneIndicator.outerHeight() / 2) + 'px'
											}).show();
											lastIndicatorY = indicatorY;
										}
									}
								},

								'drop': function (event) {
									event.preventDefault();
									event.stopPropagation();

									dropZoneIndicator.hide();

									if (containerOfDraggedEl.data('id') !== $(this).data('id') ||
										draggedEl.index() !== dropPosition) {
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

										self.notifyChange("move", "block", draggedEl,
											{'from': containerOfDraggedEl, 'to': dropTarget});
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
							}, '[rbs-row]');
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
					"link": function seLinkFn(scope, elm, attrs, ctrls) {
						var ngModel = ctrls[0],
							ctrl = ctrls[1],
							contentReady = false,
							templateInfo,
							originalValue,
							substitutionVariables,
							pendingBlockPropertySetter = null;

						scope.$on('Navigation.saveContext', function (event, args) {
							var data = {undoData: scope.undoData, blockId: ctrl.blockById(), originalValue: originalValue};
							args.context.savedData('pageEditor', data);
						});

						if (scope.contextData) {
							originalValue = scope.contextData.originalValue;
							scope.undoData = scope.contextData.undoData;
							scope.contextData = null;
						}

						function getDefaultEmptyContent() {
							var content = {}, blockId = 0;
							elm.find('[data-editable-zone-id]').each(function (index, el) {
								var zone = $(el),
									zoneId = zone.attr('data-editable-zone-id');

								content[zoneId] = {
									"id": zoneId,
									"grid": DEFAULT_GRID_SIZE,
									"type": "container",
									"items": [
										{
											"id": ++blockId,
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

						function layoutLoaded() {
							return elm.find('.structure-editor').children().length > 0;
						}

						attrs.$observe('template', function (template) {
							if (template) {
								templateInfo = JSON.parse(template);
								elm.find('.structure-editor').html(templateInfo.html);
								ngModel.$render();
							}
						});

						attrs.$observe('substitutionVariables', function (value) {
							if (value) {
								substitutionVariables = value;
							}
						});

						function setBlockProperty(blockId, propertyName, value) {
							var item = ctrl.getItemById(blockId);
							if (item) {
								item.parameters[propertyName] = Utils.isDocument(value) ? value.id : value;
								ctrl.selectBlock(ctrl.getBlockByItem(item));
							}
						}

						function consumePendingBlockPropertySetter() {
							if (pendingBlockPropertySetter) {
								setBlockProperty(pendingBlockPropertySetter.blockId, pendingBlockPropertySetter.property,
									pendingBlockPropertySetter.value);
								pendingBlockPropertySetter = null;
							}
						}

						// Respond to 'Change:StructureEditor.setBlockParameter' event to set a property on a block.
						scope.$on('Change:StructureEditor.setBlockParameter', function (event, args) {
							// If content is ready, set the block property now.
							if (contentReady) {
								setBlockProperty(args.blockId, args.property, args.value);
							}
							// Otherwise, keep these information to apply them later, when content is ready
							// (see end of ngModel.$render() method below).
							else {
								pendingBlockPropertySetter = angular.copy(args);
							}
						});

						scope.undo = function (index) {
							closeBlockSettingsEditor();
							ngModel.$setViewValue(index < (scope.undoData.length - 1) ? scope.undoData[index +
								1].data : originalValue);
							ngModel.$render();
							ArrayUtils.remove(scope.undoData, 0, index);
						};

						// Specify how UI should be updated
						ngModel.$render = function () {
							var pageContent,
								newZones = [];

							// Parse all the hierarchical items and put them in a flat, ID-indexed array.
							function registerItems(container) {
								if (container.items) {
									angular.forEach(container.items, function (item) {
										scope.items[item.id] = item;
										scope.blockIdCounter = Math.max(item.id, scope.blockIdCounter);
										registerItems(item);
									});
								}
								//if the container/item is a mail rich text block, give it variable substitutions
								if (container.type === 'block' && container.name === 'Rbs_Mail_Richtext') {
									container.substitutionVariables = substitutionVariables;
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
								angular.forEach(templateInfo.data, function (tplZone) {
									var zone, editableZone;

									// Check if the zone exists in the page.
									zone = pageContent[tplZone.id];
									if (zone) {
										editableZone = $(elm).find('[data-editable-zone-id="' + tplZone.id + '"]');
										if (editableZone.length) {
											if (!zone.hasOwnProperty('items') || zone.items.length == 0) {
												zone.items = [
													{
														"id": scope.getNextBlockId(),
														"type": "block-chooser"
													}
												];
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
											NotificationCenter.error("Bad template configuration",
												"Could not find editable zone '" + zone.id + "' in page template.");
										}
									} else {
										if (tplZone.type === 'container') {
											// Store the zones that are not found in the page to add them later.
											newZones.push(angular.copy(tplZone));
										}

									}
								});

								angular.forEach(newZones, function (zone) {
									var editableZone = $(elm).find('[data-editable-zone-id="' + zone.id + '"]');

									if (editableZone.length) {
										zone.items = [
											{
												"id": scope.getNextBlockId(),
												"type": "block-chooser"
											}
										];

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

								contentReady = true;

								consumePendingBlockPropertySetter();
								ctrl.reselectBlock();
								resizeHandler();
							}
						};

						scope.contentChanged = function (newContent, isValid) {
							ngModel.$setViewValue(newContent);
							ngModel.$setValidity("content", isValid);
						};

						// Resize handler --------------------------------------------------------------------------------------

						function resizeHandler() {
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
	// rbs-structure-viewer
	// Structure viewer
	//
	//-------------------------------------------------------------------------
	app.directive('rbsStructureViewer', function () {
		return {
			'restrict': 'E',
			'template': '<div ng-repeat="zone in zones">' +
				'<h4>(= zone.id =)</h4>' +
				'<pre ng-repeat="block in zone.blocks">(= block.parameters.content =)</pre>' +
				'</div>',
			'scope': {
				'content': '='
			},

			'link': function structureViewerLink(scope, iElement, iAttrs) {
				function findBlocks(container, blocks) {
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