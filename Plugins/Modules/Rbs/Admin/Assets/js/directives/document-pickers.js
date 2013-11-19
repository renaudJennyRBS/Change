(function ($) {

	"use strict";

	if ($('#document-picker-backdrop').length === 0) {
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var	app = angular.module('RbsChange'),
		counter = 0;


	/**
	 * @param scope
	 * @param iElement
	 * @param attrs
	 * @param ngModel
	 * @param multiple
	 * @param EditorManager
	 * @param ArrayUtils
	 * @param MainMenu
	 * @param Breadcrumb
	 * @param Clipboard
	 * @param $http
	 * @param $compile
	 * @param REST
	 * @param SelectSession
	 * @param $templateCache
	 * @param Utils
	 * @param i18n
	 * @param $timeout
	 *
	 * @attribute value-ids
	 * @attribute allow-in-place-selection
	 * @attribute input-css-class
	 * @attribute picker-template
	 * @attribute allow-creation
	 * @attribute allow-edition
	 * @attribute accepted-model
	 * @attribute property-label
	 * @attribute selector-title
	 * @attribute select-model
	 * @attribute embed-in
	 */
	function documentPickerLinkFunction (scope, iElement, attrs, ngModel, multiple, EditorManager, ArrayUtils, MainMenu, Breadcrumb, Clipboard, $http, $compile, REST, SelectSession, $templateCache, Utils, i18n, $timeout)
	{
		var	$el = $(iElement),
			documentList,
			$picker = $el.find('.document-picker-embedded'),
			$pickerContents = $picker.find('[data-role="picker-contents"]'),
			embedded = false,
			embedSelector = null,
			first,
			pickerTpl;

		if (attrs.pickerTemplate) {
			pickerTpl = attrs.pickerTemplate;
		}
		else if (attrs.picker) {
			pickerTpl = "picker.twig";
		}

		scope.allowSearchFilters = $el.closest('form.search-filters').length === 0;
		scope.allowInPlaceSelection = attrs.allowInPlaceSelection !== 'false';
		scope.showButtonsLabel = $el.closest('.dockable.pinned').length === 0;
		scope.inputCssClass = attrs.inputCssClass;

		scope.$on('$routeChangeStart', function () {
			scope.closeSelector();
		});


		// Initialize ngModel


		// If attribute "value-ids" is set to true, the value (ng-model) of the picker will be an ID
		// or an array of IDs for a multiple picker.
		if (attrs.valueIds === 'true') {
			ngModel.$parsers.unshift(function (value) {
				console.log("value=", Utils.toIds(value));
				return Utils.toIds(value);
			});
		}

		// Pickers allow ID (or Array of IDs) as value.
		// In that case, the following formatter will load the identified documents so that ngModel.$render()
		// alwayds deals with objects (documents).
		ngModel.$formatters.unshift(function (value) {
			if (angular.isArray(value)) {
				var arrayOfIds = false;
				angular.forEach(value, function (value) {
					if (/\d+/.test(''+value)) {
						arrayOfIds = true;
					}
				});
				if (arrayOfIds) {
					return REST.getResources(value);
				}
			}
			else if (/\d+/.test(''+value)) {
				return REST.getResources([value])[0];
			}

			return value;
		});


		if (multiple) {
			ngModel.$render = function() {
				scope.documents = ngModel.$viewValue;
			};
		}
		else {
			ngModel.$render = function() {
				scope.item = ngModel.$viewValue;
			};
		}

		ngModel.$render();


		// Watch from changes coming from the <token-list/> which is bound to `scope.documents`.
		scope.$watchCollection('documents', function (documents, old) {
			if (documents !== old) {
				ngModel.$setViewValue(documents);
			}
		});


		scope.getItemTemplateName = function (item) {
			var tplName = null;

			if (item && item.model) {
				tplName = 'picker-item-' + item.model + '.html';
			}

			if (tplName && $templateCache.get(tplName)) {
				return tplName;
			}

			return 'picker-item-default.html';
		};


		scope.allowCreation = attrs.allowCreation !== 'false';
		scope.allowEdition = attrs.allowEdition;

		attrs.$observe('acceptedModel', function () {
			scope.acceptedModel = iElement.attr('accepted-model');
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
			return (scope.document.label || '<em>' + i18n.trans('m.rbs.admin.adminjs.picker_no_title | ucf') + '</em>') + ' <i class="icon-caret-right margin-h"></i> ' + i18n.trans('m.rbs.admin.adminjs.picker_create_title', {'property' : attrs.propertyLabel});
		}

		function getEditLabel () {
			return (scope.document.label || '<em>' + i18n.trans('m.rbs.admin.adminjs.picker_no_title | ucf') + '</em>') + ' <i class="icon-caret-right margin-h"></i> ' + i18n.trans('m.rbs.admin.adminjs.picker_edit_title', {'property' : attrs.propertyLabel, 'item' : ngModel.$viewValue.label}) ;
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
			var srcDoc = ngModel.$viewValue;
			REST.resource(srcDoc).then(
				function(doc) {
					EditorManager.cascade(
						doc,
						getEditLabel(),
						function (editedDoc) {
							if (!angular.equals(srcDoc, editedDoc)) {
								scope.selectDocument(editedDoc);
							}
						}
					);
				}
			)
		};

		// Selection

		scope.openSelector = function () {
			Breadcrumb.freeze();
			MainMenu.freeze();
			scope.selectorTitle = attrs.selectorTitle;

			var url, model = iElement.attr('accepted-model');
			if (pickerTpl && model) {
				url = 'Document/' + model.replace(/_/g, '/') + '/' + pickerTpl + '?counter=' + (counter++) + '&model=' + model;
			} else {
				url = 'Rbs/Admin/document-picker-list.twig?counter=' + (counter++) + '&model=' + (model || '');
			}

			if (attrs.embedIn) {
				embedSelector = iElement.attr('embed-in');
			}

			if (embedSelector && ! embedded) {
				$(embedSelector).append($picker);
				$picker.css('margin-left', '0px');
				embedded = true;
			}

			$http.get(url).success(function (html)
			{
				var $html = $(html), $dl;

				if ($html.is('rbs-document-list')) {
					$dl = $html;
				} else {
					$dl = $html.find('rbs-document-list').first();
				}
				$dl.attr('actions', '');
				$dl.attr('selectable', multiple);

				if ($html.find('quick-actions').length) {
					$html.find('quick-actions').empty();
				} else {
					if (multiple) {
						$dl.append(
							'<quick-actions>' +
								'<a href="javascript:;" ng-click="extend.replaceWithDocument(doc)"><i class="icon-arrow-right"></i> ' + i18n.trans('m.rbs.admin.adminjs.picker_replace_all_by_item | ucf') + '</a>' +
							'</quick-actions>'
						);
					} else {
						$dl.append('<quick-actions></quick-actions>');
					}
				}

				// Append, compile and display picker contents.
				$pickerContents.empty().append($html);
				$compile($pickerContents)(scope);
				$picker.show();
				$('#document-picker-backdrop').show();

				// Migration to Angular 1.2.0:
				// We wait for the <rbs-document-list/> of the picker to tell us when it is ready,
				// via an event.
				scope.$on('Change:DocumentList:' + $dl.data('dlid') + ':Ready', function (event, dlScope)
				{
					documentList = dlScope;
				});
			}).error(function (data) {
				$('#document-picker-backdrop').show();
				$pickerContents.html('<div class="alert alert-danger">' + i18n.trans('m.rbs.admin.adminjs.picker_template_not_loaded | ucf', {'url' : url}) + '</div>');
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
			return ! ngModel.$viewValue || (angular.isArray(ngModel.$viewValue) && ngModel.$viewValue.length === 0);
		};

		scope.beginSelectSession = function () {
			var	p = attrs.ngModel.indexOf('.'),
				doc, property, propertyLabel, selectModel;
			if (p === -1) {
				throw new Error("Invalid 'ng-model' attribute on DocumentPicker Directive.");
			}
			doc = scope[attrs.ngModel.substr(0, p)];
			property = attrs.ngModel.substr(p+1);
			if (scope.modelInfo && scope.modelInfo.properties && scope.modelInfo.properties[property]) {
				propertyLabel = scope.modelInfo.properties[property].label;
			}
			else {
				propertyLabel = property;
			}
			selectModel = getFormModel();
			if (selectModel) {
				SelectSession.start(doc, { name : property, label : propertyLabel }, selectModel, multiple);
			}
		};


		// MULTIPLE

		if (multiple) {

			scope.getFromClipboard = function () {
				ngModel.$setViewValue(Clipboard.getItems(true));
				ngModel.$render();
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

			scope.selectAndClose = function () {
				scope.appendSelected();
				scope.closeSelector();
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

			scope.selectDocument = function (doc) {
				console.log("selectDocument=", doc);
				console.log("ngModel=", attrs.ngModel);
				ngModel.$setViewValue(doc);
				ngModel.$render();
				scope.closeSelector();
			};

			scope.picker = {
				"selectDocument" : scope.selectDocument
			};

		}
	}


	app.directive('documentPickerSingle', ['RbsChange.Clipboard', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.EditorManager', '$http', '$compile', 'RbsChange.REST', 'RbsChange.SelectSession', '$templateCache', 'RbsChange.i18n', '$timeout', function (Clipboard, Utils, ArrayUtils, Breadcrumb, MainMenu, EditorManager, $http, $compile, REST, SelectSession, $templateCache, i18n, $timeout)
	{
		return {

			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-single.twig',
			require     : 'ngModel',
			scope       : true,

			link : function (scope, iElement, attrs, ngModel) {
				documentPickerLinkFunction(scope, iElement, attrs, ngModel, false, EditorManager, ArrayUtils, MainMenu, Breadcrumb, Clipboard, $http, $compile, REST, SelectSession, $templateCache, Utils, i18n, $timeout);
			}

		};
	}]);


	app.directive('documentPickerMultiple', ['RbsChange.Clipboard', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.EditorManager', '$http', '$compile', 'RbsChange.REST', 'RbsChange.SelectSession', '$templateCache', 'RbsChange.i18n', '$timeout', function (Clipboard, Utils, ArrayUtils, Breadcrumb, MainMenu, EditorManager, $http, $compile, REST, SelectSession, $templateCache, i18n, $timeout)
	{
		return {

			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/document-picker-multiple.twig',
			require     : 'ngModel',
			scope       : true,

			link : function (scope, iElement, attrs, ngModel) {
				documentPickerLinkFunction(scope, iElement, attrs, ngModel, true, EditorManager, ArrayUtils, MainMenu, Breadcrumb, Clipboard, $http, $compile, REST, SelectSession, $templateCache, Utils, i18n, $timeout);
			}

		};
	}]);


	app.service('RbsChange.SelectSession', ['$location', 'RbsChange.UrlManager', '$rootScope', 'RbsChange.MainMenu', function ($location, UrlManager, $rootScope, MainMenu)
	{
		var	selection = [],
			selectDoc, selectDocPropertyName, selectDocPropertyLabel, selectDocUrl, selectDocumentModel, selectMultiple;

		function reset ()
		{
			selection.length = 0;
			selectDoc = null;
			selectDocUrl = null;
			selectDocumentModel = null;
			selectMultiple = false;
			selectDocPropertyName = null;
			selectDocPropertyLabel = null;
		}
		reset();

		return {

			started : function ()
			{
				return angular.isObject(selectDoc);
			},

			info : function ()
			{
				if (! this.started()) {
					return null;
				}
				return {
					document : selectDoc,
					selection : selection,
					propertyName : selectDocPropertyName,
					propertyLabel : selectDocPropertyLabel,
					multiple : selectMultiple
				};
			},

			hasSelectSession : function (doc)
			{
				return selectDoc && doc
					&& (selectDoc.id === doc.id || (selectDoc.isNew() && doc.isNew() && selectDoc.model === doc.model))
					&& (! doc.hasOwnProperty('LCID') || doc.LCID === selectDoc.LCID);
			},

			start : function (doc, property, selectionDocumentModel, multiple)
			{
				if (this.started()) {
					return;
				}
				selection.length = 0;
				selectDoc = doc;
				if (angular.isObject(property)) {
					selectDocPropertyName = property.name;
					selectDocPropertyLabel = property.label;
				}
				else {
					selectDocPropertyLabel = selectDocPropertyName = property;
				}
				selectDocUrl = $location.url();
				selectDocumentModel = selectionDocumentModel;
				selectMultiple = multiple;
				$rootScope.$broadcast('Change:SelectSessionUpdate');
				$location.url(UrlManager.getListUrl(selectionDocumentModel));
			},

			append : function (docs)
			{
				if (angular.isArray(docs)) {
					angular.forEach(docs, function (d) {
						if (selection.indexOf(d) === -1) {
							selection.push(d);
						}
					});
				}
				else if (selection.indexOf(docs) === -1) {
					selection.push(docs);
				}
				return this;
			},

			commit : function (doc)
			{
				if (angular.isObject(doc)) {
					doc[selectDocPropertyName] = angular.copy(selectMultiple ? selection : selection[0]);
					reset();
				}
				$rootScope.$broadcast('Change:SelectSessionUpdate');
				return this;
			},

			clear : function () {
				selection.length = 0;
				$rootScope.$broadcast('Change:SelectSessionUpdate');
				return this;
			},

			end : function ()
			{
				if (! selectDocUrl) {
					console.warn("SelectSession: could not go back to the editor: URL is empty.");
				}
				MainMenu.removeAside('rbsSelectSession');
				$location.url(selectDocUrl);
			},

			rollback : function ()
			{
				$rootScope.$broadcast('Change:SelectSessionUpdate');
				var redirect = selectDocUrl;
				reset();
				MainMenu.removeAside('rbsSelectSession');
				$location.url(redirect);
			}

		};
	}]);


})(window.jQuery);