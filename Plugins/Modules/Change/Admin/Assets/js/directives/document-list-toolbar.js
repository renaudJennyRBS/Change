(function ($) {
	var app = angular.module('RbsChange');

	app.directive('documentListToolbar', ['$timeout', function ($timeout) {

		var counter = 0;

		return {
			// Utilisation : <document-list-toolbar/>
			restrict: 'E',

			// URL du template HTML
			templateUrl: 'Change/Admin/js/directives/document-list-toolbar.html',

			replace: true,

			// Initialisation du scope (logique du composant)
			link: function (scope, element, attrs) {
				scope.thumbnailsViewEnabled = attrs.thumbnails;
				scope.embeddedActionsOptionsContainerId = 'EAOC_' + (++counter);
			}

		};
	}]);
})(window.jQuery);