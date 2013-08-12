(function () {

	function editorChangeCatalogProductCategorization(Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Catalog/ProductCategorization/editor.twig',

			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm, attrs) {
				Editor.initScope(scope, elm, function(){
					console.log(scope);
				});
			}
		};

	}

	editorChangeCatalogProductCategorization.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeCatalogProductCategorization', editorChangeCatalogProductCategorization);

})();