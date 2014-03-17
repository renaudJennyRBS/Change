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
	 * @id RbsChange.directive:rbsImageUploader
	 * @name Image uploader
	 * @restrict EA
	 *
	 * @description
	 * Displays a control to select an image from the user's computer.
	 */
	angular.module('RbsChange').directive('rbsImageUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout)
	{
		var MAX_PREVIEW_HEIGHT = 100;

		return {
			restrict : 'EA',
			templateUrl : 'Rbs/Admin/js/directives/image-uploader.twig',
			scope : true,

			link : function (scope)
			{
				scope.previewWidth = MAX_PREVIEW_HEIGHT * (16/9.0);
				scope.previewHeight = MAX_PREVIEW_HEIGHT;

				scope.updatePreview = function updatePreviewFn (url)
				{
					var	img = new Image();
					img.onload = function () {
						$timeout(function () {
							scope.imageWidth  = img.width;
							scope.imageHeight = img.height;
							scope.previewHeight = Math.min(scope.imageHeight, MAX_PREVIEW_HEIGHT);
							scope.previewWidth = scope.previewHeight * (scope.imageWidth / scope.imageHeight);
							scope.imageSrc = url;
						});
					};
					img.src = url;
				};

				scope.acceptedTypes = /image\/(gif|jpeg|png)/;
				scope.storageName = "images";
			}
		};
	}]);

})();