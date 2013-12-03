(function ($) {

	"use strict";

	angular.module('RbsChange').directive('fileUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout) {

		return {
			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/file-uploader.twig',
			scope       : true
		};
	}]);

})(window.jQuery);