/**
 * Copyright (C) 2014 Franck STAUFFER
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	function Editor() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.items)) {
						scope.document.items = [];
					}
				};
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsHighlightHighlightEdit', Editor);
})();
