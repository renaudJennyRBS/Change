<?php
namespace ChangeTests\Change\Configuration;

use Change\Configuration\EditableConfiguration;

class EditableConfigurationTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testAddPersistentEntry()
	{
		$config = new EditableConfiguration(array());
		try
		{
			$this->assertTrue($config->addPersistentEntry('mypath/myentry', 'value'));
			$this->fail('Configuration not found: ' . EditableConfiguration::PROJECT);
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals(30000, $e->getCode());
		}

		$sourceConfigFile = sys_get_temp_dir() . "/testAddPersistentEntry_project1.json";
		if (file_exists($sourceConfigFile))
		{
			unlink($sourceConfigFile);
		}

		copy(__DIR__ . '/TestAssets/project1.json', $sourceConfigFile);
		$config = new EditableConfiguration(array(EditableConfiguration::PROJECT => $sourceConfigFile));
		$this->assertNull($config->getEntry('mypath/myentry'));
		$this->assertTrue($config->addPersistentEntry('mypath/myentry', 'value'));
		$this->assertEquals('value', $config->getEntry('mypath/myentry'));

		$this->assertCount(1, $config->getUpdateEntries());

		$config->save();

		$newConfig = new  \Change\Configuration\EditableConfiguration(array(EditableConfiguration::PROJECT =>  $sourceConfigFile));
		$this->assertEquals('value', $newConfig->getEntry('mypath/myentry'));

		// Giving an invalid path just returns false.
		$this->assertNull($newConfig->getEntry('invalidpath'));
		$this->assertFalse($config->addPersistentEntry('invalidpath', 'value'));
		$this->assertCount(0, $config->getUpdateEntries());


		$newConfig = new  \Change\Configuration\EditableConfiguration(array(EditableConfiguration::PROJECT =>  $sourceConfigFile));
		$this->assertNull($newConfig->getEntry('invalidpath'));

		// Boolean and integer types values are correctly preserved.
		$this->assertNull($newConfig->getEntry('mypath/integer'));
		$this->assertNull($newConfig->getEntry('mypath/boolean'));
		$this->assertTrue($config->addPersistentEntry('mypath/integer', 155));
		$this->assertTrue($config->addPersistentEntry('mypath/boolean', true));
		$this->assertCount(2, $config->getUpdateEntries());
		$config->save();

		$newConfig = new  \Change\Configuration\EditableConfiguration(array(EditableConfiguration::PROJECT => $sourceConfigFile));
		$this->assertTrue(155 === $newConfig->getEntry('mypath/integer'));
		$this->assertTrue(true === $newConfig->getEntry('mypath/boolean'));


		if (file_exists($sourceConfigFile))
		{
			unlink($sourceConfigFile);
		}
	}
}