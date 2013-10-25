<?php
namespace ChangeTests\Rbs\Media\Avatar;

class GravatarTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public function testGetUrl()
	{

		$gravatar = new \Rbs\Media\Avatar\Gravatar('email@email.com');

		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=mm&r=g', $gravatar->getUrl());

		$gravatar->setSize(250);
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=250&d=mm&r=g', $gravatar->getUrl());
		$gravatar->setSize(-1);
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=mm&r=g', $gravatar->getUrl());
		$gravatar->setSize(2049);
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=mm&r=g', $gravatar->getUrl());

		$gravatar->setDefaultImg('404');
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=404&r=g', $gravatar->getUrl());
		$gravatar->setDefaultImg('http://rbs.fr/avatar.png');
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=http%3A%2F%2Frbs.fr%2Favatar.png&r=g', $gravatar->getUrl());
		$gravatar->setDefaultImg('mm');

		$gravatar->setRating('x');
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=mm&r=x', $gravatar->getUrl());
		$gravatar->setRating('y');
		$this->assertEquals('http://www.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=mm&r=g', $gravatar->getUrl());

		$gravatar->setSecure(true);
		$this->assertEquals('https://secure.gravatar.com/avatar/4f64c9f81bb0d4ee969aaf7b4a5a6f40?s=80&d=mm&r=g', $gravatar->getUrl());
	}


}
