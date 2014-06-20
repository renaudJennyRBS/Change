/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsVideoUploader
	 * @name Image uploader
	 * @restrict EA
	 *
	 * @description
	 * Displays a control to select an image from the user's computer.
	 */
	angular.module('RbsChange').directive('rbsVideoUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout)
	{
		var MAX_PREVIEW_HEIGHT = 200;

		return {
			restrict : 'EA',
			templateUrl : 'Rbs/Admin/js/directives/video-uploader.twig',
			require : 'ngModel',
			scope : true,

			link : function (scope, elm, attrs, ngModel)
			{
				scope.previewWidth = MAX_PREVIEW_HEIGHT * (16/9.0);
				scope.previewHeight = MAX_PREVIEW_HEIGHT;
				scope.justUploaded = false;

				scope.preload = "auto";

				scope.updatePreview = function updatePreviewFn (url)
				{
					if ((!scope.justUploaded || (scope.justUploaded && scope.editMode)) && (scope.videoSrc == null || scope.videoSrc == undefined) && url != null)
					{
						console.log('Update video preview with url: ' + url);
						scope.videoSrc = url;
					}
				};

				scope.fileOnload = function fileOnloadFn (event) {
					scope.videoSrc = null;
					ngModel.$setViewValue(scope.inputFile.get(0).files[0].name);
				};

				scope.fileOnUpload = function fileOnUploadFn (response) {
					scope.justUploaded = true;
					ngModel.$setViewValue(response);
					ngModel.$render();
				};

				scope.acceptedTypes = /video\/(ogg|mp4|webm)/;
				scope.storageName = "videos";
			}
		};
	}]);

})();