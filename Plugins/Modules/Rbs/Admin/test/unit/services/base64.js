describe('Base64', function () {

	var Base64;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['RbsChange.Base64', function (Base64Service) {
		Base64 = Base64Service;
	}]));


	it('should encode a string into base64', function () {

		expect(Base64.encode('{"operator":"equals", "filters": []}')).toBe('eyJvcGVyYXRvciI6ImVxdWFscyIsICJmaWx0ZXJzIjogW119');

	});


	it('should decode a base64 encoded string', function () {

		expect(Base64.decode('eyJvcGVyYXRvciI6ImVxdWFscyIsICJmaWx0ZXJzIjogW119')).toBe('{"operator":"equals", "filters": []}');

	});

});