/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";
	var app = angular.module('RbsChange');

	function rbsMaxHeight() {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Admin/js/directives/max-height.twig',
			transclude: true,
			scope: {},
			link: function(scope, elm, attrs) {
				scope.containerNode = elm.find('.max-height-container');
				scope.contentNode = elm.find('.max-height-content');
				scope.deployed = false;
				scope.showButtons = false;
				scope.maxHeight = parseInt(attrs['rbsMaxHeight'], 10);
				if (isNaN(scope.maxHeight) || scope.maxHeight < 0) {
					scope.maxHeight = 0;
				}

				scope.toggle = function() {
					scope.deployed = !scope.deployed;
					refreshStyles();
				};

				function refreshStyles() {
					if (!scope.showButtons || scope.deployed) {
						scope.containerNode.css({ overflow: 'visible', 'max-height': "" });
					}
					else {
						scope.containerNode.css({ overflow: 'hidden', 'max-height': scope.maxHeight + 'px' });
					}
				}

				scope.showButtonsFunction = function showButtonsFunction() {
					if (!scope.maxHeight) {
						scope.showButtons = false;
					}
					else if (scope.contentNode.height() > scope.maxHeight + 20) {
						scope.showButtons = true;
					}
					else {
						scope.showButtons = false;
					}
					refreshStyles();
					return scope.showButtons;
				};
			}
		}
	}

	app.directive('rbsMaxHeight', rbsMaxHeight);
})();