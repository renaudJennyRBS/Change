<?php
namespace ChangeTests\Documents\Constraints;

/**
 * @name \ChangeTests\Documents\Constraints\ConstraintsManagerTest
 */
class ConstraintsManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();
		$this->assertInstanceOf('\Change\Documents\Constraints\ConstraintsManager', $constraintsManager);
		
		$this->assertEquals('c.constraints', \Zend\Validator\AbstractValidator::getDefaultTranslatorTextDomain());
		
		$t = \Zend\Validator\AbstractValidator::getDefaultTranslator();
		$this->assertInstanceOf('\Change\Documents\Constraints\Translator', $t);
		$this->assertInstanceOf('\Change\I18n\I18nManager', $t->getI18nManager());
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testDefaultConstraint()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();
		$this->assertTrue($constraintsManager->hasDefaultConstraint('domain'));
		$this->assertTrue($constraintsManager->hasDefaultConstraint('url'));
		$this->assertTrue($constraintsManager->hasDefaultConstraint('unique'));
		$this->assertTrue($constraintsManager->hasDefaultConstraint('enum'));
		
		$this->assertFalse($constraintsManager->hasDefaultConstraint('test'));
		$constraintsManager->registerConstraint('test', '\ChangeTests\Documents\Constraints\ConstraintNotFound');
		$this->assertTrue($constraintsManager->hasDefaultConstraint('test'));
	}
	
	/**
	 * @depends testDefaultConstraint
	 */
	public function testGetByName()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();
		$this->assertInstanceOf('\Change\Documents\Constraints\Domain', $constraintsManager->getByName('domain'));
		$this->assertInstanceOf('\Change\Documents\Constraints\Min', $constraintsManager->getByName('min', array('min' => 1)));
	
		$c1 = $constraintsManager->getByName('email');
		$c2 = $constraintsManager->getByName('email');
		
		$this->assertNotNull($c1);
		$this->assertNotNull($c2);
		$this->assertNotSame($c1, $c2);
		try 
		{
			$constraintsManager->getByName('test');
			$this->fail('Constraint test not found');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Constraint test not found', $e->getMessage());
		}
		
		try
		{
			$constraintsManager->getByName('invalidconstraint');
			$this->fail('Constraint invalidconstraint not found');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Constraint invalidconstraint not found', $e->getMessage());
		}
	}	
	
	/**
	 * @depends testGetByName
	 */
	public function testEmail()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();
		include __DIR__ . '/TestAssets/Translator.php';
		$t = \ChangeTests\Documents\Constraints\TestAssets\Translator::factory(array());
		\Zend\Validator\AbstractValidator::setDefaultTranslator($t);
		

		$constraint = $constraintsManager->getByName('email');
		$this->assertTrue($constraint->isValid('noreplay@rbschange.fr'));
		
		$constraint = $constraintsManager->getByName('email');
		$this->assertFalse($constraint->isValid('rbschange.fr'));
		
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('emailAddressInvalidFormat', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['emailAddressInvalidFormat']);
	}
	

	/**
	 * @depends testEmail
	 */
	public function testEmails()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();

		$t = \ChangeTests\Documents\Constraints\TestAssets\Translator::factory(array());
		\Zend\Validator\AbstractValidator::setDefaultTranslator($t);

		$constraint = $constraintsManager->getByName('emails');
		$this->assertTrue($constraint->isValid('noreplay@rbschange.fr'));
		$this->assertTrue($constraint->isValid('noreplay@rbschange.fr, admin@rbschange.fr'));
	
		$constraint = $constraintsManager->getByName('emails');
	
		$this->assertFalse($constraint->isValid('noreplay@rbschange.fr,,rbschange.fr'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('emailsAddressInvalid', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['emailsAddressInvalid']);
	}
	
	/**
	 * @depends testEmails
	 */
	public function testInteger()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();

		$t = \ChangeTests\Documents\Constraints\TestAssets\Translator::factory(array());
		\Zend\Validator\AbstractValidator::setDefaultTranslator($t);

		$constraint = $constraintsManager->getByName('integer');
		$this->assertTrue($constraint->isValid('5'));

		$constraint = $constraintsManager->getByName('integer');
	
		$this->assertFalse($constraint->isValid('5.3'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('notDigits', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['notDigits']);
	}
	
	/**
	 * @depends testInteger
	 */
	public function testMatches()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();

		$t = \ChangeTests\Documents\Constraints\TestAssets\Translator::factory(array());
		\Zend\Validator\AbstractValidator::setDefaultTranslator($t);

		$constraint = $constraintsManager->getByName('matches', array('pattern' => '/^[0-5]+$/'));
		$this->assertTrue($constraint->isValid('4'));
		$this->assertTrue($constraint->isValid('45550'));
	
		$constraint = $constraintsManager->getByName('matches', array('pattern' => '/^[0-5]+$/', 'message' => 'test'));
	
		$this->assertFalse($constraint->isValid('46'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('regexNotMatch', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['regexNotMatch']);
	}
	
	/**
	 * @depends testMatches
	 */
	public function testRange()
	{
		$constraintsManager = $this->getApplicationServices()->getConstraintsManager();

		$t = \ChangeTests\Documents\Constraints\TestAssets\Translator::factory(array());
		\Zend\Validator\AbstractValidator::setDefaultTranslator($t);

		$constraint = $constraintsManager->getByName('range', array('min' => 5, 'max' => 10));
		$this->assertTrue($constraint->isValid('5'));
		$this->assertTrue($constraint->isValid('6'));
		$this->assertTrue($constraint->isValid('10'));
	
		$constraint = $constraintsManager->getByName('range', array('min' => 5, 'max' => 10));
	
		$this->assertFalse($constraint->isValid('46'));
		$messages = $constraint->getMessages();

		$this->assertArrayHasKey('notBetween', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['notBetween']);
		
		$constraint = $constraintsManager->getByName('range', array('min' => 5, 'max' => 10, 'inclusive' => false));
		$this->assertFalse($constraint->isValid('5'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('notBetweenStrict', $messages);
	}
}
