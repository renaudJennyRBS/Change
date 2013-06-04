(function () {

	angular.module('RbsChange').service('RbsChange.Modules', function () {

		this.models = {
			'modules_website/page': "Page",
			'modules_website/website': "Site",
			'modules_website/topic': "Rubrique",
			'modules_catalog/shop': "Boutique",
			'modules_catalog/product': "Produit",
			'modules_catalog/zone': "Zone de facturation",
			'modules_catalog/shelf': "Rayon",
			'modules_catalog/price': "Prix",
			'modules_media/media': "Media",
			'modules_news/news': "Actualité",
			'modules_news/event': "Evénement",
			'modules_order/order': "Commande",
			'modules_contact/contact': "Contact"
		};

		this.modules = {
			'website': "Sites et pages",
			'catalog': "Catalogue et produits",
			'media': "Médiathèque",
			'order': "Commandes",
			'news': "Actualités et événements",
			'contact': "Contacts"
		};

	});

})();