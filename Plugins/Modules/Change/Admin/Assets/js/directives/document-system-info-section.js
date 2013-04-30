(function () {

	angular.module('RbsChange').directive('documentSystemInfoSection', function () {

		return {
			restrict    : 'A',
			templateUrl: 'Change/Admin/js/directives/document-system-info-section.html',
			replace     : false,

			link : function (scope, elm, attrs) {
			}

		};

	});

})();