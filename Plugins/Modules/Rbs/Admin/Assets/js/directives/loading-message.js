(function () {

	var app = angular.module('RbsChange');

	/**
	 * @name loadingMessage
	 */
	app.directive('loadingMessage', function () {

		return {
			restrict: 'EA',
			replace: true,
			template: '<div class="rbsc-loading-indicator">Chargement en cours...<br/><small>Merci de bien vouloir patienter quelques instants :)</small></div>'
		};

	});

})();
