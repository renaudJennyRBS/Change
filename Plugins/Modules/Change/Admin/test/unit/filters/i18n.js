describe('i18n', function () {

	__change = {};
	__change.i18n = {
		"m.change.admin.admin.js": {
			"last-modification-date": "dernière modif.",
			"files-uploaded": "{files} files have been uploaded."
		},
		"m.change.website.admin.js": {
			"host-name": "nom d'hôte"
		}
	};

	var filter;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['$filter', function ($filter) {
		filter = $filter;
	}]));

	it('should translate a string without parameters', function () {
		expect(filter('i18n')('m.change.admin.admin.js.last-modification-date')).toBe("dernière modif.");
		expect(filter('i18n')('m.change.website.admin.js.host-name')).toBe("nom d'hôte");
		expect(filter('i18n')('m.change.admin.admin.js.unknown-key')).toBe("m.change.admin.admin.js.unknown-key");
		expect(filter('i18n')('m.change.admin.admin.js.files-uploaded')).toBe("{files} files have been uploaded.");
		expect(filter('i18n')('m.change.admin.admin.js.files-uploaded', {'files': 3})).toBe("3 files have been uploaded.");
	});

});