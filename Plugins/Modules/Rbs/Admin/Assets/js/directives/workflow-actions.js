(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsDocumentWorkflowActions', ['$timeout', 'RbsChange.REST', function ($timeout, REST) {

		return {
			restrict : 'C',
			replace  : true,
			templateUrl : 'Rbs/Admin/js/directives/workflow-actions.twig',

			scope : {
				document : '='
			},

			link : function (scope, element, attrs) {

				scope.data = {
					rejectReason : '',
					contentAction : 'accept',
					action : ''
				};

				var oldCssClass = null;

				function accept (actionName) {
					REST.executeTaskByCodeOnDocument(actionName, scope.document).then(function (doc) {
						angular.extend(scope.document, doc);
					});
				}

				function reject (actionName, reason) {
					REST.executeTaskByCodeOnDocument(actionName, scope.document, {'reason': reason}).then(function (doc) {
						angular.extend(scope.document, doc);
					});
				}

				scope.$watch('document', function documentChanged (doc) {
					if (doc) {
						angular.forEach(['requestValidation', 'contentValidation', 'publicationValidation', 'freeze', 'unfreeze'], function (action) {
							if (doc.isActionAvailable(action)) {
								scope.data.action = action;
							}
						});
						if (oldCssClass) {
							element.prev('.workflow-indicator').addBack().removeClass(oldCssClass);
						}
						element.prev('.workflow-indicator').addBack().addClass(doc.publicationStatus);
						oldCssClass = doc.publicationStatus;
					}
				}, true);

				scope.submit = function () {
					if (scope.data.action === 'contentValidation' && scope.data.contentAction === 'reject') {
						reject(scope.data.action, scope.data.rejectReason);
					}
					else {
						accept(scope.data.action);
					}

				};
			}
		};

	}]);

})();