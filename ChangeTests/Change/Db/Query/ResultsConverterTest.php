<?php
namespace ChangeTests\Change\Db\Query;

use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;

/**
* @name \ChangeTests\Change\Db\Query\ResultsConverterTest
*/
class ResultsConverterTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @param null $converter
	 * @param array $params
	 * @return ResultsConverter
	 */
	protected function getObject($converter = null, $params = array())
	{
		if ($converter === null)
		{
			$converter = $this->getApplicationServices()->getDbProvider();
		}
		$o = new ResultsConverter($converter, $params);
		return $o;
	}

	public function testConstruct()
	{
		$o = $this->getObject();
		$this->assertTrue(is_callable($o));

		$o = $this->getObject(function($v, $t) {return $t.',' . $v;});
		$this->assertTrue(is_callable($o));

		try
		{
			$o = $this->getObject('a');
			$this->fail('Argument 1 must be a valid Closure or DbProvider');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Argument 1 must be a valid Closure or DbProvider', $e->getMessage());
		}
	}

	public function testInvoke()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addBoolCol('c1', 'c2');
		$rows = array(array('c1' => 0, 'c2' => 1), array('c1' => 1, 'c2' => 1));
		$this->assertEquals(array(array('c1' => '1,0', 'c2' => '1,1'), array('c1' => '1,1', 'c2' => '1,1')), $o($rows));

		$o = $this->getObject($converter);
		$o->addBoolCol('c1', 'c3');
		$this->assertEquals(array(array('c1' => '1,0', 'c3' => null), array('c1' => '1,1', 'c3' => null)), $o($rows));


		$o = $this->getObject($converter);
		$o->addBoolCol('c1');
		$this->assertEquals(array('1,0', '1,1'), $o($rows));

		$o = $this->getObject($converter);
		$o->addBoolCol('c3');
		$this->assertEquals(array(null, null), $o($rows));

		$o = $this->getObject($converter);
		$o->addBoolCol('c2', 'c1')->singleColumn('c1');
		$this->assertEquals(array('1,0', '1,1'), $o($rows));

		$row = array('c1' => 0, 'c2' => 1, 'c3' => 1);
		$o = $this->getObject($converter);
		$o->addBoolCol('c2', 'c1');
		$this->assertEquals(array('c1' => '1,0', 'c2' => '1,1'), $o($row));

		$o = $this->getObject($converter);
		$o->addBoolCol('c2');
		$this->assertEquals('1,1', $o($row));

		$o = $this->getObject($converter);
		$o->addBoolCol('c2', 'c1')->singleColumn('c1');
		$this->assertEquals('1,0', $o($row));

		$o = $this->getObject($converter);
		$o->addBoolCol('c2', 'c1')->singleColumn('c2');
		$this->assertEquals('1,1', $o($row));

		$o = $this->getObject($converter);
		$o->addBoolCol('c2', 'c1')->singleColumn('c3');
		$this->assertEquals(array('c1' => '1,0', 'c2' => '1,1'), $o($row));
	}

	public function testAddStrCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addStrCol('c1');
		$this->assertEquals(array('c1' => ScalarType::STRING), $o->getFieldsTypes());
		$o->addStrCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::STRING, 'c3' => ScalarType::STRING), $o->getFieldsTypes());
	}



	public function testAddDtCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addDtCol('c1');
		$this->assertEquals(array('c1' => ScalarType::DATETIME), $o->getFieldsTypes());
		$o->addDtCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::DATETIME, 'c3' => ScalarType::DATETIME), $o->getFieldsTypes());
	}

	public function testAddIntCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addIntCol('c1');
		$this->assertEquals(array('c1' => ScalarType::INTEGER), $o->getFieldsTypes());
		$o->addIntCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::INTEGER, 'c3' => ScalarType::INTEGER), $o->getFieldsTypes());
	}

	public function testAddNumCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addNumCol('c1');
		$this->assertEquals(array('c1' => ScalarType::DECIMAL), $o->getFieldsTypes());
		$o->addNumCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::DECIMAL, 'c3' => ScalarType::DECIMAL), $o->getFieldsTypes());
	}

	public function testAddBoolCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addBoolCol('c1');
		$this->assertEquals(array('c1' => ScalarType::BOOLEAN), $o->getFieldsTypes());
		$o->addBoolCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::BOOLEAN, 'c3' => ScalarType::BOOLEAN), $o->getFieldsTypes());
	}

	public function testAddTxtCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addTxtCol('c1');
		$this->assertEquals(array('c1' => ScalarType::TEXT), $o->getFieldsTypes());
		$o->addTxtCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::TEXT, 'c3' => ScalarType::TEXT), $o->getFieldsTypes());
	}

	public function testAddLobCol()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter);
		$o->addLobCol('c1');
		$this->assertEquals(array('c1' => ScalarType::LOB), $o->getFieldsTypes());
		$o->addLobCol('c1', 'c3');
		$this->assertEquals(array('c1' => ScalarType::LOB, 'c3' => ScalarType::LOB), $o->getFieldsTypes());
	}

	public function testConvertRows()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter, array('c1' => ScalarType::BOOLEAN));

		$rows = array(array('c1' => 0, 'c2' => 1), array('c1' => 1, 'c2' => 1));

		$expected = array(array('c1' => '1,0', 'c2' => 1), array('c1' => '1,1', 'c2' => 1));
		$this->assertEquals($expected, $o->convertRows($rows));

	}

	public function testConvertRow()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter, array('c1' => ScalarType::BOOLEAN));

		$row = array('c1' => 0, 'c2' => 1);

		$expected = array('c1' => '1,0', 'c2' => 1);
		$this->assertEquals($expected, $o->convertRow($row));
	}

	public function testGetValue()
	{
		$converter = function($v, $t) {return $t.',' . $v;};
		$o = $this->getObject($converter, array('c1' => ScalarType::BOOLEAN));
		$this->assertEquals('1,0', $o->getValue(0, ScalarType::BOOLEAN));
	}
}