describe('filters', function () {

	var stripHtmlTags,
	    BBcode,
	    ellipsis,
	    fileSize,
	    highlight,
	    emptyLabel;

	beforeEach(module('RbsChange'));

	beforeEach(inject(function (rbsStripHtmlTagsFilter, rbsBBcodeFilter, rbsEllipsisFilter, rbsFileSizeFilter, rbsHighlightFilter, rbsEmptyLabelFilter) {
		stripHtmlTags = rbsStripHtmlTagsFilter;
		BBcode = rbsBBcodeFilter;
		ellipsis = rbsEllipsisFilter;
		fileSize = rbsFileSizeFilter;
		highlight = rbsHighlightFilter;
		emptyLabel = rbsEmptyLabelFilter;
	}));

	it('should strip HTML tags', function () {
		expect(stripHtmlTags('<b>Very important text</b>')).toBe('Very important text');
		expect(stripHtmlTags('This is a <b>very</b>, <em>very</em> important text!')).toBe('This is a very, very important text!');
	});

	it('should turn BBcode to HTML', function () {
		expect(BBcode('[b]Very[/b] important text')).toBe('<b>Very</b> important text');
	});

	it('should ellipse the text', function () {
		expect(ellipsis('photo_20120506_small.jpeg', 10, 'center')).toBe('ph....jpeg');
		expect(ellipsis('photo_20120506_small.jpeg', 20, 'center')).toBe('photo_2...small.jpeg');
		expect(ellipsis('photo_20120506_small.jpeg', 21, 'center')).toBe('photo_2...small.jpeg');
		expect(ellipsis('photo_20120506_small.jpeg', 22, 'center')).toBe('photo_20..._small.jpeg');
		expect(ellipsis('photo_20120506_small.jpeg', 22)).toBe('photo_20120506_smal...');
	});

	it('should transform a value in bytes into a human readable string', function () {
		expect(fileSize(1000)).toBe('1000 octets');
		expect(fileSize(1024)).toBe('1 <abbr title="Kilo-octets">Ko</abbr>');
		expect(fileSize(5487)).toBe('5 <abbr title="Kilo-octets">Ko</abbr>');
		expect(fileSize(195487)).toBe('191 <abbr title="Kilo-octets">Ko</abbr>');
		expect(fileSize(195487365)).toBe('186 <abbr title="Mega-octets">Mo</abbr>');
	});

	it('should highlight a string in a string with a <strong class="highlight"> tag', function () {
		expect(highlight('Would you please highlight me in this text?', 'me')).toBe('Would you please highlight <strong class="highlight">me</strong> in this text?');
	});

	it('should return a default text when the input is empty', function () {
		expect(emptyLabel('', '-')).toBe('-');
		expect(emptyLabel('   ', '-')).toBe('-');
		expect(emptyLabel('Fred', '-')).toBe('Fred');
		expect(emptyLabel(0, '-')).toBe('0');
		expect(emptyLabel(null, '-')).toBe('-');
		expect(emptyLabel('0', '-')).toBe('0');
	});
});