<?php
error_reporting(E_ALL);
class PHP2js {
	/** @var array holds tokens of the php file being converted */
	private $_tokens;
	/** @var int the current token */
	private $current = 0;
	/** @var javascript gets collected here */
	private $js;

	/** @var array these token keys will be converted to their values */
	private $_convert = array (
		'T_IS_EQUAL'=>'==',
		'T_IS_GREATER_OR_EQUAL'=>'>=',
		'T_IS_SMALLER_OR_EQUAL'=>'<=',
		'T_IS_IDENTICAL'=>'===',
		'T_IS_NOT_EQUAL'=>'!=',
		'T_IS_NOT_IDENTICAL'=>'!==',
		'T_IS_SMALLER_OR_EQUA'=>'<=',
		'T_BOOLEAN_AND'=>'&&',
		'T_BOOLEAN_OR'=>'||',
		'T_CONCAT_EQUAL'=>'+= ',
		'T_DIV_EQUAL'=>'/=',
		'T_DOUBLE_COLON'=>'.',
		'T_INC'=>'++',
		'T_MINUS_EQUAL'=>'-=',
		'T_MOD_EQUAL'=>'%=',
		'T_MUL_EQUAL'=>'*=',
		'T_OBJECT_OPERATOR'=>'.',
		'T_OR_EQUAL'=>'|=',
		'T_PLUS_EQUAL'=>'+=',
		'T_SL'=>'<<',
		'T_SL_EQUAL'=>'<<=',
		'T_SR'=>'>>',
		'T_SR_EQUAL'=>'>>=',
		'T_START_HEREDOC'=>'<<<',
		'T_XOR_EQUAL'=>'^=',
		'T_NEW'=>'new',
		'T_ELSE'=>'else',
		'.'=>'+',
		'T_IF'=>'if',
		'T_RETURN'=>'return',
		'T_AS'=>'in',
		'T_WHILE'=>'while',
		'T_LOGICAL_AND' => 'AND',
		'T_LOGICAL_OR' => 'OR',
		'T_LOGICAL_XOR' => 'XOR',
		'T_EVAL' => 'eval',
		'T_ELSEIF' => 'else if',
		'T_BREAK' => 'break',
	);
	
	/** @var array these tokens stays the same */
	private $_keep = array(
		'=', ',', '}', '{', ';', '(', ')', '*', '/', '+', '-', '>', '<', '[', ']',
	);
	
	/** @var array these tokens keeps their value */
	private $_keepValue = array (
		'T_CONSTANT_ENCAPSED_STRING', 'T_STRING', 'T_COMMENT', 'T_ML_COMMENT', 'T_DOC_COMMENT', 'T_LNUMBER',
		'T_WHITESPACE',
	);
	
	/**
	 * constructor, runs the show
	 *
	 * @param string $file path (relative or absolute) to the php file that is converted to js
	 */
	public function __construct ($file) {
		$this->file = $file;
		$this->_tokens = $this->getTokens($file);
		$this->compileJs();
		
	}
	
	/**
	 * gets tokens from file. Remove the meta PHP2js stuff.
	 *
	 * @param string $file path (relative or absolute) to the php file that is converted to js
	 * @return array
	 */
	private function getTokens($file) {
		$src = file_get_contents($this->file);
		$src = preg_replace ("/\n([\t ]*)require.*PHP2js\.php.*\'.*;/Uis", "", $src);
		$src = preg_replace ("/\n([\t ]*)new.*PHP2js.*;/Uis", "", $src);
		$this->src = $src;
		return token_get_all($src);
	}
	
	/**
	 * loops through tokens and convert to js
	 *
	 */
	private function compileJs() {
		foreach ($this->_tokens as $_) {
			$this->next ($name, $value);
			$this->parseToken($name, $value, $this->js);
		}
	}
	
	/**
	 * output the js and die
	 */
	private function renderJs () {
		echo "<script>$this->js</script>";
		die();
	}
	
	/**
	 * changed referenced args to name and value of next token
	 *
	 * @param string $name
	 * @param string $value
	 * @param unknown_type $i, the amount of nexts to skip
	 */
	private function next(& $name, & $value, $i=1) {
		for ($j=0; $j<$i; $j++) {
			$this->current++;
			if ($this->current > (count($this->_tokens)-1)) $this->renderJs();
			$_token = $this->_tokens[$this->current];
			$this->getToken ($name, $value, $_token);
		}
	}
	
	private function findFirst ($_tokenNames) {
		$name = '';
		$value = '';
		$cur = $this->current+1;
		for ($i=$cur; $i<count($this->_tokens)-1; $i++) {
			$this->getToken($name, $value, $this->_tokens[$i]);
			if (in_array($name, $_tokenNames)) {
				return $name;
			}
		}
	}
	
	/**
	 * tries to find the token in $this->_convert, $this->_keep and $this->_keepValue
	 * if it fails it tries to find a method named as the token. If fails here also it throws away the token.
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $js store js here by reference
	 */
	private function parseToken ($name, $value, & $js) {
		//change name to other value
		if (in_array($name, array_keys ($this->_convert))) {
			$js .= (!empty($this->_convert[$name])) ? $this->_convert[$name]: $name;
		//keep key
		} elseif (in_array($name, $this->_keep)) {
			$js .= $name;
		//keep value
		} elseif (in_array($name, $this->_keepValue)) {
			$js .= $value;
		//call method
		} else {
			if (method_exists($this, $name)) {
				$js .= $this->$name($value);
			}
		}
		//ignore
	}
	
	/**
	 * converters
	 *
	 * These guys are equivalents to tokens.
	 */
	
	/**
	 * class definition
	 *
	 * @param sting $value
	 * @return string
	 */
	private function T_CLASS($value) {
		$this->next ($name, $value, 2);
		return "function $value() ";
		return "var $value = new function() {";
	}
	
	/**
	 * define function
	 *
	 * @param string $value
	 * @return string
	 */
	private function T_FUNCTION($value) {
		$this->next ($name, $value, 2);
		return "this.$value = function";
	}
	
	/**
	 * echo is replaced with document.write
	 *
	 * @param string $value
	 * @return string
	 */
	private function T_ECHO($value) {
		$js = '';
		$jsTmp = '';
		for ($i=0; $i<100; $i++) {
			$this->next ($name, $value);
			$this->parseToken($name, $value, $js);
			if ($name == ';') {
				$this->js .= "document.write($jsTmp);";
				return '';
			}
			$jsTmp = $js;
		}
	}
	
	/**
	 * array. Supports both single and associative
	 *
	 * @param string $value
	 * @return string
	 */
	private function T_ARRAY($value) {
		$_convert = array (
			'('=>'{',
			')'=>'}',
			'T_DOUBLE_ARROW'=>':'
		);
		$js = '';
		$i = 0;
		while (true) {
			$this->next ($name, $value);
			if ($name == 'T_CONSTANT_ENCAPSED_STRING') {
				$jsSub = '';
				while (true) {
					if (!empty($_convert[$name])) {
						$jsSub .= $_convert[$name];
					} else {
						$this->parseToken($name, $value, $jsSub);
					}
					if ($name == ',' || $name == ')') { 
						break ;
					}
					$this->next ($name, $value);
				}
				if (strpos($jsSub, ':') === false) {
					$jsSub = "$i:$jsSub";
				}
				$js .= $jsSub;
				
			} else if (!empty($_convert[$name])) {
				$js .= $_convert[$name];
			} else {
				$this->parseToken($name, $value, $js);
			}
			if ($name == ';') break;
			$i++;
		}
		return $js;
	}
	
	/**
	 * foreach. Gets converted to for (var blah in blih). Supports as $key=>$value
	 *
	 * @param string $value
	 * @return string
	 */
	private function T_FOREACH($value) {
		$_vars = array();
		for ($i=0; $i<100; $i++) {
			$this->next ($name, $value);
			if ($name == 'T_VARIABLE') $_vars[] = $this->cVar($value);
			$this->parseToken($name, $value, $js);
			if ($name == '{') {
				if (count($_vars) == 2) {
					$array = $_vars[0];
					$val = $_vars[1];
					$this->js .= 
					"for (var {$val}Val in $array) {".
					"\n                        $val = $array"."[{$val}Val];";
				}
				if (count($_vars) == 3) {
					$array = $_vars[0];
					$key = $_vars[1];
					$val = $_vars[2];
					$this->js .= 
					"for (var $key in $array) {".
					"\n                        $val = $array"."[$key];";
				}
				return '';
			}
			$jsTmp = $js;
		}
	}
	
	/**
	 * declare a public class var
	 *
	 * @param string $value
	 * @return string
	 */
	private function T_PUBLIC ($value) {
		$type = $this->findFirst(array('T_VARIABLE', 'T_FUNCTION'));
		if ($type == 'T_FUNCTION') return '';
		$js = '';
		while (true) {
			$this->next ($name, $value);
			$this->parseToken($name, $value, $js);
			if ($name == ';') {
				$js = str_replace(array(' '), '', $js);
				return 'this.'.$js;
			} else if ($name == '=') {
				$js = str_replace(array(' ','='), '', $js);
				return 'this.'.$js.' =';
			}
		}
	}
	
	/**
	 * variable. Remove the $
	 *
	 * @param string $value
	 * @return string
	 */
	private function T_VARIABLE($value) {
		return str_replace('$', '', $value);
	}
	
	/* helpers */
	
	private function getToken(& $name, & $value, $_token) {
		if (is_array($_token)) {
			$name = trim(token_name($_token[0]));
			$value = $_token[1];
		} else {
			$name = trim($_token);
			$value = '';
		}
	}
	
	private function cVar($var) {
		return str_replace('$', '', $var);
	}
	
	/** debugging stuff. Ugly and deprecated. */
	
	/** deprecated and sucks */
	private $_openTags = array(
			'T_OPEN_TAG', 'T_CLASS', 'T_PUBLIC', 'T_FOREACH', 'T_ARRAY', '{', 'T_VARIABLE', '('
	);
	
	/** deprecated and sucks */
	
	/** deprecated and sucks */
	private $indent = 0;
	/** deprecated and sucks */
	private $debug;
	
	
	private $_closeTags = array(
		'}', 'T_CLOSE_TAG', ';', ')',
	);
	
	public function __destruct() {
		/**
		$js = htmlentities ($this->js);
		echo ("<pre>$js</pre>");
		$this->write();
		echo $this->debug;
		/*/
	}
	
	
	private function write() {
		$_tokens = token_get_all($this->src);
		foreach ($_tokens as $key=>$_token) {
			if (is_array($_token)) {
				$name = trim(token_name($_token[0]));
				$value = $_token[1];
			} else {
				$name = trim($_token);
				$value = '';
			}
			$this->printToken($name, $value, $_token);
		}
	}
	
	private function printToken ($name, $value, $_token) {
		$value = htmlentities($value);
	
		if (in_array($name, $this->_closeTags)) $this->indent--;
		$indent = str_repeat('.&nbsp;&nbsp;&nbsp;&nbsp;', $this->indent);
		if (in_array($name, $this->_openTags)) $this->indent++;
		if (!empty($value))
		$this->debug .= "
		<br />$indent
		<b>$name&nbsp;&nbsp;=&nbsp;&nbsp;'$value'</b>
	
	";
		else
		$this->debug .= "
		<br />$indent
		<b>$name</b>
	
	";
	}
}
?>