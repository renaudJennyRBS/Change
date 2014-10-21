<?php
namespace ChangeTests\Change\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \ChangeTests\Change\Collection\CollectionArrayTest
 */
class CollectionArrayTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testAll()
	{
		// Basic cases...
		$collection = new CollectionArray('Test_Collection', ['toto' => 'totoLabel', 'titi' => 'titiLabel']);
		$this->assertEquals('Test_Collection', $collection->getCode());
		$this->assertCount(2, $collection->getItems());
		$item1 = $collection->getItemByValue('toto');
		$this->assertEquals('toto', $item1->getValue());
		$this->assertEquals('totoLabel', $item1->getLabel());
		$this->assertEquals('totoLabel', $item1->getTitle());
		$item2 = $collection->getItemByValue('titi');
		$this->assertEquals('titi', $item2->getValue());
		$this->assertEquals('titiLabel', $item2->getLabel());
		$this->assertEquals('titiLabel', $item2->getTitle());

		// addItem()

		// If there is no item for this value, the item is added.
		$collection->addItem('tete', 'teteLabel');
		$this->assertCount(3, $collection->getItems());
		$item3 = $collection->getItemByValue('tete');
		$this->assertEquals('tete', $item3->getValue());
		$this->assertEquals('teteLabel', $item3->getLabel());
		$this->assertEquals('teteLabel', $item3->getTitle());

		// If there is an item for this value, the item is replaced.
		$collection->addItem('titi', 'youpiÇaMarche');
		$this->assertCount(3, $collection->getItems());
		$item3 = $collection->getItemByValue('titi');
		$this->assertEquals('titi', $item3->getValue());
		$this->assertEquals('youpiÇaMarche', $item3->getLabel());
		$this->assertEquals('youpiÇaMarche', $item3->getTitle());

		// removeItemByValue()

		// If there is an item for this value, the item is removed.
		$collection->removeItemByValue('toto');
		$this->assertCount(2, $collection->getItems());
		$item4 = $collection->getItemByValue('toto');
		$this->assertNull($item4);

		// If there is no item for this value, nothing appends.
		$collection->removeItemByValue('notExistingItem');
		$this->assertCount(2, $collection->getItems());
	}
}