(function () {
	var app = angular.module('RbsChange');

	app.directive('correctionViewer', ['$timeout', 'RbsChange.Dialog', 'RbsChange.ArrayUtils', 'RbsChange.REST', 'RbsChange.Events', function ($timeout, Dialog, ArrayUtils, REST, Events) {
		return {

			restrict    : 'A',
			templateUrl: 'Rbs/Admin/js/directives/correction-viewer.html',

			scope : {
				current : '='
			},

			link : function (scope, element, attrs) {

				var originalDiff;

				function update () {
					ArrayUtils.clear(scope.diff);
					angular.forEach(scope.correctionInfo.propertiesNames, function (property) {
						scope.diff.push({
							'id'       : property,
							'current'  : scope.current[property],
							'original' : scope.correctionInfo.original[property]
						});
					});
				}

				scope.diff = [];
				scope.advancedDiffs = false;
				scope.correctionInfo = angular.copy(scope.current.META$.correction);
				update();
				originalDiff = angular.copy(scope.diff);


				scope.$watch('correctionInfo', update, true);


				scope.cancelLine = function (line) {
					ArrayUtils.removeValue(scope.correctionInfo.propertiesNames, line.id);
				};


				scope.hasChanges = function () {
					return ! angular.equals(scope.diff, originalDiff);
				};


				scope.cancelChanges = function () {
					scope.correctionInfo = angular.copy(scope.current.META$.correction);
				};


				scope.saveChanges = function () {

					// Tell the Editor to update the document that is being edited.
					// Changes will be made on the document: the "Save" button should be enabled so that the
					// user can save the document. When the document is saved, all the properties whose value equals
					// the value in the original document are removed from the correction object.
					var properties = {};
					console.log("diff=", originalDiff);
					console.log("scope.correctionInfo.propertiesNames=", scope.correctionInfo.propertiesNames);
					angular.forEach(originalDiff, function (line) {
						if (ArrayUtils.inArray(line.id, scope.correctionInfo.propertiesNames) === -1) {
							properties[line.id] = line.original;
							ArrayUtils.removeValue(scope.current.META$.correction.propertiesNames, line.id);
						}
					});

					if (scope.current.META$.correction.propertiesNames.length === 0) {
						delete scope.current.META$.correction;
					}

					Dialog.closeEmbedded().then(function () {
						// Notify the parent scope (bound to the FormsManager)
						scope.$emit(Events.EditorCorrectionChanged, properties);
					});

				};


				scope.deleteCorrection = function () {

					var properties = {};
					angular.forEach(scope.correctionInfo.propertiesNames, function (property) {
						properties[property] = scope.current.META$.correction.original[property];
					});
					delete scope.current.META$.correction;

					Dialog.closeEmbedded().then(function () {
						// Notify the parent scope (bound to the FormsManager)
						scope.$emit(Events.EditorCorrectionRemoved, properties);
					});

				};


				scope.submitNow = function () {
					REST.resourceAction('startCorrectionValidation', scope.current).then(function (result) {
						console.log(result);
						console.log(scope.current.META$.correction);
						scope.current.META$.correction.status = result.data['correction-status'];
						scope.correctionInfo = angular.copy(scope.current.META$.correction);
					});
				};


				scope.publish = function () {
					REST.resourceAction('startCorrectionPublication', scope.current, {'publishImmediately': true}).then(function (result) {
						scope.current.META$.correction.status = result.data['correction-status'];
						scope.correctionInfo = angular.copy(scope.current.META$.correction);
						if (scope.current.META$.correction.status === 'FILED') {
							delete scope.current.META$.correction;
						}
					});
				};


				scope.submitPlanned = function () {

				};


				scope.closeEmbeddedModal = function () {
					this.cancelChanges();
					Dialog.closeEmbedded();
				};

			}

		};
	}]);

})();