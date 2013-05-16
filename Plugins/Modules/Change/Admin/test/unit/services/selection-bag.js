describe('Clipboard', function () {
	
	var sb;
	
	beforeEach(module('RbsChange'));
	
	beforeEach(inject(['RbsChange.Clipboard', function (RbsChangeClipboard) {
		sb = RbsChangeClipboard;
	}]));
	
	it('should be empty', function () {
		expect(sb.values.length).toBe(0);
		expect(sb.isEmpty()).toBeTruthy();
		expect(sb.status).toBe('empty');
	});
	
	it('should contains n elements', function () {
		sb.append('first element');
		expect(sb.values.length).toBe(1);
		expect(sb.isEmpty()).toBeFalsy();
		
		sb.append('second element');
		expect(sb.values.length).toBe(2);
		expect(sb.status).toBe('unused');
		
		sb.replace('single element');
		expect(sb.values.length).toBe(1);

		sb.append('second element');
		expect(sb.values.length).toBe(2);
		sb.remove('single element');
		expect(sb.values.length).toBe(1);
		expect(sb.values[0]).toBe('second element');
		expect(sb.status).toBe('unused');
		
		sb.getItems(true);
		expect(sb.status).toBe('used');
		
		sb.clear();
		expect(sb.isEmpty()).toBeTruthy();
		expect(sb.status).toBe('empty');
	});
	
});