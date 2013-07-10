/**
 * Created with JetBrains PhpStorm.
 * User: fredericbonjour
 * Date: 24/05/13
 * Time: 14:59
 * To change this template use File | Settings | File Templates.
 */
(function ($) {

	"use strict";

	angular.module('RbsChange').directive('imageUploader', ['RbsChange.REST', '$q', function (REST, $q) {

		var	acceptedTypes = /image\/(gif|jpeg|png)/,
			MAX_PREVIEW_HEIGHT = 100;

		function updatePreview (scope, url) {
			var	img = new Image();
			img.onload = function () {
				scope.$apply(function () {
					scope.imageWidth  = img.width;
					scope.imageHeight = img.height;
					scope.previewHeight = Math.min(scope.imageHeight, MAX_PREVIEW_HEIGHT);
					scope.previewWidth = scope.previewHeight * (scope.imageWidth / scope.imageHeight);
					scope.imageSrc = url;
				});
			};
			img.src = url;
		}


		return {
			restrict    : 'EAC',
			require     : 'ngModel',
			templateUrl : 'Rbs/Admin/js/directives/image-uploader.twig',
			scope       : true,

			link : function (scope, elm, attrs, ngModel) {

				scope.previewWidth = MAX_PREVIEW_HEIGHT * (16/9.0);
				scope.previewHeight = MAX_PREVIEW_HEIGHT;

				ngModel.$render = function ngModelRenderFn () {
					if (angular.isObject(ngModel.$viewValue)) {
						REST.storage.info(ngModel.$viewValue.storageURI).then(function (info) {
							scope.fileSize = info.size;
							scope.fileName = info.fileName;
							updatePreview(scope, info.data);
						});
					}
				};

				var	inputFile = $(elm).find("input[type=file]"),
					reader = new FileReader();

				reader.onload = function imgOnloadFn (event) {
					scope.$apply(function () {
						scope.loading = false;
						scope.fileSize = event.total;
						scope.fileName = inputFile.get(0).files[0].name;
						// Load the image to get its dimensions.
						updatePreview(scope, event.target.result);
					});

					ngModel.$setViewValue("local:" + event.target.result);
				};

				inputFile.change(function inputChangedFn () {
					var file = inputFile.get(0).files[0];
					if (acceptedTypes.test(file.type)) {
						scope.$apply('loading=true');
						reader.readAsDataURL(file);
					} else {
						window.alert('Please select an image.');
					}
				});

				scope.choose = function chooseFn () {
					inputFile.click();
				};


				scope.upload = function uploadFn () {
					var	file = inputFile.get(0).files[0],
						q = $q.defer();

					if (ngModel.$pristine) {
						console.log("imageUploader: no changes (pristine) => q is resolved with '" + ngModel.$viewValue + "'");
						return null;
					} else if (acceptedTypes.test(file.type)) {
						console.log("imageUploader: has changes (dirty) => uploading...");
						REST.storage.upload(inputFile, attrs.storageName || 'images').then(
							function uploadSuccessFn (response) {
								ngModel.$setViewValue(response);
								ngModel.$render();
								console.log("imageUploader: uploading complete => q is resolved with '" + ngModel.$viewValue + "'");
								q.resolve(response);
							},
							function uploadErrorFn (data) {
								console.log("imageUploader: uploading failed => q is REJECTED with reason '" + data.message + "'");
								if (data.message === 'error: ') {
									data.message = "Could not upload image.";
								}
								if (!data.code) {
									data.code = "UPLOAD-ERROR";
								}
								q.reject(data);
							}
						);
					} else {
						console.log("imageUploader: invalid image file => q is REJECTED");
						q.reject('imageUploader: invalid image file.');
					}

					return q.promise;
				};

			}

		};
	}]);

})(window.jQuery);