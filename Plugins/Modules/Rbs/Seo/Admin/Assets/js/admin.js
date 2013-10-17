(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Seo_ModelConfiguration');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Seo').route('home', 'Rbs/Seo', { 'redirectTo': 'Rbs/Seo/DocumentSeo/'});
			$delegate.routesForModels(['Rbs_Seo_ModelConfiguration']);
			$delegate.routesForLocalizedModels(['Rbs_Seo_DocumentSeo']);
			return $delegate;
		}]);
	}]);
})();