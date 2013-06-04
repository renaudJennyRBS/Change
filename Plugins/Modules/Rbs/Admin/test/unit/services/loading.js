describe('Base64', function () {

	var Loading;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['RbsChange.Loading', function (LoadingService) {
		Loading = LoadingService;
	}]));


	it('should tell whether something is loading or not', function () {

		expect(Loading.isLoading()).toBeFalsy();

		Loading.start("Request 1");
		expect(Loading.isLoading()).toBeTruthy();
		expect(Loading.getMessage()).toBe("Request 1");

		Loading.start("Request 2");
		expect(Loading.isLoading()).toBeTruthy();
		expect(Loading.getMessage()).toBe("Request 2");

		Loading.stop();
		expect(Loading.isLoading()).toBeTruthy();
		expect(Loading.getMessage()).toBe("Request 1");

		Loading.stop();
		expect(Loading.isLoading()).toBeFalsy();
		expect(Loading.getMessage()).toBeNull();

	});

});