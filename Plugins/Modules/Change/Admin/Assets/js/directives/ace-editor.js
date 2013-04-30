(function ($, ace) {

	"use strict";

	var app = angular.module('RbsChange');

	//=========================================================================
	//
	// ACE editor widget
	//
	//=========================================================================


	app.directive('aceEditor', [ function () {

		var aceEditorIdCounter = 0;

		return {

			restrict : 'EC',
			require  : '?ngModel',
			/*scope : {
				on
			},*/

			// Initialisation du scope (logique du composant)
			link : function (scope, element, attrs, ngModel) {
				var	$el = $(element),
					id = "chg_ace_editor_" + (++aceEditorIdCounter),
					editor, session;

				$el.attr('id', id);
				editor = ace.edit(id);
				if (attrs.theme) {
					editor.setTheme("ace/theme/" + attrs.theme);
				}

				session = editor.getSession();
				session.setMode("ace/mode/" + (attrs.mode || "html"));
				//session.setMode("ace/mode/markdown");
				session.setUseWrapMode(true);
				session.setWrapLimitRange(null, null);

				session.setFoldStyle("manual");
				editor.setShowFoldWidgets(true);

				if (angular.isDefined(attrs.gutter) && ! attrs.gutter) {
					editor.renderer.setShowGutter(false);
				}


				function heightUpdateFunction () {

					// http://stackoverflow.com/questions/11584061/
					var newHeight =
						editor.getSession().getScreenLength() * editor.renderer.lineHeight + editor.renderer.scrollBar.getWidth();

					$el.height(newHeight.toString() + "px");

					// This call is required for the editor to fix all of
					// its inner structure for adapting to a change in size
					editor.resize();
				}

				// Set initial size to match initial content
				heightUpdateFunction();

				// Whenever a change happens inside the ACE editor, update the size again
				editor.getSession().on('change', heightUpdateFunction);


				if (ngModel) {

					ngModel.$render = function() {
						editor.setValue(ngModel.$viewValue);
					};

					editor.getSession().on('change', function () {
						ngModel.$setViewValue(editor.getValue());
						if (!scope.$$phase) {
							try {
								scope.$apply();
							} catch (e) {}
						}
					});

				}

			}

		};
	}]);


})(window.jQuery, ace);