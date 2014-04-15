/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ()
{
	"use strict";

	function Editor()
	{
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link : function (scope, elm, attrs) {

				scope.disabled = true;

				scope.isLocked = function() {
					return scope.disabled;
				}

				scope.askUnlock = function() {
					scope.disabled = !scope.disabled;
				}

			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsStockInventoryEntryEdit', Editor);

})();