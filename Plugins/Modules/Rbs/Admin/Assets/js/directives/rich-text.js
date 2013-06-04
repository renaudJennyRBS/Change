(function ($) {

	var app = angular.module('RbsChange');


	//=========================================================================
	//
	// RichTextEditor widget
	//
	//=========================================================================


	app.directive('richTextEditor', [ function () {
		return {

			restrict: 'EC',

			require: '?ngModel',

			// Initialisation du scope (logique du composant)
			link: function (scope, element, attrs, ngModel) {
				var $el = $(element);
				$el.attr('contenteditable', 'true');
				$el.addClass('editor');

				if (ngModel) {
					console.log("ngModel on rich text OK");
					// view -> model
					$el.bind('blur', function () {
						console.log("blur on rich text: ", $el.html());
						scope.$apply(function () {
							ngModel.$setViewValue($el.html());
						});
					});

					// model -> view
					ngModel.$render = function () {
						$el.html(ngModel.$viewValue);
					};

					// Init initial value
					$el.html(ngModel.$viewValue || '');
				}

			}

		};
	}]);



	app.service('RbsChange.RichTextEditorService', [ function () {

		var currentEditor = null;

		this.setEditor = function setEditor (editor) {
			currentEditor = editor;
		};

		this.getEditor = function getEditor () {
			return currentEditor;
		};

	}]);


	//=========================================================================
	//
	// RichTextToolbar widget
	//
	//=========================================================================


	app.directive('richTextToolbar', ['$compile', '$http', 'RbsChange.Clipboard', 'RbsChange.RichTextEditorService', function ($compile, $http, Clipboard, RichTextEditorService) {

		var COMMANDS, GROUPS, toolbarConfig;

		COMMANDS = {
			'bold': {
				label: 'Gras',
				tooltip: 'Gras',
				icon: 'bold',
				execute: function () {
					document.execCommand('bold', false, null);
				}
			},

			'italic': {
				label: 'Italique',
				tooltip: 'Italique',
				icon: 'italic',
				execute: function () {
					document.execCommand('italic', false, null);
				}
			},

			'underline': {
				label: '<u>S</u>',
				tooltip: 'Souligné',
				execute: function () {
					document.execCommand('underline', false, null);
				}
			},

			'strikethrough': {
				label: '<s>B</s>',
				tooltip: 'Barré',
				execute: function () {
					document.execCommand('insertHTML', false, '<s>' + window.getSelection() + '</s>');
				}
			},

			'align-left': {
				label: 'Gauche',
				tooltip: 'Aligné à gauche',
				icon: 'align-left',
				execute: function () {
					document.execCommand('justifyLeft', false, null);
				}
			},

			'align-right': {
				label: 'Droite',
				tooltip: 'Aligné à droite',
				icon: 'align-right',
				execute: function () {
					document.execCommand('justifyRight', false, null);
				}
			},

			'align-center': {
				label: 'Centré',
				tooltip: 'Centré',
				icon: 'align-center',
				execute: function () {
					document.execCommand('justifyCenter', false, null);
				}
			},

			'align-justify': {
				label: 'Justifié',
				tooltip: 'Justifié',
				icon: 'align-justify',
				execute: function () {
					document.execCommand('justifyFull', false, null);
				}
			},

			'lang': {
				label: 'Langue',
				icon: 'globe',
				tooltip: 'Changement de langue',
				execute: function () {
					window.alert(this.label);
				}
			},

			'abbr': {
				label: 'Abbr.',
				tooltip: 'Abbréviation',
				execute: function () {
					window.alert(this.label);
				}
			},

			// Titles

			'h1': {
				label: 'H1',
				tooltip: 'Titre de niveau 1',
				execute: function () {
					document.execCommand('heading', false, 'h1');
				}
			},

			'h2': {
				label: 'H2',
				tooltip: 'Titre de niveau 2',
				execute: function () {
					document.execCommand('heading', false, 'h2');
				}
			},

			'h3': {
				label: 'H3',
				tooltip: 'Titre de niveau 3',
				execute: function () {
					document.execCommand('heading', false, 'h3');
				}
			},

			'h4': {
				label: 'H4',
				tooltip: 'Titre de niveau 4',
				execute: function () {
					document.execCommand('heading', false, 'h4');
				}
			},

			'h5': {
				label: 'H5',
				tooltip: 'Titre de niveau 5',
				execute: function () {
					document.execCommand('heading', false, 'h5');
				}
			},

			'h6': {
				label: 'H6',
				tooltip: 'Titre de niveau 6',
				execute: function () {
					document.execCommand('heading', false, 'h6');
				}
			},

			// Lists

			'ordered-list': {
				label: 'Numérotée',
				tooltip: 'Liste numérotée',
				execute: function () {
					document.execCommand('insertOrderedList', false, null);
				}
			},

			'unordered-list': {
				label: 'à puces',
				tooltip: 'Liste à puces',
				execute: function () {
					document.execCommand('insertUnorderedList', false, null);
				}
			},

			'increase-level': {
				label: 'Augmenter le retrait',
				execute: function () {
					window.alert(this.label);
				}
			},

			'decrease-level': {
				label: 'Diminuer le retrait',
				execute: function () {
					window.alert(this.label);
				}
			},

			// Media

			'insert-image': {
				label: 'Image',
				icon: 'picture',
				execute: function (richText) {
					window.alert(this.label);
				}
			},

			'insert-video': {
				label: 'Video',
				icon: 'film',
				execute: function () {
					window.alert(this.label);
				}
			},

			'insert-snippet': {
				label: 'Snippet',
				icon: 'th-large',
				execute: function () {
					window.alert(this.label);
				}
			},

			'insert-clipboard': {
				label: 'Presse-papier',
				icon: 'list-alt',
				execute: function () {
					var items = Clipboard.getItems(), html, i;
					if (items.length > 0) {
						html = [ ];
						for (i=0 ; i<items.length ; i++) {
							html.push('<a href="?id=' + items[i].id + '">' + items[i].label + '</a>');
						}
						if (html.length === 1) {
							document.execCommand('insertHTML', false, html[0]);
						} else {
							document.execCommand('insertHTML', false, '<ul><li>' + html.join('</li><li>') + '</li></ul>');
						}
						Clipboard.markAsUsed(true);
					}
				}
			},

			// Other

			'special-chars': {
				tooltip: 'Caractères spéciaux',
				icon: 'font',
				execute: function () {
					window.alert(this.label);
				}
			}

		};


		GROUPS = {
			font: {
				label: "Police",
				commands: [ 'bold', 'italic', 'underline', 'strikethrough' ],
				displayLabels: false
			},
			alignment: {
				label: "Alignement",
				commands: [ 'align-left', 'align-center', 'align-right', 'align-justify' ],
				displayLabels: false
			},
			semantic: {
				label: "Sémantique",
				commands: [ 'lang', 'abbr' ],
				displayLabels: true,
				recommendedDisplay: 'dropdown'
			},
			title: {
				label: "Titre",
				commands: [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
				displayLabels: true,
				recommendedDisplay: 'dropdown'
			},
			list: {
				label: "Liste",
				commands: [ 'ordered-list', 'unordered-list', '-', 'increase-level' , 'decrease-level' ],
				displayLabels: true,
				recommendedDisplay: 'dropdown'
			},
			include: {
				label: "Insertion",
				commands: [ 'insert-image', 'insert-video', 'insert-snippet', 'insert-clipboard' ],
				displayLabels: false,
				recommendedDisplay: 'button-group'
			}
		};


		return {
			restrict: 'EC',

			link: function richTextToolbarLinkFn (scope, element) {

				// TODO Load toolbar config from the server?
				toolbarConfig = {
					"buttonSize": "btn-small",
					"displayGroupCaptions": true,
					"items": [
						{
							"group": "title"
						},
						{
							"group": "font"
						},
						{
							"group": "alignment"
						},
						{
							"group": "semantic",
							"display": "button-group",
							"displayLabels": false
						},
						{
							"command": "special-chars"
						},
						{
							"group": "include"
						},
						{
							"group": "list"
						}
					]
				};

				build();

				var buttonSize, hasGroupWithCaption;

				function build () {
					buttonSize = toolbarConfig.buttonSize || 'btn-small';
					element.addClass('btn-toolbar rich-text-toolbar');
					$compile(buildToolbar())(scope, function (clone) {
						element.append(clone);
					});
				}

				function buildToolbar () {
					hasGroupWithCaption = false;
					var	html = '',
						i;
					for (i=0 ; i<toolbarConfig.items.length ; i++) {
						html += renderItem(toolbarConfig.items[i]);
					}
					//html += renderSettingsItem();
					return html;
				}

				function renderItem (item, dropdown, displayLabel) {
					var html, group, command;
					if (item.group) { // Group of commands
						group = GROUPS[item.group];
						if (typeof item.displayLabels !== 'undefined') {
							group.displayLabels = item.displayLabels;
						}
						if (('display' in item && item.display === 'dropdown') || ((!('display' in item)) && group.recommendedDisplay === 'dropdown')) {
							return renderCommandGroupWithDropDown(group);
						} else { // button-group
							return renderCommandGroupWithButtonGroup(group);
						}
					} else if (item.command) {
						command = COMMANDS[item.command];
						command.id = item.command;
						return renderItem(command, false, true);
					} else {
						if (dropdown) {
							html = '<li><a href="javascript:;" tabindex="-1" ng-click="executeCommand(\'' + item.id + '\')">';
							if (item.icon) {
								html += '<i class="icon-' + item.icon + '"></i>';
								if (item.label || item.tooltip) {
									html += ' ';
								}
							}
							html += item.tooltip ? item.tooltip : item.label;
							return html + '</a></li>';
						} else {
							html = '<button type="button" class="btn ' + buttonSize + '"';
							if (item.tooltip) {
								html += ' title="' + item.tooltip + '"';
							} else if (item.icon) {
								html += ' title="' + item.label + '"';
							}
							html += ' ng-click="executeCommand(\'' + item.id + '\')">';
							if (item.icon) {
								html += '<i class="icon-' + item.icon + '"></i>';
							} else {
								displayLabel = true;
							}
							if (displayLabel && item.label) {
								if (item.icon) {
									html += ' ';
								}
								html += item.label;
							}
							return html + '</button>';
						}
					}
				}

				function renderCommandGroupWithDropDown (group) {
					var i, html, command;

					html = '<div class="btn-group">';
					html += '<a href="javascript:;" data-toggle="dropdown" class="btn dropdown-toggle ' + buttonSize + '">' + group.label + ' <span class="caret"></span></a>';
					html += '<ul role="menu" class="dropdown-menu">';
					for (i=0 ; i<group.commands.length ; i++) {
						if (group.commands[i] in COMMANDS) {
							command = COMMANDS[group.commands[i]];
							command.id = group.commands[i];
							html += renderItem(command, true, true);
						} else {
							if (group.commands[i] === '-') {
								html += '<li class="divider"></li>';
							}
							// ELSE: Bad toolbar configuration...
							// I think it's better to avoid this and continue running.
						}
					}
					return html + '</ul></div>';
				}

				function renderCommandGroupWithButtonGroup (group) {
					var i, command, html;

					html = '<div';
					if (group.toggle) {
						html += ' data-toggle="buttons-checkbox"';
					}
					html += ' class="btn-group';
					if (group.label && toolbarConfig.displayGroupCaptions) {
						html += ' has-captions"><label class="caption small">' + group.label + '</label>';
						hasGroupWithCaption = true;
					} else {
						html += '">';
					}
					for (i=0 ; i<group.commands.length ; i++) {
						if (group.commands[i] in COMMANDS) {
							command = COMMANDS[group.commands[i]];
							command.id = group.commands[i];
							html += renderItem(command, false, group.displayLabels);
						} else {
							if (group.commands[i] === '-') {
								html += '&nbsp;';
							}
							// ELSE: Bad toolbar configuration...
							// I think it's better to avoid this and continue running.
						}
					}

					return html + '</div>';
				}

				function renderSettingsItem () {
					return '<div class="btn-group pull-right">' +
						'<button type="button" data-toggle="dropdown" class="btn dropdown-toggle ' + buttonSize + '" title="Réglages"><i class="icon-cog"></i> <span class="caret"></span></button>' +
						'<ul role="menu" class="dropdown-menu pull-right">' +
						'<li class="nav-header">&Eacute;diteur</li>' +
						'<li><a href="javascript:;">WYSIWYG</a></li>' +
						'<li><a href="javascript:;">BBCode</a></li>' +
						'<li><a href="javascript:;">Markdown</a></li>' +
						'<li><a href="help/editors.html" help="#helpListTop"><i class="icon-question-sign"></i> Aide sur les éditeurs</a></li>' +
						'<li class="divider"></li>' +
						'<li><a href="javascript:;">Personnaliser la barre d\'outils...</a></li>' +
						'</ul>' +
						'</div>';
				}

				scope.executeCommand = function (command) {
					if (command in COMMANDS) {
						var editor = RichTextEditorService.getEditor();
						COMMANDS[command].execute(editor);
						if (editor) {
							editor.focus();
							if (angular.isFunction(editor.scope) && angular.isFunction(editor.scope().richTextCommandExecuted)) {
								editor.scope().richTextCommandExecuted(COMMANDS[command]);
							}
						}
						return true;
					}
					return false;
				};
			}

		};

	}]);

})( window.jQuery );