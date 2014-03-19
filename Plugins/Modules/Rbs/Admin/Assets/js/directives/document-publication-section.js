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
	 * @id RbsChange.directive:rbsDocumentPublicationSection
	 * @name Document publication editor panel
	 * @element fieldset
	 * @restrict A
	 *
	 * @description
	 * Used to display the <em>Publication</em> section in Document editors.
	 *
	 * @param {String=} rbs-document-publication-section-help
	 *
	 * @example
	 * <pre>
	 *     <fieldset data-rbs-editor-section="publication"
	 *        data-editor-section-label="{{ i18nAttr('m.rbs.admin.admin.publication_properties', ['ucf']) }}"
	 *        data-rbs-document-publication-section="">
	 *     </fieldset>
	 * </pre>
	 */
	angular.module('RbsChange').directive('rbsDocumentPublicationSection', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Admin/js/directives/document-publication-section.twig',
			replace: false,
			scope: true,

			link: function(scope, iElement, iAttributes) {
				scope.hasSpecificHelp = false;

				if (iAttributes['rbsDocumentPublicationSectionHelp'] != undefined
					&& iAttributes['rbsDocumentPublicationSectionHelp'] != "") {
					scope.hasSpecificHelp = true;
					scope.specificHelp = iAttributes['rbsDocumentPublicationSectionHelp'];
				}

				scope.showPublicationSections = function() {
					if (!scope.document) {
						return false;
					}
					else if (!scope.modelInfo || !('publicationSections' in scope.modelInfo.properties)) {
						return false;
					}
					else if (scope.currentLCID && scope.currentLCID !== scope.document.refLCID) {
						return false;
					}
					return true;
				}
			}
		};
	}]);
})();