(function ($) {

	"use strict";

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var	app = angular.module('RbsChange'),
		counter = 0;


	function documentPickerLinkFunction (scope, iElement, attrs, ngModel, multiple, EditorManager, ArrayUtils, MainMenu, Breadcrumb, Clipboard, $http, $compile) {

		var	$el = $(iElement),
			inputEl = $el.find('input[name=label]'),
			documentList,
			$picker = $el.find('.document-picker-embedded'),
			$pickerContents = $picker.find('[data-role="picker-contents"]'),
			embedded = false,
			embedSelector = null,
			first;

		scope.allowSearchFilters = $el.closest('form.search-filters').length === 0;

		scope.inputCssClass = attrs.inputCssClass;

		// Initialize ngModel

		if (multiple) {
			ngModel.$render = function() {
				scope.documents = ngModel.$viewValue;
			};
		}
		else {
			ngModel.$render = function() {
				inputEl.val(ngModel.$viewValue ? ngModel.$viewValue.label : '');
			};
		}
		ngModel.$render();


		scope.allowCreation = attrs.allowCreation !== 'false';
		scope.allowEdition = attrs.allowEdition;

		attrs.$observe('acceptedModel', function (value) {
			scope.acceptedModel = value;
		});

		scope.selectModel = attrs.selectModel;

		function getFormModel () {
			if (! multiple && ngModel.$viewValue && ngModel.$viewValue.model) {
				return ngModel.$viewValue.model;
			} else {
				return scope.acceptedModel;
			}
		}

		function getCreateLabel () {
			return (scope.document.label || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + attrs.propertyLabel + " : création d'un nouvel élément";
		}

		function getEditLabel () {
			return (scope.document.label || '<em>Sans titre</em>') + ' <i class="icon-caret-right margin-h"></i> ' + attrs.propertyLabel + " : édition de " + ngModel.$viewValue.label;
		}


		// Clipboard

		scope.clipboardValues = Clipboard.values;
		first = scope.clipboardValues[0];
		if (first) {
			scope.clipboardFirstLabel = ' (' + first.label + ')';
		} else {
			scope.clipboardFirstLabel = '';
		}


		// Edit or create

		scope.createDocument = function () {
			EditorManager.cascade(
				getFormModel(),
				getCreateLabel(),
				function (doc) {
					scope.selectDocument(doc);
				}
			);
		};

		scope.editSelectedDocument = function () {
			var doc;
			doc = ngModel.$viewValue;

			EditorManager.cascade(
				doc,
				getEditLabel(),
				function (editedDoc) {
					if (!angular.equals(doc, editedDoc)) {
						scope.selectDocument(editedDoc);
					}
				}
			);
		};

		// Selection

		scope.openSelector = function () {
			Breadcrumb.freeze();
			MainMenu.freeze();
			scope.selectorTitle = attrs.selectorTitle;

			var url;
			if (attrs.picker === 'model') {
				url = scope.acceptedModel.replace(/_/g, '/') + '/picker.twig?counter=' + (counter++) + '&model=' + (attrs.acceptedModel || '');
			} else {
				url = 'Rbs/Admin/document-picker-list.twig?counter=' + (counter++) + '&model=' + (attrs.acceptedModel || '');
			}

			if (attrs.embedIn) {
				embedSelector = iElement.attr('embed-in');
				console.log("embedSelector=", embedSelector);
			}

			if (embedSelector && ! embedded) {
				$(embedSelector).append($picker);
				$picker.css('margin-left', '0px');
				embedded = true;
			}

			$http.get(url).success(function (html) {
				var $html = $(html), $dl;

				if ($html.is('rbs-document-list')) {
					$dl = $html;
				} else {
					$dl = $html.find('rbs-document-list').first();
				}

				$dl.attr('actions', '');
				$dl.attr('selectable', multiple);

				if ($dl.find('quick-actions').length) {
					$html.find('quick-actions').empty();
				} else {
					if (multiple) {
						$dl.append(
							'<quick-actions>' +
								'<a href="javascript:;" ng-click="extend.replaceWithDocument(doc)"><i class="icon-arrow-right"></i> tout remplacer par cet élément</a>' +
							'</quick-actions>'
						);
					} else {
						$dl.append('<quick-actions></quick-actions>');
					}
				}

				$pickerContents.empty().append($html);
				$compile($html)(scope);
				$('#document-picker-backdrop').show();
				$picker.show();

				documentList = angular.element($dl).scope();

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
			if (documentList) {
				// 'documentList' may be null if the selector has not been open.
				documentList.$destroy();
			}
			$('#document-picker-backdrop').hide();
		};

		scope.clear = function () {
			ngModel.$setViewValue(null);
			ngModel.$render();
		};

		scope.isEmpty = function () {
			return ! ngModel.$viewValue;
		};


		// MULTIPLE

		if (multiple) {

			scope.getFromClipboard = function () {
				scope.selectDocuments(Clipboard.getItems(true));
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
				console.log("selection: ", documentList.selectedDocuments);
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

		}

		// SINGLE

		else {

			scope.getFromClipboard = function () {
				var items = Clipboard.getItems(true);
				if (items.length >= 1) {
					scope.selectDocument(items[0]);
				}
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

		}
	}


	app.directive('documentPickerSingle', ['RbsChange.Clipboard', 'RbsChange.ArrayUtils', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.EditorManager', '$http', '$compile', function (Clipboard, ArrayUtils, Breadcrumb, MainMenu, EditorManager, $http, $compile) {
		return {

			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-single.twig',
			require     : 'ngModel',
			scope       : true,

			link : function (scope, iElement, attrs, ngModel) {
				documentPickerLinkFunction(scope, iElement, attrs, ngModel, false, EditorManager, ArrayUtils, MainMenu, Breadcrumb, Clipboard, $http, $compile);
			}

		};
	}]);


	app.directive('documentPickerMultiple', ['RbsChange.Clipboard', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.EditorManager', '$http', '$compile', function (Clipboard, Utils, ArrayUtils, Breadcrumb, MainMenu, EditorManager, $http, $compile) {
		return {

			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-multiple.twig',
			require     : 'ngModel',
			scope       : true,

			compile : function (tElement, tAttrs)
			{
				if (tAttrs.acceptedModel && Utils.isModelName(tAttrs.acceptedModel)) {
					tElement.find('token-list').first().attr('item-template', 'picker-item-' + tAttrs.acceptedModel + '.html');
				}

				return function (scope, iElement, attrs, ngModel) {
					documentPickerLinkFunction(scope, iElement, attrs, ngModel, true, EditorManager, ArrayUtils, MainMenu, Breadcrumb, Clipboard, $http, $compile);
				};
			}
		};
	}]);

})(window.jQuery);