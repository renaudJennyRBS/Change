(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function rbsDocumentEditorRbsStorelocatorStoreEdit(REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',
			link: function(scope, element, attrs, editorCtrl) {

				scope.onLoad = function() {
					if (angular.isArray(scope.document.card) || !angular.isObject(scope.document.card)) {
						scope.document.card = {};
					}
				};
				scope.onReload = function() {
					if (angular.isArray(scope.document.card) || !angular.isObject(scope.document.card)) {
						scope.document.card = {};
					}
				};

				scope.applyHoursToAll = function(dayHours) {
					angular.forEach(scope.document.openingHours, function(day) {
						day.amBegin = dayHours.amBegin;
						day.amEnd = dayHours.amEnd;
						day.pmBegin = dayHours.pmBegin;
						day.pmEnd = dayHours.pmEnd;
					})
				};

				scope.continuousDay = function(dayHours) {
					dayHours.amEnd = null;
					dayHours.pmBegin = null;
				};

				scope.closedDay = function(dayHours) {
					dayHours.amBegin = null;
					dayHours.amEnd = null;
					dayHours.pmBegin = null;
					dayHours.pmEnd = null;
				};

				scope.applyDayToAll = function(specialDay) {
					angular.forEach(scope.document.specialDays, function(day) {
						day.amBegin = specialDay.amBegin;
						day.amEnd = specialDay.amEnd;
						day.pmBegin = specialDay.pmBegin;
						day.pmEnd = specialDay.pmEnd;
					})
				};

				scope.deleteDay = function(specialDay) {
					var specialDays = [];
					angular.forEach(scope.document.specialDays, function(day) {
						if (day !== specialDay) {
							specialDays.push(day);
						}
					});
					scope.document.specialDays = specialDays
				};

				scope.addSpecialDay = function(specialDay) {
					scope.document.specialDays.push(specialDay);
					scope.specialDay = {};
				}
			}
		};
	}

	rbsDocumentEditorRbsStorelocatorStoreEdit.$inject = ['RbsChange.REST'];
	app.directive('rbsDocumentEditorRbsStorelocatorStoreEdit', rbsDocumentEditorRbsStorelocatorStoreEdit);


	//app.directive('rbsDocumentEditorRbsStorelocatorStoreNew', rbsDocumentEditorRbsStorelocatorStore);
})();