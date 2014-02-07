(function ($)
{
	"use strict";

	var app = angular.module('RbsChange'),
		queryLimit = 200; // limits the number of elements in dropdown lists used in the following Directives.


	/**
	 * <rbs-document-select></rbs-document-select>
	 *
	 * Attributes:
	 * - ngModel
	 * - empty-label
	 * - accepted-model
	 * - filter-property
	 * - filter-value
	 * - value-ids
	 */
	app.directive('rbsDocumentSelect', ['RbsChange.REST', 'RbsChange.Query', 'RbsChange.Utils', 'RbsChange.i18n', function (REST, Query, Utils, i18n)
	{
		return {
			restrict : 'E',
			require : 'ngModel',
			templateUrl : 'Rbs/Admin/js/directives/document-select.twig',
			scope : true,

			link : function (scope, iElement, iAttrs, ngModel)
			{
				var loadFn;

				scope.documentTarget = null;

				if (iAttrs.hasOwnProperty('filterProperty'))
				{
					loadFn = function ()
					{
						var query = Query.simpleQuery(iAttrs.acceptedModel, iAttrs.filterProperty, iAttrs.filterValue);
						query.limit = queryLimit;
						REST.query(query).then(function (docs) {
							setOptions(docs.resources);
						});
					};

					iAttrs.$observe('filterValue', function (value) {
						if (value && parseInt(value, 10) > 0) {
							loadFn();
						} else {
							setOptions([]);
						}
					});
				}
				else
				{
					loadFn = function ()
					{
						REST.collection(iAttrs.acceptedModel).then(function (docs) {
							setOptions(docs.resources);
						});
					};
				}


				if (iAttrs.hasOwnProperty('ngDisabled'))
				{
					iAttrs.$observe('disabled', function (disabled) {
						scope.disabled = disabled;
						if (disabled === false)
						{
							loadFn();
						}
					});
				}
				else
				{
					loadFn();
				}


				function setOptions (options)
				{
					var emptyLabel = iAttrs.emptyLabel;
					if (!emptyLabel)
					{
						emptyLabel = i18n.trans('m.rbs.admin.admin.select_element | ucf');
					}

					options.unshift({
						id : 0,
						label : '- ' + emptyLabel + ' -'
					});
					scope.options = options;
					var selectedOption = findOption(scope.value);
					ngModel.$setViewValue(selectedOption);
					scope.documentTarget = selectedOption;
				}


				function findOption (id)
				{
					var i, opt = null;

					if (id)
					{
						if (scope.options) {
							for (i=0 ; i<scope.options.length && opt === null; i++) {
								if (scope.options[i].id === id) {
									opt = scope.options[i];
								}
							}
						}
					}
					return opt;
				}

				scope.$watch('value', function (value, old)
				{
					if (value)
					{
						var selectedOption = findOption(scope.value);
						ngModel.$setViewValue(selectedOption);
						scope.documentTarget = selectedOption;
					}
					else
					{
						ngModel.$setViewValue(null);
						scope.documentTarget = null;
					}
				});

				// Initialize ngModel
				// If attribute "value-ids" is set to true, the value (ng-model) of the picker will be an ID
				// or an array of IDs for a multiple picker.
				ngModel.$parsers.unshift(function (value)
				{
					if (iAttrs.valueIds)
					{
						if (angular.isObject(value) && value.hasOwnProperty('id'))
						{
							if(value.id)
							{
								return value.id;
							}
						}
						return null;
					}
					else
					{
						if (angular.isObject(value) && value.hasOwnProperty('id'))
						{
							if(value.id)
							{
								return value;
							}
						}
						return null;
					}
				});

				// Pickers allow ID (or Array of IDs) as value.
				// In that case, the following formatter will load the identified documents so that ngModel.$render()
				// always deals with objects (documents).
				ngModel.$formatters.unshift(function (value)
				{
					if (iAttrs.valueIds)
					{
						if (value)
						{
							scope.value = value;
						}
						else
						{
							scope.value = 0;
						}

						return REST.getResources([value])[0];
					}
					else
					{
						if (Utils.isDocument(value))
						{
							scope.value = value.id;
						}
						else
						{
							scope.value = 0;
						}

						return value;
					}
				});
			}
		};
	}]);


	/**
	 * <rbs-document-chained-select><rbs-document-chained-select>
	 *
	 * Attributes:
	 * - ngModel
	 * - chain : Vendor_Plugin_Parent:label // Vendor_Plugin_Child.parentProperty:label
	 * - required
	 */
	app.directive('rbsDocumentChainedSelect', [function ()
	{
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/document-select-chain.twig',
			require : 'ngModel',
			scope : true,

			compile : function (tElement, tAttrs)
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

				for (i=0 ; i<chain.length ; i++)
				{
					exp = chain[i].match(re);
					if (! exp[1]) {
						throw new Error("Invalid chained value " + i + ": must be Model_Name[.propertyName]:label");
					}

					$select = $('<rbs-document-select accepted-model="' + exp[1] + '" ng-model="selects.value' + i + '" empty-label="' + exp[4].trim() + '"></rbs-document-select>');
					if (i > 0)
					{
						$select.attr('ng-disabled', '! selects.value' + (i-1) + '.id')
							.attr('filter-property', exp[3])
							.attr('filter-value', '(= selects.value' + (i-1) + '.id =)');
					}
					$selectsEl.append($select.wrap('<div class="col-md-' + Math.floor(12/chain.length) + '"></div>').parent());
				}


				return function link (scope, iElement, iAttrs, ngModel)
				{
					scope.selects = {};
					scope.editMode = false;

					// Field's value is the value of the last <select/> element.
					scope.$watch('selects["value' + (chain.length - 1) + '"]', function (value, old)
					{
						if (value !== old) {
							ngModel.$setViewValue(value);
							scope.value = ngModel.$viewValue;
						}
					});

					// If this field is required, add the required $parser and $formatter on NgModel
					// to update field's validity.
					if (iAttrs.required)
					{
						ngModel.$parsers.unshift(function (value)
						{
							if (value && value.id) {
								ngModel.$setValidity('required', true);
								return value;
							} else {
								ngModel.$setValidity('required', false);
								return undefined;
							}
						});

						ngModel.$formatters.unshift(function (value)
						{
							ngModel.$setValidity('required', value && value.id ? true : false);
							return value;
						});
					}

					ngModel.$render = function ()
					{
						scope.value = ngModel.$viewValue;
						scope.editMode = ngModel.$viewValue ? false : true;
					};
				};
			}
		};
	}]);


})(window.jQuery);