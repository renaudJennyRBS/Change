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

		return {
			restrict    : 'EAC',
			require     : 'ngModel',
			templateUrl : 'Change/Admin/js/directives/image-uploader.twig',
			scope       : true,

			link : function (scope, elm, attrs, ngModel) {

				scope.previewWidth = MAX_PREVIEW_HEIGHT * (16/9.0);
				scope.previewHeight = MAX_PREVIEW_HEIGHT;

				ngModel.$render = function () {
					console.log("render: viewValue=", ngModel.$viewValue);
					if (ngModel.$viewValue) {
						console.log("storage src=", REST.storage.getUrl(ngModel.$viewValue));
						scope.imageSrc = REST.storage.getUrl(ngModel.$viewValue);
					}
				};

				var	inputFile = $(elm).find("input[type=file]"),
					reader = new FileReader();

				reader.onload = function (event) {
					scope.$apply(function () {
						scope.loading = false;
						scope.fileSize = event.total;
						scope.fileName = inputFile.get(0).files[0].name;

						// Load the image to get its dimensions.
						var img = new Image();
						img.onload = function () {
							scope.$apply(function () {
								scope.imageWidth  = img.width;
								scope.imageHeight = img.height;
								scope.previewHeight = Math.min(scope.imageHeight, MAX_PREVIEW_HEIGHT);
								scope.previewWidth = scope.previewHeight * (scope.imageWidth / scope.imageHeight);
								scope.imageSrc = event.target.result;
							});
						};
						img.src = event.target.result;
					});
				};

				inputFile.change(function () {
					scope.$apply('loading=true');
					reader.readAsDataURL(inputFile.get(0).files[0]);
				});

				scope.choose = function () {
					inputFile.click();
				};

				scope.upload = function ($event) {
					var button = $($event.target),
						file = inputFile.get(0).files[0];

					if ( ! acceptedTypes.test(file.type) ) {
						window.alert('Please select an image.');
					} else {
						button.attr('disabled', 'disabled');
						REST.storage.upload(inputFile).then(
							function (response) {
								console.log("response=", response);
								ngModel.$setViewValue(response.path);
								ngModel.$render();
								button.removeAttr('disabled');
							},
							function (data) {
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