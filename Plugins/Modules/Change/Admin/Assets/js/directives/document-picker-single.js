(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerSingle', ['RbsChange.Modules', 'RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.Utils', 'RbsChange.REST', '$filter', function (Modules, Clipboard, FormsManager, Breadcrumb, MainMenu, Utils, REST, $filter) {
		return {
			// Utilisation : <page-header></page-header>
			restrict: 'EAC',

			// URL du template HTML
			templateUrl: 'Change/Admin/js/directives/document-picker-single.html',

			scope: {
				document: '=',
				formUrl: '@',
				masterLabel: '@',
				propertyLabel: '@',

				allowCreation: '@',
				allowEdition: '@',

				// Deprecated:
				collapsedLabelCreate: '@',
				collapsedLabelEdit: '@'
			},

			// Initialisation du scope (logique du composant)
			link: function (scope, iElement, attrs) {

				function getFormModel () {
					if (scope.document && scope.document.model) {
						return scope.document.model;
					} else {
						return attrs.acceptedModel;
					}
				}

				function getFormUrl () {
					if (scope.formUrl) {
						return scope.formUrl;
					}
					var model = getFormModel();

					return model.replace(/_/g, '/') + '/form.twig';
				}

				function getCreateLabel () {
					if (scope.masterLabel || scope.propertyLabel) {
						return (scope.masterLabel || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + scope.propertyLabel + " : création d'un nouvel élément";
					} else {
						return scope.collapsedLabelCreate;
					}
				}

				function getEditLabel () {
					if (scope.masterLabel || scope.propertyLabel) {
						return (scope.masterLabel || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + scope.propertyLabel + " : édition de " + scope.document.label;
					} else {
						return scope.collapsedLabelEdit;
					}
				}

				scope.clipboardValues = Clipboard.values;
				var first = scope.clipboardValues[0];
				if (first) {
					scope.clipboardFirstLabel = ' (' + Modules.models[first.model] + ' : ' + first.label + ')';
				} else {
					scope.clipboardFirstLabel = '';
				}

				// Edit or create

				scope.createDocument = function () {
					FormsManager.cascade(
							getFormUrl(),
							null,
							function (doc) {
								scope.document = doc;
							},
							getCreateLabel()
						);
				};

				scope.editSelectedDocument = function () {
					FormsManager.cascade(
						getFormUrl(),
						{ // TODO
							'id'   : scope.document.id,
							'LCID' : (scope.document.LCID || scope.language)
						},
						function (doc) {
							scope.document = doc;
						},
						getEditLabel()
					);
				};

				// Select

				scope.openSelector = function () {
					Breadcrumb.freeze();
					MainMenu.freeze();
					scope.selectorTitle = attrs.selectorTitle || Modules.models[attrs.acceptedModel];
					scope.documentPickerUrl = 'Change/Admin/document-picker-list.twig?model=' + attrs.acceptedModel;
				};

				scope.closeSelector = function () {
					Breadcrumb.unfreeze();
					MainMenu.unfreeze();
					scope.documentPickerUrl = null;
				};

				scope.getFromClipboard = function () {
					var items = Clipboard.getItems(true);
					if (items.length >= 1) {
						scope.document = items[0];
					}
				};

				scope.$watch('document', function () {
					if (scope.document) {
						scope.selectedModelName = $filter('modelLabel')(scope.document.model);
					}
				});

				scope.$watch('documentPickerUrl', function () {
					if (scope.documentPickerUrl) {
						$('#document-picker-backdrop').show();
					} else {
						$('#document-picker-backdrop').hide();
					}
				});

				scope.selectDocument = function (document) {
					scope.document = document;
					scope.closeSelector();
				};

				scope.selectDocuments = function (documents) {
					scope.document = documents[0];
					scope.closeSelector();
				};

				scope.replaceWithDocuments = function (documents) {
					scope.document = documents[0];
					scope.closeSelector();
				};



			}


		};
	}]);

})(window.jQuery);