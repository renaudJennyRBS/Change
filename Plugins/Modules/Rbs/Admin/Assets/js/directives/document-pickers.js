/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function documentPickerLinkFunction(scope, iElement, attrs, ngModel, multiple, REST, Utils, Navigation, $timeout, UrlManager, Models) {
		var valueIds = (attrs.valueIds === 'true');

		scope.selectorUrl = attrs.selectorUrl;
		scope.multiple = multiple;

		scope.disableReordering = !multiple;
		if (scope.disableReordering && attrs.hasOwnProperty('disableReordering')) {
			scope.disableReordering = false;
		}

		scope.selectorTitle = attrs.selectorTitle;

		scope.doc = {list: []};

		scope.showButtonsLabel = attrs.hideButtonsLabel !== 'true';

		scope.models = {};

		attrs.$observe('acceptedModel', function() {
			scope.acceptedModel = iElement.attr('accepted-model');
		});

		attrs.$observe('selectModel', function() {
			if (attrs.hasOwnProperty('selectModel')) {
				var filter = scope.$eval(attrs.selectModel);
				if (angular.isArray(filter)) {
					scope.models.filters = {name: filter};
				}
				else if (angular.isObject(filter)) {
					scope.models.filters = filter;
				}
				else {
					scope.models.filters = {abstract: false, editable: true};
				}
				scope.models.filtered = Models.getByFilter(scope.models.filters);
			}
			else {
				scope.models.filters = undefined;
				scope.models.filtered = [];
			}
		});

		scope.$watchCollection('models.filtered', function(modelsFiltered) {
			if (angular.isArray(modelsFiltered)) {
				if (selectedModelName) {
					selectModelName(selectedModelName);
				}
				if (!angular.isObject(scope.models.model) && modelsFiltered.length) {
					scope.models.model = modelsFiltered[0];
				}
			}
		});

		function arrayCopy(array) {
			var copy = [];
			angular.forEach(array, function(item) {copy.push(item);});
			return copy;
		}

		function getDocById(array, id) {
			for (var i = 0; i < array.length; i++) {
				if (array[i].id == id) {
					return array[i];
				}
			}
			return null;
		}

		// viewValue => modelValue
		ngModel.$parsers.unshift(function(viewValue) {
			if (viewValue === undefined) {
				return viewValue;
			}

			var modelValue;
			if (valueIds) {
				if (multiple) {
					modelValue = Utils.toIds(viewValue);
				}
				else {
					modelValue = (Utils.isDocument(viewValue)) ? viewValue.id : 0
				}
			}
			else {
				if (multiple) {
					modelValue = arrayCopy(viewValue)
				}
				else {
					modelValue = viewValue
				}
			}
			return modelValue;
		});

		// modelValue => viewValue
		ngModel.$formatters.unshift(function(modelValue) {
			if (modelValue === undefined) {
				return modelValue;
			}
			var viewValue = multiple ? [] : null;
			var oldList = scope.doc.list;
			var docList = [];

			if (valueIds) {
				if (multiple) {
					if (angular.isArray(modelValue)) {
						var ids = [], doc;
						angular.forEach(modelValue, function(id) {
							if (angular.isNumber(id) && id > 0) {
								doc = getDocById(oldList, id);
								if (doc) {
									docList.push(doc);
								}
								else {
									ids.push(id);
								}
							}
							else {
								console.error('Invalid number value for: ' + getContextValueKey());
							}
						});
						if (ids.length) {
							angular.forEach(REST.getResources(ids), function(doc) {
								docList.push(doc);
							});
						}
						viewValue = arrayCopy(docList);
					}
				}
				else {
					if (angular.isNumber(modelValue) && modelValue > 0) {
						viewValue = REST.getResources([modelValue])[0];
						docList.push(viewValue);
					}
				}
			}
			else {
				if (multiple) {
					if (angular.isArray(modelValue)) {
						angular.forEach(modelValue, function(doc) {
							if (Utils.isDocument(doc)) {
								docList.push(doc);
								viewValue.push(doc);
							}
						});
					}
				}
				else {
					if (Utils.isDocument(modelValue)) {
						docList.push(modelValue);
						viewValue = modelValue;
					}
				}
			}

			scope.doc.list = docList;
			return viewValue;
		});

		// Watch from changes coming from the <rbs-token-list/> which is bound to `scope.doc.list`.
		scope.$watchCollection('doc.list', function(documents) {
			if (documents.length === 0 && ngModel.$viewValue === undefined) {
				return;
			}
			if (multiple) {
				ngModel.$setViewValue(arrayCopy(documents));
			}
			else {
				ngModel.$setViewValue(documents.length ? documents[0] : null);
			}
		});

		scope.hasTargetUrl = function() {
			if (scope.selectorUrl) {
				return true;
			}
			if (angular.isObject(scope.models.model)) {
				return true;
			}
			return scope.acceptedModel ? true : false;
		};

		// Open a session to select a document directly in the module
		scope.beginSelectSession = function() {
			var selectModel, targetUrl = scope.selectorUrl;
			if (!targetUrl) {
				if (angular.isObject(scope.models.model)) {
					selectModel = scope.models.model.name;
					targetUrl = UrlManager.getSelectorUrl(selectModel);
				}
				else if (scope.acceptedModel) {
					selectModel = scope.acceptedModel;
					targetUrl = UrlManager.getSelectorUrl(selectModel);
				}
			}

			if (!targetUrl) {
				throw new Error("Invalid targetUrl for selection.");
			}

			var navParams = {
				selector: true,
				model: selectModel,
				multiple: multiple,
				label: attrs.propertyLabel || attrs.name
			};

			var valueKey = getContextValueKey();
			Navigation.startSelectionContext(targetUrl, valueKey, navParams);
		};

		function getContextValueKey() {
			return attrs.contextKey ? attrs.contextKey : attrs.ngModel;
		}

		function applyContextValue(contextValue) {
			$timeout(function() {
				if (multiple) {
					var viewValue = arrayCopy(scope.doc.list);
					if (angular.isArray(contextValue)) {
						for (var ci = 0; ci < contextValue.length; ci++) {
							var add = true, cv = contextValue[ci];
							angular.forEach(viewValue, function(doc) {
								if (add && (doc.id == cv.id)) {
									add = false;
								}
							});
							if (add) {
								viewValue.push(cv);
							}
						}
						scope.doc.list = viewValue;
						ngModel.$setViewValue(viewValue);
						ngModel.$render();
					}
				}
				else {
					if (!Utils.isDocument(contextValue)) {
						contextValue = null;
						scope.doc.list = [];
					}
					else {
						scope.doc.list = [contextValue];
					}
					ngModel.$setViewValue(contextValue);
					ngModel.$render();
				}

				Navigation.popContext(currentContext);
			});
		}

		// Clear the list of selected elements
		scope.clear = function() {
			scope.doc.list = [];
		};

		// Check if nothing is selected
		scope.isEmpty = function() {
			return (scope.doc.list.length < 1);
		};

		var currentContext = Navigation.getCurrentContext();
		if (currentContext) {
			var contextValue = currentContext.getSelectionValue(getContextValueKey());
			if (contextValue !== undefined) {
				selectModelName(currentContext.param('model'));
				applyContextValue(contextValue);
			}
		}

		scope.$on('updateContextValue', function(event, args) {
			var contextValueKey = getContextValueKey(), valueKey = args.valueKey, value = args.value;
			if (contextValueKey === valueKey) {
				selectModelName(args.model);
				applyContextValue(value);
			}
		});

		var selectedModelName;

		function selectModelName(modelName) {
			if (!modelName) {
				return;
			}

			selectedModelName = modelName;
			if (angular.isObject(scope.models.filtered)) {
				for (var i = 0; i < scope.models.filtered.length; i++) {
					if (scope.models.filtered[i].name == modelName) {
						scope.models.model = scope.models.filtered[i];
						break;
					}
				}
			}
		}

		// Selection by label.

		scope.autoComplete = {
			show: false,
			refreshing: false,
			value: '',
			documents: [],
			open: openAutoCompleteList,
			close: closeAutoCompleteList,
			refresh: refreshAutoCompleteList,
			add: autoCompleteAdd
		};

		function autoCompleteAdd(document) {
			if (!multiple) {
				closeAutoCompleteList();
			}
			scope.doc.list.push(document);
		}

		function openAutoCompleteList() {
			if (scope.autoComplete.show) {
				return;
			}

			refreshListPosition();
			scope.autoComplete.show = true;
		}

		function refreshListPosition() {
			var input = iElement.find('input[type=text]');
			var css = {
				'left': input.offset().left - iElement.offset().left + 'px',
				'top': (input.outerHeight() + input.offset().top + 3 - iElement.offset().top) + 'px',
				'width': input.outerWidth(),
				'transition': 'top 1s ease'
			};
			iElement.find('.auto-complete-list').css(css);
		}

		function closeAutoCompleteList() {
			if (!scope.autoComplete.show) {
				return;
			}

			scope.autoComplete.show = false;
		}

		function refreshAutoCompleteList() {
			scope.autoComplete.error = null;
			if (!scope.autoComplete.value.trim().length) {
				scope.autoComplete.documents = [];
				closeAutoCompleteList();
			}
			else if (!scope.autoComplete.refreshing) {
				openAutoCompleteList();
				scope.autoComplete.refreshing = true;
				setTimeout(doRefreshAutoCompleteList, 1000);
			}
		}

		function doRefreshAutoCompleteList() {
			var value = angular.lowercase(scope.autoComplete.value.trim());
			if (!value.length) {
				scope.autoComplete.documents = [];
				scope.autoComplete.refreshing = false;
				return;
			}

			var model = null;
			if (angular.isObject(scope.models.model)) {
				model = scope.models.model.name;
			}
			else if (scope.acceptedModel) {
				model = scope.acceptedModel;
			}
			else {
				console.log('no model');
				scope.autoComplete.documents = [];
				scope.autoComplete.refreshing = false;
				return;
			}

			var params = {
				modelName: model,
				searchString: value,
				limit: 10
			};

			REST.call(REST.getBaseUrl('admin/searchDocuments'), params).then(
				function(data) {
					scope.autoComplete.documents = data.resources;
					scope.autoComplete.refreshing = false;
				},
				function(error) {
					console.log('admin/searchDocuments', error);
					scope.autoComplete.error = error;
					scope.autoComplete.refreshing = false;
				}
			);
		}

		scope.$watchCollection('doc.list', function() {
			if (scope.autoComplete.show) {
				setTimeout(refreshListPosition, 100);
			}
		});

		scope.$watch('models.model', refreshAutoCompleteList);
	}

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsDocumentPickerSingle
	 * @name Document picker (single)
	 * @restrict EA
	 *
	 * @param {Object} ng-model The ng-model to bind.
	 * @param {Boolean=} value-ids If true, stores the ID instead of the Document object.
	 * @param {String=} accepted-model Model name of the Documents that can be selected.
	 * @param {String=} context-key TODO
	 * @param {String=} selector-title TODO
	 * @param {String=} select-model TODO
	 * @param {String=} property-label TODO
	 * @param {Boolean=} hide-buttons-label TODO
	 *
	 * @description
	 * Displays a Document selector to let the user select a single Document.
	 * The user is redirected to the list of Documents of the desired model name and a
	 * <strong>Navigation Context</strong> is created.
	 */
	var singlePicker = ['RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Navigation', '$timeout', 'RbsChange.UrlManager',
		'RbsChange.Models', function(REST, Utils, Navigation, $timeout, UrlManager, Models) {
			return {
				restrict: 'EA',
				templateUrl: 'Rbs/Admin/js/directives/document-picker-multiple.twig',
				require: 'ngModel',
				scope: true,

				link: function(scope, iElement, attrs, ngModel) {
					documentPickerLinkFunction(scope, iElement, attrs, ngModel, false, REST, Utils, Navigation, $timeout,
						UrlManager, Models);
				}
			};
		}];
	app.directive('rbsDocumentPickerSingle', singlePicker);
	app.directive('rbsWoodyWoodpicker', singlePicker); // Ha ha.

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsDocumentPickerMultiple
	 * @name Document picker (multiple)
	 * @restrict EA
	 *
	 * @param {Object} ng-model The ng-model to bind.
	 * @param {Boolean=} value-ids If true, stores the ID instead of the Document object.
	 * @param {String=} accepted-model Model name of the Documents that can be selected.
	 * @param {String=} context-key TODO
	 * @param {String=} selector-title TODO
	 * @param {String=} select-model TODO
	 * @param {String=} property-label TODO
	 * @param {Boolean=} hide-buttons-label TODO
	 *
	 * @description
	 * Displays a Document selector to let the user select multiple Documents.
	 * The user is redirected to the list of Documents of the desired model name and a
	 * <strong>Navigation Context</strong> is created.
	 */
	app.directive('rbsDocumentPickerMultiple',
		['RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Navigation', '$timeout', 'RbsChange.UrlManager', 'RbsChange.Models',
			function(REST, Utils, Navigation, $timeout, UrlManager, Models) {
				return {
					restrict: 'EA',
					templateUrl: 'Rbs/Admin/js/directives/document-picker-multiple.twig',
					require: 'ngModel',
					scope: true,

					link: function(scope, iElement, attrs, ngModel) {
						documentPickerLinkFunction(scope, iElement, attrs, ngModel, true, REST, Utils, Navigation, $timeout,
							UrlManager, Models);
					}
				};
			}]);

	app.service('RbsChange.SelectSession', ['$location', 'RbsChange.UrlManager', '$rootScope', 'RbsChange.MainMenu',
		function($location, UrlManager, $rootScope, MainMenu) {
			var selection = [],
				selectDoc, selectDocPropertyName, selectDocPropertyLabel, selectDocUrl, selectDocumentModel, selectMultiple;

			function reset() {
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
				started: function() {
					return angular.isObject(selectDoc);
				},

				info: function() {
					if (!this.started()) {
						return null;
					}
					return {
						document: selectDoc,
						selection: selection,
						propertyName: selectDocPropertyName,
						propertyLabel: selectDocPropertyLabel,
						multiple: selectMultiple
					};
				},

				hasSelectSession: function(doc) {
					return selectDoc && doc
						&& (selectDoc.id === doc.id || (selectDoc.isNew() && doc.isNew() && selectDoc.model === doc.model))
						&& (!doc.hasOwnProperty('LCID') || doc.LCID === selectDoc.LCID);
				},

				start: function(doc, property, selectionDocumentModel, multiple) {
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

				append: function(docs) {
					if (angular.isArray(docs)) {
						angular.forEach(docs, function(d) {
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

				commit: function(doc) {
					if (angular.isObject(doc)) {
						doc[selectDocPropertyName] = angular.copy(selectMultiple ? selection : selection[0]);
						reset();
					}
					$rootScope.$broadcast('Change:SelectSessionUpdate');
					return this;
				},

				clear: function() {
					selection.length = 0;
					$rootScope.$broadcast('Change:SelectSessionUpdate');
					return this;
				},

				end: function() {
					if (!selectDocUrl) {
						console.warn("SelectSession: could not go back to the editor: URL is empty.");
					}
					MainMenu.removeAside('rbsSelectSession');
					$location.url(selectDocUrl);
				},

				rollback: function() {
					$rootScope.$broadcast('Change:SelectSessionUpdate');
					var redirect = selectDocUrl;
					reset();
					MainMenu.removeAside('rbsSelectSession');
					$location.url(redirect);
				}
			};
		}]);

})();