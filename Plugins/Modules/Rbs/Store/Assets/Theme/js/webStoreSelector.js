(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsStoreWebStoreSelectorInit(AjaxAPI) {
		return {
			templateUrl: '/rbsStoreWebStoreSelectorInit.tpl',
			restrict : 'A',
			scope: {},
			link : function (scope, elm, attrs) {
				var cacheKey = attrs['cacheKey'];
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);

				var data = AjaxAPI.globalVar(cacheKey);
				scope.webStoreData = data.webStores;
				scope.originalSelection = angular.copy(data.common);
				scope.selection = data.common;

				function buildSelection(selection) {
					scope.billingAreaData = [];
					scope.zoneData = [];
					if (selection) {
						if (scope.webStoreData.length == 1) {
							selection.webStoreId = scope.webStoreData[0].common.id;
						}

						if (selection.webStoreId) {
							var validWebStoreId = false;
							for (var i = 0; i < scope.webStoreData.length; i++) {
								var store = scope.webStoreData[i];
								if (store.common.id == selection.webStoreId) {
									validWebStoreId = true;
									scope.billingAreaData = store.billingAreas;
									if (store.billingAreas.length == 1) {
										selection.billingAreaId = store.billingAreas[0].common.id;
									}
									if (selection.billingAreaId) {
										var validBillingAreaId = false;
										for (var j = 0; j < store.billingAreas.length; j++) {
											var area = store.billingAreas[j];
											if (area.common.id == selection.billingAreaId) {
												validBillingAreaId = true;
												scope.zoneData = area.zones;
												if (area.zones.length == 1) {
													selection.zone = area.zones[0].common.code;
												}
												var validZone = false;
												for (var k = 0; k < area.zones.length; k++) {
													if (selection.zone == area.zones[k].common.code) {
														validZone = true;
													}
												}
												if (!validZone) {
													selection.zone = null;
												}
											}
										}
										if (!validBillingAreaId) {
											selection.billingAreaId = 0;
										}
									}
								}
							}
							if (!validWebStoreId) {
								selection.webStoreId = 0;
							}
						}
					}
				}

				buildSelection(scope.selection, true);

				scope.$watch('selection', function(selection) {
					buildSelection(selection, false);
				}, true);

				scope.canSubmit = function() {
					return (scope.selection.webStoreId != scope.originalSelection.webStoreId ||
					scope.selection.billingAreaId != scope.originalSelection.billingAreaId ||
					scope.selection.zone != scope.originalSelection.zone);
				};

				scope.submit = function() {
					AjaxAPI.putData('Rbs/Commerce/Context', scope.selection)
						.success (function(data) {
							window.location.reload();
						})
						.error(function(data, status, headers) {
							console.log('SelectWebStore error', data, status, headers);
						});
				};

				scope.show = scope.webStoreData.length > 1;
				angular.forEach(scope.webStoreData, function(store) {
					if (store.billingAreas.length > 1) {
						scope.show = true;
					}
					angular.forEach(store.billingAreas, function(billingArea) {
						if (billingArea.zones.length > 1) {
							scope.show = true;
						}
					})
				})
			}
		}
	}
	rbsStoreWebStoreSelectorInit.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsStoreWebStoreSelectorInit', rbsStoreWebStoreSelectorInit);
})();