(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsDocumentWorkflowActions', ['$timeout', '$q', 'RbsChange.REST', 'RbsChange.Utils', function ($timeout, $q, REST, Utils) {

		return {
			restrict : 'C',
			replace  : true,
			templateUrl : 'Rbs/Admin/js/directives/workflow-actions.twig',

			scope : {
				'document' : '=',
				'onClose'  : '&'
			},

			link : function (scope, element, attrs) {

				var	lastUpdatedDoc = null,
					oldCssClass = null;

				scope.data = {
					rejectReason : '',
					contentAction : 'accept',
					action : ''
				};


				function freezeUI () {
					element.find('button').attr('disabled', 'disabled');
				}


				function unfreezeUI (error) {
					element.find('button').removeAttr('disabled');
					scope.data.progress = undefined;

					if (error) {
						scope.data.error = error;
					}
					else {
						scope.data.error = null;
					}

					if (lastUpdatedDoc) {
						angular.extend(scope.document, lastUpdatedDoc);
						lastUpdatedDoc = null;
					}
				}


				function accept (actionName) {
					freezeUI();
					REST.executeTaskByCodeOnDocument(actionName, scope.document).then(
						// Success
						function (doc) {
							lastUpdatedDoc = doc;
							unfreezeUI();
						},
						// Error
						unfreezeUI
					);
				}


				function reject (actionName, reason) {
					freezeUI();
					REST.executeTaskByCodeOnDocument(actionName, scope.document, {'reason': reason}).then(
						// Success
						function (doc) {
							lastUpdatedDoc = doc;
							unfreezeUI();
						},
						// Error
						unfreezeUI
					);
				}


				function publicationDatesChanged () {
					return ! angular.equals(scope.data.startPublication, scope.document.startPublication) || ! angular.equals(scope.data.endPublication, scope.document.endPublication);
				}


				function doSubmit () {
					console.log("executing task ", scope.data.action);
					if (scope.data.action === 'contentValidation' && scope.data.contentAction === 'reject') {
						reject(scope.data.action, scope.data.rejectReason);
					}
					else {
						accept(scope.data.action);
					}
				}


				scope.$watch('document', function documentChanged (doc) {
					if (Utils.isDocument(doc))
					{
						if (Utils.hasCorrection(doc)) {
							scope.data.action = 'correction';
						}
						else {
							angular.forEach(['requestValidation', 'contentValidation', 'publicationValidation', 'freeze', 'unfreeze'], function (action) {
								if (doc.isActionAvailable(action)) {
									scope.data.action = action;
								}
							});
						}

						scope.data.startPublication = doc.startPublication;
						scope.data.endPublication = doc.endPublication;

						if (oldCssClass) {
							element.prev('.workflow-indicator').addBack().removeClass(oldCssClass);
						}
						element.prev('.workflow-indicator').addBack().addClass(doc.publicationStatus);
						oldCssClass = doc.publicationStatus;
					}
				}, true);


				scope.submit = function () {
					// When validating the publication, we need to save the 'startPublication' and 'endPublication'
					// properties on the Document before executing the workflow task.
					if (scope.data.action === 'publicationValidation' && publicationDatesChanged()) {
						console.log("publicationValidation: saving Document with ", scope.data.startPublication, scope.data.endPublication);
						scope.document.startPublication = scope.data.startPublication;
						scope.document.endPublication = scope.data.endPublication;
						REST.save(scope.document, null, ['startPublication', 'endPublication']).then(function (updated) {
							angular.extend(scope.document, updated);
							doSubmit();
						});
					}
					else {
						doSubmit();
					}
				};


				scope.closeWorkflow = function () {
					scope.onClose();
				};


				scope.runWholeWorkflow = function () {
					freezeUI();
					var defer = $q.defer();
					scope.data.progress = 0;
					lastUpdatedDoc = null;
					REST.executeTaskByCodeOnDocument('requestValidation', scope.document).then(

						// Success
						function (doc) {
							lastUpdatedDoc = doc;
							scope.data.progress = 33.33;
							REST.executeTaskByCodeOnDocument('contentValidation', doc).then(

								// Success
								function (doc) {
									lastUpdatedDoc = doc;
									scope.data.progress = 66.66;
									REST.executeTaskByCodeOnDocument('publicationValidation', doc).then(

										// Success
										function (doc) {
											lastUpdatedDoc = doc;
											scope.data.progress = 100;
											$timeout(function () {
												scope.data.progress = undefined;
												unfreezeUI();
												defer.resolve();
											}, 100);
										},

										// publicationValidation error
										unfreezeUI
									);
								},

								// contentValidation error
								unfreezeUI
							);
						},

						// requestValidation error
						unfreezeUI
					);

					return defer.promise;
				};


				scope.hasProgressInfo = function () {
					return scope.data.progress !== undefined;
				};
			}
		};

	}]);

})();