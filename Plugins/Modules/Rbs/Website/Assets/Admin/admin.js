(function () {
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsDocumentPreviewRbsWebsiteTopic', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'E',
			scope: {
				document: '='
			},
			templateUrl: 'Rbs/Website/rbs-document-preview-rbs-website-topic.twig',
			link: function(scope) {
				REST.ensureLoaded(scope.document);
			}
		};
	}]);

	app.directive('rbsDocumentPreviewRbsWebsiteWebsite', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'E',
			scope: {
				document: '='
			},
			templateUrl: 'Rbs/Website/rbs-document-preview-rbs-website-website.twig',
			link: function(scope) {
				REST.ensureLoaded(scope.document);
			}
		};
	}]);
})();