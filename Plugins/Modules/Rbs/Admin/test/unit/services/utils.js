describe('Utils', function () {

	var Utils;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['RbsChange.Utils', function (RbsChangeUtils) {
		Utils = RbsChangeUtils;
	}]));


	it("should return the name of the function's parameters", function () {
		function testFn ($param1, arg2, loveUnitTests) {};
		expect(Utils.getFunctionParamNames(testFn)).toEqual(['$param1', 'arg2', 'loveUnitTests']);
	});


	it("should return values from an object as an array in a given order", function () {

		var me = {
			    'name' : "Fred",
			'yearsOld' : 33,
			    'pets' : {"Yoshi": "cat"},
			     'job' : "Web developper/designer"
		};

		var order = ['yearsOld', 'pets', 'job'];

		expect(Utils.objectValues(me, order)).toEqual([ 33, {"Yoshi": "cat"}, "Web developper/designer" ]);
		expect(Utils.objectValues(me)).toEqual([ "Fred", 33, {"Yoshi": "cat"}, "Web developper/designer" ]);


		order = ['yearsOld', 'pets', 'country', 'job', 'language'];
		expect(Utils.objectValues(me, order)).toEqual([ 33, {"Yoshi": "cat"}, null, "Web developper/designer", null ]);

		var e = undefined;
		expect(Utils.objectValues(e, ['param1', 'param2'])).toEqual([]);


	});


	it("should tell if a document's status is one of those given as parameters", function () {

		var doc = {
			"publicationStatus": "PUBLISHABLE"
		};

		expect(Utils.hasStatus(doc, "PUBLISHABLE")).toBeTruthy();
		expect(Utils.hasStatus(doc, "ACTIVE")).toBeFalsy();
		expect(Utils.hasStatus(doc, "ACTIVE", "PUBLISHABLE")).toBeTruthy();
		expect(Utils.hasStatus(doc, "DEACTIVATED", "CORRECTION")).toBeFalsy();

	});


	it("should tell if a document's model is one of those given as parameters", function () {

		var page = {
			"model": "change_website_page"
		};

		expect(Utils.isModel(page, "change_website_page")).toBeTruthy();
		expect(Utils.isModel(page, "*")).toBeTruthy();
		expect(Utils.isModel(page, "change_website_page", "change_website_pagegroup")).toBeTruthy();
		expect(Utils.isModel(page, "change_website_website", "*", "change_website_pagegroup")).toBeTruthy();

	});


	it("should tell if a document's model is NOT one of those given as parameters", function () {

		var page = {
			"model": "change_website_page"
		};

		expect(Utils.isNotModel(page, "change_website_pagegroup")).toBeTruthy();
		expect(Utils.isNotModel(page, "change_website_pagegroup", "change_website_website", "change_news_news")).toBeTruthy();
		expect(Utils.isNotModel(page, "change_website_page")).toBeFalsy();
		expect(Utils.isNotModel(page, "change_website_website", "change_website_page", "change_news_news")).toBeFalsy();

	});


	it("should build a URL from given URL and parameters", function () {

		expect(Utils.makeUrl('base-url/folder/resource.php', { 'page': 3, 'user': 'Fred'})).toBe('base-url/folder/resource.php?page=3&user=Fred');
		expect(Utils.makeUrl('base-url/folder/resource.php?page=58487', { 'page': 3, 'user': 'Fred'})).toBe('base-url/folder/resource.php?page=3&user=Fred');
		expect(Utils.makeUrl('base-url/folder/resource.php?page=58487', { 'pageId': 3, 'user': 'Fred'})).toBe('base-url/folder/resource.php?page=58487&pageId=3&user=Fred');
		expect(Utils.makeUrl('base-url/folder/resource.php?page=58487#foo=bar', { 'pageId': 3, 'user': 'Fred'})).toBe('base-url/folder/resource.php?page=58487&pageId=3&user=Fred#foo=bar');

		// null parameter (page): parameter is removed from the resulting URL
		expect(Utils.makeUrl('base-url/folder/resource.php?page=58487#foo=bar', { 'pageId': 3, 'user': 'Fred', 'page': null})).toBe('base-url/folder/resource.php?pageId=3&user=Fred#foo=bar');

		// empty parameter (page): parameter is kept, but with an empty value (of course :))
		expect(Utils.makeUrl('base-url/folder/resource.php?page=58487#foo=bar', { 'pageId': 3, 'user': 'Fred', 'page': ''})).toBe('base-url/folder/resource.php?page=&pageId=3&user=Fred#foo=bar');
	});


	it("should tell if a String starts with another String or not", function () {

		expect(Utils.startsWith("Frederic", "Fred")).toBeTruthy();
		expect(Utils.startsWith("Frederic", "fred")).toBeFalsy();
		expect(Utils.startsWithIgnoreCase("Frederic", "fred")).toBeTruthy();

	});

	it("should tell if a String ends with another String or not", function () {

		expect(Utils.endsWith("Frederic", "eric")).toBeTruthy();
		expect(Utils.endsWith("Frederic", "Eric")).toBeFalsy();
		expect(Utils.endsWithIgnoreCase("Frederic", "Eric")).toBeTruthy();

	});


	it("should tell if a String is a model name or not", function () {

		expect(Utils.isModelName('Rbs_Website_Page')).toBeTruthy();
		expect(Utils.isModelName('Rbs_WebsitePage')).toBeFalsy();

	});
});