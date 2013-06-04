describe('REST', function () {

	var REST, $httpBackend, $rootScope, HOST = 'http://server/rest.php/';

	afterEach(function () {
		$httpBackend.verifyNoOutstandingExpectation();
		$httpBackend.verifyNoOutstandingRequest();
	});

	beforeEach(module('RbsChange'));



	beforeEach(inject(['$httpBackend', '$rootScope', 'RbsChange.REST', function ($httpBck, $rootScp, RbsChangeREST) {
		$httpBackend = $httpBck;
		$rootScope = $rootScp;
		REST = RbsChangeREST;

		$httpBackend.when('GET', HOST + 'resources/100005').respond(
			'{"properties":{"id":100005,"model":"Rbs_Website_Topic","pathPart":null,"indexPage":null,"allowedTemplateNames":null,"creationDate":"2013-03-25T15:43:21+0000","modificationDate":"2013-03-25T15:43:21+0000","refLCID":"fr_FR","LCID":"fr_FR","publicationStatus":"DRAFT","startPublication":null,"endPublication":null,"treeName":"Rbs_Website","website":{"id":100004,"model":"Rbs_Website_Website","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Website\/100004\/fr_FR","hreflang":"fr_FR"}},"label":"Modules standards","authorName":"Anonymous","authorId":null,"documentVersion":0},"links":[{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Topic\/100005\/fr_FR","hreflang":"fr_FR"},{"rel":"node","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005"}],"actions":[{"rel":"startValidation","href":"http:\/\/server\/rest.php\/resourcesactions\/startValidation\/100005\/fr_FR"}],"i18n":{"fr_FR":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Topic\/100005\/fr_FR"}}'
			//'{"properties":{"id":100008,"model":"Rbs_Website_Topic","pathPart":null,"indexPage":null,"allowedTemplateNames":null,"creationDate":"2013-04-30T14:04:44+0000","modificationDate":"2013-04-30T14:04:44+0000","refLCID":"fr_FR","LCID":"fr_FR","publicationStatus":"DRAFT","startPublication":null,"endPublication":null,"treeName":"Rbs_Website","website":{"id":100005,"model":"Rbs_Website_Website","link":{"rel":"self","href":"http:\/\/change4dev.rbs.fr\/rest.php\/resources\/Rbs\/Website\/Website\/100005\/fr_FR","hreflang":"fr_FR"},"label":"Site par d\u00e9faut"},"label":"Produits","authorName":"Anonymous","authorId":null,"documentVersion":0},"links":[{"rel":"self","href":"http:\/\/change4dev.rbs.fr\/rest.php\/resources\/Rbs\/Website\/Topic\/100008\/fr_FR","hreflang":"fr_FR"},{"rel":"model","href":"http:\/\/change4dev.rbs.fr\/rest.php\/models\/Rbs\/Website\/Topic"},{"rel":"node","href":"http:\/\/change4dev.rbs.fr\/rest.php\/resourcestree\/Rbs\/Website\/100004\/100005\/100008"}],"actions":[{"rel":"startValidation","href":"http:\/\/change4dev.rbs.fr\/rest.php\/resourcesactions\/startValidation\/100008\/fr_FR"}],"i18n":{"fr_FR":"http:\/\/change4dev.rbs.fr\/rest.php\/resources\/Rbs\/Website\/Topic\/100008\/fr_FR"}}'
		);

		$httpBackend.when('GET', HOST + 'resources/Rbs/Website/Website/').respond(
			'{"pagination":{"count":1,"offset":0,"limit":10,"sort":"id","desc":false},"resources":[{"id":100004,"model":"Rbs_Website_Website","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Website\/100004\/fr_FR","hreflang":"fr_FR"},"creationDate":"2013-03-25T14:08:02+0000","modificationDate":"2013-03-25T14:08:02+0000","label":"Site 1","documentVersion":0,"publicationStatus":"DRAFT","refLCID":"fr_FR","LCID":"fr_FR"}],"links":[{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Website\/?limit=10&offset=0&sort=id&desc=false"}]}'
		);

		$httpBackend.when('GET', HOST + 'resources/Rbs/Website/StaticPage/100013/fr_FR').respond(
			'{"properties":{"id":100013,"model":"Rbs_Website_StaticPage","website":{"id":100004,"model":"Rbs_Website_Website","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Website\/100004\/fr_FR","hreflang":"fr_FR"}},"templateName":"defaultTpl","editableContent":null,"creationDate":"2013-03-26T09:53:22+0000","modificationDate":"2013-03-26T16:09:38+0000","refLCID":"fr_FR","LCID":"fr_FR","publicationStatus":"PUBLISHABLE","startPublication":null,"endPublication":null,"versionOfId":null,"label":"CMS","pathPart":null,"navigationTitle":"CMS","metaTitle":null,"description":null,"keywords":null,"robotsMeta":"index,follow","authorName":"Anonymous","authorId":null,"documentVersion":3,"treeName":"Rbs_Website"},"links":[{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/StaticPage\/100013\/fr_FR","hreflang":"fr_FR"},{"rel":"node","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005\/100013"}],"actions":[{"rel":"getCorrection","href":"http:\/\/server\/rest.php\/resourcesactions\/getCorrection\/100013\/fr_FR"},{"rel":"startCorrectionValidation","href":"http:\/\/server\/rest.php\/resourcesactions\/startCorrectionValidation\/100013\/fr_FR"},{"rel":"deactivate","href":"http:\/\/server\/rest.php\/resourcesactions\/deactivate\/100013\/fr_FR"}],"i18n":{"fr_FR":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/StaticPage\/100013\/fr_FR"}}'
		);

		$httpBackend.when('GET', HOST + 'resourcesactions/getCorrection/100013/fr_FR').respond(
			'{"properties":{"id":100013,"model":"Rbs_Website_StaticPage","website":{"id":100004,"model":"Rbs_Website_Website","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Website\/100004\/fr_FR","hreflang":"fr_FR"}},"templateName":"defaultTpl","editableContent":null,"creationDate":"2013-03-26T09:53:22+0000","modificationDate":"2013-03-26T16:09:38+0000","refLCID":"fr_FR","LCID":"fr_FR","publicationStatus":"PUBLISHABLE","startPublication":null,"endPublication":null,"versionOfId":null,"label":"CMS","pathPart":null,"navigationTitle":"Gestion de contenu","metaTitle":null,"description":null,"keywords":null,"robotsMeta":"index,follow","authorName":"Anonymous","authorId":null,"documentVersion":3,"treeName":"Rbs_Website"},"links":[{"rel":"resource","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/StaticPage\/100013\/fr_FR","hreflang":"fr_FR"}],"correction":{"id":1,"status":"DRAFT","propertiesNames":["navigationTitle"],"creationDate":"2013-03-26T16:09:38+0000","publicationDate":null}}'
		);

		$httpBackend.when('GET', HOST + 'resourcestree/Rbs/Website/100003').respond(
			'{"properties":{"id":100003,"childrenCount":1,"level":0,"nodeOrder":0,"document":{"id":100003,"model":"Rbs_Generic_Folder","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Generic\/Folder\/100003"},"creationDate":"2013-03-25T14:06:49+0000","modificationDate":"2013-03-25T14:06:49+0000","label":"Website root","documentVersion":0}},"links":[{"rel":"self","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003"},{"rel":"children","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/"}]}'
		);

		$httpBackend.when('GET', HOST + 'resourcestree/Rbs/Website/100003/100004/100005').respond(
			'{"properties":{"id":100005,"childrenCount":2,"level":2,"nodeOrder":0,"document":{"id":100005,"model":"Rbs_Website_Topic","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Topic\/100005\/fr_FR","hreflang":"fr_FR"},"creationDate":"2013-03-25T15:43:21+0000","modificationDate":"2013-03-25T15:43:21+0000","label":"Modules standards","documentVersion":0,"publicationStatus":"DRAFT","refLCID":"fr_FR","LCID":"fr_FR"}},"links":[{"rel":"self","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005"},{"rel":"children","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005\/"},{"rel":"parent","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004"}]}'
		);

		$httpBackend.when('GET', HOST + 'resourcestree/Rbs/Website/100003/100004/100005/').respond(
			'{"pagination":{"count":2,"offset":0,"limit":10,"sort":"nodeOrder","desc":false},"resources":[{"id":100013,"childrenCount":0,"level":3,"nodeOrder":0,"link":{"rel":"self","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005\/100013"},"document":{"id":100013,"model":"Rbs_Website_StaticPage","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/StaticPage\/100013\/fr_FR","hreflang":"fr_FR"},"creationDate":"2013-03-26T09:53:22+0000","modificationDate":"2013-03-26T16:09:38+0000","label":"CMS","documentVersion":3,"publicationStatus":"PUBLISHABLE","refLCID":"fr_FR","LCID":"fr_FR","actions":[{"rel":"getCorrection","href":"http:\/\/server\/rest.php\/resourcesactions\/getCorrection\/100013\/fr_FR"}]}},{"id":100014,"childrenCount":0,"level":3,"nodeOrder":1,"link":{"rel":"self","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005\/100014"},"document":{"id":100014,"model":"Rbs_Website_Topic","link":{"rel":"self","href":"http:\/\/server\/rest.php\/resources\/Rbs\/Website\/Topic\/100014\/fr_FR","hreflang":"fr_FR"},"creationDate":"2013-03-26T09:56:07+0000","modificationDate":"2013-03-26T09:56:07+0000","label":"Modules communautaires","documentVersion":0,"publicationStatus":"DRAFT","refLCID":"fr_FR","LCID":"fr_FR"}}],"links":[{"rel":"self","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005\/?limit=10&offset=0"},{"rel":"parent","href":"http:\/\/server\/rest.php\/resourcestree\/Rbs\/Website\/100003\/100004\/100005"}]}'
		);
	}]));


	/**
	 * Document resources.
	 */

	it("should fetch a Document from the REST server and build a ChangeDocument object.", function () {

		REST.resource(100005).then(function (doc) {
			expect(doc.META$).toBeDefined();
			expect(doc.META$.locales.length).toBe(1);
			expect(doc.META$.correction).toBeNull();
			expect(doc.id).toBe(100005);
		});

		$httpBackend.flush();

	});


	it("should fetch a Document with a Correction from the REST server and build a ChangeDocument object.", function () {

		REST.resource("Rbs_Website_StaticPage", 100013, "fr_FR").then(function (doc) {
			expect(doc.META$).toBeDefined();
			expect(doc.META$.correction).toBeDefined();

			expect(doc.META$.correction.original.navigationTitle).toBe("CMS");
			expect(doc.navigationTitle).toBe("Gestion de contenu");
		});

		$httpBackend.flush();

	});


	/**
	 * Collection resources.
	 */

	it("should fetch a Collection from the REST server and build an Array of ChangeDocument objects.", function () {

		REST.collection("Rbs_Website_Website").then(function (data) {
			expect(data.pagination).toBeDefined();
			expect(data.resources).toBeDefined();
			expect(data.resources.length).toBe(1);
			expect(data.resources[0].id).toBe(100004);
			expect(data.resources[0].model).toBe('Rbs_Website_Website');
			expect(data.resources[0].META$).toBeDefined();
		});

		//$httpBackend.flush();

	});


	/**
	 * Tree nodes resources.
	 */

	it("should fetch a Tree Node from the REST server and build a ChangeDocument object.", function () {

		// Load a Topic resource.
		var topic = null;
		REST.resource(100005).then(function (doc) {
			topic = doc;
		});
		$httpBackend.flush();
		expect(topic).not.toBeNull();

		REST.treeNode(topic).then(function (doc) {
			expect(doc.META$).toBeDefined();
			expect(doc.META$.correction).toBeDefined();

			expect(doc.id).toBe(100005);
			expect(doc.model).toBe('Rbs_Website_Topic');

			expect(doc.META$.treeNode).toBeDefined();
			expect(doc.META$.treeNode.url).toBe('http://server/rest.php/resourcestree/Rbs/Website/100003/100004/100005');
			expect(doc.META$.treeNode.parentUrl).toBe('http://server/rest.php/resourcestree/Rbs/Website/100003/100004');
			expect(doc.META$.treeNode.childrenUrl).toBe('http://server/rest.php/resourcestree/Rbs/Website/100003/100004/100005/');
		});

		//$httpBackend.flush();

	});


	it("should fetch the Tree Children from the REST server and build an Array of ChangeDocument objects.", function () {

		// Load a Topic resource.
		var topic = null;
		REST.resource(100005).then(function (doc) {
			topic = doc;
		});
		$httpBackend.flush();
		expect(topic).not.toBeNull();

		REST.treeChildren(topic).then(function (data) {
			expect(data.pagination).toBeDefined();
			expect(data.pagination['count']).toBe(2);
			expect(data.resources.length).toBe(2);

			expect(data.resources[0].id).toBe(100013);
			expect(data.resources[0].model).toBe('Rbs_Website_StaticPage');
			expect(data.resources[0].META$).toBeDefined();
			expect(data.resources[0].META$.correction).not.toBeNull();

			expect(data.resources[1].id).toBe(100014);
			expect(data.resources[1].model).toBe('Rbs_Website_Topic');
			expect(data.resources[1].META$).toBeDefined();
			expect(data.resources[1].META$.correction).toBeNull();
		});

		//$httpBackend.flush();

	});


});