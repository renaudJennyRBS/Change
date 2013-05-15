(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerMultiple', ['RbsChange.Modules', 'RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', '$filter', 'RbsChange.ArrayUtils', function (Modules, Clipboard, FormsManager, Breadcrumb, MainMenu, $filter, ArrayUtils) {

		var counter = 0;

		return {
			restrict: 'ECA',

			templateUrl: 'Change/Admin/js/directives/document-picker-multiple.twig',

			scope: {
				documents: '=',
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
					var model = getFormModel();
					return model.replace(/_/g, '/') + '/form.twig';
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

				scope.createDocument = function () {
					FormsManager.cascade(
							getFormUrl(),
							null,
							function (doc) {
								if (!scope.documents) {
									scope.documents = [];
								}
								scope.documents.push(doc);
							},
							scope.collapsedLabelCreate
						);
				};

				scope.clear = function () {
					ArrayUtils.clear(scope.documents);
				};

				scope.openSelector = function () {
					Breadcrumb.freeze();
					MainMenu.freeze();
					scope.selectorTitle = attrs.selectorTitle;
					scope.documentPickerUrl = 'Change/Admin/document-picker-list.twig?multiple=true&model=' + attrs.acceptedModel;
				};

				scope.closeSelector = function () {
					Breadcrumb.unfreeze();
					MainMenu.unfreeze();
					scope.documentPickerUrl = null;
				};

				scope.getFromClipboard = function () {
					scope.selectDocuments(Clipboard.getItems(true));
				};

				scope.$watch('documentPickerUrl', function () {
					if (scope.documentPickerUrl) {
						$('#document-picker-backdrop').show();
					} else {
						$('#document-picker-backdrop').hide();
					}
				});

				scope.selectDocument = function (doc) {
					console.log("Doc=", doc);
					if ( ! scope.documents ) {
						scope.documents = [];
					}
					if (scope.documents.indexOf(doc) === -1) {
						scope.documents.push(doc);
					}
				};

				scope.selectDocuments = function (docs) {
					angular.forEach(docs, function (doc) {
						scope.selectDocument(doc);
						doc.selected = false;
					});
				};

				scope.replaceWithDocument = function (doc) {
					if ( ! scope.documents ) {
						scope.documents = [];
					} else {
						scope.clear();
					}
					scope.documents.push(doc);
				};

				scope.replaceWithDocuments = function (docs) {
					if ( ! scope.documents ) {
						scope.documents = [];
					} else {
						scope.clear();
					}
					scope.selectDocuments(docs);
				};

				scope.selectDocumentFirst = function (doc) {
					if ( ! scope.documents ) {
						scope.documents = [];
					}
					if (scope.documents.indexOf(doc) === -1) {
						scope.documents.unshift(doc);
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
							for (var i = 0 ; i < scope.documents.length ; i++) {
								if (scope.documents[i].selected) {
									selected = i;
									nb++;
								}
							}
							// Move items only if one is selected
							if (nb === 1 && selected !== -1) {
								if (event.keyCode === 38) { // top
									if (selected > 0) {
										ArrayUtils.move(scope.documents, selected, selected-1);
										selected--;
										scope.$apply();
										$el.find('li:eq(' + selected + ') a.delete').focus();
									}
								} else if (event.keyCode === 40) { // bottom
									if (selected < (scope.documents.length-1)) {
										ArrayUtils.move(scope.documents, selected, selected+1);
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
					ArrayUtils.remove(scope.documents, index);
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
								scope.documents[i].selected = true;
							}
						} else {
							scope.clearSelected();
							scope.documents[index].selected = ! scope.documents[index].selected;
						}
					} else {
						scope.documents[index].selected = ! scope.documents[index].selected;
					}
					$el.find('li:eq(' + index + ') a.delete').focus();
					if (scope.documents[index].selected) {
						lastSelectedItemIndex = index;
					} else {
						lastSelectedItemIndex = -1;
					}
				};

				scope.clearSelected = function () {
					angular.forEach(scope.documents, function (item) {
						item.selected = false;
					});
				};

				scope.deleteSelected = function () {
					var i;
					if (scope.readonly) {
						return;
					}
					for (i = scope.documents.length-1 ; i >= 0 ; i--) {
						if (scope.documents[i].selected) {
							scope.remove(i);
						}
					}
				};
			}

		};
	}]);

})(window.jQuery);