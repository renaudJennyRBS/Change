(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.controller('RbsChangeSocialController', ['RbsChange.REST', '$scope', '$filter', '$routeParams', '$http',
		function (REST, $scope, $filter, $routeParams) {
			$scope.$watch('model', function (model) {
				if (model) {
					REST.resource(model, $routeParams.id, $routeParams.LCID).then(function (doc) {
						$scope.document = doc;

						REST.call(REST.getBaseUrl('Rbs/Social/GetSocialData'), {
							'documentId': doc.id
						}).then(function (data) {
							$scope.socialData = data;
						}, function (errorData) {
							console.error(errorData);
						});
					});
				}
			});
		}]);

})(window.jQuery);