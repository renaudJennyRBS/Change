# RBS Change v4.0 Coding Guidelines #

## Classes & interfaces ##


### Abstract classes ###

* Abstract classes names must always start with "`Abstract`" (eg. : `AbstractAction`) - **the only exception to that rule are classes containing only static methods (helpers, factories, …)**.
* Abstract classes must have at least one concrete method. If you provide no implementation, declare an interface instead.



Example :

    <?php
    
    namespace \Change\Mvc;
    
    abstract class AbstractAction {
    
    }

### Class inheritance ###

When you write a class extending another class, you should always refer to the latter using a **fully-qualified class name**. That means you should never use an alias or a namespace relative name.

Example :

    <?php
    
    namespace \Change\Mvc;
    
    class Test extends \Change\Example\Test 
    {
    	[…]
    }
    
### Interfaces ###

An interface should only be introduced when there are really needed - in general only if at least two classes sharing no code implementing it. In that case they should always be named `Interface` (using the correct namespace of course).

 
    
## Namespaces ##

### Built-in PHP Classes ###

Built-in PHP Classes should always be referred to in a fully qualified way regarding namespaces. For instance, you would refer to PHP's built-in exception class that way `\Exception`.

### Classes from external libraries ###

Classes from 3rd party libraries must be referred to in a fully qualified way regarding namespaces : `use <fully-qualified class name> as <alias>` and `use` are thus forbidden.

Example :

    /**
	 * @return \Zend\Http\Client
	 */
	public function getNewHttpClient($params = array())
	{
		return new \Zend\Http\Client(null, $this->getHttpClientConfig($params));
	}
	
### Classes inside the \Change namespaces

Inside the \Change namespace, the use of aliases is permitted as long as it doesn't carry out any ambiguity for the reader. 

### Classes inside the \Modules namespaces

Inside RBS Change module's developed by the R&D Team, the following rules applies :


### Services ###


