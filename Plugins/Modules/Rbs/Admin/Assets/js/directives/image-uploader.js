/**
 * Created with JetBrains PhpStorm.
 * User: fredericbonjour
 * Date: 24/05/13
 * Time: 14:59
 * To change this template use File | Settings | File Templates.
 */
(function ($) {

	"use strict";

	angular.module('RbsChange').directive('imageUploader', ['RbsChange.REST', function (REST) {

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
					if (ngModel.$viewValue) {
						REST.storage.info(ngModel.$viewValue).then(function (info) {
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
				};

				inputFile.change(function inputChangedFn () {
					scope.$apply('loading=true');
					reader.readAsDataURL(inputFile.get(0).files[0]);
				});

				scope.choose = function chooseFn () {
					inputFile.click();
				};

				scope.upload = function uploadFn ($event) {
					var	button = $($event.target),
						file = inputFile.get(0).files[0];

					if ( ! acceptedTypes.test(file.type) ) {
						window.alert('Please select an image.');
					} else {
						button.attr('disabled', 'disabled');
						REST.storage.upload(inputFile).then(
							function uploadSuccessFn (response) {
								ngModel.$setViewValue(response.path);
								ngModel.$render();
								button.removeAttr('disabled');
							},
							function uploadErrorFn (data) {
								window.alert(data.message);
								button.removeAttr('disabled');
							}
						);
					}
				};

			}

		};
	}]);

})(window.jQuery);