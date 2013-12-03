(function ($) {

	"use strict";

	angular.module('RbsChange').directive('imageUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout) {

		var MAX_PREVIEW_HEIGHT = 100;

		return {
			restrict    : 'EAC',
			templateUrl : 'Rbs/Admin/js/directives/image-uploader.twig',
			scope       : true,

			link : function (scope, elm, attrs, ngModel) {

				scope.previewWidth = MAX_PREVIEW_HEIGHT * (16/9.0);
				scope.previewHeight = MAX_PREVIEW_HEIGHT;

				scope.updatePreview = function updatePreviewFn (url) {
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

})(window.jQuery);