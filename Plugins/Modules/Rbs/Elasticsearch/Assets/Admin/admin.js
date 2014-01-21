(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Elasticsearch', 'Rbs/Elasticsearch', { 'redirectTo': 'Rbs/Elasticsearch/FullText/'});

			$delegate.routesForModels([
				'Rbs_Elasticsearch_FullText', 'Rbs_Elasticsearch_StoreIndex'
			]);

			$delegate.routesForLocalizedModels([
				'Rbs_Elasticsearch_FacetGroup', 'Rbs_Elasticsearch_Facet'
			]);
			$delegate.model("Rbs_Elasticsearch_StoreIndex").route('list', '/Rbs/Elasticsearch/FullText/', {'templateUrl':'Document/Rbs/Elasticsearch/FullText/list.twig'});

			return $delegate.module(null);
		}]);
	}]);

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Elasticsearch_FullText');
	__change.createEditorForModel('Rbs_Elasticsearch_StoreIndex');

	__change.createEditorsForLocalizedModel('Rbs_Elasticsearch_FacetGroup');
	__change.createEditorsForLocalizedModel('Rbs_Elasticsearch_Facet');

})();