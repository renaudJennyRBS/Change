(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Elasticsearch')
				.route('home', 'Rbs/Elasticsearch', { 'redirectTo': 'Rbs/Elasticsearch/FullText/'});

			$delegate.routesForModels([
				'Rbs_Elasticsearch_FullText', 'Rbs_Elasticsearch_StoreIndex'
			]);

			$delegate.routesForLocalizedModels([
				'Rbs_Elasticsearch_FacetGroup', 'Rbs_Elasticsearch_Facet'
			]);

			return $delegate;
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Elasticsearch_FullText');
	__change.createEditorsForLocalizedModel('Rbs_Elasticsearch_FacetGroup');
})();