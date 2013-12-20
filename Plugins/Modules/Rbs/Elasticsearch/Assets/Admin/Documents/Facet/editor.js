(function ()
{
	"use strict";

	function RbsElasticsearchFacetEditor(REST, $routeParams, Settings, Navigation)
	{
		return {
			restrict: 'EA',
			templateUrl: 'Document/Rbs/Elasticsearch/Facet/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					var navCtx = Navigation.getCurrentContext();
					if (scope.document.isNew() && navCtx && navCtx.parentDocument &&
						(navCtx.parentDocument.model === 'Rbs_Elasticsearch_FullText' ||
							navCtx.parentDocument.model === 'Rbs_Elasticsearch_StoreIndex'))
					{
						scope.document.indexId = navCtx.parentDocument.id;
					}
				};
				editorCtrl.init('Rbs_Elasticsearch_Facet');
			}
		};
	}
	RbsElasticsearchFacetEditor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.Settings', 'RbsChange.Navigation'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsElasticsearchFacet', RbsElasticsearchFacetEditor);
})();