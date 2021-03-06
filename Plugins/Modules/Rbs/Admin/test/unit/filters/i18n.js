describe('i18n', function () {

	__change = {};
	__change.i18n = {
		"m.rbs.admin.admin.js": {
			"last-modification-date": "dernière modif.",
			"files-uploaded": "{files} files have been uploaded."
		},
		"m.rbs.website.admin.js": {
			"host-name": "nom d'hôte"
		}
	};

	var filter;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['$filter', function ($filter) {
		filter = $filter;
	}]));

	it('should translate a string without parameters', function () {
		expect(filter('i18n')('m.rbs.admin.adminjs.last_modification_date')).toBe("dernière modif.");
		expect(filter('i18n')('m.rbs.website.admin.host_name')).toBe("nom d'hôte");
		expect(filter('i18n')('m.rbs.admin.admin.js.unknown-key')).toBe("m.rbs.admin.admin.js.unknown-key");
		expect(filter('i18n')('m.rbs.admin.admin.js.files-uploaded')).toBe("{files} files have been uploaded.");
		expect(filter('i18n')('m.rbs.admin.admin.js.files-uploaded', {'files': 3})).toBe("3 files have been uploaded.");

		// With filters
		expect(filter('i18n')('m.rbs.website.admin.host_name | ucf')).toBe("Nom d'hôte");
		expect(filter('i18n')('m.rbs.website.admin.host_name | uppercase')).toBe("NOM D'HÔTE");
		expect(filter('i18n')('m.rbs.website.admin.host_name | uppercase | ucf')).toBe("NOM D'HÔTE");
	});

});