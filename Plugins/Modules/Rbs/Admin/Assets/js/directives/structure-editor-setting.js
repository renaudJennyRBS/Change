(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		highlightMargin = 2, highlightBorder = 5,
		RICH_TEXT_BLOCK_NAMES = ['Rbs_Website_Richtext', 'Rbs_Mail_Richtext'],
		DEFAULT_GRID_SIZE = 12;

	//-------------------------------------------------------------------------
	// rbs-block-settings-editor
	// Block settings editor.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsBlockSettingsEditor',
		['structureEditorService', 'RbsChange.Workspace', 'RbsChange.ArrayUtils', 'RbsChange.Utils', 'RbsChange.REST',
			'$rootScope', 'RbsChange.Dialog', '$timeout', '$http', '$compile', 'RbsChange.i18n', '$templateCache',
			function (structureEditorService, Workspace, ArrayUtils, Utils, REST, $rootScope, Dialog, $timeout, $http, $compile, i18n, $templateCache) {
				return {
					"restrict": 'A',
					"transclude": true,
					"scope": true,
					"templateUrl": 'Rbs/Admin/js/directives/structure-editor-block-settings.twig',

					"link": function (scope, element, attrs) {
						var ctrl = scope.editorCtrl;
						scope.isMailSuitable = attrs.mailSuitable || false;

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
									angular.forEach(blockInfo.parameters, function (parameter) {
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

						function replaceItem(item) {
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
								$timeout(function () {
									ctrl.selectBlock(block);
								});
							}
						}

						scope.$watch('blockType', function (blockType, old) {
							if (blockType && blockType !== old) {
								if (RICH_TEXT_BLOCK_NAMES.indexOf(blockType.name) > -1) {
									onBlockTypeChanged(blockType);
								}
								else {
									$http.get(blockType.template, {cache: $templateCache}).success(function (html) {
										html = $(html);
										html.find('rbs-document-picker-single')
											.attr('data-navigation-block-id', scope.block.id)
											.each(function () {
												var el = $(this);
												el.attr('property-label',
													el.attr('property-label') + ' (' + scope.block.label + ')');
											});

										$compile(html)(scope, function (clone) {
											element.find('[data-role="blockParametersContainer"]').append(clone);
											onBlockTypeChanged(blockType);
										});
									});
								}
							}
						}, true);

						// Block TTL options ------------------------------------------

						scope.hasTTL = function (seconds) {
							return scope.blockParameters.TTL == seconds;
						};

						scope.setTTL = function (seconds) {
							scope.blockParameters.TTL = seconds;
						};

						// Block visibility options -----------------------------------

						scope.isVisibleFor = function (device) {
							if (!scope.block) {
								return false;
							}
							if (device == 'raw') {
								return (scope.block.visibility == 'raw');
							} else {
								if (scope.block.visibility == 'raw') {
									return false;
								}
							}
							return (!scope.block.visibility || scope.block.visibility.indexOf(device) !== -1);
						};

						scope.toggleVisibility = function (device) {
							var value = !scope.isVisibleFor(device),
								splat,
								block,
								originalValue = '' + scope.block.visibility;

							if (device == 'raw') {
								if (value) {
									scope.block.visibility = device;
								} else {
									delete scope.block.visibility;
								}
							} else {
								if (scope.block.visibility == 'raw') {
									delete scope.block.visibility;
								} else {
									if (scope.block.visibility) {
										splat = scope.block.visibility.split('');
										if (ArrayUtils.inArray(device, splat) !== -1 && !value) {
											ArrayUtils.removeValue(splat, device);
										} else {
											if (ArrayUtils.inArray(device, splat) === -1 && value) {
												splat.push(device);
											}
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
							}

							block = ctrl.getSelectedBlock();
							block.attr('data-visibility', scope.block.visibility);
							ctrl.notifyChange("visibility", "block", block,
								{'from': originalValue, 'to': scope.block.visibility});
						};

						scope.canInsertSideways = function () {
							var block = ctrl.getSelectedBlock();
							return !block.is('[rbs-row]') && !ctrl.isInColumnLayout(block);
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
							if (block.attr('rbs-block-chooser')) {
								ctrl.removeBlock(block);
								ctrl.notifyChange("remove", "block", block);
							}
							else {
								Dialog.confirmLocal(
									block,
									i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block | ucf'),
									"<strong>" + i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block_confirm | ucf') +
										"</strong>",
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
	// rbs-block-selector
	// Block selector.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsBlockSelector', ['RbsChange.REST', function (REST) {
		var blockList = [], loading = false, blockListType;

		function loadBlockList(isMailSuitable) {
			// mail and page share the same blockList, it's useless to load blockList again if it's for the same type
			// check if there is already something in the blockList for the right type
			var alreadyLoaded = blockList.length &&
				((blockListType === 'Mail' && isMailSuitable) || (blockListType === 'Page' && !isMailSuitable));
			if (!loading && !alreadyLoaded) {
				blockList = [];
				loading = true;
				REST.call(REST.getBaseUrl('admin/blockList/'), {isMailSuitable: isMailSuitable}).then(function (blockData) {
					angular.forEach(blockData, function (pluginBlocks, pluginLabel) {
						angular.forEach(pluginBlocks, function (block) {
							block.plugin = pluginLabel;
							blockList.push(block);
						});
					});
					loading = false;
					blockListType = isMailSuitable ? 'Mail' : 'Page';
				});
			}
			return blockList;
		}

		function getBlockByName(name) {
			for (var i = 0; i < blockList.length; i++) {
				if (blockList[i].name === name) {
					return blockList[i];
				}
			}
			return null;
		}

		return {
			restrict: 'E',
			template: '<select class="form-control" ng-model="block" ng-required="required" ng-options="block.label group by block.plugin for block in blocks"></select>',
			scope: { block: '=', selected: '@', required: '@' },

			link: function (scope, element, attrs) {
				attrs.$observe('mailSuitable', function (value) {
					if (value) {
						var isMailSuitable = value === 'true';
						scope.blocks = loadBlockList(isMailSuitable);
					}
				});

				function updateSelection() {
					if (scope.selected && scope.blocks.length > 0 && (!scope.block || scope.block.name !== scope.selected)) {
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
	// rbs-row-settings
	// Row settings.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsRowSettings', ['structureEditorService', '$timeout', 'RbsChange.Dialog', 'RbsChange.i18n',
		function (structureEditorService, $timeout, Dialog, i18n) {
			return {
				"restrict": 'A',
				"scope": true,
				templateUrl: 'Rbs/Admin/js/directives/structure-editor-row-settings.twig',

				"link": function seRowSettingsLinkFn(scope, elm, attrs) {
					var ctrl = scope.editorCtrl,
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
									"Col. " + (scope.highlightedColIndex + 1) + ' (<i class="icon-resize-horizontal"></i> ' +
										scope.columns[scope.highlightedColIndex].span + ")",
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

						structureEditorService.highlightZone(x, y, w, h,
							'<i class="icon-arrow-down"></i> <i class="icon-plus"></i> Col. (<i class="icon-resize-horizontal"></i> ' +
								newColSpan + ')');
					};

					scope.unhighlightNewColumn = function () {
						scope.highlightColumn(previouslyHighlightedColIndex);
						previouslyHighlightedColIndex = -1;
					};

					scope.setEqualColumns = function () {
						angular.forEach(scope.columns, function (column) {
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
							|| (scope.columns[index + 1] && scope.columns[index + 1].offset >= 1);
					};

					scope.moveColumnRight = function (index, $event) {
						if (index === scope.highlightedColIndex) {
							$event.stopPropagation();
						}

						var offset = 0,
							isLastCol = (index === scope.columns.length - 1);

						if (isLastCol && scope.totalColumns < gridSize) {
							offset = $event.altKey ? gridSize - scope.totalColumns : 1;
						} else {
							if (scope.columns[index + 1] && scope.columns[index + 1].offset >= 1) {
								offset = $event.altKey ? scope.columns[index + 1].offset : 1;
								scope.columns[index + 1].offset -= offset;
							} else {
								if (scope.totalColumns < gridSize) {
									offset = $event.altKey ? gridSize - scope.totalColumns : 1;
								}
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
							|| (scope.columns[index + 1] && scope.columns[index + 1].offset >= 1)
							|| (scope.columns[index].offset >= 1);
					};

					scope.expandColumn = function (index, $event) {
						if (index === scope.highlightedColIndex) {
							$event.stopPropagation();
						}

						var expandSize = 0,
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
							} else {
								if (column.offset >= 1) {
									// Can we reduce the offset of the column?
									offsetToRemove = 1;
								} else {
									if (nextColumn.offset >= 1) {
										// Can we reduce the offset of the next column?
										nextOffsetToRemove = 1;
									}
								}
							}
						}

						if (offsetToRemove) {
							scope.columns[index].offset -= offsetToRemove;
							expandSize += offsetToRemove;
						}
						if (nextOffsetToRemove) {
							scope.columns[index + 1].offset -= nextOffsetToRemove;
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
						if (scope.columns[index + 1]) {
							scope.columns[index + 1].offset++;
						}

						ctrl.notifyChange("resize", "column", rowEl,
							{'from': scope.columns[index].span + 1, 'to': scope.columns[index].span});
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
							$(rowEl.children().get(index)).removeClass("offset" +
								scope.columns[index].offset).removeAttr('data-offset');
						} else {
							if (scope.totalColumns < gridSize) {
								size = gridSize - scope.totalColumns;
							}
						}

						if (size) {
							ctrl.createBlock(
								rowEl,
								{
									'size': size,
									'offset': 0,
									'type': 'cell'
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
							i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_column_confirm | ucf',
								{CHILDCOUNT: scope.columns[index].childCount}),
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
								angular.forEach(columns, function (col) {
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
							"<strong>" + i18n.trans('m.rbs.admin.adminjs.structure_editor_remove_block_group_confirm | ucf') +
								"</strong>",
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

})(window.jQuery);