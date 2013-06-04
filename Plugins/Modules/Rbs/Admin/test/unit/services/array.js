describe('ArrayUtils', function () {

	var ArrayUtils;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['RbsChange.ArrayUtils', function (RbsChangeArrayUtils) {
		ArrayUtils = RbsChangeArrayUtils;
	}]));

	it('should remove a part of an array', function () {
		var input = ['one', 2, 'THREE', 'four', 5];
		ArrayUtils.remove(input, 2, 3);
		expect(input).toEqual(['one', 2, 5]);
	});

	it('should move an array item to another position in the array', function () {
		var input = ['one', 2, 'THREE', 'four', 5];
		ArrayUtils.move(input, 2, 3);
		expect(input).toEqual(['one', 2, 'four', 'THREE', 5]);
	});

	it('should append the contents of an array to another array', function () {
		var input = ['one', 2, 'THREE', 'four', 5];
		ArrayUtils.append(input, ['Fred', 'AngularJS']);
		expect(input).toEqual(['one', 2, 'THREE', 'four', 5, 'Fred', 'AngularJS']);
	});

});