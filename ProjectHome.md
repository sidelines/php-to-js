# PHP2js #

Release early? ... ok! :)

So this is the first release of a PHP script that converts a php file to js.

## Whats working? ##
  * Operators and stuff (=, ==, &&, etc.)
  * if, else, elseif, while, break
  * arrays (gets converted to hashes)
  * classes, methods, functions, return, new
  * echo (document.write()) :)
  * foreach (gets converted to for (i in var)
  * public class variables
  * whitespace, eval
  * Comments, phpdoc

## Whats not working? ##
  * extends, abstract, typecasting, case, alternative syntax, global, implements, include, isset, private, protected, switch, throw, try, var
  * all the native php functions, although some can be found at http://phpjs.org/

## Code Status ##
  * As ugly as ass, and the result of a one-night stand with eclipse, hardly any comments. See it here: http://code.google.com/p/php-to-js/source/browse/trunk/PHP2js.php

# Show me something god dammit! #
Can has php:
```
<?
require_once 'PHP2js.php';
new PHP2js(__FILE__);
/**
 * My super cool php class that will be converted to js!!!
 */
class HelloWorld {
	/**
	 * So here goes a function that echos
	 *
	 * @param string $foo
	 * @param string $bar
	 */
	function foo($foo, $bar) {
		echo $foo . ' ' . $bar;
	}
}
$H = new HelloWorld;
$H->foo();
?>
```
Point your browser to it and you get the js code:
```
/**
 * My super cool php class that will be converted to js!!!
 */
function HelloWorld()  {
	/**
	 * So here goes a function that echos
	 *
	 * @param string $foo
	 * @param string $bar
	 */
	this.foo = function(foo, bar) {
		document.write( foo + ' ' + bar);
	}
}
H = new HelloWorld;
H.foo('Hello', 'World');
```
and what you will see:
```
Hello World
```

See examples of working stuff here: http://code.google.com/p/php-to-js/source/browse/trunk/HelloWorld.php and http://code.google.com/p/php-to-js/source/browse/trunk/Test.php