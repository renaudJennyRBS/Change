(function () {
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Seo').route('home', 'Rbs/Seo', { 'redirectTo': 'Rbs/Seo/DocumentSeo/'});
			$delegate.routesForLocalizedModels(['Rbs_Seo_DocumentSeo']);
			return $delegate;
		}]);
	}]);
})();