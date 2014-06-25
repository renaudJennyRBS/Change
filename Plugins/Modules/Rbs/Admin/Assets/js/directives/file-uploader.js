/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($) {

	"use strict";

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFileUploader
	 * @name File uploader
	 * @restrict EA
	 *
	 * @description
	 * Displays a control to select a file from the user's computer.
	 */
	angular.module('RbsChange').directive('rbsFileUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout) {

		return {
			restrict    : 'EA',
			templateUrl : 'Rbs/Admin/js/directives/file-uploader.twig',
			require : 'ngModel',
			scope : true,

			link : function (scope, elm, attrs, ngModel)
			{
				scope.updatePreview = function updatePreviewFn (url) {
				};

				scope.fileOnload = function fileOnloadFn (event) {
					ngModel.$setViewValue(scope.inputFile.get(0).files[0].name);
				};

				scope.fileOnUpload = function fileOnUploadFn (response) {
					ngModel.$setViewValue(response);
					ngModel.$render();
				};
			}
		};
	}]);

})(window.jQuery);