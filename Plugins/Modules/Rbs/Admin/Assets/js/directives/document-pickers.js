(function ($)
{

	"use strict";

	if ($('#document-picker-backdrop').length === 0)
	{
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange'),
		counter = 0;

	/**
	 * @param scope
	 * @param iElement
	 * @param attrs
	 * @param ngModel
	 * @param multiple
	 * @param REST
	 * @param Utils
	 * @param Navigation
	 * @param $location
	 * @param UrlManager
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
	 * @attribute disable-reordering
	 */
	function documentPickerLinkFunction(scope, iElement, attrs, ngModel, multiple, REST, Utils, Navigation, $location, UrlManager, ArrayUtils)
	{
		var $el = $(iElement);

		// Example: selector-url="/Rbs/User/Group/new"
		scope.selectorUrl = attrs.selectorUrl;

		scope.multiple = multiple;

		scope.disableReordering = !multiple;
		scope.showButtonsLabel = $el.closest('.dockable.pinned').length === 0; // TODO: detect block properties editor in page editor...

		scope.selectModel = attrs.selectModel;
		attrs.$observe('acceptedModel', function ()
		{
			scope.acceptedModel = iElement.attr('accepted-model');
		});

		// Initialize ngModel
		// If attribute "value-ids" is set to true, the value (ng-model) of the picker will be an ID
		// or an array of IDs for a multiple picker.
		ngModel.$parsers.unshift(function (value)
		{
			if (attrs.valueIds === 'true')
			{
				value = Utils.toIds(value);
			}
			return value;
		});

		// Pickers allow ID (or Array of IDs) as value.
		// In that case, the following formatter will load the identified documents so that ngModel.$render()
		// always deals with objects (documents).
		ngModel.$formatters.unshift(function (value)
		{
			if (!scope.documents)
			{
				scope.documents = [];
			}

			if (value)
			{
				if (angular.isArray(value))
				{
					if (value.length > 0)
					{
						var arrayOfIds = false;
						angular.forEach(value, function (value)
						{
							if (/\d+/.test('' + value))
							{
								arrayOfIds = true;
							}
						});
						if (arrayOfIds)
						{
							scope.documents = REST.getResources(value);
						}
						else
						{
							scope.documents = value;
						}
					}
				}
				else if (/\d+/.test('' + value))
				{
					scope.documents = REST.getResources([value])[0];
				}
				else
				{
					scope.documents.push(value);
				}
			}

			return value;
		});

		// Watch from changes coming from the <rbs-token-list/> which is bound to `scope.documents`.
		scope.$watch('documents', function (documents, old)
		{
			if (scope.multiple)
			{
				if (documents !== old)
				{
					ngModel.$setViewValue(documents);
				}
			}
			else
			{
				if (documents.length > 1)
				{
					ArrayUtils.remove(documents, 0, 0);
				}

				if (documents.length == 0)
				{
					ngModel.$setViewValue(null);
				}
				else if (documents[0] !== old)
				{
					ngModel.$setViewValue(documents[0]);
				}
			}
		}, true);

		// Open a session to select a document directly in the module
		scope.beginSelectSession = function ()
		{
			var p = attrs.ngModel.indexOf('.'),
				doc, property, selectModel, navParams;

			if (p === -1)
			{
				throw new Error("Invalid 'ng-model' attribute on DocumentPicker Directive.");
			}

			selectModel = getFormModel();
			if (selectModel)
			{
				doc = scope[attrs.ngModel.substr(0, p)];
				property = attrs.ngModel.substr(p + 1);
				navParams = {
					selector: true,
					property: property,
					model: selectModel,
					multiple: multiple,
					document: doc || scope.document,
					ngModel: attrs.ngModel
				};

				if (scope.modelInfo && scope.modelInfo.properties && scope.modelInfo.properties[property])
				{
					navParams.label = scope.modelInfo.properties[property].label;
				}

				Navigation.start(iElement, navParams);
				if (scope.selectorUrl)
				{
					$location.url(scope.selectorUrl);
				}
				else
				{
					$location.url(UrlManager.getSelectorUrl(selectModel));
				}
			}
		};

		// Get allowed model
		function getFormModel()
		{
			if (!multiple && ngModel.$viewValue && ngModel.$viewValue.model)
			{
				return ngModel.$viewValue.model;
			}
			else
			{
				return scope.acceptedModel;
			}
		}

		// Clear the list of selected elements
		scope.clear = function ()
		{
			//setValue(null);
			scope.documents = [];
			ngModel.$setViewValue(null);
			ngModel.$render();
		};

		// Check if nothing is selected
		scope.isEmpty = function ()
		{
			return !ngModel.$viewValue || (angular.isArray(ngModel.$viewValue) && ngModel.$viewValue.length === 0);
		};
	}

	var singlePicker = ['RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Navigation', '$location', 'RbsChange.UrlManager',
		'RbsChange.ArrayUtils', function (REST, Utils, Navigation, $location, UrlManager, ArrayUtils)
		{
			return {
				restrict: 'EA',
				templateUrl: 'Rbs/Admin/js/directives/document-picker-multiple.twig',
				require: 'ngModel',
				scope: true,

				link: function (scope, iElement, attrs, ngModel)
				{
					documentPickerLinkFunction(scope, iElement, attrs, ngModel, false, REST, Utils, Navigation, $location,
						UrlManager, ArrayUtils);
				}
			};
		}];
	app.directive('rbsDocumentPickerSingle', singlePicker);
	app.directive('rbsWoodyWoodpicker', singlePicker); // Ha ha.

	app.directive('rbsDocumentPickerMultiple',
		['RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Navigation', '$location', 'RbsChange.UrlManager', 'RbsChange.ArrayUtils',
			function (REST, Utils, Navigation, $location, UrlManager, ArrayUtils)
			{
				return {
					restrict: 'EA',
					templateUrl: 'Rbs/Admin/js/directives/document-picker-multiple.twig',
					require: 'ngModel',
					scope: true,

					link: function (scope, iElement, attrs, ngModel)
					{
						documentPickerLinkFunction(scope, iElement, attrs, ngModel, true, REST, Utils, Navigation, $location,
							UrlManager, ArrayUtils);
					}
				};
			}]);

	app.service('RbsChange.SelectSession', ['$location', 'RbsChange.UrlManager', '$rootScope', 'RbsChange.MainMenu',
		function ($location, UrlManager, $rootScope, MainMenu)
		{
			var selection = [],
				selectDoc, selectDocPropertyName, selectDocPropertyLabel, selectDocUrl, selectDocumentModel, selectMultiple;

			function reset()
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
				started: function ()
				{
					return angular.isObject(selectDoc);
				},

				info: function ()
				{
					if (!this.started())
					{
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

				hasSelectSession: function (doc)
				{
					return selectDoc && doc
						&& (selectDoc.id === doc.id || (selectDoc.isNew() && doc.isNew() && selectDoc.model === doc.model))
						&& (!doc.hasOwnProperty('LCID') || doc.LCID === selectDoc.LCID);
				},

				start: function (doc, property, selectionDocumentModel, multiple)
				{
					if (this.started())
					{
						return;
					}
					selection.length = 0;
					selectDoc = doc;
					if (angular.isObject(property))
					{
						selectDocPropertyName = property.name;
						selectDocPropertyLabel = property.label;
					}
					else
					{
						selectDocPropertyLabel = selectDocPropertyName = property;
					}
					selectDocUrl = $location.url();
					selectDocumentModel = selectionDocumentModel;
					selectMultiple = multiple;
					$rootScope.$broadcast('Change:SelectSessionUpdate');
					$location.url(UrlManager.getListUrl(selectionDocumentModel));
				},

				append: function (docs)
				{
					if (angular.isArray(docs))
					{
						angular.forEach(docs, function (d)
						{
							if (selection.indexOf(d) === -1)
							{
								selection.push(d);
							}
						});
					}
					else if (selection.indexOf(docs) === -1)
					{
						selection.push(docs);
					}
					return this;
				},

				commit: function (doc)
				{
					if (angular.isObject(doc))
					{
						doc[selectDocPropertyName] = angular.copy(selectMultiple ? selection : selection[0]);
						reset();
					}
					$rootScope.$broadcast('Change:SelectSessionUpdate');
					return this;
				},

				clear: function ()
				{
					selection.length = 0;
					$rootScope.$broadcast('Change:SelectSessionUpdate');
					return this;
				},

				end: function ()
				{
					if (!selectDocUrl)
					{
						console.warn("SelectSession: could not go back to the editor: URL is empty.");
					}
					MainMenu.removeAside('rbsSelectSession');
					$location.url(selectDocUrl);
				},

				rollback: function ()
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