(function ($) {

	"use strict";

	function rbsMediaImageEditor (REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Media/Image/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				editorCtrl.init('Rbs_Media_Image');

				scope.upload = function ($event) {
					var button = $($event.target);
					button.attr('disabled', 'disabled');
					REST.upload(element.find('#file')).then(
						function () {
							button.removeAttr('disabled');
						},
						function () {
							button.removeAttr('disabled');
						}
					);
				};

				scope.preSave = function () {

				};

				scope.$watch('document.path', function (path) {
					if (path && ! scope.document.label) {
						var fileName = angular.element(element.find('.image-uploader').first()).scope().fileName;
						scope.document.label = fileName.replace(/(\.png|\.gif|\.jpg|\.jpeg)$/i, '');
					}
				});
			}
		};
	}

	rbsMediaImageEditor.$inject = [ 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMediaImage', rbsMediaImageEditor);

})(window.jQuery);