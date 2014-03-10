/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	function rbsOrderCoupons(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Order/Order/coupons.twig',
			scope: true,

			link: function(scope) {
				angular.extend(scope.orderContext, {
					showCouponUI: false,
					showNewCouponUI: false
				});
				scope.data = {
					newCoupon: null,
					editedLineIndex: null
				};

				scope.addCoupon = function() {
					if (scope.data.newCoupon) {
						var coupons = scope.document.coupons;
						for (var i = 0; i < coupons.length; i++) {
							if (coupons[i].options && coupons[i].options.id == scope.data.newCoupon.id) {
								// Already added.
								scope.orderContext.showNewCouponUI = false;
								scope.data.newCoupon = null;
								return;
							}
						}
						REST.ensureLoaded(scope.data.newCoupon).then(function(doc) {
							coupons.push({
								code: doc.code,
								title: doc.title,
								options: { id: doc.id }
							})
						});
					}
					scope.orderContext.showNewCouponUI = false;
					scope.data.newCoupon = null;
				};

				scope.removeCoupon = function(index) {
					scope.document.coupons.splice(index, 1);
				};
			}
		};
	}

	rbsOrderCoupons.$inject = [ 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsOrderCoupons', rbsOrderCoupons);
})();