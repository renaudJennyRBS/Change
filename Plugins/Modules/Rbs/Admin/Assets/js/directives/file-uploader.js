(function ($) {

	"use strict";

	angular.module('RbsChange').directive('rbsFileUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout) {

		return {
			restrict    : 'EA',
			templateUrl : 'Rbs/Admin/js/directives/file-uploader.twig',
			scope       : true
		};
	}]);

})(window.jQuery);