(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.directive('rbsItemsFromCollection', [
		'RbsChange.REST',
		rbsItemsFromCollectionDirective
	]);

	function rbsItemsFromCollectionDirective (REST) {

		return {
			restrict : 'A',
			require  : 'ngModel',

			link : function (scope, elm, attrs, ngModel) {

				// Load Collection's items.
				REST.action(
					'collectionItems',
					{ "code" : attrs.rbsItemsFromCollection }
				).then(function (data) {
					angular.forEach(data.items, function (item, value) {
						var $opt = $('<option></option>');
						if (ngModel.$viewValue === value) {
							$opt.attr('selected', 'selected');
						}
						$opt
							.attr('value', value)
							.text(item.label)
							.appendTo(elm);
					});
				}, function () {
					$('<option></option>')
						.attr('selected', 'selected')
						.text("Unable to load Collection '" + attrs.rbsItemsFromCollection + "'.")
						.appendTo(elm);
				});

			}
		};

	}


	/**
	 * <rbs-collection-item-selector/>
	 * TODO Implement a list of radio buttons/checkboxes linked to a Collection.
	app.directive('rbsCollectionItemSelector', [
		'RbsChange.REST',
		rbsCollectionItemSelectorDirective
	]);

	function rbsCollectionItemSelectorDirective (REST) {

		var counter = 0;

		function tplRadio () {
			return '<label class="radio" ng-repeat="item in items">' +
				'<input type="radio" name="rbsCollectionItemSelectorDirective' + (++counter) + '" ng-checked="selectedValue == item.value" value="(=item.value=)"/> (=item.label=) | (=selectedValue=)' +
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

				scope.multiple = attrs.multiple === 'true';

				// Load Collection's items.
				var promise = REST.action('collectionItems', {
					"code" : attrs.collectionCode
				});
				promise.then(function (data) {
					var items = [];
					angular.forEach(data.items, function (item, value) {
						items.push({
							'value' : value,
							'label' : item.label
						});
					});
					scope.items = items;
					ngModel.$render();
				});

				scope.$watch('selectedValue', function (value) {
					console.log("selectedValue=", value);
					ngModel.$viewValue = value;
				}, true);

				ngModel.$render = function () {
					scope.selectedValue = ngModel.$viewValue;
				};

			}
		};
	}
	*/

})(window.jQuery);