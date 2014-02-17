(function () {
	"use strict";

	var app = angular.module('RbsChange');

	app.controller('Rbs_Seo_DocumentSeoAsideController', ['$scope', 'RbsChange.REST', '$location', function ($scope, REST, $location)
	{
		$scope.seoCreate = function ()
		{
			$scope.seoCreating = true;
			REST.call($scope.document.getActionUrl('addSeo'), null, REST.resourceTransformer()).then(function (seoDocument)
			{
				$scope.seoCreating = false;
				$scope.seoDocument = seoDocument;
				$location.path(seoDocument.url());
			});
		};
	}]);
})();