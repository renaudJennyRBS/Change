(function () {

	"use strict";

	angular.module('RbsChange').directive('rbsDocumentPublicationSection', function () {

		return {
			restrict    : 'A',
			templateUrl : 'Rbs/Admin/js/directives/document-publication-section.twig',
			replace     : false,

			link : function (scope, iElement, iAttrs)
			{
				scope.hasSpecificHelp = false;

				if (iAttrs.rbsDocumentPublicationSectionHelp != undefined && iAttrs.rbsDocumentPublicationSectionHelp != "")
				{
					scope.hasSpecificHelp = true;
					scope.specificHelp = iAttrs.rbsDocumentPublicationSectionHelp;
				}
			}

		};

	});

})();