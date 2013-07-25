(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsCorrectionViewer', ['RbsChange.Dialog', 'RbsChange.ArrayUtils', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.i18n', function (Dialog, ArrayUtils, REST, Utils, i18n) {
		return {

			restrict    : 'A',
			templateUrl: 'Rbs/Admin/js/directives/correction-viewer.twig',

			scope : {
				current : '=document'
			},

			link : function (scope, element, attrs) {

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

				scope.params = {
					'applyCorrectionWhen' : scope.correctionInfo.publicationDate ? 'planned' : 'now',
					'plannedCorrectionDate' : scope.correctionInfo.publicationDate
				};

				scope.reject = false;

				scope.deleteCorrection = function () {
					if (!window.confirm(i18n.trans('m.rbs.admin.admin.js.correction-confirm-delete'))) {
						return;
					}

					Dialog.closeEmbedded().then(function () {
						var copy = angular.copy(scope.current);
						if (Utils.removeCorrection(copy)) {
							REST.save(copy).then(function (updated) {
								delete scope.current.META$.correction;
								angular.extend(scope.current, updated);
								console.log("saved ", scope.current);
							});
						}
						else {
							console.warn("Could not remove Correction from ", copy);
						}
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

				scope.rejectContentValidation = function (message) {
					executeTask('contentValidation', {'reason' : message});
				};


				scope.publicationValidation = function () {
					executeTask('publicationValidation');
				};


				function executeTask (taskCode, params) {
					if (!params) {
						params = {};
					}
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