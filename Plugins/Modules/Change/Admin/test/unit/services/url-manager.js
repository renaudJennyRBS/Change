describe('UrlManager', function () {

	var UrlManager = null;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['RbsChange.UrlManager', function (RbsChangeUrlManager) {
		UrlManager = RbsChangeUrlManager;
		UrlManager.register('change_website_page', {
			'editor' : '/website/pages/:id/:LCID/editor',
			'form'   : '/website/pages/:id/:LCID',
			'list'   : '/website/pages/:LCID',
			'i18n'   : '/website/pages/:id/:LCID?translate-from=:fromLCID'
		});
	}]));


	it("should return the URL template of a Model name, with the parameters unchanged", function () {
		expect(UrlManager.getUrl('change_website_page')).toEqual('website/pages/:id/:LCID');
	});


	it("should return the URL of a Document, with the parameters replaced", function () {
		var doc = {
			'model': 'change_website_page',
			'id'   : 4563,
			'LCID' : 'fr_FR'
		};
		expect(UrlManager.getUrl(doc)).toEqual('website/pages/4563/fr_FR');
		expect(UrlManager.getUrl(doc, { 'LCID': 'en_US' })).toEqual('website/pages/4563/en_US');
		expect(UrlManager.getUrl(doc, { 'LCID': 'en_US' }, "editor")).toEqual('website/pages/4563/en_US/editor');

		var doc = {
			'model': 'change_website_page',
			'id'   : 4563
		};
		expect(UrlManager.getUrl(doc)).toEqual('website/pages/4563');
		expect(UrlManager.getUrl(doc, { 'LCID': 'en_US' })).toEqual('website/pages/4563/en_US');
		expect(UrlManager.getUrl(doc, { 'LCID': 'en_US' }, "editor")).toEqual('website/pages/4563/en_US/editor');


		expect(UrlManager.getI18nUrl(doc, 'en_US', 'fr_FR')).toEqual('website/pages/4563/en_US?translate-from=fr_FR');

		expect(UrlManager.getUrl(doc, "editor")).toEqual('website/pages/4563/editor');

		doc.LCID = 'fr_FR';
		expect(UrlManager.getUrl(doc, "editor")).toEqual('website/pages/4563/fr_FR/editor');
	});


	it("should return the URL of a List of Documents, with the parameters replaced", function () {
		var params = {};
		expect(UrlManager.getListUrl('change_website_page')).toEqual('website/pages/:LCID');

		expect(UrlManager.getListUrl('change_website_page', params)).toEqual('website/pages');
		params.LCID = 'en_GB';
		expect(UrlManager.getListUrl('change_website_page', params)).toEqual('website/pages/en_GB');
		params.LCID = 'fr_FR';
		expect(UrlManager.getListUrl('change_website_page', params)).toEqual('website/pages/fr_FR');
	});


});