(function($, ace) {
	"use strict";

	var app = angular.module('RbsChange'),
		MIN_HEIGHT = 150,
		editorIdCounter = 0;

	/**
	 * RichText input field.
	 * Attributes:
	 *  - use-tabs
	 *  - draggable
	 *  - profile
	 *  - substitution-variables
	 *  - id
	 *  - input-id
	 */
	app.directive('rbsRichTextInput',
		['$timeout', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Device', '$compile', 'RbsChange.i18n',
			'RbsChange.Navigation', 'RbsChange.UrlManager', 'RbsChange.ArrayUtils',
			function($timeout, REST, Utils, Device, $compile, i18n, Navigation, UrlManager, ArrayUtils) {
				return {
					restrict: 'E',
					require: '?ngModel',
					scope: true,
					templateUrl: 'Rbs/Admin/js/directives/rich-text-input.twig',

					link: function(scope, element, attrs, ngModel) {
						var editor,
							session,
							$textarea,
							id,
							$previewEl,
							$editorTab,
							renderEditorValue = function() {}, // Will be overwritten later, depending on the editor mode.
							wysiwygInitialized = false,
							editorModeChosen = false;

						// Init the dialog.
						if (!document.getElementById('rbs-rich-text-dialog')) {
							$('<div class="modal fade" id="rbs-rich-text-dialog">' +
								'	<div class="modal-dialog modal-lg"><div class="modal-content"></div></div>' +
								'</div>'
							).appendTo('body');
						}

						scope.contextKey = attrs.contextKey ? attrs.contextKey : attrs.ngModel;

						function parseSubstitutionVariables(substitutions) {
							scope.substitutionVariables = null;
							if (angular.isString(substitutions) && substitutions != '') {
								substitutions = JSON.parse(substitutions);
							}
							if (angular.isArray(substitutions)) {
								if (substitutions.length) {
									scope.substitutionVariables = {};
									for (var i = 0; i < substitutions.length; i++) {
										var substitution = substitutions[i];
										if (angular.isString(substitution)) {
											scope.substitutionVariables[substitution] = substitution;
										}
									}
								}
							}
							else if (angular.isObject(substitutions)) {
								scope.substitutionVariables = substitutions;
							}
							scope.availableSelectors.substitutionVariables = (scope.substitutionVariables != null);
						}

						// Initialize ACE editor.
						function initWithAceEditor() {
							element.find('[data-role="rbs-ace-editor"]').attr('id', id);
							editor = ace.edit(id);
							session = editor.getSession();
							session.setMode('ace/mode/markdown');
							session.setUseWrapMode(true);
							session.setWrapLimitRange(null, null);
							session.setFoldStyle('manual');
							editor.setShowFoldWidgets(true);
							editor.renderer.setShowGutter(false);

							scope.readOnly = (element.attr('readonly') === 'true' || element.attr('readonly') === 'readonly');
							editor.setReadOnly(scope.readOnly);

							// If 'id' and 'input-id' attributes are found are equal, move this id to the real input field
							// so that the binding with the '<label/>' element works as expected.
							// (see Directives in 'Rbs/Admin/Assets/js/directives/form-fields.js').
							if (attrs.id && attrs.id === attrs.inputId) {
								element.find('textarea.ace_text-input').attr('id', attrs.id);
								element.removeAttr('id');
								element.removeAttr('input-id');
							}

							$editorTab = $('#rbsInputMarkdown' + scope.editorId + 'TabEditor');

							session.on('change', function() {
								$timeout(function() {
									scope.wysiwygContent = editor.getValue();
									if (angular.isObject(ngModel.$viewValue)) {
										ngModel.$viewValue.t = editor.getValue();
										ngModel.$setViewValue(ngModel.$viewValue);
									}
									else {
										ngModel.$setViewValue(editor.getValue());
									}
								});
							});

							editor.on("focus", function() {
								element.addClass('focused');
							});

							editor.on("blur", function() {
								element.removeClass('focused');
							});

							editor.on("drop", function(event) {
								event.stopPropagation();
								event.preventDefault();
							});

							renderEditorValue = function(v) {
								editor.setValue(v);
							};

							// Tabs and preview.
							$previewEl = element.find('div[data-role="preview-container"] .preview-content'),
								element.find('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
									if ($(e.target).data('role') === 'preview') {
										$previewEl.empty();
										scope.previewing = true;
										var params = {
											'profile': (attrs.profile || 'Website'),
											'editor': 'Markdown'
										};
										REST.postAction('renderRichText', getEditorContent(), params).then(function(data) {
											$previewEl.html(data.html);
											scope.previewing = false;
										});
									}
								});

							// Auto-height.
							heightUpdateFunction(id, editor);
							editor.getSession().on('change', function() {
								heightUpdateFunction(id, editor);
							});

							ngModel.$render();
						}

						// Initialize Textarea-based editor.
						function initWithTextarea() {
							$textarea = $('<textarea></textarea>');
							$('#' + id).append($textarea);

							// If 'id' and 'input-id' attributes are found and equal, move this id to the real input field
							// so that the binding with the '<label/>' element works as expected.
							// (see Directives in 'Rbs/Admin/Assets/js/directives/form-fields.js').
							if (attrs.id && attrs.id === attrs.inputId) {
								$textarea.attr('id', attrs.id);
								element.removeAttr('id');
								element.removeAttr('input-id');
							}

							$editorTab = $('#rbsInputMarkdown' + scope.editorId + 'TabEditor');

							$textarea.on('change', function() {
								scope.$apply(function() {
									if (angular.isObject(ngModel.$viewValue)) {
										ngModel.$viewValue.t = $textarea.val();
										ngModel.$setViewValue(ngModel.$viewValue);
									}
									else {
										ngModel.$setViewValue($textarea.val());
									}
								});
							});

							$textarea.on("focus", function() {
								element.addClass('focused');
							});

							$textarea.on("blur", function() {
								element.removeClass('focused');
							});

							$textarea.on("drop", function(event) {
								event.stopPropagation();
								event.preventDefault();
							});

							renderEditorValue = function(v) {
								$textarea.val(v);
							};

							ngModel.$render();
						}

						ngModel.$render = function() {
							if (angular.isDefined(ngModel.$viewValue)) {
								if (angular.isObject(ngModel.$viewValue)) {
									// If there is already a content, the editor mode is chosen.
									if (ngModel.$viewValue.t || editorModeChosen) {
										scope.editorMode = ngModel.$viewValue.e;

										if (shouldInitEditors()) {
											if (scope.editorMode === 'Markdown') {
												initMarkdownEditor(ngModel.$viewValue.t);
											}
											else if (scope.editorMode === 'Html') {
												initWysiwygEditor(ngModel.$viewValue.t);
											}
										}
									}

									// Restore selectionMode.
									restoreSelectionContext();
								}
							}
						};

						function shouldInitEditors() {
							return !editor && !$textarea && !wysiwygInitialized;
						}

						scope.chooseEditorMode = function(editorMode) {
							editorModeChosen = true;
							ngModel.$setViewValue({e: editorMode, t: '', h: null});
							ngModel.$render();
						};

						var currentContext = Navigation.getCurrentContext();

						function getContextValueKey() {
							return attrs.contextKey ? attrs.contextKey : attrs.ngModel;
						}

						function applyContextValueMarkdown(contextValue, range) {
							$timeout(function() {
								// Position cursor where it was before the select session:
								if (range) {
									editor.navigateTo(range.row, range.column);
								}
							});
						}

						function initMarkdownEditor(value) {
							$timeout(function() {

								// Initialize editor (Markdown or simple textarea).
								if (!editor && !$textarea) {
									if (canUseAceEditor()) {
										initWithAceEditor();
									}
									else {
										initWithTextarea();
									}
								}
								renderEditorValue(value);

								// Apply navigation context if there is one.
								if (currentContext) {
									var contextValue = currentContext.getSelectionValue(getContextValueKey());
									if (contextValue !== undefined) {
										applyContextValueMarkdown(contextValue, currentContext.param('range'));
									}
								}

							});
						}

						scope.wysiwyg = {};

						function canUseAceEditor() {
							return !Device.isMultiTouch();
						}

						function getEditorContent() {
							if (canUseAceEditor()) {
								return editor.getValue();
							}
							else {
								return $textarea.val();
							}
						}

						// Auto-height.
						function heightUpdateFunction(id, editor) {
							// http://stackoverflow.com/questions/11584061/
							var newHeight =
								editor.getSession().getScreenLength() * editor.renderer.lineHeight +
									editor.renderer.scrollBar.getWidth();
							newHeight = Math.max(MIN_HEIGHT, newHeight);
							$('#' + id).height(newHeight + "px");
							// This call is required for the editor to fix all of
							// its inner structure for adapting to a change in size
							editor.resize();
							$previewEl.css('min-height', $editorTab.outerHeight() + "px");
						}

						scope.useTabs = attrs.useTabs === 'true';
						scope.draggable = attrs.draggable === 'true';
						scope.editorId = ++editorIdCounter;
						scope.useTextarea = !canUseAceEditor();

						// Init available selectors.
						scope.availableSelectors = {
							'media': true,
							'links': true,
							'anchors': true,
							'mentions': false
						};
						if (attrs.profile === 'Admin') {
							scope.availableSelectors.anchors = false;
							scope.availableSelectors.mentions = true;
						}

						parseSubstitutionVariables(attrs.substitutionVariables);
						attrs.$observe('substitutionVariables', function(value) {
							parseSubstitutionVariables(value);
						});

						id = 'rbsInputMarkdownAceEditor' + scope.editorId;

						scope.preview = function() {
							element.find('a[data-toggle="tab"][data-role="preview"]').tab('show');
						};

						scope.closePreview = function() {
							element.find('a[data-toggle="tab"][data-role="editor"]').tab('show');
						};

						// Document insertion.

						scope.insertionContext = {
							selectionMode: 'none',
							mediasToInsert: [],
							documentsToLink: [],
							documentsToMention: [],
							wysiwygData: { range: null, contents: null }
						};

						scope.closeSelection = function closeSelection() {
							scope.insertionContext.selectionMode = 'none';
						};
						scope.selectMediasToInsert = function selectMediasToInsert() {
							scope.insertionContext.selectionMode = 'media';
						};
						scope.selectDocumentsToLink = function selectDocumentsToLink() {
							scope.insertionContext.selectionMode = 'publishable';
						};
						scope.selectDocumentsToMention = function selectDocumentsToMention() {
							scope.insertionContext.selectionMode = 'mentionable';
						};

						scope.$watch('insertionContext.selectionMode', function(selectionMode) {
							switch (selectionMode) {
								case 'media':
									element.find('a[data-toggle="tab"][data-role="richtext-select-media"]').tab('show');
									break;

								case 'publishable':
									element.find('a[data-toggle="tab"][data-role="richtext-select-publishable"]').tab('show');
									break;

								case 'mentionable':
									element.find('a[data-toggle="tab"][data-role="richtext-select-mentionable"]').tab('show');
									break;

								default:
									element.find('a[data-toggle="tab"][data-role="richtext-editor"]').tab('show');
									break;
							}
						});

						scope.$on('Navigation.saveContext', function onNavigationSaveContext(event, args) {
							args.context.savedData(
								getContextValueKey() + '_selection',
								{
									selectionMode: scope.insertionContext.selectionMode,
									mediasToInsert: scope.insertionContext.mediasToInsert,
									documentsToLink: scope.insertionContext.documentsToLink,
									documentsToMention: scope.insertionContext.documentsToMention,
									wysiwygData: scope.insertionContext.wysiwygData
								}
							);
						});

						function restoreSelectionContext() {
							if (currentContext) {
								var savedData = currentContext.savedData(getContextValueKey() + '_selection');
								if (savedData && savedData.hasOwnProperty('selectionMode')) {
									scope.insertionContext.selectionMode = savedData.selectionMode;
									var i, doc;
									for (i = 0; i < savedData.mediasToInsert.length; i++) {
										doc = savedData.mediasToInsert[i];
										if (!ArrayUtils.documentInArray(doc, scope.insertionContext.mediasToInsert)) {
											scope.insertionContext.mediasToInsert.push(doc);
										}
									}
									for (i = 0; i < savedData.documentsToLink.length; i++) {
										doc = savedData.documentsToLink[i];
										if (!ArrayUtils.documentInArray(doc, scope.insertionContext.documentsToLink)) {
											scope.insertionContext.documentsToLink.push(doc);
										}
									}
									for (i = 0; i < savedData.documentsToMention.length; i++) {
										doc = savedData.documentsToMention[i];
										if (!ArrayUtils.documentInArray(doc, scope.insertionContext.documentsToMention)) {
											scope.insertionContext.documentsToMention.push(doc);
										}
									}
									scope.insertionContext.wysiwygData = savedData.wysiwygData;
								}
							}
						}

						scope.canInsertMedia = function canInsertMedia() {
							return scope.insertionContext.mediasToInsert.length > 0;
						};

						scope.insertMedia = function insertMedia() {
							if (scope.canInsertMedia()) {
								switch (scope.editorMode) {
									case 'Markdown':
										mdInsertMedias(scope.insertionContext.mediasToInsert);
										scope.closeSelection();
										break;

									case 'Html':
										wysiwygInsertMedias(scope.insertionContext.mediasToInsert);
										scope.closeSelection();
										break;

									default:
										console.log('insertMedia', 'Unexpected editorMode: ' + scope.editorMode);
								}
							}
						};

						scope.canLinkDocument = function canLinkDocument() {
							return scope.insertionContext.documentsToLink.length > 0;
						};

						scope.linkDocument = function linkDocument() {
							if (scope.canLinkDocument()) {
								switch (scope.editorMode) {
									case 'Markdown':
										mdInsertDocumentLinks(scope.insertionContext.documentsToLink);
										scope.closeSelection();
										break;

									case 'Html':
										wysiwygInsertDocumentLinks(scope.insertionContext.documentsToLink);
										scope.closeSelection();
										break;

									default:
										console.log('linkDocument', 'Unexpected editorMode: ' + scope.editorMode);
								}
							}
						};

						scope.canInsertMention = function canInsertMention() {
							return scope.insertionContext.documentsToMention.length > 0;
						};

						scope.insertMention = function insertMention() {
							if (scope.canInsertMention()) {
								switch (scope.editorMode) {
									case 'Markdown':
										mdInsertIdentifiers(scope.insertionContext.documentsToMention);
										scope.closeSelection();
										break;

									// Mentions only work in markdown mode.
									default:
										console.log('insertMention', 'Unexpected editorMode: ' + scope.editorMode);
								}
							}
						};

						// Editor's functions.

						function isSurroundedWith(range, marker) {
							var l = marker.length,
								sr = null, er = null,
								sc = null, ec = null,
								lastLine = session.getLine(range.end.row),
								lineCount = session.getLength(),
								line, newRange, text;

							if (range.start.column >= l) {
								sr = range.start.row;
								sc = range.start.column - l;
							}
							else {
								if (range.start.row > 0) {
									sr = range.start.row - 1;
									line = session.getLine(sr);
									sc = line.length - l;
								}
							}

							if (range.end.column <= (lastLine.length - l)) {
								er = range.end.row;
								ec = range.end.column + l;
							}
							else {
								if (range.start.row < (lineCount - 1)) {
									er = range.end.row + 1;
									ec = l;
								}
							}

							if (sr !== null && er !== null && sc !== null && ec !== null) {
								newRange = angular.copy(range);
								newRange.start.row = sr;
								newRange.start.column = sc;
								newRange.end.row = er;
								newRange.end.column = ec;
								text = session.getTextRange(newRange);
								if (Utils.startsWith(text, marker) && Utils.endsWith(text, marker)) {
									return newRange;
								}
							}

							return null;
						}

						function toggleSurrounding(marker) {
							var range = editor.getSelectionRange(),
								selection = session.getTextRange(range),
								surroundingRange;
							if (range.isEmpty()) {
								range = session.getWordRange(range.start.row, range.start.column);
							}
							surroundingRange = isSurroundedWith(range, marker);
							if (surroundingRange === null) {
								editor.insert(marker + selection + marker);
							}
							else {
								session.replace(surroundingRange, selection);
							}
						}

						scope.mdBold = function() {
							toggleSurrounding('**');
						};

						scope.mdItalic = function() {
							toggleSurrounding('*');
						};

						scope.mdHeading = function(level) {
							var range = editor.getSelectionRange(), i, headingMarker = '';

							// Prepare heading marker.
							for (i = 0; i < level; i++) {
								headingMarker += '#';
							}
							if (level) {
								headingMarker += ' ';
							}

							replaceMarker(range, headingMarker);
						};

						scope.mdOrderedList = function() {
							var range = editor.getSelectionRange();
							replaceMarker(range, '{LINE_NUMBER}. ');
						};

						scope.mdUnorderedList = function() {
							var range = editor.getSelectionRange();
							replaceMarker(range, '* ');
						};

						scope.mdPre = function() {
							var range = editor.getSelectionRange();
							replaceMarker(range, '    ');
						};

						scope.mdBlockQuote = function() {
							var range = editor.getSelectionRange();
							replaceMarker(range, '> ');
						};

						function replaceMarker(range, newMarker) {
							var line, endColumn, lineNumber = 0;
							var originalRange = angular.copy(range);
							var startRow = range.start.row, endRow = range.end.row;
							for (var i = startRow; i <= endRow; i++) {
								line = session.getLine(i);

								var startC, endC;
								if (newMarker == '> ') {
									startC = 0;
									endC = blockQuoteMarkerLength(line, 0);
								}
								else {
									startC = blockQuoteMarkerLength(line, 0);
									endC = otherMarkerLength(line, startC);
								}

								// Replace existing marker with the new one.
								var newRange = angular.copy(originalRange);
								newRange.setStart(i, startC);
								newRange.setEnd(i, endC);
								if (endC < line.length) {
									lineNumber++;
									session.replace(newRange, newMarker.replace('{LINE_NUMBER}', lineNumber));
								}
								else {
									lineNumber = 0;
									session.replace(newRange, '');
								}
							}

							line = session.getLine(endRow);
							endColumn = line.length;

							line = session.getLine(endRow + 1);
							if (line.length > 0) {
								session.insert({'row': endRow + 1, 'column': 0}, "\n");
							}

							line = session.getLine(startRow - 1);
							if (line.length > 0) {
								session.insert({'row': startRow, 'column': 0}, "\n");
								startRow++;
								endRow++;
							}

							range.setStart(startRow, 0);
							range.setEnd(endRow, endColumn);
							session.getSelection().setSelectionRange(range);
						}

						function otherMarkerLength(line, c) {
							// Ordered list.
							var result = orderedListMarkerLength(line, c);
							if (result > c) {
								return result;
							}

							// Unordered list.
							result = unorderedListMarkerLength(line, c);
							if (result > c) {
								return result;
							}

							// Title.
							result = titleMarkerLength(line, c);
							if (result > c) {
								return result;
							}

							return c;
						}

						function blockQuoteMarkerLength(line, c) {
							if (line.charAt(c) === '>') {
								var tmpC = 0;
								while (line.charAt(tmpC) === '>') {
									tmpC++;
								}
								if (line.charAt(tmpC) === ' ') {
									c += tmpC + 1;
								}
							}
							return c;
						}

						function orderedListMarkerLength(line, c) {
							while (line.charAt(c) === ' ') {
								c++;
							}

							if (parseInt(line.charAt(c)) == line.charAt(c)) {
								var tmpC = 0;
								while (parseInt(line.charAt(c + tmpC)) == line.charAt(c + tmpC)) {
									tmpC++;
								}
								if (line.charAt(c + tmpC) === '.' && line.charAt(c + tmpC + 1) === ' ') {
									c += tmpC + 2;
								}
							}

							return c;
						}

						function unorderedListMarkerLength(line, c) {
							while (line.charAt(c) === ' ') {
								c++;
							}

							if (line.charAt(c) === '*' && line.charAt(c + 1) === ' ') {
								return c + 2;
							}

							return c;
						}

						function titleMarkerLength(line, c) {
							while (line.charAt(c) === ' ') {
								c++;
							}

							if (line.charAt(c) === '#') {
								while (line.charAt(c) === '#') {
									c++;
								}
								if (line.charAt(c) === ' ') {
									c++;
								}
							}

							return c;
						}

						function replaceInRange(range, line, start, end, replacement) {
							var newRange = angular.copy(range);
							newRange.setStart(line, start);
							newRange.setEnd(line, end);
							session.replace(newRange, replacement);
						}

						scope.mdIndent = function() {
							var range = editor.getSelectionRange();
							var startRow = range.start.row, endRow = range.end.row;
							for (var i = startRow; i <= endRow; i++) {
								var line = session.getLine(i);

								// Block quote case.
								if (blockQuoteMarkerLength(line, 0)) {
									replaceInRange(range, i, 0, 0, '>');
								}
								// Lists.
								else if (orderedListMarkerLength(line, 0) > 0 || unorderedListMarkerLength(line, 0) > 0) {
									replaceInRange(range, i, 0, 0, '    ');
								}
							}
						};

						scope.mdOutdent = function() {
							var range = editor.getSelectionRange();
							var line, markerLength;
							var startRow = range.start.row, endRow = range.end.row;
							for (var i = startRow; i <= endRow; i++) {
								line = session.getLine(i);

								// Block quote case.
								markerLength = blockQuoteMarkerLength(line, 0);
								console.log(line, 'block quote', markerLength);
								if (markerLength) {
									if (markerLength > 2) {
										markerLength = 1;
									}
									replaceInRange(range, i, 0, markerLength, '');
									continue;
								}

								// List.
								markerLength = orderedListMarkerLength(line, 0);
								console.log(line, 'ol', markerLength);
								if (!markerLength) {
									markerLength = unorderedListMarkerLength(line, 0);
									console.log(line, 'ul', markerLength);
								}
								if (markerLength) {
									if (markerLength > 4 && line.charAt(3) == ' ') {
										markerLength = 4;
									}
									replaceInRange(range, i, 0, markerLength, '');
								}
							}
						};

						scope.mdUndo = function() {
							editor.undo();
						};

						scope.mdRedo = function() {
							editor.redo();
						};

						scope.mdInsertText = function(text) {
							editor.insert(text);
						};

						//
						// Media insertion
						//

						function buildMdMediaTag(imageId, imageLabel) {
							var range = editor.getSelectionRange();
							var alt = range.isEmpty() ? imageLabel : session.getTextRange(range);
							return '![' + alt + '](' + imageId + ' "' + imageLabel + '")';
						}

						function mdInsertMedias(medias) {
							var toInsert = [];
							for (var i = 0; i < medias.length; i++) {
								var media = medias[i];
								switch (media.model) {
									case 'Rbs_Media_Image':
									case 'Rbs_Media_Video':
										toInsert.push(buildMdMediaTag(media.id, media.label));
										break;
									case 'Rbs_Media_File':
										toInsert.push(buildMdLinkTag(media.id, media.label));
										break;
									default:
										console.log('mdInsertMedia', 'Unexpected model: ' + media.model);
										break;
								}
							}
							if (toInsert.length) {
								scope.mdInsertText(toInsert.join(' '));
							}
						}

						//
						// Links insertion
						//

						function buildMdLinkTag(href, title) {
							var range = editor.getSelectionRange(), text;
							if (range.isEmpty()) {
								text = title;
							}
							else {
								text = session.getTextRange(range);
							}
							return '[' + text + '](' + href + ' "' + title + '")';
						}

						function mdInsertDocumentLinks(documents, route) {
							var toInsert = [];
							for (var i = 0; i < documents.length; i++) {
								var document = documents[i];
								var href = document.id;
								if (route) {
									href += ',' + route;
								}
								toInsert.push(buildMdLinkTag(href, document.label));
							}
							if (toInsert.length) {
								scope.mdInsertText(toInsert.join(' '));
							}
						}

						function mdInsertIdentifiers(documents) {
							for (var i = 0; i < documents.length; i++) {
								REST.resource(documents[i].id).then(function(userOrGroup) {
									switch (userOrGroup.model) {
										case 'Rbs_User_User':
											if (userOrGroup.login) {
												scope.mdInsertText('@' + userOrGroup.login + ' ');
											}
											break;
										case 'Rbs_User_Group':
											if (userOrGroup.identifier) {
												scope.mdInsertText('@+' + userOrGroup.identifier + ' ');
											}
											break;
										default:
											console.log('mdInsertIdentifier', 'Unexpected model: ' + userOrGroup.model);
											break;
									}
								});
							}
						}

						scope.insertExternalLink = function() {
							var href = prompt(i18n.trans('m.rbs.admin.adminjs.richtext_enter_external_link'), 'http://');

							if (href != null && href != '') {
								var title = prompt(i18n.trans('m.rbs.admin.adminjs.richtext_enter_external_link_title'));

								scope.mdInsertText(buildMdLinkTag(href, title || href));
							}
						};

						scope.insertExternalOrAnchorLink = function() {
							var href = prompt(i18n.trans('m.rbs.admin.adminjs.richtext_enter_external_or_anchor_link'), 'http://');

							if (href != null && href != '') {
								var title = prompt(i18n.trans('m.rbs.admin.adminjs.richtext_enter_external_link_title'));

								scope.mdInsertText(buildMdLinkTag(href, title || href));
							}
						};

						scope.insertAnchor = function() {
							var anchorName = prompt(i18n.trans('m.rbs.admin.adminjs.richtext_enter_anchor_name'), '');
							if (anchorName != null && anchorName != '') {
								if (anchorName.charAt(0) !== '#') {
									anchorName = '#' + anchorName;
								}
								scope.mdInsertText(' {' + anchorName + '}');
							}
						};

						scope.insertSubstitutionVariable = function(variable) {
							scope.mdInsertText('{' + variable + '}');
						};

						//
						// Help
						//

						scope.openHelp = function() {
							window.open('http://fr.wikipedia.org/wiki/Markdown#Quelques_exemples', 'rbsChangeHelp',
								'width=800,height=600,scrollbars=yes,resizable=yes');
						};

						// ----------------------------------------------------
						//
						// WYSIWYG editor methods
						//
						// ----------------------------------------------------

						scope.$on('WYSIWYG.SelectMediasToInsert', function(event, data) {
							scope.insertionContext.wysiwygData.range = data.range;
							scope.insertionContext.wysiwygData.contents = data.contents;
							scope.selectMediasToInsert();
						});

						scope.$on('WYSIWYG.SelectDocumentsToLink', function(event, data) {
							scope.insertionContext.wysiwygData.range = data.range;
							scope.insertionContext.wysiwygData.contents = data.contents;
							scope.selectDocumentsToLink();
						});

						function applyContextValueWysiwyg(context) {
							scope.insertionContext.wysiwygData.range = context.param('range');
							scope.insertionContext.wysiwygData.contents = context.param('contents');
						}

						function wysiwygInsertMedias(medias) {
							var toInsert = [];
							for (var i = 0; i < medias.length; i++) {
								var media = medias[i];
								switch (media.model) {
									case 'Rbs_Media_Image':
										toInsert.push('<img src="' + media.META$.actions.resizeurl.href + '"' +
											' data-document-id="' + media.id + '" />');
										break;
									case 'Rbs_Media_Video':
										toInsert.push('<video src="' + media.publicurl + '" preload="auto"' +
											' controls="controls"' +
											' data-document-id="' + media.id + '"></video>');
										break;
									case 'Rbs_Media_File':
										toInsert.push('<a href="javascript:;" data-document-id="' + media.id + '">' +
											media.label + '</a>');
										break;
									default:
										console.log('wysiwygInsertMedia', 'Unexpected model: ' + media.model);
										break;
								}
							}

							if (toInsert.length) {
								var data = {
									range: scope.insertionContext.wysiwygData.range,
									contents: scope.insertionContext.wysiwygData.contents,
									html: toInsert.join(' ')
								};
								scope.$broadcast('WYSIWYG.InsertImage', data);
							}
						}

						function wysiwygInsertDocumentLinks(documents) {
							var toInsert = [];
							for (var i = 0; i < documents.length; i++) {
								var document = documents[i];
								toInsert.push('<a href="javascript:;" data-document-id="' + document.id + '">' +
									document.label + '</a>');
							}

							if (toInsert.length) {
								var data = {
									range: scope.insertionContext.wysiwygData.range,
									contents: scope.insertionContext.wysiwygData.contents,
									html: toInsert.join(' ')
								};
								scope.$broadcast('WYSIWYG.InsertLink', data);
							}
						}

						function initWysiwygEditor(value) {
							if (!value || value === '<br>') {
								value = ' ';
							}
							scope.wysiwyg.content = value;
							scope.$watch('wysiwyg.content', function(value) {
								ngModel.$setViewValue({e: 'Html', t: value, h: null});
							});
							wysiwygInitialized = true;

							// Apply navigation context if there is one.
							if (currentContext) {
								var contextValue = currentContext.getSelectionValue(getContextValueKey());
								if (contextValue !== undefined) {
									applyContextValueWysiwyg(currentContext);
								}
							}
						}
					}
				};
			}]);

	function parseRbsDocumentHref(href) {
		var doc, matches;

		matches = href.match(/^([a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+),(\d+)(,([a-z]{2}_[A-Z]{2}))?(,([a-zA-Z0-9\-_]+))?$/);
		//                      11111111111111111111111111111111111111   222    44444444444444444      666666666666666
		if (matches === null) {
			return null;
		}

		doc = {
			"model": matches[1],
			"id": matches[2]
		};
		if (matches[4]) {
			doc.LCID = matches[4];
		}
		if (matches[6]) {
			doc.route = matches[6];
		}

		return doc;
	}

	/**
	 * Builds the 'href' attribute based on a value of the form: model,id[,LCID[,routeName]].
	 */
	app.directive('rbsDocumentHref', ['RbsChange.UrlManager', 'RbsChange.Settings', function(UrlManager, Settings) {
		return {
			restrict: 'A',
			priority: 1001,

			link: function(scope, element, attrs) {
				if (!element.is('a')) {
					console.warn("Directive 'rbs-document-href' only works on <a></a> elements.");
				}
				if (!attrs.rbsDocumentHref) {
					throw new Error("Attribute 'rbs-document-href' must not be empty. Should be: 'model,id[,LCID[,routeName]]");
				}

				attrs.$observe('rbsDocumentHref', function(href) {
					console.log("href=", href);
					var doc = parseRbsDocumentHref(href);
					if (doc !== null) {
						doc.LCID = doc.LCID || Settings.get('LCID');
						element.attr('href', UrlManager.getUrl(doc, null, doc.route || 'edit'));
					}
				});
			}
		};
	}]);

	/**
	 *
	 */
	app.directive('rbsDocumentPopover',
		['$timeout', '$q', 'RbsChange.Settings', 'RbsChange.REST', '$compile', function($timeout, $q, Settings, REST, $compile) {
			var popovers = [],
				POPOVER_WIDTH = 200,
				POPOVER_DEFAULT_DELAY = 500;

			// Close all registered popovers when
			$(window.document).on('click.rbsDocumentPopover.close', function() {
				angular.forEach(popovers, function(popover) {
					if (popover.visible) {
						popover.element.popover('hide');
						popover.visible = false;
					}
				});
			});

			function registerPopover(elm) {
				var popover = {
					element: elm,
					index: popovers.length,
					visible: null
				};
				popovers.push(popover);
				return popover;
			}

			function unregisterPopover(popover) {
				var index = popover.index;
				if (index >= 0 && index < popovers.length) {
					popovers.splice(index, 1);
				}
			}

			return {
				restrict: 'A',
				priority: 1000,

				link: function(scope, element, attrs) {
					var valueAttr = null,
						doc,
						popoverReady = false,
						popover,
						delay = POPOVER_DEFAULT_DELAY, d,
						trigger = attrs.trigger || 'hover',
						triggerSelector = null,
						popoverReadyPromise = null,
						popoverTimer = null;

					// Initialize popover's trigger.
					if (trigger !== 'hover' && trigger !== 'click') {
						throw new Error("Invalid 'trigger' attribute: should be 'click' or 'hover'.");
					}
					if (trigger === 'click' && element.is('a')) {
						element.append(' <i class="icon-eye-open document-preview-trigger"></i>');
						triggerSelector = '.document-preview-trigger';
					}

					// Initialize delay.
					if (attrs.delay) {
						d = parseInt(attrs.delay, 10);
						if (!isNaN(d)) {
							delay = d;
						}
					}

					// This directive can work well if the 'rbs-document-href' directive is present.
					if (attrs.rbsDocumentPopover) {
						valueAttr = 'rbsDocumentPopover';
					}
					else if (attrs.rbsDocumentHref) {
						valueAttr = 'rbsDocumentHref';
					}
					else {
						throw new Error("Unable to find Document information. Please provide one of these attributes: 'rbs-document-popover' or 'rbs-document-href'.");
					}

					attrs.$observe(valueAttr, function(href) {
						// Document information has changed:
						// destroy popover so that it can be rebuilt correctly with its new content.
						if (popover) {
							element.popover('destroy');
						}
						popoverReady = false;
						doc = parseRbsDocumentHref(href);
					});

					// Popover registration:
					// registered popovers will be closed when a click occurs outside them.
					popover = registerPopover(element);
					scope.$on('$destroy', function() {
						unregisterPopover(popover);
					});

					// Install event handlers

					if (trigger === 'hover') {
						element.on('hover.rbsDocumentPopover', triggerSelector, function(event) {
							event.preventDefault();
							event.stopPropagation();

							// Nothing to do if document information is unavailable.
							if (!doc) {
								return;
							}

							if (event.type === 'mouseenter' && !popover.visible) {
								popoverTimer = $timeout(function() {
									preparePopover(event.shiftKey, event.pageX).then(function() {
										element.popover('show');
										popover.visible = true;
										popoverTimer = null;
									});
								}, delay);
							}
							else if (event.type === 'mouseleave') {
								if (popover.visible) {
									element.popover('hide');
								}
								else {
									$timeout.cancel(popoverTimer);
								}
								popover.visible = false;
							}
						});
					}
					else {
						element.on('click.rbsDocumentPopover', triggerSelector, function(event) {
							event.preventDefault();
							event.stopPropagation();

							// Nothing to do if document information is unavailable.
							if (!doc) {
								return;
							}

							if (popover.visible) {
								element.popover('hide');
								popover.visible = false;
							}
							else {
								$timeout(function() {
									preparePopover(event.shiftKey, event.pageX).then(function() {
										element.popover('show');
										popover.visible = true;
									});
								}); // no delay for click triggers
							}
						});
					}

					// Popover preparation:
					// load its content and determine the bast placement for it.
					// Returns a Promise, resolved when the popover is ready to be displayed.

					function preparePopover(reload, x) {
						if (!popoverReady || reload) {
							var defered = $q.defer(),
								placement = 'bottom';

							// Destroy existing popover before rebuilding it.
							if (popoverReady && reload) {
								element.popover('destroy');
							}

							// Determine the best placement for the popover
							// based on the event's X and the screen's size.
							if (x < POPOVER_WIDTH) {
								placement = 'right';
							}
							else if (x > ($(document).width() - POPOVER_WIDTH)) {
								placement = 'left';
							}

							// The following REST action will search for the following template:
							// - name: popover-preview.twig
							// - location: where other templates for the document (list.twig and editor.twig) are.
							REST.call(REST.getBaseUrl('admin/documentPreview'), {"id": doc.id}).then(function(result) {
								var options = {
									'container': 'body',
									'placement': placement,
									'trigger': 'manual',
									'html': true
								};
								//content need to be compiled
								$compile(result.content)(scope, function(cloneElm) {
									result.content = cloneElm;
								});
								element.popover(angular.extend(options, result));

								defered.resolve();
							});
							popoverReadyPromise = defered.promise;
							popoverReady = true;
						}
						return popoverReadyPromise;
					}
				}
			};
		}]);

	/**
	 * Directive to edit properties for an external link.
	 */
	app.directive('rbsRichTextDialogExternalLink', function() {
		return {
			restrict: 'A',
			scope: {
				properties: '=',
				onSubmit: '='
			},
			templateUrl: 'Rbs/Admin/js/directives/rich-text-dialog-external-link.twig',

			link: function(scope, element, attrs) {
				scope.canSubmit = function canSubmit() {
					return scope.properties.href && scope.properties.text;
				};
			}
		};
	});

	/**
	 * Directive to edit properties for a document link.
	 */
	app.directive('rbsRichTextDialogDocumentLink', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			scope: {
				properties: '=',
				onSubmit: '='
			},
			templateUrl: 'Rbs/Admin/js/directives/rich-text-dialog-document-link.twig',

			link: function(scope, element, attrs) {
				scope.canSubmit = function canSubmit() {
					return scope.properties.href && scope.properties.text;
				};

				scope.appendValue = function appendValue(propertyName, value) {
					var newValue = scope.properties[propertyName];
					newValue = (newValue ? (newValue + ' ') : '') + value;
					scope.properties[propertyName] = newValue;
				};

				REST.resource(scope.properties.documentId).then(function(document) {
					scope.document = document;
					scope.predefinedValues = [];
					if (document.hasOwnProperty('title')) {
						scope.predefinedValues.push(document.title);
					}
					if (document.hasOwnProperty('label') &&
						(!document.hasOwnProperty('title') || document.title != document.label)) {
						scope.predefinedValues.push(document.label);
					}
				});

			}
		};
	}]);

	/**
	 * Directive to edit properties for an image.
	 */
	app.directive('rbsRichTextDialogImage', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			scope: {
				properties: '=',
				onSubmit: '='
			},
			templateUrl: 'Rbs/Admin/js/directives/rich-text-dialog-image.twig',

			link: function(scope, element, attrs) {
				scope.canSubmit = function canSubmit() {
					return (!scope.properties.height || scope.properties.height > 0)
						&& (!scope.properties.width || scope.properties.width > 0);
				};

				scope.appendValue = function appendValue(propertyName, value) {
					var newValue = scope.properties[propertyName];
					newValue = (newValue ? (newValue + ' ') : '') + value;
					scope.properties[propertyName] = newValue;
				};

				REST.resource(scope.properties.documentId).then(function(document) {
					scope.document = document;
					scope.predefinedValues = [];
					if (document.hasOwnProperty('title')) {
						scope.predefinedValues.push(document.title);
					}
					if (document.hasOwnProperty('label') &&
						(!document.hasOwnProperty('title') || document.title != document.label)) {
						scope.predefinedValues.push(document.label);
					}
				});

			}
		};
	}]);

	/**
	 * Directive to edit properties for a video.
	 */
	app.directive('rbsRichTextDialogVideo', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			scope: {
				properties: '=',
				onSubmit: '='
			},
			templateUrl: 'Rbs/Admin/js/directives/rich-text-dialog-video.twig',

			link: function(scope, element, attrs) {
				scope.canSubmit = function canSubmit() {
					return (!scope.properties.height || scope.properties.height > 0)
						&& (!scope.properties.width || scope.properties.width > 0);
				};

				scope.appendValue = function appendValue(propertyName, value) {
					var newValue = scope.properties[propertyName];
					newValue = (newValue ? (newValue + ' ') : '') + value;
					scope.properties[propertyName] = newValue;
				};

				REST.resource(scope.properties.documentId).then(function(document) {
					scope.document = document;
					scope.predefinedValues = [];
					if (document.hasOwnProperty('title')) {
						scope.predefinedValues.push(document.title);
					}
					if (document.hasOwnProperty('label') &&
						(!document.hasOwnProperty('title') || document.title != document.label)) {
						scope.predefinedValues.push(document.label);
					}
				});
			}
		};
	}]);
})(window.jQuery, ace);