(function($, rangy) {
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsWysiwygEditor',
		['$timeout', '$document', '$q', '$rootScope', '$compile', 'RbsChange.i18n', 'RbsChange.Utils',
			function($timeout, $document, $q, $rootScope, $compile, i18n, Utils) {
				var tools = {
					h1: {
						display: "H1",
						title: i18n.trans('m.rbs.admin.admin.heading_level_1|ucf'),
						block: true
					},
					h2: {
						display: "H2",
						title: i18n.trans('m.rbs.admin.admin.heading_level_2|ucf'),
						block: true
					},
					h3: {
						display: "H3",
						title: i18n.trans('m.rbs.admin.admin.heading_level_3|ucf'),
						block: true
					},
					h4: {
						display: "H4",
						title: i18n.trans('m.rbs.admin.admin.heading_level_4|ucf'),
						block: true
					},
					h5: {
						display: "H5",
						title: i18n.trans('m.rbs.admin.admin.heading_level_5|ucf'),
						block: true
					},
					h6: {
						display: "H6",
						title: i18n.trans('m.rbs.admin.admin.heading_level_6|ucf'),
						block: true
					},
					p: {
						display: "¶",
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_paragraph|ucf'),
						block: true
					},
					pre: {
						display: "pre",
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_pre|ucf'),
						block: true
					},
					blockquote: {
						display: '<i class="icon-quote-right"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_quote|ucf'),
						block: true
					},
					insertUnorderedList: {
						display: '<i class="icon-list-ul"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_unordered_list|ucf')
					},
					insertOrderedList: {
						display: '<i class="icon-list-ol"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_ordered_list|ucf')
					},
					undo: {
						display: '<i class="icon-undo"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_undo|ucf')
					},
					redo: {
						display: '<i class="icon-repeat"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_redo|ucf')
					},
					bold: {
						display: '<i class="icon-bold"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_bold|ucf')
					},
					justifyLeft: {
						display: '<i class="icon-align-left"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_align_left|ucf')
					},
					justifyRight: {
						display: '<i class="icon-align-right"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_align_right|ucf')
					},
					justifyCenter: {
						display: '<i class="icon-align-center"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_align_center|ucf')
					},
					italic: {
						display: '<i class="icon-italic"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_italic|ucf')
					},
					underline: {
						display: '<i class="icon-underline"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_underline|ucf')
					},
					removeFormat: {
						display: '<i class="icon-ban-circle"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_clear|ucf')
					},
					insertImage: {
						display: '<i class="icon-picture"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_insert_picture|ucf'),
						action: function(scope) {
							scope.selectMediasToInsert();
						}
					},
					insertExternalLink: {
						display: '<i class="icon-external-link"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_insert_external_link|ucf'),
						action: function(scope) {
							var urlLink = window.prompt(i18n.trans('m.rbs.admin.adminjs.richtext_enter_external_link'),
								'http://');
							if (urlLink) {
								scope.wrapSelection('createLink', urlLink);
							}
						}
					},
					insertLink: {
						display: '<i class="icon-link"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_insert_link|ucf'),
						action: function(scope) {
							scope.selectDocumentsToLink();
						}
					},
					unlink: {
						display: '<i class="icon-unlink"></i>',
						title: i18n.trans('m.rbs.admin.admin.wysiwyg_remove_link|ucf')
					}
				};

				return {
					restrict: 'E',
					require: 'ngModel',
					scope: true,
					templateUrl: 'Rbs/Admin/js/directives/wysiwyg-editor.twig',

					link: function(scope, element, attrs, ngModel) {
						var editableEl = element.find('[contenteditable]'),
							sourceEl = element.find('textarea');

						scope.contextKey = attrs.contextKey;
						scope.draggable = attrs.draggable === 'true';
						scope.tools = angular.copy(tools);
						scope.toolbarConfig = [
							{
								/* This group is hard-coded in template but must be here for 'active' property refresh */
								tools: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'blockquote', 'pre']
							},
							{
								label: i18n.trans('m.rbs.admin.admin.wysiwyg_lists|ucf'),
								tools: ['insertUnorderedList', 'insertOrderedList']
							},
							{
								label: i18n.trans('m.rbs.admin.admin.wysiwyg_format|ucf'),
								tools: ['bold', 'italic', 'underline', 'removeFormat']
							},
							{
								label: i18n.trans('m.rbs.admin.admin.wysiwyg_alignment|ucf'),
								tools: ['justifyLeft', 'justifyCenter', 'justifyRight']
							},
							{
								label: i18n.trans('m.rbs.admin.admin.wysiwyg_insertion|ucf'),
								tools: ['insertImage', 'insertLink', 'insertExternalLink', 'unlink']
							},
							{
								tools: ['insertImage', 'insertLink', 'insertExternalLink', 'unlink']
							}
						];

						scope.getButtonLabel = function(item) {
							return scope.tools.hasOwnProperty(item) ? scope.tools[item].display : item;
						};

						scope.getButtonTooltip = function(item) {
							return scope.tools.hasOwnProperty(item) ? scope.tools[item].title : '';
						};

						scope.toolIsActive = function(item) {
							return scope.tools.hasOwnProperty(item) ? (scope.tools[item].active || false) : false;
						};

						scope.runTool = function(toolId) {
							var tool = scope.tools[toolId];
							if (angular.isFunction(tool.action)) {
								tool.action(scope);
							}
							else {
								if (tool.block) {
									scope.wrapSelection("formatBlock", '<' + toolId.toUpperCase() + '>');
								}
								else {
									scope.wrapSelection(toolId);
								}
							}
							updateSelectedStyles();
						};

						scope.wrapSelection = function wrapSelection(command, options) {
							$document[0].execCommand(command, false, options || null);
						};

						scope.queryState = function queryState(toolId) {
							var tool = scope.tools[toolId];
							if (tool && tool.block) {
								return $document[0].queryCommandValue('formatBlock').toLowerCase() === toolId.toLowerCase();
							}
							else if ($document[0].queryCommandSupported(toolId)) {
								return $document[0].queryCommandState(toolId);
							}
							return null;
						};

						scope.selectDocumentsToLink = function() {
							var range = rangy.saveSelection();
							scope.$emit('WYSIWYG.SelectDocumentsToLink', {
								range: range,
								contents: editableEl.html()
							});
						};

						scope.selectMediasToInsert = function() {
							var range = rangy.saveSelection();
							scope.$emit('WYSIWYG.SelectMediasToInsert', {
								range: range,
								contents: editableEl.html()
							});
						};

						scope.$on('WYSIWYG.InsertLink', function(event, data) {
							editableEl.html(data.contents);
							refocus().then(function() {
								rangy.restoreSelection(data.range);

								// Whole an element.
								if (data.html.trim().charAt(0) === '<') {
									scope.wrapSelection('insertHTML', data.html);
								}
								// URL.
								else {
									scope.wrapSelection('createLink', data.html);
								}
							});
						});

						scope.$on('WYSIWYG.InsertImage', function(event, data) {
							editableEl.html(data.contents);
							refocus().then(function() {
								rangy.restoreSelection(data.range);
								scope.wrapSelection('insertHTML', data.html);
							});
						});

						scope.sourceView = false;

						scope.toggleViewSource = function() {
							if (scope.sourceView) {
								scope.sourceView = false;
								updateNgModel();
								commitNgModel();
								refocus();
							}
							else {
								scope.sourceView = true;
								updateSourceView(editableEl.html());
								refocus();
							}
						};

						// view -> model
						editableEl.on('input', function() {
							updateNgModel();
							commitNgModel();
						});

						function updateNgModel() {
							var html = editableEl.html();
							ngModel.$setViewValue(html);
							if (html === '') {
								// the cursor disappears if the contents is empty
								// so we need to refocus
								refocus(editableEl);
							}
						}

						function refocus(el) {
							if (!el) {
								el = scope.sourceView ? sourceEl : editableEl;
							}
							return $timeout(function() {
								el[0].blur();
								el[0].focus();
							}, 100);
						}

						// model -> view
						var oldRender = ngModel.$render;
						ngModel.$render = function() {
							if (!!oldRender) {
								oldRender();
							}
							editableEl.html(ngModel.$viewValue || '');
						};

						// Select non-editable elements.
						editableEl.bind('click', function(e) {
							var range, sel, target;
							target = e.toElement;
							if (target !== this && angular.element(target).attr('contenteditable') === 'false') {
								range = document.createRange();
								sel = window.getSelection();
								range.setStartBefore(target);
								range.setEndAfter(target);
								sel.removeAllRanges();
								sel.addRange(range);
							}
						});

						// Watch changes in the HTML source view to update the WYSIWYG view.
						scope.$watch('source', function(source) {
							if (angular.isDefined(source)) {
								// Remove new line characters after each block tag.
								editableEl.html(source.replace(/<\/(p|h[1-6]|pre|ul|ol|blockquote)>\n+/g, '</$1>'));
							}
						});

						function updateSourceView(html) {
							// Add new line characters after each block tag.
							scope.source = html.replace(/<\/(p|h[1-6]|pre|ul|ol|blockquote)>/g, "</$1>\n\n");
						}

						editableEl.on('keydown', updateSelectedStyles);
						editableEl.on('keyup', updateSelectedStyles);
						editableEl.on('mouseup', updateSelectedStyles);

						editableEl.on('dblclick', 'a', openLinkSettings);
						editableEl.on('dblclick', 'img[data-document-id]', openImageSettings);
						editableEl.on('dblclick', 'video[data-document-id]', openVideoSettings);

						var dialog = $('#rbs-rich-text-dialog');

						// Image.
						function openImageSettings(event) {
							var img = $(event.target);
							scope.imageToEdit = img;
							var width = parseInt(img.attr('width'), 10);
							if (isNaN(width) || width < 1) {
								width = null;
							}
							var height = parseInt(img.attr('height'), 10);
							if (isNaN(height) || height < 1) {
								height = null;
							}
							scope.imageData = {
								documentId: img.attr('data-document-id'),
								title: img.attr('title'),
								alt: img.attr('alt'),
								width: width,
								height: height
							};

							var dialogContent = '<div data-rbs-rich-text-dialog-image=""' +
								' data-properties="imageData" data-on-submit="updateImage"></div>';
							$compile(dialogContent)(scope, function(element) {
								dialog.find('.modal-content').empty().append(element);
								dialog.modal();
							});
						}

						scope.updateImage = function updateImage() {
							var width = parseInt(scope.imageData.width, 10);
							if (isNaN(width) || width < 1) {
								width = null;
							}
							var height = parseInt(scope.imageData.height, 10);
							if (isNaN(height) || height < 1) {
								height = null;
							}
							scope.imageToEdit.attr('alt', scope.imageData.alt);
							scope.imageToEdit.attr('title', scope.imageData.title);
							scope.imageToEdit.attr('width', width);
							scope.imageToEdit.attr('height', height);
							scope.imageToEdit.attr('src',
								Utils.makeUrl(scope.imageToEdit.attr('src'), { maxWidth: width, maxHeight: height }));
							dialog.modal('hide');
							updateNgModel();
							commitNgModel();
						};

						// Video.
						function openVideoSettings(event) {
							event.preventDefault();
							var video = $(event.target);
							scope.videoToEdit = video;
							var width = parseInt(video.attr('width'), 10);
							if (isNaN(width) || width < 1) {
								width = null;
							}
							var height = parseInt(video.attr('height'), 10);
							if (isNaN(height) || height < 1) {
								height = null;
							}
							scope.videoData = {
								documentId: video.attr('data-document-id'),
								title: video.attr('title'),
								width: width,
								height: height
							};

							var dialogContent = '<div data-rbs-rich-text-dialog-video=""' +
								' data-properties="videoData" data-on-submit="updateVideo"></div>';
							$compile(dialogContent)(scope, function(element) {
								dialog.find('.modal-content').empty().append(element);
								dialog.modal();
							});
						}

						scope.updateVideo = function updateVideo() {
							var width = parseInt(scope.videoData.width, 10);
							if (isNaN(width) || width < 1) {
								width = null;
							}
							var height = parseInt(scope.videoData.height, 10);
							if (isNaN(height) || height < 1) {
								height = null;
							}
							scope.videoToEdit.attr('title', scope.videoData.title);
							scope.videoToEdit.attr('width', width);
							scope.videoToEdit.attr('height', height);
							dialog.modal('hide');
							updateNgModel();
							commitNgModel();
						};

						// Links.
						function openLinkSettings(event) {
							var dialogContent;
							var link = $(event.target);
							scope.linkToEdit = link;
							scope.linkData = {
								href: link.attr('href'),
								title: link.attr('title'),
								text: link.html()
							};

							// Document link case.
							if (link.attr('data-document-id')) {
								scope.linkData.documentId = link.attr('data-document-id');
								dialogContent = '<div data-rbs-rich-text-dialog-document-link=""' +
									' data-properties="linkData" data-on-submit="updateLink"></div>';
							}
							// External link case.
							else {
								dialogContent = '<div data-rbs-rich-text-dialog-external-link=""' +
									' data-properties="linkData" data-on-submit="updateLink"></div>';
							}

							$compile(dialogContent)(scope, function(element) {
								dialog.find('.modal-content').empty().append(element);
								dialog.modal();
							});
						}

						scope.updateLink = function updateLink() {
							scope.linkToEdit.attr('href', scope.linkData.href);
							scope.linkToEdit.attr('title', scope.linkData.title);
							scope.linkToEdit.html(scope.linkData.text);
							dialog.modal('hide');
							updateNgModel();
							commitNgModel();
						};

						// Updates buttons active state.
						function updateSelectedStyles() {
							angular.forEach(scope.toolbarConfig, function(group) {
								angular.forEach(group.tools, function(toolId) {
									var tool = scope.tools[toolId];
									if (angular.isFunction(tool.activeState)) {
										tool.active = tool.activeState(scope);
									}
									else {
										tool.active = scope.queryState(toolId);
									}
								});
							});
							commitNgModel();
						}

						// Calls Angular's digest cycle if needed.
						function commitNgModel() {
							if (!$rootScope.$$phase) {
								scope.$apply();
							}
						}
					}
				};
			}]);
})(window.jQuery, rangy);