(function ($) {

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');

	app.directive('documentPickerSingle', ['RbsChange.Clipboard', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', '$http', '$compile', 'RbsChange.Utils', 'RbsChange.REST', '$filter', function (Clipboard, FormsManager, Breadcrumb, MainMenu, $http, $compile, Utils, REST, $filter) {
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
				scope.acceptedModel = attrs.acceptedModel;


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
							$html.attr('selectable', 'false');
						} else {
							$html.find('rbs-document-list').attr('actions', '');
						}

						if ($html.find('quick-actions').length) {
							$html.find('quick-actions').empty();
						} else {
							$html.append('<quick-actions></quick-actions>');
						}

						$pickerContents.empty().append($html);
						$compile($html)(scope);
						$('#document-picker-backdrop').show();
						$picker.show();
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