/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	function rbsOrderModifiers(REST, $filter, i18n, NotificationCenter, ErrorFormatter, Events) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Order/Order/modifiers.twig',
			scope: true,

			link: function(scope) {
				scope.data = {
					newDocumentFee: null,
					newCustomFee: getClearCustomFee(),
					editedFeeIndex: null,
					newDocumentDiscount: null,
					newCustomDiscount: getClearCustomDiscount(),
					editedDiscountIndex: null
				};

				// Fees.
				scope.addCustomFee = function() {
					scope.data.newCustomFee.index = scope.document['fees'].length;
					scope.data.newCustomFee.key = scope.data.newCustomFee.items[0].codeSKU;
					scope.document['fees'].push(scope.data.newCustomFee);
					scope.data.newCustomFee = getClearCustomFee();
					scope.updateNewFeeUI(null);
				};

				scope.removeFee = function(index) {
					scope.document['fees'].splice(index, 1);
				};

				scope.updateNewFeeUI = function(mode) {
					if (mode == 'document') {
						scope.orderContext.showNewCustomFeeUI = false;
						scope.orderContext.showNewDocumentFeeUI = !scope.orderContext.showNewDocumentFeeUI;
					}
					else if (mode == 'custom') {
						scope.orderContext.showNewDocumentFeeUI = false;
						scope.orderContext.showNewCustomFeeUI = !scope.orderContext.showNewCustomFeeUI;
					}
					else {
						scope.orderContext.showNewDocumentFeeUI = false;
						scope.orderContext.showNewCustomFeeUI = false;
					}
				};

				function getClearCustomFee() {
					return {
						"index": 0,
						"key": null,
						"designation": "",
						"quantity": 1,
						"items": [
							{
								"codeSKU": "",
								"reservationQuantity": 1,
								"price": {
									"value": null,
									"withTax": scope.priceInfo.withTax,
									"taxCategories": {}
								},
								"options": {
									"lockedPrice": true
								}
							}
						],
						"options": {}
					};
				}

				// Discounts.
				scope.addCustomDiscount = function() {
					scope.document['discounts'].push(scope.data.newCustomDiscount);
					scope.data.newCustomDiscount = getClearCustomDiscount();
					scope.updateNewDiscountUI(null);
				};

				scope.removeDiscount = function(index) {
					scope.document['discounts'].splice(index, 1);
				};

				scope.updateNewDiscountUI = function(mode) {
					if (mode == 'document') {
						scope.orderContext.showNewCustomDiscountUI = false;
						scope.orderContext.showNewDocumentDiscountUI = !scope.orderContext.showNewDocumentDiscountUI;
					}
					else if (mode == 'custom') {
						scope.orderContext.showNewDocumentDiscountUI = false;
						scope.orderContext.showNewCustomDiscountUI = !scope.orderContext.showNewCustomDiscountUI;
					}
					else {
						scope.orderContext.showNewDocumentDiscountUI = false;
						scope.orderContext.showNewCustomDiscountUI = false;
					}
				};

				function getClearCustomDiscount() {
					return {
						"title": "",
						"price": {
							"value": null,
							"withTax": scope.priceInfo.withTax,
							"taxCategories": {}
						},
						"options": {
							"lockedPrice": true
						}
					};
				}

				scope.$watch('document.fees', function(fees) {
					scope.amounts.totalFeesAmount = 0;
					for (var i = 0; i < fees.length; i++) {
						for (var j = 0; j < fees[i].items.length; j++) {
							scope.amounts.totalFeesAmount += fees[i].items[j].price.value;
						}
					}
				}, true);

				scope.$watch('document.discounts', function(discounts) {
					scope.amounts.totalDiscountsAmount = 0;
					for (var i = 0; i < discounts.length; i++) {
						scope.amounts.totalDiscountsAmount += discounts[i].price.value;
					}
				}, true);
			}
		};
	}
	rbsOrderModifiers.$inject = [ 'RbsChange.REST', '$filter', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Events' ];
	angular.module('RbsChange').directive('rbsOrderModifiers', rbsOrderModifiers);

	/**
	 * Fee edition.
	 */
	function rbsOrderModifierFeeEditor() {
		return {
			restrict: 'E',
			templateUrl: 'Document/Rbs/Order/Order/modifierFeeEditor.twig',
			require: 'ngModel',
			scope: {
				'priceInfo': "="
			},

			link: function(scope, element, attrs, ngModel) {
				scope.fee = {};

				ngModel.$render = function ngModelRenderFn() {
					scope.fee = ngModel.$viewValue;
					var price = scope.fee.items[0].price;
					if (!angular.isObject(price.taxCategories)) {
						price.taxCategories = {};
					}
				};
			}
		};
	}
	angular.module('RbsChange').directive('rbsOrderModifierFeeEditor', rbsOrderModifierFeeEditor);

	/**
	 * Discount edition.
	 */
	function rbsOrderModifierDiscountEditor() {
		return {
			restrict: 'E',
			templateUrl: 'Document/Rbs/Order/Order/modifierDiscountEditor.twig',
			require: 'ngModel',
			scope: {
				'priceInfo': "="
			},

			link: function(scope, element, attrs, ngModel) {
				scope.discount = {};

				ngModel.$render = function ngModelRenderFn() {
					scope.discount = ngModel.$viewValue;
					var price = scope.discount.items[0].price;
					if (!angular.isObject(price.taxCategories)) {
						price.taxCategories = {};
					}
				};
			}
		};
	}
	angular.module('RbsChange').directive('rbsOrderModifierDiscountEditor', rbsOrderModifierDiscountEditor);
})();