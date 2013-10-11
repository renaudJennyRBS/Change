(function ($, ace) {

	"use strict";

	var app = angular.module('RbsChange'),
		MIN_HEIGHT = 150,
		editorIdCounter = 0;

	/**
	 * RichText input field.
	 */
	app.directive('rbsRichTextInput', ['$timeout', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Device', '$compile', function ($timeout, REST, Utils, Device, $compile) {

		return {
			restrict : 'EC',
			require : 'ngModel',
			scope : true,
			// TODO Localization
			template :
				'<div class="tabbable">' +
					'<ul ng-show="useTabs" class="nav nav-tabs" style="margin-bottom: 0">' +
						'<li class="active"><a href="javascript:;" data-role="editor" data-target="#rbsInputMarkdown(=editorId=)TabEditor" data-toggle="tab">Éditeur</a></li>' +
						'<li><a href="javascript:;" data-role="preview" data-target="#rbsInputMarkdown(=editorId=)TabPreview" data-toggle="tab">Aperçu <i ng-show="previewing" class="icon-spinner icon-spin"></i></a></li>' +
					'</ul>' +
					'<div class="tab-content">' +
						'<div class="tab-pane active" id="rbsInputMarkdown(=editorId=)TabEditor">' +
							'<div class="btn-toolbar" ng-if="! readOnly">' +

								'<span class="pull-right">' +
									'<button type="button" class="btn btn-sm btn-info" ng-click="openHelp()"><i class="icon-info-sign"></i></button>' +
									'<button ng-if="!useTabs" type="button" class="btn btn-sm" ng-click="preview()"><i class="icon-eye-open"></i></button>' +
								'</span>' +

								// TODO Remove 'ng-disabled="useTextarea"' when Textarea is fully supported

								// Headings
								'<div class="btn-group">' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm dropdown-toggle" data-toggle="dropdown" href="#">Titre <span class="caret"></span></button>' +
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
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-click="mdBold()"><i class="icon-bold"></i></button>' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-click="mdItalic()"><i class="icon-italic"></i></button>' +
								'</div>' +

								// Undo/redo
								'<div class="btn-group">' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-click="mdUndo()"><i class="icon-undo"></i></button>' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-click="mdRedo()"><i class="icon-repeat"></i></button>' +
								'</div>' +

								// Media
								'<div class="btn-group" ng-if="availableSelectors.media">' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-class="{active:currentSelector==\'media\'}" ng-click="toggleSelector(\'media\')"><i class="icon-picture"></i></button>' +
								'</div>' +

								// Links
								'<div class="btn-group" ng-if="availableSelectors.links">' +
									'<button type="button" title="Insérer un lien interne" ng-disabled="useTextarea" class="btn btn-sm" ng-class="{active:currentSelector==\'link\'}" ng-click="toggleSelector(\'link\')"><i class="icon-link"></i></button>' +
									'<button type="button" title="Insérer un lien externe" ng-disabled="useTextarea" class="btn btn-sm" ng-click="insertExternalLink()"><i class="icon-external-link"></i></button>' +
								'</div>' +

								// Users
								'<div class="btn-group" ng-if="availableSelectors.users">' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-class="{active:currentSelector==\'user\'}" ng-click="toggleSelector(\'user\')"><i class="icon-user"></i></button>' +
								'</div>' +

								// Groups
								'<div class="btn-group" ng-if="availableSelectors.usergroups">' +
									'<button type="button" ng-disabled="useTextarea" class="btn btn-sm" ng-class="{active:currentSelector==\'usergroup\'}" ng-click="toggleSelector(\'usergroup\')"><i class="icon-group"></i></button>' +
								'</div>' +

							'</div>' +

							'<div class="media-picker"></div>' +
							'<div class="link-picker"></div>' +
							'<div class="user-picker"></div>' +
							'<div class="usergroup-picker"></div>' +

							'<div data-role="ace-editor"></div>' +
						'</div>' +
						'<div class="tab-pane" data-role="preview-container" id="rbsInputMarkdown(=editorId=)TabPreview" style="background:white;">' +
							'<button ng-if="!useTabs" type="button" class="btn btn-sm pull-right active" ng-click="closePreview()" style="margin:5px;"><i class="icon-eye-open"></i></button>' +
							'<div class="preview-content"></div>' +
						'</div>' +
					'</div>' +
				'</div>',

			link : function (scope, element, attrs, ngModel) {

				var	editor,
					session,
					$textarea,
					id,
					$previewEl = element.find('div[data-role="preview-container"] .preview-content'),
					$editorTab,
					$selectorsContainer,
					$selectors;

				scope.useTabs = angular.isUndefined(attrs.useTabs) || attrs.useTabs === 'true';

				// Init available selectors.
				scope.availableSelectors = {
					'media': true,
					'links': true,
					'users': false,
					'usergroups': false
				};
				if (attrs.profile === 'Admin') {
					scope.availableSelectors.users = true;
					scope.availableSelectors.usergroups = true;
				}

				function ensureSelectorsReady () {
					if (! $selectors) {

						var seEditor = element.closest('structure-editor');
						if (seEditor.length === 1) {
							$selectorsContainer = seEditor.find('.rich-text-input-selectors-container').first();
							$selectorsContainer.append(
								'<div class="media-picker"></div>' +
								'<div class="link-picker"></div>' +
								'<div class="user-picker"></div>' +
								'<div class="usergroup-picker"></div>'
							);
						} else {
							$selectorsContainer = element;
						}
						$selectors = {
							'media' : $selectorsContainer.find('div.media-picker'),
							'link' : $selectorsContainer.find('div.link-picker'),
							'user' : $selectorsContainer.find('div.user-picker'),
							'usergroup' : $selectorsContainer.find('div.usergroup-picker')
						};
					}
				}

				scope.editorId = ++editorIdCounter;

				id = "rbsInputMarkdownAceEditor" + scope.editorId;
				element.find('[data-role="ace-editor"]').attr('id', id);

				// Initialize ACE editor when the scope has been completely applied.
				function initWithAceEditor () {
					editor = ace.edit(id);
					session = editor.getSession();
					session.setMode("ace/mode/markdown");
					session.setUseWrapMode(true);
					session.setWrapLimitRange(null, null);
					session.setFoldStyle("manual");
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

					heightUpdateFunction(id, editor);
					editor.getSession().on('change', function () {
						heightUpdateFunction(id, editor);
					});

					ngModel.$render = function() {
						if (angular.isObject(ngModel.$viewValue))
						{
							editor.setValue(ngModel.$viewValue.t);
						}
						else
						{
							editor.setValue(ngModel.$viewValue);
						}
					};

					session.on('change', function () {
						$timeout(function () {
							if (angular.isObject(ngModel.$viewValue))
							{
								ngModel.$viewValue.t = editor.getValue();
								ngModel.$setViewValue(ngModel.$viewValue);
							}
							else
							{
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

					editor.on("drop", function (event) {
						event.stopPropagation();
						event.preventDefault();
					});

					ngModel.$render();
				}


				// Initialize Textarea-based editor.
				function initWithTextarea () {

					$textarea = $('<textarea></textarea>');
					$('#' + id).append($textarea);

					// If 'id' and 'input-id' attributes are found are equal, move this id to the real input field
					// so that the binding with the '<label/>' element works as expected.
					// (see Directives in 'Rbs/Admin/Assets/js/directives/form-fields.js').
					if (attrs.id && attrs.id === attrs.inputId) {
						$textarea.attr('id', attrs.id);
						element.removeAttr('id');
						element.removeAttr('input-id');
					}

					$editorTab = $('#rbsInputMarkdown' + scope.editorId + 'TabEditor');

					ngModel.$render = function() {
						if (angular.isObject(ngModel.$viewValue))
						{
							$textarea.val(ngModel.$viewValue.t);
						}
						else
						{
							$textarea.val(ngModel.$viewValue);
						}
					};

					$textarea.on('change', function () {
						scope.$apply(function () {
							if (angular.isObject(ngModel.$viewValue))
							{
								ngModel.$viewValue.t = $textarea.val();
								ngModel.$setViewValue(ngModel.$viewValue);
							}
							else
							{
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

					$textarea.on("drop", function (event) {
						event.stopPropagation();
						event.preventDefault();
					});

					ngModel.$render();
				}


				function canUseAceEditor () {
					return ! Device.isMultiTouch();
				}

				scope.useTextarea = ! canUseAceEditor();

				$timeout(function () {
					if (canUseAceEditor()) {
						initWithAceEditor();
					} else {
						initWithTextarea();
					}
				});


				function getEditorContent () {
					if (canUseAceEditor()) {
						return editor.getValue();
					} else {
						return $textarea.val();
					}
				}


				// Auto-height.
				function heightUpdateFunction (id, editor) {
					// http://stackoverflow.com/questions/11584061/
					var newHeight =
						editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();
					newHeight = Math.max(MIN_HEIGHT, newHeight);
					$('#'+id).height(newHeight + "px");
					// This call is required for the editor to fix all of
					// its inner structure for adapting to a change in size
					editor.resize();
					$previewEl.css('min-height', $editorTab.outerHeight()+"px");
				}


				scope.preview = function () {
					element.find('a[data-toggle="tab"][data-role="preview"]').tab('show');
				};

				scope.closePreview = function () {
					element.find('a[data-toggle="tab"][data-role="editor"]').tab('show');
				};

				// Tabs and preview.
				element.find('a[data-toggle="tab"]').on('show', function (e) {
					if ($(e.target).data('role') === 'preview') {
						$previewEl.empty();
						scope.previewing = true;
						var params = {
							'profile': (attrs.previewProfile || 'Website'),
							'editor' : 'Markdown'
						};
						REST.postAction('renderRichText', getEditorContent(), params).then(function (data) {
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
							ec = l; // FIXME 'l' or 'line' ? :(
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

						// FIXME : fix replacement of # at the end of the line

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


				//
				// Resources selectors
				//

				scope.picker = {
					"insertMedia" : function (doc, $event) {
						$event.stopPropagation();
						$event.preventDefault();
						scope.mdInsertMedia(doc);
					},
					"insertDocumentLink" : function (doc, $event, route) {
						$event.stopPropagation();
						$event.preventDefault();
						scope.mdInsertDocumentLink(doc, route);
					},
					"insertIdentifier" : function (doc, $event, profile) {
						$event.stopPropagation();
						$event.preventDefault();
						scope.mdInsertIdentifier(doc, profile);
					}
				};

				scope.currentSelector = null;

				scope.toggleSelector = function (name) {
					if (scope.currentSelector === name) {
						scope.closeSelector(name);
					} else {
						scope.closeSelector(scope.currentSelector);
						scope.openSelector(name);
					}
				};

				scope.closeSelector = function (name) {
					if (name && $selectors[name]) {
						$selectors[name].hide();
					}
					scope.currentSelector = null;
				};

				scope.openSelector = function (name) {
					if (scope.currentSelector === name) {
						return;
					}

					ensureSelectorsReady();

					scope.closeSelector(scope.currentSelector);
					scope.currentSelector = name;
					if (name && $selectors[name]) {
						var $sel = $selectors[name];
						if (! $sel.children().length) {
							$sel.html('<rbs-rich-text-input-' + name + '-selector></rbs-rich-text-input-' + name + '-selector>');
							$compile($sel)(scope);
						}
						$sel.show();
					}
				};


				//
				// Media insertion
				//

				function buildMdImageTag (imageId, imageLabel) {
					var	range = editor.getSelectionRange(), alt;
					alt = range.isEmpty() ? imageLabel : session.getTextRange(range);
					return '![' + alt + '](' + imageId + ' "' + imageLabel + '")';
				}

				scope.mdInsertMedia = function (media) {
					scope.mdInsertText(buildMdImageTag(media.id, media.label));
				};


				//
				// Links insertion
				//

				function buildMdLinkTag (href, title) {
					var	range = editor.getSelectionRange(), text;
					if (range.isEmpty()) {
						text = title;
					} else {
						text = session.getTextRange(range);
					}
					return '[' + text + '](' + href + ' "' + title + '")';
				}

				scope.mdInsertDocumentLink = function (doc, route) {
					var href = doc.id;
					/*
					TODO See if MarkdownParser can handle this.
					if (doc.LCID)
					{
						href += ',' + doc.LCID;
					}*/
					if (route)
					{
						href += ',' + route;
					}
					scope.mdInsertText(buildMdLinkTag(href, doc.label));
				};

				scope.mdInsertIdentifier = function (doc, profile) {
					REST.resource(doc.id).then(function (userOrGroup){
						if(profile === 'user')
						{
							scope.mdInsertText('@' + userOrGroup.identifier);
						}
						else if(profile === 'usergroup')
						{
							scope.mdInsertText('@+' + userOrGroup.identifier);
						}
					});
				};

				scope.insertExternalLink = function () {
					var	href = prompt("Veuillez saisir l'adresse de la page à lier"),
						title = prompt("Veuillez saisir le titre du lien (optionnel)");
					scope.mdInsertText(buildMdLinkTag(href, title || href));
				};


				//
				// Help
				//

				scope.openHelp = function () {
					window.open('http://fr.wikipedia.org/wiki/Markdown#Quelques_exemples', 'rbsChangeHelp', 'width=800,height=600,scrollbars=yes,resizable=yes');
				};
			}

		};
	}]);


	/**
	 * Media selector
	 */
	app.directive('rbsRichTextInputMediaSelector', [ function () {
		return {
			restrict : 'E',
			scope    : false,
			// TODO Localization
			template :
				'<div class="rbs-rich-text-input-inner-selector">' +
					'<button type="button" class="close pull-right" ng-click="closeSelector(\'media\')">&times;</button>' +
					'<h4>Sélectionner une image à insérer dans l\'éditeur ci-dessous</h4>' +
					'<rbs-document-list class="grid-small" model="Rbs_Media_Image" display="grid" toolbar="false" extend="picker">' +
						'<column name="path" thumbnail="XS"></column>' +
						'<grid-item>' +
							'<img rbs-storage-image="doc" thumbnail="XS"/>' +
							'<a style="display:block" href="javascript:;" ng-click="extend.insertMedia(doc, $event)">(= doc.label =)</a>' +
						'</grid-item>' +
					'</rbs-document-list>' +
				'</div>',

			compile : function (tElement) {
				tElement.find("rbs-document-list").attr("data-dlid", "rbsRichTextInputUsergroupPicker" + ++editorIdCounter);
			}
		};
	}]);


	/**
	 * Document selector for links
	 */
	app.directive('rbsRichTextInputLinkSelector', [ function () {
		return {
			restrict : 'E',
			scope    : true,
			// TODO Localization
			template :
				'<div class="rbs-rich-text-input-inner-selector">' +
					'<button type="button" class="close pull-right" ng-click="closeSelector(\'link\')">&times;</button>' +
					'<h4>Sélectionner un document à lier dans l\'éditeur ci-dessous</h4>' +
					'<rbs-model-selector filter="{publishable:true}" model="selectedModel"></rbs-model-selector>' +
					'<rbs-document-list display="list" model="(= selectedModel.name =)" toolbar="false" extend="picker">' +
						'<column name="label" label="Label">' +
							'<a href="javascript:;" ng-click="extend.insertDocumentLink(doc, $event)">(= doc.label =)</a>' +
						'</column>' +
					'</rbs-document-list>' +
				'</div>',

			compile : function (tElement) {
				tElement.find("rbs-document-list").attr("data-dlid", "rbsRichTextInputUsergroupPicker" + ++editorIdCounter);
			}
		};
	}]);

	/**
	 * Document selector for users
	 */
	app.directive('rbsRichTextInputUserSelector', [ function () {
		return {
			restrict : 'E',
			scope    : true,
			// TODO Localization
			template :
				'<div class="rbs-rich-text-input-inner-selector">' +
					'<button type="button" class="close pull-right" ng-click="closeSelector(\'user\')">&times;</button>' +
					'<h4>Sélectionner un utilisateur à insérer dans l\'éditeur ci-dessous</h4>' +
					'<rbs-document-list model="Rbs_User_User" query="userListQuery" toolbar="false" extend="picker">' +
					'<column name="identifier">' +
					'<a href="javascript:;" ng-click="extend.insertIdentifier(doc, $event, \'user\')">@(= doc.identifier =)</a>' +
					'</column>' +
					'<column name="pseudonym">' +
					'<a href="javascript:;" ng-click="extend.insertIdentifier(doc, $event, \'user\')">(= doc.pseudonym =)</a>' +
					'</column>' +
					'</rbs-document-list>' +
				'</div>',

			//TODO filter the user, include only activated user.
			compile : function (tElement) {
				tElement.find("rbs-document-list").attr("data-dlid", "rbsRichTextInputUsergroupPicker" + ++editorIdCounter);
			}
		};
	}]);

	/**
	 * Document selector for user groups
	 */
	app.directive('rbsRichTextInputUsergroupSelector', [ function () {
		return {
			restrict : 'E',
			scope    : true,
			// TODO Localization
			template :
				'<div class="rbs-rich-text-input-inner-selector">' +
					'<button type="button" class="close pull-right" ng-click="closeSelector(\'usergroup\')">&times;</button>' +
					'<h4>Sélectionner un groupe d\'utilisateurs à insérer dans l\'éditeur ci-dessous</h4>' +
					'<rbs-document-list model="Rbs_User_Group" toolbar="false" extend="picker">' +
					'<column name="identifier">' +
					'<a href="javascript:;" ng-click="extend.insertIdentifier(doc, $event, \'usergroup\')">@+(= doc.identifier =)</a>' +
					'</column>' +
					'<column name="label">' +
					'<a href="javascript:;" ng-click="extend.insertIdentifier(doc, $event, \'usergroup\')">(= doc.label =)</a>' +
					'</column>' +
					'</rbs-document-list>' +
				'</div>',

			compile : function (tElement) {
				tElement.find("rbs-document-list").attr("data-dlid", "rbsRichTextInputUsergroupPicker" + ++editorIdCounter);
			}
		};
	}]);

	function parseRbsDocumentHref (href) {
		var doc, matches;

		matches = href.match(/^([a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+),(\d+)(,([a-z]{2}_[A-Z]{2}))?(,([a-zA-Z0-9\-_]+))?$/);
		//                      11111111111111111111111111111111111111   222    44444444444444444      666666666666666
		if (matches === null) {
			return null;
		}

		doc = {
			"model" : matches[1],
			"id" : matches[2]
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
	app.directive('rbsDocumentHref', ['RbsChange.UrlManager', 'RbsChange.Settings', function (UrlManager, Settings) {
		return {

			restrict : 'A',
			priority : 1001,

			link : function (scope, element, attrs)
			{
				if (! element.is('a')) {
					console.warn("Directive 'rbs-document-href' only works on <a></a> elements.");
				}
				if (! attrs.rbsDocumentHref) {
					throw new Error("Attribute 'rbs-document-href' must not be empty. Should be: 'model,id[,LCID[,routeName]]");
				}

				attrs.$observe('rbsDocumentHref', function (href) {
					console.log("href=", href);
					var doc = parseRbsDocumentHref(href);
					if (doc !== null) {
						doc.LCID = doc.LCID || Settings.get('LCID');
						element.attr('href', UrlManager.getUrl(doc, null, doc.route || 'form'));
					}
				});
			}

		};
	}]);


	/**
	 *
	 */
	app.directive('rbsDocumentPopover', ['$timeout', '$q', 'RbsChange.Settings', 'RbsChange.REST', '$compile', function ($timeout, $q, Settings, REST, $compile) {

		var popovers = [],
			POPOVER_WIDTH = 200,
			POPOVER_DEFAULT_DELAY = 500;

		// Close all registered popovers when
		$(window.document).on('click.rbsDocumentPopover.close', function () {
			angular.forEach(popovers, function (popover) {
				if (popover.visible) {
					popover.element.popover('hide');
					popover.visible = false;
				}
			});
		});

		function registerPopover (elm) {
			var popover = {
				element : elm,
				index : popovers.length,
				visible : null
			};
			popovers.push(popover);
			return popover;
		}

		function unregisterPopover (popover) {
			var index = popover.index;
			if (index >= 0 && index < popovers.length) {
				popovers.splice(index, 1);
			}
		}

		return {

			restrict : 'A',
			priority : 1000,

			link : function (scope, element, attrs)
			{
				var	valueAttr = null,
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
					if (! isNaN(d)) {
						delay = d;
					}
				}

				// This directive can work well if the 'rbs-document-href' directive is present.
				if (attrs.rbsDocumentPopover) {
					valueAttr = 'rbsDocumentPopover';
				} else if (attrs.rbsDocumentHref) {
					valueAttr = 'rbsDocumentHref';
				} else {
					throw new Error("Unable to find Document information. Please provide one of these attributes: 'rbs-document-popover' or 'rbs-document-href'.");
				}

				attrs.$observe(valueAttr, function (href) {
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
				scope.$on('$destroy', function () {
					unregisterPopover(popover);
				});

				// Install event handlers

				if (trigger === 'hover') {
					element.on('hover.rbsDocumentPopover', triggerSelector, function (event) {
						event.preventDefault();
						event.stopPropagation();

						// Nothing to do if document information is unavailable.
						if (!doc) {
							return;
						}

						if (event.type === 'mouseenter' && ! popover.visible) {
							popoverTimer = $timeout(function () {
								preparePopover(event.shiftKey, event.pageX).then(function () {
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
					element.on('click.rbsDocumentPopover', triggerSelector, function (event) {
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
							$timeout(function () {
								preparePopover(event.shiftKey, event.pageX).then(function () {
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

				function preparePopover (reload, x)
				{
					if (! popoverReady || reload)
					{
						var	defered = $q.defer(),
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
						else if (x > ($(document).width()-POPOVER_WIDTH)) {
							placement = 'left';
						}

						// The following REST action will search for the following template:
						// - name: popover-preview.twig
						// - location: where other templates for the document (list.twig and editor.twig) are.
						REST.call(REST.getBaseUrl('admin/documentPreview'), {"id": doc.id}).then(function (result) {
							var options = {
								'container' : 'body',
								'placement' : placement,
								'trigger'   : 'manual',
								'html'      : true
							};
							//content need to be compiled
							$compile(result.content)(scope, function(cloneElm){
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

})(window.jQuery, ace);