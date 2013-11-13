<?php
namespace ChangeTests\Rbs\Generic;

class GenericServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Rbs\Generic\GenericServices
	 */
	protected function getGenericServices()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('genericServices', $genericServices);
		return $genericServices;
	}

	public function testGetInstance()
	{
		$genericServices = $this->getGenericServices();
		$this->assertInstanceOf('Rbs\Seo\SeoManager', $genericServices->getSeoManager());
		$this->assertInstanceOf('Rbs\Media\Avatar\AvatarManager', $genericServices->getAvatarManager());
		$this->assertInstanceOf('Rbs\Simpleform\Field\FieldManager', $genericServices->getFieldManager());
		$this->assertInstanceOf('Rbs\Simpleform\Security\SecurityManager', $genericServices->getSecurityManager());
	}
} 