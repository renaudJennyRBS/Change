(function ()
{
	"use strict";

	function cleanParameters(ArrayUtils, scope, parameterNames)
	{
		for (var key in scope.document.parameters)
		{
			console.log('scope.document.parameters.' + key);
			if (scope.document.parameters.hasOwnProperty(key) && ArrayUtils.inArray(key, parameterNames) == -1)
			{
				console.log('delete');
				delete scope.document.parameters[key];
			}
		}
		for (var i in parameterNames)
		{
			var key = parameterNames[i];
			if (!scope.document.parameters.hasOwnProperty(key))
			{
				scope.document.parameters[key] = null;
			}
		}
	}

	// Text fields.

	function TextInput(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-text-input.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				if (!scope.document.parameters.hasOwnProperty('autoComplete'))
				{
					scope.document.parameters.autoComplete = true;
				}
				if (!scope.document.parameters.hasOwnProperty('autoCorrect'))
				{
					scope.document.parameters.autoCorrect = true;
				}
				if (!scope.document.parameters.hasOwnProperty('autoCapitalize'))
				{
					scope.document.parameters.autoCapitalize = 'sentences';
				}
				cleanParameters(ArrayUtils, scope,
					['placeHolder', 'autoComplete', 'autoCapitalize', 'autoCorrect', 'minSize', 'maxSize', 'pattern']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformTextInput', ['RbsChange.ArrayUtils', TextInput]);

	function TextArea(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-text-area.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				if (!scope.document.parameters.hasOwnProperty('lineCount'))
				{
					scope.document.parameters.lineCount = 5;
				}
				if (!scope.document.parameters.hasOwnProperty('autoCorrect'))
				{
					scope.document.parameters.autoCorrect = true;
				}
				if (!scope.document.parameters.hasOwnProperty('autoCapitalize'))
				{
					scope.document.parameters.autoCapitalize = 'sentences';
				}
				cleanParameters(ArrayUtils, scope, ['lineCount', 'placeHolder', 'autoCapitalize', 'autoCorrect']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformTextArea', ['RbsChange.ArrayUtils', TextArea]);

	function TextEmail(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-text-email.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				if (!scope.document.parameters.hasOwnProperty('acknowledgmentReceiver'))
				{
					scope.document.parameters.acknowledgmentReceiver = false;
				}
				cleanParameters(ArrayUtils, scope, ['acknowledgmentReceiver', 'placeHolder']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformTextEmail', ['RbsChange.ArrayUtils', TextEmail]);

	function TextInteger(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-text-integer.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, ['placeHolder', 'min', 'max']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformTextInteger', ['RbsChange.ArrayUtils', TextInteger]);

	function TextFloat(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-text-float.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, ['placeHolder', 'min', 'max']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformTextFloat', ['RbsChange.ArrayUtils', TextFloat]);

	// Collections.

	function Collection(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-collection.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, ['collectionCode']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformCollectionSelect', ['RbsChange.ArrayUtils', Collection]);
	angular.module('RbsChange').directive('rbsSimpleformCollectionRadio', ['RbsChange.ArrayUtils', Collection]);

	function CollectionMultiple(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-collection-multiple.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				if (!scope.document.parameters.hasOwnProperty('lineCount'))
				{
					scope.document.parameters.lineCount = 5;
				}
				cleanParameters(ArrayUtils, scope, ['collectionCode', 'lineCount', 'minCount', 'maxCount']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformCollectionSelectMultiple',
		['RbsChange.ArrayUtils', CollectionMultiple]);

	function CollectionCheckbox(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-collection-checkbox.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, ['collectionCode', 'minCount', 'maxCount']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformCollectionCheckbox', ['RbsChange.ArrayUtils', CollectionCheckbox]);

	// Dates.

	function DatePicker(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-date-picker.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				if (!scope.document.parameters.hasOwnProperty('placeHolder'))
				{
					scope.document.parameters.placeHolder = 'yyyy-mm-dd';
				}
				cleanParameters(ArrayUtils, scope, ['intervalType', 'minimalRelativeDate', 'maximalRelativeDate',
					'minimalDate', 'maximalDate', 'placeHolder']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformDatePicker', ['RbsChange.ArrayUtils', DatePicker]);

	function DateTimePicker(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-date-time-picker.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				if (!scope.document.parameters.hasOwnProperty('placeHolder'))
				{
					scope.document.parameters.placeHolder = 'yyyy-mm-dd';
				}
				if (!scope.document.parameters.hasOwnProperty('timePlaceHolder'))
				{
					scope.document.parameters.timePlaceHolder = 'hh:mm';
				}
				cleanParameters(ArrayUtils, scope, ['intervalType', 'minimalRelativeDate', 'maximalRelativeDate',
					'minimalDate', 'maximalDate', 'placeHolder', 'timePlaceHolder']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformDateTimePicker', ['RbsChange.ArrayUtils', DateTimePicker]);

	// Only help-text and placeholder

	function HelpTextAndPlaceHolder(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-default-help-text-placeholder.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, ['placeHolder']);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformTextEmails', ['RbsChange.ArrayUtils', HelpTextAndPlaceHolder]);
	angular.module('RbsChange').directive('rbsSimpleformTextUrl', ['RbsChange.ArrayUtils', HelpTextAndPlaceHolder]);

	// Only help-text.

	function HelpText(ArrayUtils)
	{
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Simpleform/FieldTypes/field-default-help-text.twig',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, []);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformBooleanRadio', ['RbsChange.ArrayUtils', HelpText]);
	angular.module('RbsChange').directive('rbsSimpleformBooleanCheckbox', ['RbsChange.ArrayUtils', HelpText]);
	angular.module('RbsChange').directive('rbsSimpleformFile', ['RbsChange.ArrayUtils', HelpText]);

	// Not configurable fields.

	function NotConfigurable(ArrayUtils)
	{
		return {
			restrict: 'A',
			template: '',
			replace: false,
			scope: true,
			link: function (scope)
			{
				cleanParameters(ArrayUtils, scope, []);
			}
		};
	}

	angular.module('RbsChange').directive('rbsSimpleformHidden', ['RbsChange.ArrayUtils', NotConfigurable]);
})();