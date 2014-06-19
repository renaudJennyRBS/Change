(function($) {
	"use strict";

	function rbsDocumentEditorRbsMediaVideo(REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.$watch('document.path', function(path) {
					if (path && !scope.document.label) {
						var fileName = angular.element(element.find('[rbs-video-uploader]').first()).scope().fileName;
						scope.document.label = fileName.replace(/(\.ogg|\.mp4|\.webm)$/i, '');
					}
				});
			}
		};
	}

	rbsDocumentEditorRbsMediaVideo.$inject = [ 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaVideoNew', rbsDocumentEditorRbsMediaVideo);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaVideoEdit', rbsDocumentEditorRbsMediaVideo);

})(window.jQuery);