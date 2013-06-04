(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerSingle', ['RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.Utils', 'RbsChange.REST', '$filter', function (Clipboard, FormsManager, Breadcrumb, MainMenu, Utils, REST, $filter) {
		return {

			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-single.twig',
			require     : 'ngModel',

			scope       : true,

			link: function (scope, iElement, attrs, ngModel) {

				var	$el = $(iElement),
					inputEl = $el.find('input[name=label]');


				// Initialize ngModel

				ngModel.$render = function() {
					inputEl.val(ngModel.$viewValue ? ngModel.$viewValue.label : '');
				};
				ngModel.$render();


				scope.allowCreation = attrs.allowCreation;
				scope.allowEdition = attrs.allowEdition;


				function getFormModel () {
					if (ngModel.$viewValue && ngModel.$viewValue.model) {
						return ngModel.$viewValue.model;
					} else {
						return attrs.acceptedModel;
					}
				}

				function getFormUrl () {
					return getFormModel().replace(/_/g, '/') + '/form.twig';
				}

				function getCreateLabel () {
					return (scope.document.label || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + attrs.propertyLabel + " : création d'un nouvel élément";
				}

				function getEditLabel () {
					return (scope.document.label || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + attrs.propertyLabel + " : édition de " + ngModel.$viewValue.label;
				}


				// Clipboard

				scope.clipboardValues = Clipboard.values;
				var first = scope.clipboardValues[0];
				if (first) {
					scope.clipboardFirstLabel = ' (' + first.label + ')';
				} else {
					scope.clipboardFirstLabel = '';
				}

				scope.getFromClipboard = function () {
					var items = Clipboard.getItems(true);
					if (items.length >= 1) {
						scope.selectDocument(items[0]);
					}
				};


				// Edit or create

				scope.createDocument = function () {
					FormsManager.cascade(
						getFormUrl(),
						null,
						function (doc) {
							scope.selectDocument(doc);
						},
						getCreateLabel()
					);
				};

				scope.editSelectedDocument = function ($event) {
					var doc, msg;
					doc = ngModel.$viewValue;
					msg = FormsManager.cascade(
						getFormUrl(),
						{
							'id'   : doc.id,
							'LCID' : (doc.LCID || scope.language)
						},
						function (editedDoc) {
							if (!angular.equals(doc, editedDoc)) {
								scope.selectDocument(editedDoc);
							}
						},
						getEditLabel()
					);
					if (msg) {
						// TODO Use a nicer way to inform the user :)
						window.alert(msg);
					}
				};


				// Selection

				scope.$watch('documentPickerUrl', function () {
					if (scope.documentPickerUrl) {
						$('#document-picker-backdrop').show();
					} else {
						$('#document-picker-backdrop').hide();
					}
				});

				scope.openSelector = function () {
					Breadcrumb.freeze();
					MainMenu.freeze();
					scope.selectorTitle = attrs.selectorTitle;
					scope.documentPickerUrl = 'Rbs/Admin/document-picker-list.twig?model=' + attrs.acceptedModel;
				};

				scope.closeSelector = function () {
					Breadcrumb.unfreeze();
					MainMenu.unfreeze();
					scope.documentPickerUrl = null;
				};

				scope.selectDocument = function (document) {
					ngModel.$setViewValue(document);
					ngModel.$render();
					scope.closeSelector();
				};

				scope.picker = {
					"selectDocument" : function (d) {
						scope.selectDocument(d);
					}
				};

				scope.clear = function () {
					ngModel.$setViewValue(null);
					ngModel.$render();
				};

				scope.isEmpty = function () {
					return ! ngModel.$viewValue;
				};

			}

		};
	}]);

})(window.jQuery);