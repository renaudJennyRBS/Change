(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsItemsFromCollection', ['RbsChange.REST', 'RbsChange.Utils', rbsItemsFromCollectionDirective]);

	function rbsItemsFromCollectionDirective (REST, Utils) {

		return {
			restrict : 'A',
			require  : 'ngModel',

			link : function (scope, elm, attrs, ngModel) {
				elm.find('option').attr('data-option-from-template', 'true');
				var ngModelReady = false;
				var collectionLoaded = false;
				var paramsAttrReady = elm.is('[rbs-items-collection-params]') ? false : true;
				var items, itemsFilter;
				if (!paramsAttrReady)
				{
					// It seems that $observe always gets called *before* $render
					// so we can force a collection load here...
					attrs.$observe('rbsItemsCollectionParams', function(value){
						paramsAttrReady = true;
						if (value)
						{
							collectionLoaded = false;
							loadCollection();
						}
					});
				}
				// Load Collection's items.
				function loadCollection () {
					if (!ngModelReady || !paramsAttrReady || collectionLoaded)
					{
						return;
					}
					collectionLoaded = true;
					var params = {code: attrs.rbsItemsFromCollection};
					if (attrs.rbsItemsCollectionParams)
					{
						var parts = attrs.rbsItemsCollectionParams.split(';');
						angular.forEach(parts, function(value, index){
							var values = value.split(':');
							params[values[0].trim()] = values[1].trim();
						});
					}
					REST.action('collectionItems', params)
						.then(function (data) {
							items = data.items;
							redraw();
						},
						function () {
							$('<option></option>')
								.attr('selected', 'selected')
								.text("Unable to load Collection '" + attrs.rbsItemsFromCollection + "'.")
								.appendTo(elm);
						});
				}


				function redraw () {
					elm.find('option:not([data-option-from-template])').remove();
					angular.forEach(items, function (item, value) {
						if (! itemsFilter || Utils.containsIgnoreCase(item.label, itemsFilter) || Utils.containsIgnoreCase(value, itemsFilter)) {
							var $opt = $('<option></option>');
							$opt
								.attr('value', value)
								.text(item.label)
								.appendTo(elm);
						}
					});
					selectCurrentOption();
				}

				function selectCurrentOption () {
					elm.find('option').each(function(index, option){
						if ($(this).attr('value') == ngModel.$viewValue)
						{
							$(this).attr('selected', 'selected');
						}
						else
						{
							$(this).removeAttr('selected');
						}
					});
				}

				ngModel.$render = function () {
					ngModelReady = true;
					loadCollection();
					selectCurrentOption();
				};


				if (attrs.filter) {
					scope.$watch(attrs.filter, function (filter, old) {
						if (filter !== old) {
							itemsFilter = filter;
							redraw();
						}
					}, true);
				}
			}
		};
	}

	/**
	 * <rbs-collection-item-selector ng-model="" collection-code="" [collection-params="param1:value1;param2:value2;..."] />
	 */
	app.directive('rbsCollectionItemSelector', ['RbsChange.REST', rbsCollectionItemSelectorDirective]);

	function rbsCollectionItemSelectorDirective (REST) {

		var counter = 0;

		function tplRadio () {
			return '<label class="radio" ng-repeat="(value, item) in List.items">' +
				'	<input type="radio" name="rbsCollectionItemSelectorDirective(= counter =)" ng-model="List.value" value="(= value =)"/>' +
				'	(= item.label =)' +
				'</label>';
		}

		return {
			restrict : 'E',
			require  : 'ngModel',
			scope    : true,
			template : tplRadio(),

			link : function (scope, elm, attrs, ngModel) {
				scope.counter = ++counter;
				if (!attrs.collectionCode) {
					throw new Error("Missing required attribute: 'collection-code'.");
				}

				// Load Collection's items.
				scope.List = { loading: true, value: null };

				var collectionParamsReady = elm.is('[collectionParams]') ? false : true;
				if (!collectionParamsReady)
				{
					attrs.$observe('collectionParams', function(value) {
						if (value) {
							collectionParamsReady = true;
							loadCollection();
						}
					});
				}

				var ngModelReady = false;

				ngModel.$render = function () {
					ngModelReady = true;
					loadCollection();
				};

				function loadCollection() {
					if (!ngModelReady || !collectionParamsReady) {
						return;
					}

					var params = { code: attrs.collectionCode };
					if (attrs.collectionParams) {
						var parts = attrs.collectionParams.split(';');
						angular.forEach(parts, function(value, index){
							var values = value.split(':');
							params[values[0].trim()] = values[1].trim();
						});
					}

					REST.action('collectionItems', params).then(function (data) {
						scope.List.loading = false;
						scope.List.items = data.items;
						scope.List.value = ngModel.$viewValue;
					});

					scope.$watch('List.value', function (value, oldValue) {
						if (value !== null && value !== oldValue) {
							ngModel.$setViewValue(value);
						}
					}, true);
				}
			}
		};
	}

	/**
	 * <rbs-collection-multiple-item-selector ng-model="" collection-code="" [collection-params="param1:value1;param2:value2;..."] />
	 */
	app.directive('rbsCollectionMultipleItemSelector', ['RbsChange.REST', rbsCollectionMultipleItemSelectorDirective]);

	function rbsCollectionMultipleItemSelectorDirective (REST) {

		return {
			restrict : 'E',
			require  : 'ngModel',
			scope    : true,
			template : '<label class="checkbox" ng-repeat="(value, item) in List.items">' +
				'	<input type="checkbox" ng-model="List.values[value]" ng-change="checkboxChange(value)" value="(= value =)" />' +
				'	(= item.label =)' +
				'</label>',

			link : function (scope, elm, attrs, ngModel) {
				if (!attrs.collectionCode) {
					throw new Error("Missing required attribute: 'collection-code'.");
				}

				// Load Collection's items.
				scope.List = { loading: true, value: null };

				var collectionParamsReady = elm.is('[collectionParams]') ? false : true;
				if (!collectionParamsReady)
				{
					attrs.$observe('collectionParams', function(value) {
						if (value) {
							collectionParamsReady = true;
							loadCollection();
						}
					});
				}

				var ngModelReady = false;
				ngModel.$render = function () {
					ngModelReady = true;
					loadCollection();
				};

				function loadCollection() {
					if (!ngModelReady || !collectionParamsReady) {
						return;
					}

					var params = { code: attrs.collectionCode };
					if (attrs.collectionParams) {
						var parts = attrs.collectionParams.split(';');
						angular.forEach(parts, function(value, index){
							var values = value.split(':');
							params[values[0].trim()] = values[1].trim();
						});
					}

					REST.action('collectionItems', params).then(function (data) {
						scope.List.loading = false;
						scope.List.items = data.items;
						scope.List.values = {};
						angular.forEach(data.items, function(item){
							scope.List.values[item.value] = scope.isChecked(item.value);
						});

					});

					scope.checkboxChange = function (value){
						var viewValue = (!angular.isArray(ngModel.$viewValue)) ? [] : ngModel.$viewValue;
						if (scope.List.values[value] && viewValue.indexOf(value) == -1)
						{
							viewValue.push(value);
						}
						else
						{
							var index = viewValue.indexOf(value);
							if (index != -1)
							{
								viewValue.splice(index, 1);
							}
						}
						ngModel.$setViewValue(viewValue);
					};

					scope.isChecked = function (value){
						return (angular.isArray(ngModel.$viewValue) && ngModel.$viewValue.indexOf(value) != -1);
					}
				}
			}
		};
	}
})(window.jQuery);