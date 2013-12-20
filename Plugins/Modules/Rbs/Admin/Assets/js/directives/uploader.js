(function ($) {

	"use strict";

	angular.module('RbsChange').directive('rbsUploader', ['RbsChange.REST', '$q', '$timeout', function (REST, $q, $timeout) {

		return {
			restrict    : 'EA',
			require     : 'ngModel',
			scope       : true,
			priority    : -1,

			link : function (scope, elm, attrs, ngModel) {

				scope.updatePreview = function updatePreviewFn (url) {
				};

				scope.acceptedTypes = /.*/;
				scope.fileAccept = attrs.fileAccept

				scope.storageName = "files";


				ngModel.$render = function ngModelRenderFn () {
					if (angular.isObject(ngModel.$viewValue)) {
						REST.storage.info(ngModel.$viewValue.storageURI).then(function (info) {
							scope.fileSize = info.size;
							scope.fileName = info.fileName;
							scope.fileType = info.mimeType;
							scope.updatePreview(info.data);
						});
					}
				};

				var	inputFile = $(elm).find("input[type=file]"),
					reader = new FileReader();

				scope.choose = function chooseFn () {
					inputFile.click();
				};

				reader.onload = function fileOnloadFn (event) {
					scope.$apply(function () {
						scope.loading = false;
						scope.fileSize = event.total;
						scope.fileName = inputFile.get(0).files[0].name;
						scope.fileType = inputFile.get(0).files[0].type;
						// Load the image to get its dimensions.
						scope.updatePreview(event.target.result);
						ngModel.$setViewValue("local:" + event.target.result);
					});
				};

				inputFile.change(function inputChangedFn () {
					var file = inputFile.get(0).files[0];
					if (scope.acceptedTypes.test(file.type)) {
						scope.$apply('loading=true');
						reader.readAsDataURL(file);
					} else {
						window.alert('Please select a valid file type.');
					}
				});

				scope.upload = function uploadFn () {
					var	file = inputFile.get(0).files[0],
						q = $q.defer();

					if (ngModel.$pristine) {
						console.log("uploader: no changes (pristine) => q is resolved with '" + ngModel.$viewValue + "'");
						return null;
					} else if (scope.acceptedTypes.test(file.type)) {
						console.log("uploader: has changes (dirty) => uploading...");
						REST.storage.upload(inputFile, attrs.storageName || scope.storageName).then(
							function uploadSuccessFn (response) {
								ngModel.$setViewValue(response);
								ngModel.$render();
								console.log("uploader: uploading complete => q is resolved with '" + ngModel.$viewValue + "'");
								q.resolve(response);
							},
							function uploadErrorFn (data) {
								console.log("uploader: uploading failed => q is REJECTED with reason: ", data);
								q.reject(data);
							}
						);
					} else {
						console.log("uploader: invalid file type => q is REJECTED");
						q.reject('uploader: invalid file type.');
					}

					return q.promise;
				};

			}

		};
	}]);

})(window.jQuery);