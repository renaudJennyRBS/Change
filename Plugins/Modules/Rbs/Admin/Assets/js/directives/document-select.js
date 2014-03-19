/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		queryLimit = 200; // limits the number of elements in dropdown lists used in the following Directives.

	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-document-select
	 * @restrict E
	 *
	 * @description
	 * Displays a list of Documents in a dropdown listbox (<code>&lt;select/&gt;</code>).
	 *
	 * @param {*} ng-model ngModel
	 * @param {String} accepted-model The full model name of the Documents to display in the list.
	 * @param {String=} empty-label Text to display when nothing is selected.
	 * @param {String=} filter-property Name of the property on which the Documents should be filtered (see `filter-value`).
	 * @param {String=} filter-value The value of the `filter-property`.
	 * @param {Boolean=} value-ids If true, stores the ID of the selected Document in ngModel instead of the Document object.
	 */
	app.directive('rbsDocumentSelect',
		['RbsChange.REST', 'RbsChange.Query', 'RbsChange.Utils', 'RbsChange.i18n', function (REST, Query, Utils, i18n)
		{
			return {
				restrict : 'E',
				require : 'ngModel',
				templateUrl : 'Rbs/Admin/js/directives/document-select.twig',
				scope : true,

				link : function (scope, iElement, iAttrs, ngModel)
				{
					var loadFn;

					scope.documentTarget = undefined;
					scope.value = undefined;
					scope.options = undefined;

					if (iAttrs.hasOwnProperty('filterProperty')) {
						loadFn = function () {
							var query = Query.simpleQuery(iAttrs.acceptedModel, iAttrs.filterProperty, iAttrs.filterValue);
							query.limit = queryLimit;
							REST.query(query).then(function (docs) {
								setOptions(docs.resources);
							});
						};

						iAttrs.$observe('filterValue', function (value) {
							if (value && parseInt(value, 10) > 0) {
								loadFn();
							}
						});
					} else {
						loadFn = function () {
							REST.collection(iAttrs.acceptedModel).then(function (docs) {
								setOptions(docs.resources);
							});
						};
					}

					if (iAttrs.hasOwnProperty('ngDisabled')) {
						iAttrs.$observe('disabled', function (disabled) {
							scope.disabled = disabled;
							if (disabled === false) {
								loadFn();
							}
						});
					} else {
						loadFn();
					}

					function setOptions(options) {
						var emptyLabel = iAttrs.emptyLabel;
						if (!emptyLabel) {
							emptyLabel = i18n.trans('m.rbs.admin.admin.select_element | ucf');
						}

						options.unshift({
							id: 0,
							label: '- ' + emptyLabel + ' -'
						});
						scope.options = options;
						scope.documentTarget = findOption(scope.value);;
					}

					function findOption(id) {
						var i, opt = null;
						if (id) {
							if (scope.options) {
								for (i = 0; i < scope.options.length && opt === null; i++) {
									if (scope.options[i].id === id) {
										opt = scope.options[i];
									}
								}
							}
						}
						return opt;
					}

					scope.$watch('value', function (value, old) {
						if (angular.isArray(scope.options))
						{
							var selectedOption = findOption(value);
							var sv = angular.isObject(selectedOption) ? selectedOption.id : null;
							var vv = angular.isObject(ngModel.$viewValue) ? ngModel.$viewValue.id : ngModel.$viewValue;
							if (sv != vv) {
								ngModel.$setViewValue(selectedOption);
							}
							scope.documentTarget = selectedOption;
						}
					});

					// viewValue => modelValue
					ngModel.$parsers.unshift(function (value) {
						if (value === undefined) {
							return value;
						}
						if (iAttrs.valueIds) {
							if (angular.isObject(value) && value.hasOwnProperty('id')) {
								if (value.id) {
									return value.id;
								}
							}
							return null;
						}
						else {
							if (angular.isObject(value) && value.hasOwnProperty('id')) {
								if (value.id) {
									return value;
								}
							}
							return null;
						}
					});

					// modelValue => viewValue
					ngModel.$formatters.unshift(function (value) {
						if (value === undefined) {
							return value;
						}
						if (iAttrs.valueIds) {
							if (value) {
								scope.value = value;
							}
							else {
								scope.value = 0;
							}
							return REST.getResources([value])[0];
						} else {
							if (Utils.isDocument(value)) {
								scope.value = value.id;
							}
							else {
								scope.value = 0;
							}
							return value;
						}
					});
				}
			};
		}]);


	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-document-chained-select
	 * @restrict E
	 *
	 * @description
	 * Displays multiple chained lists of Documents (<code>&lt;select/&gt;</code>).
	 *
	 * @param {*} ng-model ngModel: the value is the Document selected in the last dropdown listbox.
	 * @param {String} chain Chain description: `Vendor_Plugin_Parent:label // Vendor_Plugin_Child.parentProperty:label`
	 */
	app.directive('rbsDocumentChainedSelect', [function ()
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/document-select-chain.twig',
			require : 'ngModel',
			scope : true,

			compile: function (tElement, tAttrs)
			{
				if (! angular.isString(tAttrs.chain)) {
					throw new Error("Attribute 'chain' is required.");
				}

				var chain = tAttrs.chain.split(/\s*\/\/\s*/),
					$select,
					$selectsEl = tElement.find('[data-role="selects"]'),
					i, exp, re;

				// 1: model name
				// 3: property name
				// 4: label
				//      11111111111111111111111111111111111111     333333333     44
				re = /^([A-Z][a-z]*_[A-Z][a-z]*_[A-Z][A-Za-z]+)(\.([A-Za-z]+))?:(.*)$/;

				for (i = 0; i < chain.length; i++) {
					exp = chain[i].match(re);
					if (!exp[1]) {
						throw new Error("Invalid chained value " + i + ": must be Model_Name[.propertyName]:label");
					}

					$select = $('<rbs-document-select accepted-model="' + exp[1] + '" ng-model="selects.value' + i +
						'" empty-label="' + exp[4].trim() + '"></rbs-document-select>');
					if (i > 0) {
						$select.attr('ng-disabled', '! selects.value' + (i - 1) + '.id')
							.attr('filter-property', exp[3])
							.attr('filter-value', '(= selects.value' + (i - 1) + '.id =)');
					}
					$selectsEl.append($select.wrap('<div class="col-md-' + Math.floor(12 / chain.length) + '"></div>').parent());
				}

				return function rbsDocumentChainedSelectLinkFn (scope, iElement, iAttrs, ngModel)
				{
					scope.selects = {};
					scope.editMode = false;

					// Field's value is the value of the last <select/> element.
					scope.$watch('selects["value' + (chain.length - 1) + '"]', function (value, old) {
						if (value !== old) {
							ngModel.$setViewValue(value);
							scope.value = ngModel.$viewValue;
						}
					});

					// If this field is required, add the required $parser and $formatter on NgModel
					// to update field's validity.
					if (iAttrs.required) {
						ngModel.$parsers.unshift(function (value) {
							if (value && value.id) {
								ngModel.$setValidity('required', true);
								return value;
							} else {
								ngModel.$setValidity('required', false);
								return undefined;
							}
						});

						ngModel.$formatters.unshift(function (value) {
							ngModel.$setValidity('required', value && value.id ? true : false);
							return value;
						});
					}

					ngModel.$render = function () {
						scope.value = ngModel.$viewValue;
						scope.editMode = ngModel.$viewValue ? false : true;
					};
				};
			}
		};
	}]);

})(window.jQuery);