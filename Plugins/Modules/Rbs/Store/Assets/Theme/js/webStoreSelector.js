(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsStoreWebStoreSelectorInit($http) {
		return {
			restrict : 'A',
			link : function (scope, elm, attrs) {
				scope.selection = { webStoreId: parseInt(attrs.webStoreId, 10), billingAreaId: parseInt(attrs.billingAreaId, 10) };
				scope.webStoreData = angular.fromJson(attrs.webStoreData);

				function areaInArray(areas, id) {
					for (var i = 0; i < areas.length; i++) {
						var area = areas[i];
						if (area.id == id) {
							return true;
						}
					}
					return false;
				}

				scope.$watch('selection.webStoreId', function() {
					if (scope.selection.webStoreId) {
						for (var i = 0; i < scope.webStoreData.length; i++) {
							var store = scope.webStoreData[i];
							if (store.id == scope.selection.webStoreId) {
								var areas = store['billingAreas'];
								if (areas.length == 1) {
									scope.selection.billingAreaId = areas[0].id;
								}
								else if (!areaInArray(areas, scope.selection.billingAreaId)) {
									scope.selection.billingAreaId = null;
								}
							}
						}
					}
				});

				scope.canSubmit = function() {
					return (scope.selection.webStoreId !== null && scope.selection.billingAreaId !== null);
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

					if (scope.webStoreData[0]['billingAreas'].length == 1) {
						scope.selection.billingAreaId = scope.webStoreData[0]['billingAreas'][0].id;

						if (scope.selection.webStoreId != attrs.webStoreId
							|| scope.selection.billingAreaId != attrs.billingAreaId) {
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