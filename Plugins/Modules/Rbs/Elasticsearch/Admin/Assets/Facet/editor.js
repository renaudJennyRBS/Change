(function ()
{
	"use strict";

	function RbsElasticsearchFacetEditor(REST, $routeParams, Settings)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Elasticsearch/Facet/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					if (scope.document.isNew() && scope.parentDocument &&
						(scope.parentDocument.model == 'Rbs_Elasticsearch_FullText' ||
							scope.parentDocument.model == 'Rbs_Elasticsearch_StoreIndex'))
					{
						scope.document.indexId = scope.parentDocument.id;
					}
				};
				editorCtrl.init('Rbs_Elasticsearch_Facet');
			}
		};
	}
	RbsElasticsearchFacetEditor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.Settings'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsElasticsearchFacet', RbsElasticsearchFacetEditor);
})();