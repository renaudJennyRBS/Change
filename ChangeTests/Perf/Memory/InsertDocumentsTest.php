<?php
namespace ChangeTests\Perf\Memory;

/**
 * @name \ChangeTests\Perf\Memory\InsertDocumentsTest
 */
class InsertDocumentsTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testInsert()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$dm = $this->getDocumentServices()->getDocumentManager();
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
				/* @var $item \Rbs\Collection\Documents\Item */
				$item = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$item->setValue($it)->setLabel('item: ' . $count)->setTitle('titre: ' . $count);
				$item->save();
				$items[] = $item;
			}

			/* @var $collection \Rbs\Collection\Documents\Collection */
			$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
			$collection->setLabel('col' . $iteration)
				->setCode(microtime(true) . uniqid());
			$collection->setItems($items);
			$collection->save();

		}
		$tm->commit();
	}
}