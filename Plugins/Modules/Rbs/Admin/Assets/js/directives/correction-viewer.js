(function () {
	var app = angular.module('RbsChange');

	app.directive('rbsCorrectionViewer', ['$timeout', 'RbsChange.Dialog', 'RbsChange.ArrayUtils', 'RbsChange.REST', 'RbsChange.Events', 'RbsChange.Utils', function ($timeout, Dialog, ArrayUtils, REST, Events, Utils) {
		return {

			restrict    : 'A',
			templateUrl: 'Rbs/Admin/js/directives/correction-viewer.twig',

			scope : {
				current : '=document'
			},

			link : function (scope, element, attrs) {

//				var originalDiff;

				function update () {
					ArrayUtils.clear(scope.diff);
					if (scope.correctionInfo) {
						angular.forEach(scope.correctionInfo.propertiesNames, function (property) {
							scope.diff.push({
								'id'       : property,
								'current'  : scope.current[property],
								'original' : scope.correctionInfo.original[property]
							});
						});
					}
				}

				scope.diff = [];
				scope.advancedDiffs = true;
				scope.correctionInfo = angular.copy(scope.current.META$.correction);
				update();

/*
				originalDiff = angular.copy(scope.diff);

				scope.$watch('correctionInfo', update, true);


				scope.cancelLine = function (line) {
					//ArrayUtils.removeValue(scope.correctionInfo.propertiesNames, line.id);
					Utils.removeCorrection(scope.current, line.id);
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
*/

				// TODO Init this according to the Correction info
				scope.params = {
					'applyCorrectionWhen' : scope.correctionInfo.publicationDate ? 'planned' : 'now',
					'plannedCorrectionDate' : scope.correctionInfo.publicationDate
				};

				scope.deleteCorrection = function () {
					Dialog.closeEmbedded().then(function () {
						console.log("removing correction from ", scope.current);

						var copy = angular.copy(scope.current);
						if (Utils.removeCorrection(copy)) {
							console.log("saving ", copy);
							REST.save(copy).then(function (updated) {
								delete scope.current.META$.correction;
								angular.extend(scope.current, updated);
								console.log("saved ", scope.current);
							});
						}
						else {
							console.warn("Could not remove Correction from ", copy);
						}

						// Notify the parent scope (bound to the FormsManager)
						//scope.$emit(Events.EditorCorrectionRemoved);
					});
				};

				scope.canChooseDate = function () {
					var cs = scope.correctionInfo.status;
					return scope.diff.length > 0 && (cs === 'DRAFT' || cs === 'VALIDATION' || cs === 'VALIDCONTENT');
				};


				scope.requestValidation = function () {
					executeTask('requestValidation');
				};


				scope.contentValidation = function () {
					executeTask('contentValidation');
				};


				scope.publicationValidation = function () {
					executeTask('publicationValidation');
				};


				function executeTask (taskCode) {
					var params = {};
					console.log("execute task: ", scope.params.applyCorrectionWhen, scope.params.plannedCorrectionDate);
					if (scope.params.applyCorrectionWhen === 'planned') {
						params['publicationDate'] = scope.params.plannedCorrectionDate;
					}

					REST.executeTask(taskCode, scope.current, params).then(function (doc) {
						if (Utils.hasCorrection(doc)) {
							scope.current.META$.correction = doc.META$.correction;
							scope.current.META$.actions = doc.META$.actions;
							scope.correctionInfo = angular.copy(scope.current.META$.correction);
						}
						else {
							delete scope.current.META$.correction;
							Dialog.closeEmbedded();
						}
					});
				}

			}

		};
	}]);

})();