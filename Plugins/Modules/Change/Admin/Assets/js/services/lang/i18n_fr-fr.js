(function () {

	var app = angular.module('RbsChange');

	app.provider('RbsChange.i18nStrings', function RbsChangeStringsProvider() {
		this.$get = function () {
			return {
				'm.order.actions.ValidatePayment': "Valider le paiement",
				'm.generic.EditSelectedDocumentProperties' : "Modifier les propriétés du document sélectionné ({{document.label}})"
			};
		};
	});

	app.provider('RbsChange.Locales', function RbsChangeStringsProvider() {
		this.$get = function () {
			return {
				'fr_FR' : "Français (France)",
				'fr_CA' : "Français (Canada)",
		
				'en_US' : "Anglais (États-Unis)",
				'en_GB' : "Anglais (Royaume-Uni)",
		
				'de_DE' : "Allemand",
				'it_IT' : "Italien",
				'es_ES' : "Espagnol"
			};
		};
	});

})();