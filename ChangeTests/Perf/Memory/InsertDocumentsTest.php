<?php
namespace ChangeTests\Perf\Memory;

/**
 * @name \ChangeTests\Perf\Memory\InsertDocumentsTest
 */
class InsertDocumentsTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::clearDB();
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testInsertInline()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$dm = $this->getApplicationServices()->getDocumentManager();
		$this->iterationInline($tm, $dm, -1, null);

		$sum = 0;
		$max = 0;
		for ($i = 0; $i < 10; $i++)
		{
			$this->iterationInline($tm, $dm, $i);
			$memoryIteration =  memory_get_usage();

			$sum += $memoryIteration;
			$max = max($max, $memoryIteration);
		}
		$avg = ($sum / 10.0);
		$actual = ($max - $avg) / $avg;
		//echo $sum, "\t", $max, "\t", $avg, "\t", $actual, PHP_EOL;
		$this->assertLessThan(0.001, $actual);
	}

	/**
	 * @param \Change\Transaction\TransactionManager $tm
	 * @param \Change\Documents\DocumentManager $dm
	 * @param integer $iteration
	 * @return array
	 */
	protected function iterationInline($tm, $dm, $iteration)
	{
		$count = $iteration * 100;
		$tm->begin();
		for ($col = 0; $col < 20; $col++)
		{
			/* @var $collection \Rbs\Collection\Documents\Collection */
			$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
			$items = array();
			for ($it = 0; $it < 5; $it++)
			{
				$count++;
				$item = $collection->newCollectionItem();
				$item->setValue($it)->setLabel('item: ' . $count)->getCurrentLocalization()->setTitle('titre: ' . $count);
				$items[] = $item;
			}

			$collection->setLabel('col' . $iteration)
				->setCode(microtime(true) . uniqid());
			$collection->setItems($items);
			$collection->save();
		}
		$tm->commit();
	}

	public function testInsert()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$dm = $this->getApplicationServices()->getDocumentManager();
		$this->iteration($tm, $dm, -1, null);

		$sum = 0;
		$max = 0;
		for ($i = 0; $i < 10; $i++)
		{
			$this->iteration($tm, $dm, $i);
			$memoryIteration =  memory_get_usage();

			$sum += $memoryIteration;
			$max = max($max, $memoryIteration);
		}
		$avg = ($sum / 10.0);
		$actual = ($max - $avg) / $avg;
		//echo $sum, "\t", $max, "\t", $avg, "\t", $actual, PHP_EOL;
		$this->assertLessThan(0.001, $actual);
	}

	/**
	 * @param \Change\Transaction\TransactionManager $tm
	 * @param \Change\Documents\DocumentManager $dm
	 * @param integer $iteration
	 * @return array
	 */
	protected function iteration($tm, $dm, $iteration)
	{
		$count = $iteration * 100;
		$tm->begin();
		for ($col = 0; $col < 20; $col++)
		{

			$items = array();
			for ($it = 0; $it < 5; $it++)
			{
				$count++;
				/* @var $item \Project\Tests\Documents\Localized */
				$item = $dm->getNewDocumentInstanceByModelName('Project_Tests_Localized');
				$item->setPStr('item: ' . $count)->getCurrentLocalization()->setPLStr('titre: ' . $count);
				$item->save();
				$items[] = $item;
			}

			/* @var $collection \Project\Tests\Documents\Basic */
			$collection = $dm->getNewDocumentInstanceByModelName('Project_Tests_Basic');
			$collection->setPStr('col' . $iteration)
				->setPText(microtime(true) . uniqid());
			$collection->setPDocArr($items);
			$collection->save();
		}
		$tm->commit();
	}
}