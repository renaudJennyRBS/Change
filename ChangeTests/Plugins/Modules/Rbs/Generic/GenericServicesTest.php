<?php
namespace ChangeTests\Rbs\Generic;

class GenericServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Rbs\Generic\GenericServices
	 */
	protected function getGenericServices()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(),  $this->getApplicationServices());
		return $genericServices;
	}

	public function testGetInstance()
	{
		$genericServices = $this->getGenericServices();
		$this->assertInstanceOf('Rbs\Elasticsearch\Index\IndexManager', $genericServices->getIndexManager());
		$this->assertInstanceOf('Rbs\Elasticsearch\Facet\FacetManager', $genericServices->getFacetManager());
		$this->assertInstanceOf('Rbs\Geo\GeoManager', $genericServices->getGeoManager());
		$this->assertInstanceOf('Rbs\Media\Avatar\AvatarManager', $genericServices->getAvatarManager());
		$this->assertInstanceOf('Rbs\Seo\SeoManager', $genericServices->getSeoManager());
		$this->assertInstanceOf('Rbs\Simpleform\Field\FieldManager', $genericServices->getFieldManager());
		$this->assertInstanceOf('Rbs\Simpleform\Security\SecurityManager', $genericServices->getSecurityManager());
		$this->assertInstanceOf('Rbs\Mail\MailManager', $genericServices->getMailManager());
		$this->assertInstanceOf('Rbs\Admin\AdminManager', $genericServices->getAdminManager());
	}
} 