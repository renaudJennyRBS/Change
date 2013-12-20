(function ($, ace) {

	"use strict";

	var app = angular.module('RbsChange');

	//=========================================================================
	//
	// ACE editor widget
	//
	//=========================================================================


	app.directive('rbsAceEditor', ['$timeout', function ($timeout) {

		var aceEditorIdCounter = 0;

		return {

			restrict : 'E',
			require  : '?ngModel',

			link : function (scope, element, attrs, ngModel) {

				var editor, session, id = "chg_ace_editor_" + (++aceEditorIdCounter);
				element.html('<div id="'+id+'"></div>');

				editor = ace.edit(id);

				if (attrs.theme) {
					editor.setTheme("ace/theme/" + attrs.theme);
				}

				session = editor.getSession();
				session.setMode("ace/mode/" + (attrs.mode || "html"));
				session.setUseWrapMode(true);
				session.setWrapLimitRange(null, null);

				session.setFoldStyle("manual");
				editor.setShowFoldWidgets(true);

				if (angular.isDefined(attrs.gutter) && ! attrs.gutter) {
					editor.renderer.setShowGutter(false);
				}

				attrs.$observe('autoHeight', function (autoHeight) {
					if (autoHeight) {
						heightUpdateFunction(id, editor);
						editor.getSession().on('change', function () {
							heightUpdateFunction(id, editor);
						});
					}
				});

				if (ngModel) {

					ngModel.$render = function() {
						if (angular.isString(ngModel.$viewValue)) {
							editor.setValue(ngModel.$viewValue);
						}
						else {
							editor.setValue(JSON.stringify(ngModel.$viewValue));
						}
					};

					session.on('change', function () {
						$timeout(function () {
							if (angular.isString(ngModel.$viewValue)) {
								ngModel.$setViewValue(editor.getValue());
							}
							else {
								ngModel.$setViewValue(JSON.parse(editor.getValue()));
							}
						});
					});

				}

				function heightUpdateFunction (id, editor) {

					// http://stackoverflow.com/questions/11584061/
					var newHeight =
						editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();

					$('#'+id).height(newHeight.toString() + "px");

					// This call is required for the editor to fix all of
					// its inner structure for adapting to a change in size
					editor.resize();
				}

			}

		};
	}]);


})(window.jQuery, ace);