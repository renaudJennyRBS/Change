(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsPageHeader', ['RbsChange.EditorManager', 'RbsChange.Utils', function (EditorManager, Utils) {
		return {

			restrict: 'E',
			templateUrl: 'Rbs/Admin/js/directives/page-header.twig',
			replace: true,

			link: function (scope, element, attrs) {
				attrs.$observe('title', function (value) {
					scope.title = value;
				});

				if (attrs.subTitle) {
					scope.subTitle = attrs.subTitle;
				}

				scope.$on('Change:Editor:LocalCopyMerged', function () {
					scope.localCopyMerged = true;
				});

				scope.showLocalCopyMessage = true;

				scope.hasLocalCopy = function() {
					return Utils.isDocument(scope.document) && EditorManager.getLocalCopy(scope.document) != null
				};

				scope.mergeLocalCopy = function() {
					var doc = scope.document;
					var localCopy = EditorManager.getLocalCopy(doc);
					if (localCopy) {
						angular.extend(doc, localCopy);
						EditorManager.removeLocalCopy(doc);
						scope.showLocalCopyMessage = false;
					}
				};

				scope.removeLocalCopy = function() {
					var doc = scope.document;
					EditorManager.removeLocalCopy(doc);
					scope.showLocalCopyMessage = false;
				};

				scope.showWorkflowButton = function () {
					return Utils.isDocument(scope.document) && !scope.document.isNew()
						&& (scope.document.publicationStatus || scope.document.hasCorrection());
				};

				scope.getWorkflowRouteName = function () {
					return (Utils.isDocument(scope.document) && scope.document.refLCID && scope.document.refLCID != scope.document.LCID) ? 'localizedWorkflow' : 'workflow';
				};
			}
		};
	}]);
})(window.jQuery);