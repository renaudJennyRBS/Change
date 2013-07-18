(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsItemsFromCollection', ['RbsChange.REST', '$timeout', rbsItemsFromCollectionDirective]);

	function rbsItemsFromCollectionDirective (REST, $timeout) {

		return {
			restrict : 'A',
			require  : 'ngModel',

			link : function (scope, elm, attrs, ngModel) {
				elm.find('option').attr('data-option-from-template', 'true');
				var ngModelReady = false;
				var collectionLoaded = false;
				var paramsAttrReady = elm.is('[rbs-items-collection-params]') ? false : true;
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
					elm.find('option:not([data-option-from-template])').remove();
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
							angular.forEach(data.items, function (item, value) {
								var $opt = $('<option></option>');
								if (ngModel.$viewValue == value) {
									$opt.attr('selected', 'selected');
								}
								$opt
									.attr('value', value)
									.text(item.label)
									.appendTo(elm);
							});
						},
						function () {
							$('<option></option>')
								.attr('selected', 'selected')
								.text("Unable to load Collection '" + attrs.rbsItemsFromCollection + "'.")
								.appendTo(elm);
						});
				}

				ngModel.$render = function () {
					ngModelReady = true;
					loadCollection();
				};
			}
		};
	}

	/**
	 * <rbs-collection-item-selector ng-model="" collection-code="" [collection-params="param1:value1;param2:value2;..."] />
	 * TODO: Implement a multiple selection (checkboxes).
	 */
	app.directive('rbsCollectionItemSelector', ['RbsChange.REST', rbsCollectionItemSelectorDirective]);

	function rbsCollectionItemSelectorDirective (REST) {

		var counter = 0;

		function tplRadio () {
			return '<label class="radio" ng-repeat="(value, item) in List.items">' +
				'	<input type="radio" name="rbsCollectionItemSelectorDirective' + (++counter) + '" ng-model="List.value" value="(= value =)"/>' +
				'	(= item.label =)' +
				'</label>';
		}

		return {
			restrict : 'E',
			require  : 'ngModel',
			scope    : true,
			template : tplRadio(),

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
})(window.jQuery);