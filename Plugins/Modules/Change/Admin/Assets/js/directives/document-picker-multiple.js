(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerMultiple', ['RbsChange.Modules', 'RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', '$filter', 'RbsChange.ArrayUtils', function (Modules, Clipboard, FormsManager, Breadcrumb, MainMenu, $filter, ArrayUtils) {

		var counter = 0;

		return {
			// Utilisation : <page-header></page-header>
			restrict: 'E',

			// URL du template HTML
			templateUrl: 'Change/Admin/js/directives/document-picker-multiple.html',

			scope: {
				items: '=',
				formUrl: '@',
				collapsedLabelCreate: '@',
				collapsedLabelEdit: '@'
			},

			// Initialisation du scope (logique du composant)
			link: function (scope, elm, attrs) {

				var $el = $(elm),
				lastSelectedItemIndex = -1,
				labelProperty = attrs.labelProperty || 'label';

				function getFormModel () {
					return attrs.acceptedModel;
				}

				function getFormUrl () {
					if (scope.formUrl) {
						return scope.formUrl;
					}
					var model = getFormModel();
					return model.replace('_', '/') + '/form.php';
				}

				scope.readonly = attrs.readonly ? true : false;

				scope.counter = ++counter;

				scope.clipboardValues = Clipboard.values;
				var first = scope.clipboardValues[0];
				if (first) {
					scope.clipboardFirstLabel = ' (' + Modules.models[first.model] + ' : ' + first.label + ')';
				} else {
					scope.clipboardFirstLabel = '';
				}

				scope.getDocumentUrl = function (doc) {
					// FIXME
					return 'media/media/' + doc.id;
				};

				scope.createDocument = function () {
					FormsManager.cascade(
							getFormUrl(),
							null,
							function (doc) {
								doc.model = getFormModel(); // FIXME Useless in real life :)
								if (!scope.items) {
									scope.items = [];
								}
								scope.items.push(doc);
							},
							scope.collapsedLabelCreate
						);
				};

				scope.editSelectedDocument = function (index) {
					FormsManager.cascade(
							getFormUrl(),
							{ 'id': scope.items[index].id },
							function (doc) {
								scope.items[index] = doc;
							},
							scope.collapsedLabelEdit.replace('{}', scope.getItemLabel(scope.items[index].label))
						);
				};

				scope.clear = function () {
					ArrayUtils.clear(scope.items);
				};

				scope.openSelector = function () {
					Breadcrumb.freeze();
					MainMenu.freeze();
					var p = attrs.acceptedModel.indexOf('/');
					var module = attrs.acceptedModel.substring(8, p);
					var model = attrs.acceptedModel.substring(p+1);
					scope.selectorTitle = attrs.selectorTitle || Modules.models[attrs.acceptedModel];
					scope.documentPickerUrl = 'modules/document-picker-list.php?multiple=true&module=' + module + '&model=' + model;
				};

				scope.closeSelector = function () {
					Breadcrumb.unfreeze();
					MainMenu.unfreeze();
					scope.documentPickerUrl = null;
				};

				scope.getFromClipboard = function () {
					var items = Clipboard.getItems(true);
					for (var i=0 ; i<items.length ; i++) {
						scope.items.push(items[i]);
					}
				};

				scope.$watch('documentPickerUrl', function () {
					if (scope.documentPickerUrl) {
						$('#document-picker-backdrop').show();
					} else {
						$('#document-picker-backdrop').hide();
					}
				});

				scope.selectDocument = function (doc) {
					if ( ! scope.items ) {
						scope.items = [];
					}
					if (scope.items.indexOf(doc) === -1) {
						scope.items.push(doc);
					}
				};

				scope.selectDocuments = function (docs) {
					angular.forEach(docs, function (doc) {
						scope.selectDocument(doc);
						doc.selected = false;
					});
				};

				scope.replaceWithDocument = function (doc) {
					if ( ! scope.items ) {
						scope.items = [];
					} else {
						scope.clear();
					}
					scope.items.push(doc);
				};

				scope.replaceWithDocuments = function (docs) {
					if ( ! scope.items ) {
						scope.items = [];
					} else {
						scope.clear();
					}
					scope.selectDocuments(docs);
				};

				scope.selectDocumentFirst = function (doc) {
					if ( ! scope.items ) {
						scope.items = [];
					}
					if (scope.items.indexOf(doc) === -1) {
						scope.items.unshift(doc);
					}
				};

				scope.getItemLabel = function (item) {
					var val = item[labelProperty];
					if (attrs.labelFilter) {
						val = $filter(attrs.labelFilter)(val);
					}
					return val;
				};

				if ( ! scope.readonly ) {
					$el.on('keydown', 'li a.delete', function (event) {
						if (event.keyCode === 46 || event.keyCode === 8) { // delete or back key
							scope.$apply(function () {
								scope.deleteSelected();
							});
						} else if (event.keyCode === 38 || event.keyCode === 40) {
							var selected = -1, nb = 0;
							for (var i = 0 ; i < scope.items.length ; i++) {
								if (scope.items[i].selected) {
									selected = i;
									nb++;
								}
							}
							// Move items only if one is selected
							if (nb === 1 && selected !== -1) {
								if (event.keyCode === 38) { // top
									if (selected > 0) {
										ArrayUtils.move(scope.items, selected, selected-1);
										selected--;
										scope.$apply();
										$el.find('li:eq(' + selected + ') a.delete').focus();
									}
								} else if (event.keyCode === 40) { // bottom
									if (selected < (scope.items.length-1)) {
										ArrayUtils.move(scope.items, selected, selected+1);
										selected++;
									}
								}
								scope.$apply();
								$el.find('li:eq(' + selected + ') a.delete').focus();
							}
						}
				});
				}

				scope.remove = function (index) {
					ArrayUtils.remove(scope.items, index);
				};

				scope.itemClicked = function (index, event) {
					if (scope.readonly) {
						return;
					}
					if ( ! event.metaKey ) {
						if (event.shiftKey && lastSelectedItemIndex !== -1) {
							var from = Math.min(lastSelectedItemIndex, index);
							var to = Math.max(lastSelectedItemIndex, index);
							for (var i=from ; i<=to ; i++) {
								scope.items[i].selected = true;
							}
						} else {
							scope.clearSelected();
							scope.items[index].selected = ! scope.items[index].selected;
						}
					} else {
						scope.items[index].selected = ! scope.items[index].selected;
					}
					$el.find('li:eq(' + index + ') a.delete').focus();
					if (scope.items[index].selected) {
						lastSelectedItemIndex = index;
					} else {
						lastSelectedItemIndex = -1;
					}
				};

				scope.clearSelected = function () {
					angular.forEach(scope.items, function (item) {
						item.selected = false;
					});
				};

				scope.deleteSelected = function () {
					var i;
					if (scope.readonly) {
						return;
					}
					for (i = scope.items.length-1 ; i >= 0 ; i--) {
						if (scope.items[i].selected) {
							scope.remove(i);
						}
					}
				};
			}

		};
	}]);

})(window.jQuery);