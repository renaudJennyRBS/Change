(function ($)
{
	"use strict";

	function EditorFn (Editor, REST)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Geo/TerritorialUnit/editor.twig',
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
							console.log(data);
							button.removeAttr('disabled');
						},
						function () {
							button.removeAttr('disabled');
						}
					);
				};
			}
		};
	}

	EditorFn.$inject = [
		'RbsChange.Editor',
		'RbsChange.REST'
	];
	angular.module('RbsChange').directive('editorRbsGeoTerritorialunit', EditorFn);

})(window.jQuery);