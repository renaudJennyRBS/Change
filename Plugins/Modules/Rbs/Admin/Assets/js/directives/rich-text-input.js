(function ($, ace) {

	"use strict";

	var app = angular.module('RbsChange'),
		MIN_HEIGHT = 150;

	//=========================================================================
	//
	// ACE editor widget
	//
	//=========================================================================


	app.directive('rbsRichTextInput', ['$timeout', 'RbsChange.REST', 'RbsChange.Utils', '$compile', function ($timeout, REST, Utils, $compile) {

		var	aceEditorIdCounter = 0,
			mediaPickerTpl =
			'<div style="padding: 10px; background: #444; color: white;">' +
				'<button type="button" style="color: white;" class="close pull-right" ng-click="closeMediaSelector()">&times;</button>' +
				'<h4 style="margin:0">Sélectionner une image à insérer</h4>' +
				'<rbs-document-list class="grid-small" data-dlid="rbsRichTextInputMediaPicker" model="Rbs_Media_Image" display="grid" toolbar="false" picker="picker">' +
				'<column name="path" thumbnail="XS"></column>' +
				'<column name="label" primary="true"></column>' +
				'<grid-item>' +
				'<img rbs-storage-image="(= doc.path =)" thumbnail="XS"/>' +
				'<a style="display:block" href="javascript:;" ng-click="picker.selectMedia(doc, $event)">(= doc.label =)</a>' +
				'</grid-item>' +
				'</rbs-document-list>' +
				'</div>';

		return {
			restrict : 'EC',
			require  : 'ngModel',
			// TODO Localization
			template :
				'<div class="tabbable">' +
					'<ul class="nav nav-tabs" style="margin-bottom: 0">' +
						'<li class="active"><a href="javascript:;" data-role="editor" data-target="#rbsInputMarkdown(=editorId=)TabEditor" data-toggle="tab">Éditeur</a></li>' +
						'<li><a href="javascript:;" data-role="preview" data-target="#rbsInputMarkdown(=editorId=)TabPreview" data-toggle="tab">Aperçu <i ng-show="previewing" class="icon-spinner icon-spin"></i></a></li>' +
					'</ul>' +
					'<div class="tab-content">' +
						'<div class="tab-pane active" id="rbsInputMarkdown(=editorId=)TabEditor">' +
							'<div class="btn-toolbar">' +

								// Headings
								'<div class="btn-group">' +
									'<button type="button" class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">Titre <span class="caret"></span></button>' +
									'<ul class="dropdown-menu">' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(0)">Supprimer</a></li>' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(1)">Niveau 1</a></li>' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(2)">Niveau 2</a></li>' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(3)">Niveau 3</a></li>' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(4)">Niveau 4</a></li>' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(5)">Niveau 5</a></li>' +
										'<li><a tabindex="-1" href="javascript:;" ng-click="mdHeading(6)">Niveau 6</a></li>' +
									'</ul>' +
								'</div>' +

								// Bold, italic, ...
								'<div class="btn-group">' +
									'<button type="button" class="btn btn-small" ng-click="mdBold()"><i class="icon-bold"></i></button>' +
									'<button type="button" class="btn btn-small" ng-click="mdItalic()"><i class="icon-italic"></i></button>' +
								'</div>' +

								// Undo/redo
								'<div class="btn-group">' +
									'<button type="button" class="btn btn-small" ng-click="mdUndo()"><i class="icon-undo"></i></button>' +
									'<button type="button" class="btn btn-small" ng-click="mdRedo()"><i class="icon-repeat"></i></button>' +
								'</div>' +

								// Media
								'<div class="btn-group">' +
									'<button type="button" class="btn btn-small" ng-class="{active:isMediaSelectorOpen}" ng-click="toggleMediaSelector()"><i class="icon-picture"></i></button>' +
								'</div>' +

							'</div>' +

							'<div class="media-picker"></div>' +

							'<div id="rbsInputMarkdownAceEditor(=editorId=)"></div>' +
						'</div>' +
						'<div class="tab-pane" data-role="preview-container" id="rbsInputMarkdown(=editorId=)TabPreview">' +
							'<p>Générer l\'aperçu...</p>' +
						'</div>' +
					'</div>' +
				'</div>',

			link : function (scope, element, attrs, ngModel) {

				var	editor,
					session,
					id,
					$previewEl = element.find('div[data-role="preview-container"]'),
					$mediaPicker = element.find('div.media-picker');

				scope.editorId = ++aceEditorIdCounter;
				id = "rbsInputMarkdownAceEditor" + scope.editorId;


				// Initialize ACE editor when the scope has been completely applied.
				function initEditor () {
					editor = ace.edit(id);
					session = editor.getSession();
					session.setMode("ace/mode/markdown");
					session.setUseWrapMode(true);
					session.setWrapLimitRange(null, null);
					session.setFoldStyle("manual");
					editor.setShowFoldWidgets(true);
					// editor.renderer.setShowGutter(false);

					heightUpdateFunction(id, editor);
					editor.getSession().on('change', function () {
						heightUpdateFunction(id, editor);
					});

					ngModel.$render = function() {
						editor.setValue(ngModel.$viewValue);
					};

					session.on('change', function () {
						$timeout(function () {
							ngModel.$setViewValue(editor.getValue());
						});
					});

					editor.on("focus", function() {
						element.addClass('focused');
					});

					editor.on("blur", function() {
						element.removeClass('focused');
					});

					ngModel.$render();
				}
				$timeout(initEditor);


				// Auto-height.
				function heightUpdateFunction (id, editor) {
					// http://stackoverflow.com/questions/11584061/
					var newHeight =
						editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();
					$('#'+id).height(Math.max(MIN_HEIGHT, newHeight) + "px");
					// This call is required for the editor to fix all of
					// its inner structure for adapting to a change in size
					editor.resize();
				}


				// Tabs and preview.
				element.find('a[data-toggle="tab"]').on('shown', function (e) {
					if ($(e.target).data('role') === 'preview') {
						// TODO Localization
						scope.previewing = true;
						REST.postAction('md2html', editor.getValue()).then(function (data) {
							$previewEl.html(data.html);
							scope.previewing = false;
						});
					}
				});


				// Editor's functions.

				function isSurroundedWith (range, marker) {
					var	l = marker.length,
						sr = null, er = null,
						sc = null, ec = null,
						lastLine = session.getLine(range.end.row),
						lineCount = session.getLength(),
						line, newRange, text;

					if (range.start.column >= l) {
						sr = range.start.row;
						sc = range.start.column - l;
					} else {
						if (range.start.row > 0) {
							sr = range.start.row - 1;
							line = session.getLine(sr);
							sc = line.length-l;
						}
					}

					if (range.end.column <= (lastLine.length-l)) {
						er = range.end.row;
						ec = range.end.column + l;
					} else {
						if (range.start.row < (lineCount-1)) {
							er = range.end.row + 1;
							line = session.getLine(er);
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

				function toggleSurrounding (marker) {
					var	range = editor.getSelectionRange(),
						selection = session.getTextRange(range),
						surroundingRange;
					if (range.isEmpty()) {
						range = session.getWordRange(range.start.row, range.start.column);
					}
					surroundingRange = isSurroundedWith(range, marker);
					if (surroundingRange === null) {
						editor.insert(marker + selection + marker);
					} else {
						session.replace(surroundingRange, selection);
					}
				}

				scope.mdBold = function () {
					toggleSurrounding('**');
				};

				scope.mdItalic = function () {
					toggleSurrounding('*');
				};

				scope.mdHeading = function (level) {
					var	range = editor.getSelectionRange(),
						line = session.getLine(range.start.row),
						c = 0, i,
						newRange, headingMarker = '';

					// Prepare heading marker
					for (i=0 ; i<level ; i++) {
						headingMarker += '#';
					}
					if (level) {
						headingMarker += ' ';
					}

					// Check if the line containing the selection is already a heading or not.
					while (line.charAt(c) === '#') {
						c++;
					}
					if (c > 0) {
						// Replace existing heading marker with the new one
						newRange = angular.copy(range);
						newRange.start.column = 0;
						newRange.end.row = newRange.start.row;
						if (line.charAt(c) === ' ') {
							c++;
						}
						newRange.end.column = c;
						session.replace(newRange, headingMarker);
					} else if (range.isEmpty()) {
						session.insert({'row': range.start.row, 'column': 0}, headingMarker);
					} else {
						// Create new heading with the selection
						editor.insert("\n\n" + headingMarker + session.getTextRange(range) + "\n\n");
					}
				};

				scope.mdUndo = function () {
					editor.undo();
				};

				scope.mdRedo = function () {
					editor.redo();
				};

				scope.mdInsertText = function (text) {
					editor.insert(text);
				};

				function buildMdImageTag (imagePath, imageLabel) {
					var	range = editor.getSelectionRange(), alt;
					alt = range.isEmpty() ? imageLabel : session.getTextRange(range);
					return '![' + alt + '](' + imagePath + ' "")';
				}

				scope.mdInsertMedia = function (media) {
					scope.mdInsertText(buildMdImageTag(REST.storage.displayUrl(media.path), media.label));
				};

				scope.picker = {
					"selectMedia" : function (doc, $event) {
						$event.stopPropagation();
						$event.preventDefault();
						scope.mdInsertMedia(doc);
					}
				};

				scope.isMediaSelectorOpen = false;

				scope.toggleMediaSelector = function () {
					if (scope.isMediaSelectorOpen) {
						scope.closeMediaSelector();
					} else {
						scope.openMediaSelector();
					}
				};

				scope.closeMediaSelector = function () {
					$mediaPicker.empty();
					scope.isMediaSelectorOpen = false;
				};

				scope.openMediaSelector = function () {
					$mediaPicker.html(mediaPickerTpl);
					$compile($mediaPicker)(scope);
					scope.isMediaSelectorOpen = true;
				};

			}

		};
	}]);


})(window.jQuery, ace);