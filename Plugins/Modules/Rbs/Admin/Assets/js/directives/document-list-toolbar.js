(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('documentListToolbar', function () {

		var counter = 0;

		return {
			restrict    : 'E',
			replace     : true,
			templateUrl : 'Rbs/Admin/js/directives/document-list-toolbar.twig',


			// Initialisation du scope (logique du composant)
			link : function documentListToolbarLinkFn (scope, element, attrs) {
				scope.thumbnailsViewEnabled = attrs.thumbnails;
				scope.embeddedActionsOptionsContainerId = 'EAOC_' + (++counter);
			}

		};
	});

})();