(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	app.directive('rbsStoreshippingShortStore', ['RbsChange.AjaxAPI', '$rootScope', function(AjaxAPI, $rootScope) {
		return {
			restrict: 'A',
			templateUrl: '/rbsStoreshippingShortStore.tpl',
			scope: {},
			controller: ['$scope', '$element', function(scope, elem) {
				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				scope.storeData = AjaxAPI.globalVar(cacheKey);

				$rootScope.$on('rbsStorelocatorChooseStore', function(event, storeId) {
					if (angular.isNumber(storeId)) {
						var data = {storeId: storeId};
						AjaxAPI.putData('Rbs/Storeshipping/Store/Default', data, {URLFormats:'canonical'})
							.success(function(data) {
								if (!angular.isArray(data.dataSets) && angular.isObject(data.dataSets)) {
									scope.storeData = data.dataSets;
								} else {
									scope.storeData = null;
								}
								scope.$emit('rbsStorelocatorDefaultStore', scope.storeData);
							}).error(function(data) {
								console.log('error: setDefault', data, storeId);
							});
					} else {
						scope.storeData = null;
					}
				});

				$rootScope.$on('rbsUserConnected', function(event, params) {
					if (params && params.accessorId) {
						scope.$emit('rbsStorelocatorChooseStore', 0);
					}
				});

				this.search = function(data) {
					var request = AjaxAPI.getData('Rbs/Storelocator/Store/', data);
					request.success(function(data) {
						if (data.items.length) {
							scope.$emit('rbsStorelocatorChooseStore', data.items[0].common.id);
						}
					});
					return request;
				};
			}],

			link: function(scope, elem, attrs, controller) {
				scope.chooseStoreUrl = attrs.chooseStoreUrl;
				if (!angular.isString(scope.chooseStoreUrl) || !scope.chooseStoreUrl.length) {
					scope.chooseStoreUrl = null;
				}

				if (attrs.autoSelect == '1' && !scope.storeData) {
					navigator.geolocation.getCurrentPosition(
						function (position) {
							var coordinates = {latitude: position.coords.latitude, longitude: position.coords.longitude};
							controller.search({coordinates:coordinates, distance: '50km',
								facetFilters: {storeAllow: {allowReservation: 1, allowPurchase: 1}}});
						},
						function (error) {
							console.log('unable to locate')
						}, {timeout: 5000, maximumAge: 0}
					);
				}
			}
		}
	}]);
})();