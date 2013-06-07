(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerMultiple', ['RbsChange.Modules', 'RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', '$filter', 'RbsChange.ArrayUtils', function (Modules, Clipboard, FormsManager, Breadcrumb, MainMenu, $filter, ArrayUtils) {

		return {
			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-multiple.twig',
			require     : 'ngModel',

			scope       : true,

			// Initialisation du scope (logique du composant)
			link: function (scope, elm, attrs, ngModel) {

				ngModel.$render = function() {
					scope.documents = ngModel.$viewValue;
				};
				ngModel.$render();


				scope.allowCreation = attrs.allowCreation;
				scope.allowEdition = attrs.allowEdition;


				function getFormModel () {
					return attrs.acceptedModel;
				}

				function getFormUrl () {
					return getFormModel().replace(/_/g, '/') + '/form.twig';
				}

				function getCreateLabel () {
					return (scope.document.label || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + attrs.propertyLabel + " : création d'un nouvel élément";
				}

				scope.readonly = attrs.readonly ? true : false;


				// Clipboard

				scope.clipboardValues = Clipboard.values;
				var first = scope.clipboardValues[0];
				if (first) {
					scope.clipboardFirstLabel = ' (' + Modules.models[first.model] + ' : ' + first.label + ')';
				} else {
					scope.clipboardFirstLabel = '';
				}

				scope.getFromClipboard = function () {
					scope.selectDocuments(Clipboard.getItems(true));
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
					if (attrs.picker === 'model') {
						scope.documentPickerUrl = attrs.acceptedModel.replace(/_/g, '/') + '/picker.twig?multiple=true&model=' + attrs.acceptedModel;
					} else {
						scope.documentPickerUrl = 'Rbs/Admin/document-picker-list.twig?multiple=true&model=' + attrs.acceptedModel;
					}
				};

				scope.closeSelector = function () {
					Breadcrumb.unfreeze();
					MainMenu.unfreeze();
					scope.documentPickerUrl = null;
				};

				scope.selectDocument = function (doc) {
					var value;
					if (angular.isArray(ngModel.$viewValue)) {
						if (ArrayUtils.inArray(doc, ngModel.$viewValue) === -1) {
							value = ngModel.$viewValue;
							value.push(doc);
						}
					} else {
						value = [doc];
					}
					ngModel.$setViewValue(value);
					ngModel.$render();
				};

				scope.selectDocuments = function (docs) {
					var value;
					if (angular.isArray(ngModel.$viewValue)) {
						value = ngModel.$viewValue;
						angular.forEach(docs, function (doc) {
							if (ArrayUtils.inArray(doc, ngModel.$viewValue) === -1) {
								value.push(doc);
							}
						});
						ngModel.$setViewValue(value);
					} else {
						ngModel.$setViewValue(docs);
					}
					ngModel.$render();
				};

				scope.replaceWithDocument = function (doc) {
					ngModel.$setViewValue([doc]);
					ngModel.$render();
				};

				scope.replaceWithDocuments = function (docs) {
					ngModel.$setViewValue(docs);
					ngModel.$render();
				};

				scope.picker = {
					"replaceWithDocuments" : function (d) {
						scope.replaceWithDocuments(d);
					},
					"replaceWithDocument" : function (d) {
						scope.replaceWithDocument(d);
					},
					"selectDocument" : function (d) {
						scope.selectDocument(d);
					},
					"selectDocuments" : function (d) {
						scope.selectDocuments(d);
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