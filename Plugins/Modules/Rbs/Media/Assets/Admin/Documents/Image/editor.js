(function($) {
	"use strict";

	function rbsDocumentEditorRbsMediaImage(REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.upload = function($event) {
					var button = $($event.target);
					button.attr('disabled', 'disabled');
					REST.upload(element.find('#file')).then(
						function() {
							button.removeAttr('disabled');
						},
						function() {
							button.removeAttr('disabled');
						}
					);
				};

				scope.$watch('document.path', function(path) {
					if (path && !scope.document.label) {
						var fileName = angular.element(element.find('[rbs-image-uploader]').first()).scope().fileName;
						scope.document.label = fileName.replace(/(\.png|\.gif|\.jpg|\.jpeg)$/i, '');
					}
				});
			}
		};
	}

	rbsDocumentEditorRbsMediaImage.$inject = [ 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaImageNew', rbsDocumentEditorRbsMediaImage);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaImageEdit', rbsDocumentEditorRbsMediaImage);

})(window.jQuery);