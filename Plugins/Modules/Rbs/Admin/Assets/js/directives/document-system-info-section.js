(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsDocumentSystemInfoSection', ['RbsChange.REST', function (REST) {

		return {
			restrict    : 'A',
			templateUrl : 'Rbs/Admin/js/directives/document-system-info-section.twig',
			replace     : false,

			link : function (scope)
			{
				REST.getAvailableLanguages().then(function (langs) {
					scope.availableLanguages = langs.items;
				});
			}

		};

	}]);

})();