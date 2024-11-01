<?php
require_once (ABSPATH . WPINC . "/class-snoopy.php");

class plugin_base {

	var $show_options_page = true;
	var $name = 'Give me a name!!';
	var $filename = __FILE__;
	var $debug = false;
	
	function plugin_base() {
		if ($this->show_options_page) { add_action('admin_menu', array(&$this, '_create_options_menu_item')); }		
		if (method_exists($this, '_install')) { register_activation_hook($this->filename, array($this, '_install')); }
		if (method_exists($this, '_uninstall')) { register_deactivation_hook($this->filename, array($this, '_uninstall')); }
	}
	
	function _create_options_menu_item(){
		if (function_exists('add_options_page')) { add_options_page($this->name, $this->name, 9, $this->filename, array($this, 'options_page')); }
	}	
	
	function parse_remote_xml($url) {
		$results = $this->get_remote_content($url);
		if ($results) {	
			return $this->parse_xml_content($results);
		} else {
			return $results;
		}
	}
	
	function parse_xml_content($content) {
		$parser = new xml(false, true, true);
		return $parser->parse($content);	
	}
	
	function throw_error($message) {
		print "\n<div style=\"color:#FF0000;\"><strong>Plugin Error in $this->name</strong><br />$message</div>\n";
		return false;
	}
	
	function get_remote_content($url) {
		$client = new Snoopy();
		$client->read_timeout = 3;
		$client->use_gzip = true;
		@$client->fetch($url);
		if ($client->results) {	
			return $client->results;
		} else {
			return $this->throw_error("Cannot load URL '$url'");
		}
	}
	
	function printr($string) {
		print "<pre>";
		print_r($string);
		print "</pre>";
	}

}

if (!class_exists('SAXY_Parser') && !class_exists('SAXY_Parser_Base') && !class_exists('SAXY_Custom') && !class_exists('xml')) {
	    /*
	     * Written by Aaron Colflesh: acolflesh@users.sourceforge.net
	     * Released under the LGPL License: http://www.gnu.org/licenses/lgpl.html
	     *
	     * Modified by Dan Coulter: dancoulter@users.sourceforge.net
	     * This version was modified to replace the built in PHP XML Parsing functions.
	     */

	   /** Updated with phpFlickr 1.6 **/

	class SAXY_Custom  {

	    var $result;
	    var $sp;

	    function SAXY_Custom() {
	        $this->sp = new SAXY_Parser();
	        $this->result = array();
	        $this->sp->xml_set_element_handler(array(&$this, "startElement"), array(&$this, "endElement"));
	        $this->sp->xml_set_character_data_handler(array(&$this, "charData"));
	    }
	    
	    function parse($xml)
	    {
	        $this->sp->parse($xml);
	        return $this->sp->endElementHandler[0]->result;
	    }
	    
	    function startElement($parser, $name, $attributes) {
	        $this->level++;
	        $tmp = array();
	        $tmp['tag'] = $name;
	        $tmp['type'] = 'open';
	        $tmp['level'] = $this->level;
	        $tmp['attributes'] = $attributes;
	        $this->result[] = $tmp;

	    } 
	    
	    function endElement($parser, $name) {
	        $tmp = array_pop($this->result);
	        if ($tmp['type'] == 'complete' && $tmp['tag'] == $name) {
	            $this->result[] = $tmp;
	        } elseif ($tmp['type'] == 'open' && $tmp['tag'] == $name) {
	            $tmp['type'] = 'complete';
	            $this->result[] = $tmp;
	        } else {
	            $this->result[] = $tmp;
	            $tmp = array();
	            $tmp['type'] = 'close';
	            $tmp['tag'] = $name;
	            $tmp['level'] = $this->level;
	            $this->result[] = $tmp;
	        }
	        $this->level--;
	    } 
	    
	    function charData($parser, $text) {
	        $tmp = array_pop($this->result);
	        $tmp['type'] = 'complete';
	        $tmp['value'] = $text;
	        $this->result[] = $tmp;
	    }
	    
	} 
		
	class xml  {
	   /** If attributesDirectlyUnderParent is true then a tag's attributes will be merged into
	     * the tag itself rather than under the special '_attributes' key.
	     * For example: 
	     *  false: $tag['_attributes'][$attributeName];
	     *  true: $tag[$attributeName]; OR $tag['_attributes'][$attributeName];
	     *
	     * @var boolean
	     */
	   var $attributesDirectlyUnderParent = false;
	   
	   /** If childTagsDirectlyUnderParent is true then a tag's children will be merged into
	     * the tag itself rather than under the special '_value' key.
	     * For example: 
	     *  false: $tag['_value'][$childTagName];
	     *  true: $tag[$childTagName];
	     *
	     * @var boolean
	     */
	   var $childTagsDirectlyUnderParent = false;
	   
	   var $caseInsensitive = false;
	   
	   var $useSAXYParser = false;

	   var $_replace = array('°','&',"\n","", "Â","£");
	   var $_replaceWith = array('{deg}', '{amp}', '{lf}','{ESC}', "&#194;","{GBP}");
	   //var $_replace = array();
	   //var $_replaceWith = array();

	   function xml($caseInsensitive = false, $attributesDirectlyUnderParent = false, $childTagsDirectlyUnderParent = false)
	   {
	     $this->caseInsensitive = $caseInsensitive;
	     $this->attributesDirectlyUnderParent = $attributesDirectlyUnderParent;
	     $this->childTagsDirectlyUnderParent = $childTagsDirectlyUnderParent;
	   }
	    
	    function useSAXY($useIt = true) {
	        $this->useSAXYParser = $useIt;
	    }
	   
	    function parse($xml)
	    {
	    // This is the original code that uses PHP's crappy XML functions.
	        if ($this->useSAXY) {
	            $this->_parser = new SAXY_Custom;
	            $this->_struct = $this->_parser->parse($xml);
	        } else {
	            $this->_parser = xml_parser_create();
	           
	            $this->input = $xml;
	            $xml = str_replace($this->_replace, $this->_replaceWith, $xml);
	            $xml = str_replace(">{lf}", ">\n", $xml);
	            
	            unset($this->_struct, $this->_index, $this->parsed);
	            xml_set_object($this->_parser, $this);
	            xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, $this->caseInsensitive);
	            xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, 1);
	            
	            xml_parse_into_struct($this->_parser, $xml, $this->_struct, $this->_index);
	        }
	        //*/
	        
	        //* This is the replacement code that uses the SAXY Parser class I defined above.
	        //*/
	        
	        $this->parsed = $this->_postProcess($this->_struct);
	        $this->parsed = array($this->parsed['_name']=>$this->parsed);
	        
	        return $this->parsed;
	    }
	   
	   /* You'll note that I used php's array pointer functions in the _postProcess function.
	    In fact it looks like I made a foreach overly complicated in the 'open' case of the 
	    switch statement. However this is not the case. By using the array pointer functions, 
	    each time you go another call deeper (or shallower) in the recursion it doesn't loose 
	    its place in the structure array.*/
	  function _postProcess() {
	    $item = current($this->_struct);
	    
	    $ret = array('_name'=>$item['tag'], '_attributes'=>array(), '_value'=>null);

	    if (isset($item['attributes']) && count($item['attributes'])>0) {
	        foreach ($item['attributes'] as $key => $data) {
	            if (!is_null($data) && !$this->useSAXYParser) {
	                $item['attributes'][$key] = str_replace($this->_replaceWith, $this->_replace, $item['attributes'][$key]);
	            }
	        }
	      $ret['_attributes'] = $item['attributes'];
	      if ($this->attributesDirectlyUnderParent)
	        $ret = array_merge($ret, $item['attributes']);
	    }

	    if (isset($item['value']) && $item['value'] != null && !$this->useSAXYParser)
	      $item['value'] = str_replace($this->_replaceWith, $this->_replace, $item['value']);
	    
	    switch ($item['type']) {
	      case 'open':
	        $children = array();
	        while (($child = next($this->_struct)) !== FALSE ) {
	          if ($child['level'] <= $item['level'])
	            break;
	          
	          $subItem = $this->_postProcess();
	          
	          if (isset($subItem['_name'])) {
	            if (!isset($children[$subItem['_name']]))
	              $children[$subItem['_name']] = array();
	          
	            $children[$subItem['_name']][] = $subItem;
	          }
	          else {
	            foreach ($subItem as $key=>$value) {
	              if (isset($children[$key])) {
	                if (is_array($children[$key]))
	                  $children[$key][] = $value;
	                else
	                  $children[$key] = array($children[$key], $value);
	              }
	              else {
	                $children[$key] = $value;
	              }
	            }
	          }
	        }
	        
	        if ($this->childTagsDirectlyUnderParent)
	          $ret = array_merge($ret, $this->_condenseArray($children));
	        else
	          $ret['_value'] = $this->_condenseArray($children);
	        
	        break;
	      case 'close':
	        break;
	      case 'complete':
	        if (count($ret['_attributes']) > 0) {
	          if (isset($item['value']))
	            $ret['_value'] = $item['value'];
	        }
	        else {
				if (isset($item['value'])) {
					$ret = array($item['tag']=> $item['value']);
				} else {
					$ret = array($item['tag']=> "");
				}
	        }
	        break;
	    }

	    //added by Dan Coulter

	    
	    /*
	    foreach ($ret as $key => $data) {
	        if (!is_null($data) && !is_array($data)) {
	            $ret[$key] = str_replace($this->_replaceWith, $this->_replace, $ret[$key]);
	        }
	    }
	    */
	    return $ret;
	  }
	  
	  function _condenseArray($array) {
	    $newArray = array();
	    foreach ($array as $key => $value) {
	      if (is_array($value) && count($value)==1)
	        $newArray[$key] = current($value);
	      else
	        $newArray[$key] = $value;
	    }
	    
	    return $newArray;
	  }
	}

	/**
	* SAXY is a non-validating, but lightweight and fast SAX parser for PHP, modelled on the Expat parser
	* @package saxy-xmlparser
	* @subpackage saxy-xmlparser-main
	* @version 1.0
	* @copyright (C) 2004 John Heinstein. All rights reserved
	* @license http://www.gnu.org/copyleft/lesser.html LGPL License
	* @author John Heinstein <johnkarl@nbnet.nb.ca>
	* @link http://www.engageinteractive.com/saxy/ SAXY Home Page
	* SAXY is Free Software
	*
	* This version was modified by Dan Coulter to bring the base and parser files into
	* the same file for the purpose of including it in his project.  Visit the SAXY
	* Home page listed above to download the full version of this class along with
	* documentation.
	**/

	if (!defined('SAXY_INCLUDE_PATH')) {
			define('SAXY_INCLUDE_PATH', (dirname(__FILE__) . "/"));
	}

	/** current version of SAXY */
	define ('SAXY_VERSION', '1.0');

	/** default XML namespace */
	define ('SAXY_XML_NAMESPACE', 'http://www.w3.org/xml/1998/namespace');

	/** saxy parse state, before prolog is encountered */
	define('SAXY_STATE_PROLOG_NONE', 0);
	/** saxy parse state, in processing instruction */
	define('SAXY_STATE_PROLOG_PROCESSINGINSTRUCTION', 1);
	/** saxy parse state, an exclamation mark has been encountered */
	define('SAXY_STATE_PROLOG_EXCLAMATION', 2);
	/** saxy parse state, in DTD */
	define('SAXY_STATE_PROLOG_DTD', 3);
	/** saxy parse state, an inline DTD */
	define('SAXY_STATE_PROLOG_INLINEDTD', 4);
	/** saxy parse state, a comment */
	define('SAXY_STATE_PROLOG_COMMENT', 5);
	/** saxy parse state, processing main document */
	define('SAXY_STATE_PARSING', 6);
	/** saxy parse state, processing comment in main document */
	define('SAXY_STATE_PARSING_COMMENT', 7);

	//SAXY error codes; same as EXPAT error codes
	/** no error */
	define('SAXY_XML_ERROR_NONE', 0);
	/** out of memory error */
	define('SAXY_XML_ERROR_NO_MEMORY', 1);
	/** syntax error */
	define('SAXY_XML_ERROR_SYNTAX', 2);
	/** no elements in document */
	define('SAXY_XML_ERROR_NO_ELEMENTS', 3);
	/** invalid token encountered error */
	define('SAXY_XML_ERROR_INVALID_TOKEN', 4);
	/** unclosed token error */
	define('SAXY_XML_ERROR_UNCLOSED_TOKEN', 5);
	/** partial character error */
	define('SAXY_XML_ERROR_PARTIAL_CHAR', 6);
	/** mismatched tag error */
	define('SAXY_XML_ERROR_TAG_MISMATCH', 7);
	/** duplicate attribute error */
	define('SAXY_XML_ERROR_DUPLICATE_ATTRIBUTE', 8);
	/** junk after document element error */
	define('SAXY_XML_ERROR_JUNK_AFTER_DOC_ELEMENT', 9);
	/** parameter enitity reference error */
	define('SAXY_XML_ERROR_PARAM_ENTITY_REF', 10);
	/** undefined entity error */
	define('SAXY_XML_ERROR_UNDEFINED_ENTITY', 11);
	/** recursive entity error */
	define('SAXY_XML_ERROR_RECURSIVE_ENTITY_REF', 12);
	/** asynchronous entity error */
	define('SAXY_XML_ERROR_ASYNC_ENTITY', 13);
	/** bad character reference error */
	define('SAXY_XML_ERROR_BAD_CHAR_REF', 14);
	/** binary entity reference error */
	define('SAXY_XML_ERROR_BINARY_ENTITY_REF', 15);
	/** attribute external entity error */
	define('SAXY_XML_ERROR_ATTRIBUTE_EXTERNAL_ENTITY_REF', 16);
	/** misplaced processing instruction error */
	define('SAXY_XML_ERROR_MISPLACED_XML_PI', 17);
	/** unknown encoding error */
	define('SAXY_XML_ERROR_UNKNOWN_ENCODING', 18);
	/** incorrect encoding error */
	define('SAXY_XML_ERROR_INCORRECT_ENCODING', 19);
	/** unclosed CDATA Section error */
	define('SAXY_XML_ERROR_UNCLOSED_CDATA_SECTION', 20);
	/** external entity handling error */
	define('SAXY_XML_ERROR_EXTERNAL_ENTITY_HANDLING', 21);

	//require_once(SAXY_INCLUDE_PATH . 'xml_saxy_shared.php');

	/**
	* SAXY_Parser_Base is a base class for SAXY and SAXY Lite
	* @package saxy-xmlparser
	* @version 1.0
	* @copyright (C) 2004 John Heinstein. All rights reserved
	* @license http://www.gnu.org/copyleft/lesser.html LGPL License
	* @author John Heinstein <johnkarl@nbnet.nb.ca>
	* @link http://www.engageinteractive.com/saxy/ SAXY Home Page
	* SAXY is Free Software
	**/

	/** the initial characters of a cdata section */
	define('SAXY_SEARCH_CDATA', '![CDATA[');
	/** the length of the initial characters of a cdata section */
	define('SAXY_CDATA_LEN', 8);
	/** the initial characters of a notation */
	define('SAXY_SEARCH_NOTATION', '!NOTATION');
	/** the initial characters of a doctype */
	define('SAXY_SEARCH_DOCTYPE', '!DOCTYPE');
	/** saxy parse state, just before parsing an attribute */
	define('SAXY_STATE_ATTR_NONE', 0);
	/** saxy parse state, parsing an attribute key */
	define('SAXY_STATE_ATTR_KEY', 1);
	/** saxy parse state, parsing an attribute value */
	define('SAXY_STATE_ATTR_VALUE', 2);

	/**
	* The base SAX Parser class
	*
	* @package saxy-xmlparser
	* @author John Heinstein <johnkarl@nbnet.nb.ca>
	*/
	class SAXY_Parser_Base {
		/** @var int The current state of the parser */
		var $state;
		/** @var int A temporary container for parsed characters */
		var $charContainer;
		/** @var Object A reference to the start event handler */
		var $startElementHandler;
		/** @var Object A reference to the end event handler */
		var $endElementHandler;
		/** @var Object A reference to the data event handler */
		var $characterDataHandler;
		/** @var Object A reference to the CDATA Section event handler */
		var $cDataSectionHandler = null;
		/** @var boolean True if predefined entities are to be converted into characters */
		var $convertEntities = true;
		/** @var Array Translation table for predefined entities */
		var $predefinedEntities = array('&amp;' => '&', '&lt;' => '<', '&gt;' => '>',
								'&quot;' => '"', '&apos;' => "'"); 
		/** @var Array User defined translation table for entities */
		var $definedEntities = array();
		/** @var boolean True if whitespace is to be preserved during parsing. NOT YET IMPLEMENTED! */
		var $preserveWhitespace = false;
		
			
		/**
		* Constructor for SAX parser
		*/					
		function SAXY_Parser_Base() {
			$this->charContainer = '';
		} //SAXY_Parser_Base
		
		/**
		* Sets a reference to the handler for the start element event 
		* @param mixed A reference to the start element handler 
		*/
		function xml_set_element_handler($startHandler, $endHandler) {
			$this->startElementHandler = $startHandler;
			$this->endElementHandler = $endHandler;
		} //xml_set_element_handler
		
		/**
		* Sets a reference to the handler for the data event 
		* @param mixed A reference to the data handler 
		*/
		function xml_set_character_data_handler($handler) {
			$this->characterDataHandler =& $handler;
		} //xml_set_character_data_handler
		
		/**
		* Sets a reference to the handler for the CDATA Section event 
		* @param mixed A reference to the CDATA Section handler 
		*/
		function xml_set_cdata_section_handler($handler) {
			$this->cDataSectionHandler =& $handler;
		} //xml_set_cdata_section_handler
		
		/**
		* Sets whether predefined entites should be replaced with their equivalent characters during parsing
		* @param boolean True if entity replacement is to occur 
		*/
		function convertEntities($truthVal) {
			$this->convertEntities = $truthVal;
		} //convertEntities
		
		/**
		* Appends an array of entity mappings to the existing translation table
		* 
		* Intended mainly to facilitate the conversion of non-ASCII entities into equivalent characters 
		* 
		* @param array A list of entity mappings in the format: array('&amp;' => '&');
		*/
		function appendEntityTranslationTable($table) {
			$this->definedEntities = $table;
		} //appendEntityTranslationTable
		

		/**
		* Gets the nth character from the end of the string
		* @param string The text to be queried 
		* @param int The index from the end of the string
		* @return string The found character
		*/
		function getCharFromEnd($text, $index) {
			$len = strlen($text);
			$char = $text{($len - 1 - $index)};
			
			return $char;
		} //getCharFromEnd
		
		/**
		* Parses the attributes string into an array of key / value pairs
		* @param string The attribute text
		* @return Array An array of key / value pairs
		*/
		function parseAttributes($attrText) {
			$attrText = trim($attrText);	
			$attrArray = array();
			$maybeEntity = false;			
			
			$total = strlen($attrText);
			$keyDump = '';
			$valueDump = '';
			$currentState = SAXY_STATE_ATTR_NONE;
			$quoteType = '';
			
			for ($i = 0; $i < $total; $i++) {								
				$currentChar = $attrText{$i};
				
				if ($currentState == SAXY_STATE_ATTR_NONE) {
					if (trim($currentChar != '')) {
						$currentState = SAXY_STATE_ATTR_KEY;
					}
				}
				
				switch ($currentChar) {
					case "\t":
						if ($currentState == SAXY_STATE_ATTR_VALUE) {
							$valueDump .= $currentChar;
						}
						else {
							$currentChar = '';
						}
						break;
					
					case "\x0B": //vertical tab	
					case "\n":
					case "\r":
						$currentChar = '';
						break;
						
					case '=':
						if ($currentState == SAXY_STATE_ATTR_VALUE) {
							$valueDump .= $currentChar;
						}
						else {
							$currentState = SAXY_STATE_ATTR_VALUE;
							$quoteType = '';
							$maybeEntity = false;
						}
						break;
						
					case '"':
						if ($currentState == SAXY_STATE_ATTR_VALUE) {
							if ($quoteType == '') {
								$quoteType = '"';
							}
							else {
								if ($quoteType == $currentChar) {
									if ($this->convertEntities && $maybeEntity) {
									    $valueDump = strtr($valueDump, $this->predefinedEntities);
										$valueDump = strtr($valueDump, $this->definedEntities);
									}
									
									$keyDump = trim($keyDump);
									$attrArray[$keyDump] = $valueDump;
									$keyDump = $valueDump = $quoteType = '';
									$currentState = SAXY_STATE_ATTR_NONE;
								}
								else {
									$valueDump .= $currentChar;
								}
							}
						}
						break;
						
					case "'":
						if ($currentState == SAXY_STATE_ATTR_VALUE) {
							if ($quoteType == '') {
								$quoteType = "'";
							}
							else {
								if ($quoteType == $currentChar) {
									if ($this->convertEntities && $maybeEntity) {
									    $valueDump = strtr($valueDump, $this->predefinedEntities);
										$valueDump = strtr($valueDump, $this->definedEntities);
									}
									
									$keyDump = trim($keyDump);
									$attrArray[$keyDump] = $valueDump;
									$keyDump = $valueDump = $quoteType = '';
									$currentState = SAXY_STATE_ATTR_NONE;
								}
								else {
									$valueDump .= $currentChar;
								}
							}
						}
						break;
						
					case '&':
						//might be an entity
						$maybeEntity = true;
						$valueDump .= $currentChar;
						break;
						
					default:
						if ($currentState == SAXY_STATE_ATTR_KEY) {
							$keyDump .= $currentChar;
						}
						else {
							$valueDump .= $currentChar;
						}
				}
			}

			return $attrArray;
		} //parseAttributes		
		
		/**
		* Parses character data
		* @param string The character data
		*/
		function parseBetweenTags($betweenTagText) {
			if (trim($betweenTagText) != ''){
				$this->fireCharacterDataEvent($betweenTagText);
			}
		} //parseBetweenTags	
		
		/**
		* Fires a start element event
		* @param string The start element tag name
		* @param Array The start element attributes
		*/
		function fireStartElementEvent($tagName, $attributes) {
			call_user_func($this->startElementHandler, $this, $tagName, $attributes);
		} //fireStartElementEvent		
		
		/**
		* Fires an end element event
		* @param string The end element tag name
		*/
		function fireEndElementEvent($tagName) {
			call_user_func($this->endElementHandler, $this, $tagName);
		} //fireEndElementEvent
		
		/**
		* Fires a character data event
		* @param string The character data
		*/
		function fireCharacterDataEvent($data) {
			if ($this->convertEntities && ((strpos($data, "&") != -1))) {
				$data = strtr($data, $this->predefinedEntities);
				$data = strtr($data, $this->definedEntities);
			}
			
			call_user_func($this->characterDataHandler, $this, $data);
		} //fireCharacterDataEvent	
		
		/**
		* Fires a CDATA Section event
		* @param string The CDATA Section data
		*/
		function fireCDataSectionEvent($data) {
			call_user_func($this->cDataSectionHandler, $this, $data);
		} //fireCDataSectionEvent	
	} //SAXY_Parser_Base

	/**
	* The SAX Parser class
	*
	* @package saxy-xmlparser
	* @subpackage saxy-xmlparser-main
	* @author John Heinstein <johnkarl@nbnet.nb.ca>
	*/
	class SAXY_Parser extends SAXY_Parser_Base {
	    /** @var int The current error number */
		var $errorCode = SAXY_XML_ERROR_NONE;
		/** @var Object A reference to the DocType event handler */
		var $DTDHandler = null;
		/** @var Object A reference to the Comment event handler */
		var $commentHandler = null;
		/** @var Object A reference to the Processing Instruction event handler */
		var $processingInstructionHandler = null;
		/** @var Object A reference to the Start Namespace Declaration event handler */
		var $startNamespaceDeclarationHandler = null;
		/** @var Object A reference to the End Namespace Declaration event handler */
		var $endNamespaceDeclarationHandler = null;
		/** @var boolean True if SAXY takes namespaces into consideration when parsing element tags */
		var $isNamespaceAware = false;
		/** @var array An indexed array containing associative arrays of namespace prefixes mapped to their namespace URIs */
		var $namespaceMap = array();
		/** @var array A stack used to determine when an end namespace event should be fired */
		var $namespaceStack = array();
		/** @var array A track used to track the uri of the current default namespace */
		var $defaultNamespaceStack = array();
		/** @var array A stack containing tag names of unclosed elements */
		var $elementNameStack = array();	

		/**
		* Constructor for SAX parser
		*/
		function SAXY_Parser() {
			$this->SAXY_Parser_Base();
			$this->state = SAXY_STATE_PROLOG_NONE;
		} //SAXY_Parser
		
		/**
		* Sets a reference to the handler for the DocType event 
		* @param mixed A reference to the DocType handler 
		*/
		function xml_set_doctype_handler($handler) {
			$this->DTDHandler =& $handler;
		} //xml_set_doctype_handler
		
		/**
		* Sets a reference to the handler for the Comment event 
		* @param mixed A reference to the Comment handler 
		*/
		function xml_set_comment_handler($handler) {
			$this->commentHandler =& $handler;
		} //xml_set_comment_handler
		
		/**
		* Sets a reference to the handler for the Processing Instruction event 
		* @param mixed A reference to the Processing Instruction handler 
		*/
		function xml_set_processing_instruction_handler($handler) {
			$this->processingInstructionHandler =& $handler;
		} //xml_set_processing_instruction_handler
		
		/**
		* Sets a reference to the handler for the Start Namespace Declaration event
		* @param mixed A reference to the Start Namespace Declaration handler
		*/
		function xml_set_start_namespace_decl_handler($handler) {
			$this->startNamespaceDeclarationHandler =& $handler;
		} //xml_set_start_namespace_decl_handler
		
		/**
		* Sets a reference to the handler for the End Namespace Declaration event
		* @param mixed A reference to the Start Namespace Declaration handler
		*/
		function xml_set_end_namespace_decl_handler($handler) {
			$this->endNamespaceDeclarationHandler =& $handler;
		} //xml_set_end_namespace_decl_handler
		
		/**
		* Specifies whether SAXY is namespace sensitive
		* @param boolean True if SAXY is namespace aware
		*/
		function setNamespaceAwareness($isNamespaceAware) {
			$this->isNamespaceAware =& $isNamespaceAware;
		} //setNamespaceAwareness
		
		/**
		* Returns the current version of SAXY
		* @return Object The current version of SAXY
		*/
		function getVersion() {
			return SAXY_VERSION;
		} //getVersion		
		
		/**
		* Processes the xml prolog, doctype, and any other nodes that exist outside of the main xml document
		* @param string The xml text to be processed
		* @return string The preprocessed xml text
		*/	
		function preprocessXML($xmlText) {
			//strip prolog
			$xmlText = trim($xmlText);
			$startChar = -1;
			$total = strlen($xmlText);
			
			for ($i = 0; $i < $total; $i++) {
				$currentChar = $xmlText{$i};

				switch ($this->state) {
					case SAXY_STATE_PROLOG_NONE:	
						if ($currentChar == '<') {
							$nextChar = $xmlText{($i + 1)};
							
							if ($nextChar == '?')  {
								$this->state = SAXY_STATE_PROLOG_PROCESSINGINSTRUCTION;
								$this->charContainer = '';
							}
							else if ($nextChar == '!') {								
								$this->state = SAXY_STATE_PROLOG_EXCLAMATION;								
								$this->charContainer .= $currentChar;
								break;
							}
							else {
								$this->charContainer = '';
								$startChar  = $i;
								$this->state = SAXY_STATE_PARSING;
								return (substr($xmlText, $startChar));
							}
						}
						
						break;
						
					case SAXY_STATE_PROLOG_EXCLAMATION:
						if ($currentChar == 'D') {
							$this->state = SAXY_STATE_PROLOG_DTD;	
							$this->charContainer .= $currentChar;							
						}
						else if ($currentChar == '-') {
							$this->state = SAXY_STATE_PROLOG_COMMENT;	
							$this->charContainer = '';
						}
						else {
							//will trap ! and add it
							$this->charContainer .= $currentChar;
						}						
						
						break;
						
					case SAXY_STATE_PROLOG_PROCESSINGINSTRUCTION:
						if ($currentChar == '>') {
							$this->state = SAXY_STATE_PROLOG_NONE;							
							$this->parseProcessingInstruction($this->charContainer);							
							$this->charContainer = '';
						}
						else {
							$this->charContainer .= $currentChar;
						}
						
						break;
						
					case SAXY_STATE_PROLOG_COMMENT:
						if ($currentChar == '>') {
							$this->state = SAXY_STATE_PROLOG_NONE;							
							$this->parseComment($this->charContainer);							
							$this->charContainer = '';
						}
						else if ($currentChar == '-') {
							if ((($xmlText{($i + 1)} == '-')  && ($xmlText{($i + 2)} == '>')) || 
								($xmlText{($i + 1)} == '>') ||
								(($xmlText{($i - 1)} == '-')  && ($xmlText{($i - 2)}== '!')) ){
								//do nothing
							}
							else {
								$this->charContainer .= $currentChar;
							}
						}
						else {
							$this->charContainer .= $currentChar;
						}
						
						break;
					
					case SAXY_STATE_PROLOG_DTD:
						if ($currentChar == '[') {
							$this->charContainer .= $currentChar;
							$this->state = SAXY_STATE_PROLOG_INLINEDTD;
						}					
						else if ($currentChar == '>') {
							$this->state = SAXY_STATE_PROLOG_NONE;
							
							if ($this->DTDHandler != null) {
								$this->fireDTDEvent($this->charContainer . $currentChar);
							}
							
							$this->charContainer = '';
						}
						else {
							$this->charContainer .= $currentChar;
						}	
						
						break;
						
					case SAXY_STATE_PROLOG_INLINEDTD:
						$previousChar = $xmlText{($i - 1)};

						if (($currentChar == '>') && ($previousChar == ']')){
							$this->state = SAXY_STATE_PROLOG_NONE;
							
							if ($this->DTDHandler != null) {
								$this->fireDTDEvent($this->charContainer . $currentChar);
							}
							
							$this->charContainer = '';
						}
						else {
							$this->charContainer .= $currentChar;
						}	
						
						break;
					
				}
			}
		} //preprocessXML

		/**
		* The controlling method for the parsing process 
		* @param string The xml text to be processed
		* @return boolean True if parsing is successful
		*/
		function parse ($xmlText) {
			$xmlText = $this->preprocessXML($xmlText);			
			$total = strlen($xmlText);

			for ($i = 0; $i < $total; $i++) {
				$currentChar = $xmlText{$i};

				switch ($this->state) {
					case SAXY_STATE_PARSING:
						switch ($currentChar) {
							case '<':
								if (substr($this->charContainer, 0, SAXY_CDATA_LEN) == SAXY_SEARCH_CDATA) {
									$this->charContainer .= $currentChar;
								}
								else {
									$this->parseBetweenTags($this->charContainer);
									$this->charContainer = '';
								}						
								break;
								
							case '-':
								if (($xmlText{($i - 1)} == '-') && ($xmlText{($i - 2)} == '!')
									&& ($xmlText{($i - 3)} == '<')) {
									$this->state = SAXY_STATE_PARSING_COMMENT;
									$this->charContainer = '';
								}
								else {
									$this->charContainer .= $currentChar;
								}
								break;

							case '>':
								if ((substr($this->charContainer, 0, SAXY_CDATA_LEN) == SAXY_SEARCH_CDATA) &&
									!(($this->getCharFromEnd($this->charContainer, 0) == ']') &&
									($this->getCharFromEnd($this->charContainer, 1) == ']'))) {
									$this->charContainer .= $currentChar;
								}
								else {
									$this->parseTag($this->charContainer);
									$this->charContainer = '';
								}
								break;
								
							default:
								$this->charContainer .= $currentChar;
						}
						
						break;
						
					case SAXY_STATE_PARSING_COMMENT:
						switch ($currentChar) {
							case '>':
								if (($xmlText{($i - 1)} == '-') && ($xmlText{($i - 2)} == '-')) {
									$this->fireCommentEvent(substr($this->charContainer, 0, 
														(strlen($this->charContainer) - 2)));
									$this->charContainer = '';
									$this->state = SAXY_STATE_PARSING;
								}
								else {
									$this->charContainer .= $currentChar;
								}
								break;
							
							default:
								$this->charContainer .= $currentChar;
						}
						
						break;
				}
			}	

			return ($this->errorCode == 0);
		} //parse

		/**
		* Parses an element tag
		* @param string The interior text of the element tag
		*/
		function parseTag($tagText) {
			$tagText = trim($tagText);
			$firstChar = $tagText{0};
			$myAttributes = array();

			switch ($firstChar) {
				case '/':
					$tagName = substr($tagText, 1);				
					$this->_fireEndElementEvent($tagName);
					break;
				
				case '!':
					$upperCaseTagText = strtoupper($tagText);
				
					if (strpos($upperCaseTagText, SAXY_SEARCH_CDATA) !== false) { //CDATA Section
						$total = strlen($tagText);
						$openBraceCount = 0;
						$textNodeText = '';
						
						for ($i = 0; $i < $total; $i++) {
							$currentChar = $tagText{$i};
							
							if (($currentChar == ']') && ($tagText{($i + 1)} == ']')) {
								break;
							}
							else if ($openBraceCount > 1) {
								$textNodeText .= $currentChar;
							}
							else if ($currentChar == '[') { //this won't be reached after the first open brace is found
								$openBraceCount ++;
							}
						}
						
						if ($this->cDataSectionHandler == null) {
							$this->fireCharacterDataEvent($textNodeText);
						}
						else {
							$this->fireCDataSectionEvent($textNodeText);
						}
					}
					else if (strpos($upperCaseTagText, SAXY_SEARCH_NOTATION) !== false) { //NOTATION node, discard
						return;
					}
					/*
					else if (substr($tagText, 0, 2) == '!-') { //comment node
						if ($this->commentHandler != null) {
							$this->fireCommentEvent(substr($tagText, 3, (strlen($tagText) - 5)));
						}
					}
					*/
					break;
					
				case '?': 
					//Processing Instruction node
					$this->parseProcessingInstruction($tagText);
					break;
					
				default:				
					if ((strpos($tagText, '"') !== false) || (strpos($tagText, "'") !== false)) {
						$total = strlen($tagText);
						$tagName = '';

						for ($i = 0; $i < $total; $i++) {
							$currentChar = $tagText{$i};
							
							if (($currentChar == ' ') || ($currentChar == "\t") ||
								($currentChar == "\n") || ($currentChar == "\r") ||
								($currentChar == "\x0B")) {
								$myAttributes = $this->parseAttributes(substr($tagText, $i));
								break;
							}
							else {
								$tagName .= $currentChar;
							}
						}

						if (strrpos($tagText, '/') == (strlen($tagText) - 1)) { //check $tagText, but send $tagName
							$this->_fireStartElementEvent($tagName, $myAttributes);
							$this->_fireEndElementEvent($tagName);
						}
						else {
							$this->_fireStartElementEvent($tagName, $myAttributes);
						}
					}
					else {
						if (strpos($tagText, '/') !== false) {
							$tagText = trim(substr($tagText, 0, (strrchr($tagText, '/') - 1)));
							$this->_fireStartElementEvent($tagText, $myAttributes);
							$this->_fireEndElementEvent($tagText);
						}
						else {
							$this->_fireStartElementEvent($tagText, $myAttributes);
						}
					}					
			}
		} //parseTag

	 	/**
		* Fires a start element event and pushes the element name onto the elementName stack
		* @param string The start element tag name
		* @param Array The start element attributes
		*/
		function _fireStartElementEvent($tagName, &$myAttributes) {
		    $this->elementNameStack[] = $tagName;
		    
		    if ($this->isNamespaceAware) {
				$this->detectStartNamespaceDeclaration($myAttributes);
				$tagName = $this->expandNamespacePrefix($tagName);
				
				$this->expandAttributePrefixes($myAttributes);
		    }
		    
		    $this->fireStartElementEvent($tagName, $myAttributes);
		} //_fireStartElementEvent
		
		/**
		* Expands attribute prefixes to full namespace uri
		* @param Array The start element attributes
		*/
		function expandAttributePrefixes(&$myAttributes) {
		    $arTransform = array();
		    
		    foreach ($myAttributes as $key => $value) {
		        if (strpos($key, 'xmlns') === false) {
		            if (strpos($key, ':') !== false) {
		                $expandedTag = $this->expandNamespacePrefix($key);
		                $arTransform[$key] = $expandedTag;
		            }
		        }
		    }
		    
		    foreach ($arTransform as $key => $value) {
		        $myAttributes[$value] = $myAttributes[$key];
		        unset($myAttributes[$key]);
		    }
		} //expandAttributePrefixes
		
		/**
		* Expands the namespace prefix (if one exists) to the full namespace uri
		* @param string The tagName with the namespace prefix
		* @return string The tagName, with the prefix expanded to the namespace uri
		*/
		function expandNamespacePrefix($tagName) {
		    $stackLen = count($this->defaultNamespaceStack);
		    $defaultNamespace = $this->defaultNamespaceStack[($stackLen - 1)];

		    $colonIndex = strpos($tagName, ':');

		    if ($colonIndex !== false) {
				$prefix = substr($tagName, 0, $colonIndex);
				
				if ($prefix != 'xml') {
		        	$tagName = $this->getNamespaceURI($prefix) . substr($tagName, $colonIndex);
				}
				else {
					$tagName = SAXY_XML_NAMESPACE . substr($tagName, $colonIndex);
				}
		    }
		    else if ($defaultNamespace != '') {
		        $tagName = $defaultNamespace . ':' . $tagName;
		    }

		    return $tagName;
		} //expandNamespacePrefix
		
		/**
		* Searches the namespaceMap for the specified prefix, and returns the full namespace URI
		* @param string The namespace prefix
		* @return string The namespace uri
		*/
		function getNamespaceURI($prefix) {
		    $total = count($this->namespaceMap);
		    $uri = $prefix; //in case uri can't be found, just send back prefix
		                    //should really generate an error, but worry about this later
			//reset($this->namespaceMap);

		    for ($i = ($total - 1); $i >= 0; $i--) {
		        $currMap =& $this->namespaceMap[$i];

		        if (isset($currMap[$prefix])) {
		            $uri = $currMap[$prefix];
		            break;
		        }
		    }

		    return $uri;
		} //getNamespaceURI
		
		/**
		* Searches the attributes array for an xmlns declaration and fires an event if found
		* @param Array The start element attributes
		*/
		function detectStartNamespaceDeclaration(&$myAttributes) {
		    $namespaceExists = false;
		    $namespaceMapUpper = 0;
		    $userDefinedDefaultNamespace = false;
		    $total = count($myAttributes);
		    
		    foreach ($myAttributes as $key => $value) {
		        if (strpos($key, 'xmlns') !== false) {
		            //add an array to store all namespaces for the current element
		            if (!$namespaceExists) {
						$this->namespaceMap[] = array();
						$namespaceMapUpper = count($this->namespaceMap) - 1;
		            }

					//check for default namespace override, i.e. xmlns='...'
					if (strpos($key, ':') !== false) {
					    $prefix = $namespaceMapKey = substr($key, 6);
					    $this->namespaceMap[$namespaceMapUpper][$namespaceMapKey] = $value;
					}
					else {
					    $prefix = '';
						$userDefinedDefaultNamespace = true;
						
						//if default namespace '', store in map using key ':'
						$this->namespaceMap[$namespaceMapUpper][':'] = $value;
						$this->defaultNamespaceStack[] = $value;
					}
					
		            $this->fireStartNamespaceDeclarationEvent($prefix, $value);
		            $namespaceExists = true;
					
					unset($myAttributes[$key]);
		        }
		    }
		    
		    //store the default namespace (inherited from the parent elements so grab last one)
			if (!$userDefinedDefaultNamespace) {
			    $stackLen = count($this->defaultNamespaceStack);
			    if ($stackLen == 0) {
			        $this->defaultNamespaceStack[] = '';
			    }
			    else {
					$this->defaultNamespaceStack[] =
						$this->defaultNamespaceStack[($stackLen - 1)];
			    }
			}
			
		    $this->namespaceStack[] = $namespaceExists;
		} //detectStartNamespaceDeclaration
		
		/**
		* Fires an end element event and pops the element name from the elementName stack
		* @param string The end element tag name
		*/
		function _fireEndElementEvent($tagName) {
		    $lastTagName = array_pop($this->elementNameStack);

			//check for mismatched tag error
			if ($lastTagName != $tagName) {
				$this->errorCode = SAXY_XML_ERROR_TAG_MISMATCH;
			}

			if ($this->isNamespaceAware) {
			    $tagName = $this->expandNamespacePrefix($tagName);
			    $this->fireEndElementEvent($tagName);
				$this->detectEndNamespaceDeclaration();
				$defaultNamespace = array_pop($this->defaultNamespaceStack);
			}
			else {
			    $this->fireEndElementEvent($tagName);
			}
		} //_fireEndElementEvent

		/**
		* Determines whether an end namespace declaration event should be fired
		*/
		function detectEndNamespaceDeclaration() {
		    $isNamespaceEnded = array_pop($this->namespaceStack);
		    
		    if ($isNamespaceEnded) {
				$map = array_pop($this->namespaceMap);
				
		        foreach ($map as $key => $value) {
		            if ($key == ':') {
						$key = '';
		            }
					$this->fireEndNamespaceDeclarationEvent($key);
		        }
			}
		} //detectEndNamespaceDeclaration

		/**
		* Parses a processing instruction
		* @param string The interior text of the processing instruction
		*/
		function parseProcessingInstruction($data) {
			$endTarget = 0;
			$total = strlen($data);
			
			for ($x = 2; $x < $total; $x++) {
				if (trim($data{$x}) == '') {
					$endTarget = $x;
					break;
				}
			}
			
			$target = substr($data, 1, ($endTarget - 1));
			$data = substr($data, ($endTarget + 1), ($total - $endTarget - 2));
		
			if ($this->processingInstructionHandler != null) {
				$this->fireProcessingInstructionEvent($target, $data);
			}
		} //parseProcessingInstruction
		
		/**
		* Parses a comment
		* @param string The interior text of the comment
		*/
		function parseComment($data) {
			if ($this->commentHandler != null) {
				$this->fireCommentEvent($data);
			}
		} //parseComment
		
		/**
		* Fires a doctype event
		* @param string The doctype data
		*/
		function fireDTDEvent($data) {
			call_user_func($this->DTDHandler, $this, $data);
		} //fireDTDEvent
		
		/**
		* Fires a comment event
		* @param string The text of the comment
		*/
		function fireCommentEvent($data) {
			call_user_func($this->commentHandler, $this, $data);
		} //fireCommentEvent
		
		/**
		* Fires a processing instruction event
		* @param string The processing instruction data
		*/
		function fireProcessingInstructionEvent($target, $data) {
			call_user_func($this->processingInstructionHandler, $this, $target, $data);
		} //fireProcessingInstructionEvent
		
		/**
		* Fires a start namespace declaration event
		* @param string The namespace prefix
		* @param string The namespace uri
		*/
		function fireStartNamespaceDeclarationEvent($prefix, $uri) {
			call_user_func($this->startNamespaceDeclarationHandler, $this, $prefix, $uri);
		} //fireStartNamespaceDeclarationEvent
		
		/**
		* Fires an end namespace declaration event
		* @param string The namespace prefix
		*/
		function fireEndNamespaceDeclarationEvent($prefix) {
			call_user_func($this->endNamespaceDeclarationHandler, $this, $prefix);
		} //fireEndNamespaceDeclarationEvent
		
		/**
		* Returns the current error code
		* @return int The current error code
		*/
		function xml_get_error_code() {
			return $this->errorCode;
		} //xml_get_error_code
		
		/**
		* Returns a textual description of the error code
		* @param int The error code
		* @return string The error message
		*/
		function xml_error_string($code) {
			switch ($code) {
			    case SAXY_XML_ERROR_NONE:
			        return "No error";
			        break;
				case SAXY_XML_ERROR_NO_MEMORY:
				    return "Out of memory";
			        break;
				case SAXY_XML_ERROR_SYNTAX:
				    return "Syntax error";
			        break;
				case SAXY_XML_ERROR_NO_ELEMENTS:
				    return "No elements in document";
			        break;
				case SAXY_XML_ERROR_INVALID_TOKEN:
				    return "Invalid token";
			        break;
				case SAXY_XML_ERROR_UNCLOSED_TOKEN:
				    return "Unclosed token";
			        break;
				case SAXY_XML_ERROR_PARTIAL_CHAR:
				    return "Partial character";
			        break;
				case SAXY_XML_ERROR_TAG_MISMATCH:
				    return "Tag mismatch";
			        break;
				case SAXY_XML_ERROR_DUPLICATE_ATTRIBUTE:
				    return "Duplicate attribute";
			        break;
				case SAXY_XML_ERROR_JUNK_AFTER_DOC_ELEMENT:
				    return "Junk encountered after document element";
			        break;
				case SAXY_XML_ERROR_PARAM_ENTITY_REF:
				    return "Parameter entity reference error";
			        break;
				case SAXY_XML_ERROR_UNDEFINED_ENTITY:
				    return "Undefined entity";
			        break;
				case SAXY_XML_ERROR_RECURSIVE_ENTITY_REF:
				    return "Recursive entity reference";
			        break;
				case SAXY_XML_ERROR_ASYNC_ENTITY:
				    return "Asynchronous internal entity found in external entity";
			        break;
				case SAXY_XML_ERROR_BAD_CHAR_REF:
				    return "Bad character reference";
			        break;
				case SAXY_XML_ERROR_BINARY_ENTITY_REF:
					return "Binary entity reference";
			        break;
				case SAXY_XML_ERROR_ATTRIBUTE_EXTERNAL_ENTITY_REF:
				    return "Attribute external entity reference";
			        break;
				case SAXY_XML_ERROR_MISPLACED_XML_PI:
				    return "Misplaced processing instruction";
			        break;
				case SAXY_XML_ERROR_UNKNOWN_ENCODING:
				    return "Unknown encoding";
			        break;
				case SAXY_XML_ERROR_INCORRECT_ENCODING:
					return "Incorrect encoding";
			        break;
				case SAXY_XML_ERROR_UNCLOSED_CDATA_SECTION:
				    return "Unclosed CDATA Section";
			        break;
				case SAXY_XML_ERROR_EXTERNAL_ENTITY_HANDLING:
				    return "Problem in external entity handling";
			        break;
				default:
				    return "No definition for error code " . $code;
			        break;
			}
		} //xml_error_string

	} //SAXY_Parser
}
?>