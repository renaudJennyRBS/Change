(function($) {
	"use strict";

	function rbsDocumentEditorRbsMediaFile(REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.$watch('document.path', function(path) {
					if (path && !scope.document.label) {
						var fileName = angular.element(element.find('[rbs-file-uploader]').first()).scope().fileName;
						var reg = new RegExp("[.]+", "g");
						var array = fileName.split(reg);
						array.pop();
						scope.document.label = array.join('.');
					}
				});
			}
		};
	}

	rbsDocumentEditorRbsMediaFile.$inject = [ 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaFileNew', rbsDocumentEditorRbsMediaFile);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaFileEdit', rbsDocumentEditorRbsMediaFile);

})(window.jQuery);