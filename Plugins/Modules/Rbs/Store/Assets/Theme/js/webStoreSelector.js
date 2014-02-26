(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsStoreWebStoreSelectorInit($http) {
		return {
			restrict : 'A',
			link : function (scope, elm, attrs) {
				scope.selection = { webStoreId: parseInt(attrs.webStoreId, 10), billingAreaId: parseInt(attrs.billingAreaId, 10), zone: attrs.zone };
				scope.webStoreData = angular.fromJson(attrs.webStoreData);

				scope.billingAreaData = {};
				scope.zoneData = null;

				scope.$watch('selection.webStoreId', function() {
					if (scope.selection.webStoreId)
					{
						for (var i = 0; i < scope.webStoreData.length; i++)
						{
							var store = scope.webStoreData[i];
							if (store.id == scope.selection.webStoreId)
							{
								scope.billingAreaData = store.billingAreas;
							}
						}
					}
				});

				scope.$watch('selection.billingAreaId', function() {
					if (scope.selection.billingAreaId)
					{
						for (var i = 0; i < scope.billingAreaData.length; i++)
						{
							var area = scope.billingAreaData[i];
							if (area.id == scope.selection.billingAreaId)
							{
								scope.zoneData = area.zones;
							}
						}
					}
				});

				scope.canSubmit = function() {
					return (scope.selection.webStoreId !== null && scope.selection.billingAreaId !== null && scope.selection.zone !== null);
				};

				scope.submit = function() {
					$http.post('Action/Rbs/Store/SelectWebStore', scope.selection )
						.success (function() {
							window.location.reload();
						})
						.error(function(data, status, headers) {
							console.log('SelectWebStore error', data, status, headers);
						});
				};

				if (scope.webStoreData.length == 1) {
					scope.selection.webStoreId = scope.webStoreData[0].id;

					if (scope.webStoreData[0].billingAreas.length == 1) {
						scope.selection.billingAreaId = scope.webStoreData[0].billingAreas[0].id;

						if (scope.webStoreData[0].billingAreas[0].zones.length == 1)
						{
							scope.selection.zone = scope.webStoreData[0].billingAreas[0].zones[0];
						}

						if (scope.selection.webStoreId != attrs.webStoreId
							|| scope.selection.billingAreaId != attrs.billingAreaId
							|| scope.selection.zone != attrs.zone)
						{
							scope.submit();
						}
					}
				}
			}
		}
	}
	rbsStoreWebStoreSelectorInit.$inject = ['$http'];
	app.directive('rbsStoreWebStoreSelectorInit', rbsStoreWebStoreSelectorInit);
})();