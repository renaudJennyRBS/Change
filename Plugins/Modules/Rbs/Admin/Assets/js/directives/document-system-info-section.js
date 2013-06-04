(function () {

	angular.module('RbsChange').directive('documentSystemInfoSection', function () {

		return {
			restrict    : 'A',
			templateUrl: 'Rbs/Admin/js/directives/document-system-info-section.twig',
			replace     : false,

			link : function (scope, elm, attrs) {
			}

		};

	});

})();