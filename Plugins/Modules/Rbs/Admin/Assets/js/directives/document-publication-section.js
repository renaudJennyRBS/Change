/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-document-publication-section
	 * @restrict A
	 *
	 * @description
	 * Used to display the <em>Publication</em> section in Document editors.
	 *
	 * @example
	 * <pre>
	 *     <fieldset data-rbs-editor-section="publication"
	 *        data-editor-section-label="{{ i18nAttr('m.rbs.admin.admin.publication_properties', ['ucf']) }}"
	 *        data-rbs-document-publication-section="">
	 *     </fieldset>
	 * </pre>
	 */
	angular.module('RbsChange').directive('rbsDocumentPublicationSection', function ()
	{
		return {
			restrict    : 'A',
			templateUrl : 'Rbs/Admin/js/directives/document-publication-section.twig',
			replace     : false,

			link : function (scope, iElement, iAttrs)
			{
				scope.hasSpecificHelp = false;

				if (iAttrs.rbsDocumentPublicationSectionHelp != undefined && iAttrs.rbsDocumentPublicationSectionHelp != "")
				{
					scope.hasSpecificHelp = true;
					scope.specificHelp = iAttrs.rbsDocumentPublicationSectionHelp;
				}
			}
		};
	});

})();