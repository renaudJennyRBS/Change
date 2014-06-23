/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsDocumentSystemInfoSection
	 * @name Document information panel
	 * @element fieldset
	 * @restrict A
	 *
	 * @description
	 * Used to display the <em>Info</em> section in Document editors.
	 *
	 * @example
	 * <pre>
	 *     <fieldset data-rbs-editor-section="systeminfo"
	 *        data-editor-section-label="Information"
	 *        data-rbs-document-system-info-section="">
	 *     </fieldset>
	 * </pre>
	 */
	angular.module('RbsChange').directive('rbsDocumentSystemInfoSection', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Admin/js/directives/document-system-info-section.twig',
			replace: false,

			link: function(scope) {
				REST.getAvailableLanguages().then(function(langs) {
					scope.availableLanguages = langs.items;
				});
			}
		};
	}]);
})();