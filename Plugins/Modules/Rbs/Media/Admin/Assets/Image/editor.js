(function ($)
{
	"use strict";

	function EditorFn (Editor, REST)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Media/Image/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm);

				scope.upload = function ($event) {
					var button = $($event.target);
					button.attr('disabled', 'disabled');
					REST.upload(elm.find('#file')).then(
						function (data) {
							button.removeAttr('disabled');
						},
						function () {
							button.removeAttr('disabled');
						}
					);
				};

				scope.$watch('document.path', function (path) {
					if (path && ! scope.document.label) {
						var fileName = angular.element(elm.find('.image-uploader').first()).scope().fileName;
						scope.document.label = fileName.replace(/(\.png|\.gif|\.jpg|\.jpeg)$/i, '');
					}
				});
			}
		};
	}

	EditorFn.$inject = [
		'RbsChange.Editor',
		'RbsChange.REST'
	];
	angular.module('RbsChange').directive('editorRbsMediaImage', EditorFn);

})(window.jQuery);