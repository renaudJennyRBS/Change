(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerMultiple', ['RbsChange.Modules', 'RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', '$filter', 'RbsChange.ArrayUtils', '$compile', '$http', function (Modules, Clipboard, FormsManager, Breadcrumb, MainMenu, $filter, ArrayUtils, $compile, $http) {

		return {
			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-multiple.twig',
			require     : 'ngModel',

			scope       : true,

			// Initialisation du scope (logique du composant)
			link: function (scope, elm, attrs, ngModel) {

				var	$el = $(elm),
					documentList;

				ngModel.$render = function() {
					scope.documents = ngModel.$viewValue;
				};
				ngModel.$render();


				scope.allowCreation = attrs.allowCreation;
				scope.allowEdition = attrs.allowEdition;
				scope.acceptedModel = attrs.acceptedModel;

				function getFormUrl () {
					return attrs.acceptedModel.replace(/_/g, '/') + '/form.twig';
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

				var	$picker = $el.find('.document-picker-embedded'),
					$pickerContents = $picker.find('[data-role="picker-contents"]');

				scope.openSelector = function () {
					Breadcrumb.freeze();
					MainMenu.freeze();
					scope.selectorTitle = attrs.selectorTitle;

					var url;
					if (attrs.picker === 'model') {
						url = attrs.acceptedModel.replace(/_/g, '/') + '/picker.twig?model=' + attrs.acceptedModel;
					} else {
						url = 'Rbs/Admin/document-picker-list.twig?model=' + attrs.acceptedModel;
					}

					$http.get(url).success(function (html) {
						var $html = $(html);
						if ($html.is('rbs-document-list')) {
							$html.attr('actions', '');
						} else {
							$html.find('rbs-document-list').attr('actions', '');
							documentList = angular.element($html.find('rbs-document-list').first()).scope();
						}

						if ($html.find('quick-actions').length) {
							$html.find('quick-actions').empty();
						} else {
							$html.append(
								'<quick-actions>' +
									'<a href="javascript:;" ng-click="extend.replaceWithDocument(doc)"><i class="icon-arrow-right"></i> tout remplacer par cet élément</a>' +
								'</quick-actions>'
							);
						}

						$pickerContents.empty().append($html);
						$compile($html)(scope);
						$('#document-picker-backdrop').show();
						$picker.show();

						if ($html.is('rbs-document-list')) {
							documentList = angular.element($html).scope();
						} else {
							documentList = angular.element($html.find('rbs-document-list').first()).scope();
						}

					}).error(function (data) {
							$('#document-picker-backdrop').show();
							$pickerContents.html('<div class="alert alert-danger">Could not load picker template at <em>' + url + '</em></div>');
							$picker.show();
						});
				};

				scope.closeSelector = function () {
					Breadcrumb.unfreeze();
					MainMenu.unfreeze();
					$picker.hide();
					$('#document-picker-backdrop').hide();
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

				scope.appendSelected = function () {
					var value, docs = documentList.selectedDocuments;
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

				scope.prependSelected = function () {
					var value, docs = documentList.selectedDocuments;
					if (angular.isArray(ngModel.$viewValue)) {
						value = ngModel.$viewValue;
						angular.forEach(docs, function (doc) {
							if (ArrayUtils.inArray(doc, ngModel.$viewValue) === -1) {
								value.unshift(doc);
							}
						});
						ngModel.$setViewValue(value);
					} else {
						ngModel.$setViewValue(docs);
					}
					ngModel.$render();
				};

				scope.replaceWithSelected = function () {
					ngModel.$setViewValue(documentList.selectedDocuments);
					ngModel.$render();
				};

				scope.picker = {
					"replaceWithDocument" : function (doc) {
						ngModel.$setViewValue([doc]);
						ngModel.$render();
					},
					"selectDocument" : function (doc) {
						if (doc.selected === undefined) {
							doc.selected = true;
						} else {
							doc.selected = ! doc.selected;
						}
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