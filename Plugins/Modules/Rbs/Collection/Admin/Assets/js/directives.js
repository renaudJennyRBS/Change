(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.directive('rbsItemsFromCollection', [
		'RbsChange.REST',
		'$timeout',
		rbsItemsFromCollectionDirective
	]);

	function rbsItemsFromCollectionDirective (REST, $timeout) {

		return {
			restrict : 'A',
			require  : 'ngModel',

			link : function (scope, elm, attrs, ngModel) {
				elm.find('option').attr('data-option-from-template', 'true');
				var ngModelReady = false;
				var paramsAttrReady = elm.is('[rbs-items-collection-params]') ? false : true;
				if (!paramsAttrReady)
				{
					attrs.$observe('rbsItemsCollectionParams', function(value){
						if (value)
						{
							paramsAttrReady = true;
							loadCollection();
						}
					});
				}
				// Load Collection's items.
				function loadCollection () {
					if (!ngModelReady || !paramsAttrReady)
					{
						return;
					}
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
					REST.action(
						'collectionItems',
						params
					).then(function (data) {
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
					}, function () {
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