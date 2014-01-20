(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModelTranslation('Rbs_Seo_ModelConfiguration');
	__change.createEditorForModelTranslation('Rbs_Seo_DocumentSeo');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Seo', 'Rbs/Seo', { 'redirectTo': 'Rbs/Seo/DocumentSeo/'})
				.route('sitemapConfiguration', 'Rbs/Seo/Sitemap', 'Document/Rbs/Seo/SitemapConfiguration/list.twig');
			$delegate.routesForLocalizedModels(['Rbs_Seo_DocumentSeo', 'Rbs_Seo_ModelConfiguration']);
			return $delegate.module(null);
		}]);
	}]);


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