<?php
/**
 * Tracking common interface libray
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 0.1.7
 * @build 20080618
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @license GNU General Public License (GPL)
 */
/**
 * Functions used to handle interface
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0.0
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @since 0.1.7
 */
class CommonView
{
	/**
	 * Builds a block with links
	 *
	 * @param array $block
	 * @return array
	 */
	private function setActionBlock($block, $float = true) {
		foreach($block as $key => $value) {
			if (isset($value['icon'])) {
				$i = $this->setIcons($value['icon']) . '&nbsp;';
			} else {
				$i = '';
			}
			if (isset($value['action_prefix'])) {
				$p = $value['action_prefix'];
				if (APP_INDICATIVE == 'cs') {
					$v = str_replace($value['action_prefix'], 'id'
						, $value['view']);
				} else {
					$v = str_replace($value['action_prefix'], 'Id'
						, $value['view']);
				}
			} else {
				$p = 'list';
				$v = $value['view'];
			}
			$sReturn[] = $this->setStringIntoTag($i . $key, 'a'
				, array('href' => '?view=' . $p . '_' . $v));
		}
		if ($float) {
			$f = 'left';
		} else {
			$f = 'none';
		}
		return $this->setStringIntoTag(
			implode(PHP_EOL . '<br/>', $sReturn)
			, 'span', array('style' => 'float: ' . $f
				. '; padding-left: 10px; '));
	}
	/**
	 * Builds a block with links
	 *
	 * @param array $links
	 * @return array
	 */
	public function setActionBlockMulti($links, $float = true) {
		require_once 'action/actions.inc.php';
		$aa = new AvailableActions();
		foreach($links as $value) {
			$elem = $aa->getAcOpArray($value);
			foreach($elem as $key2 => $value2) {
				if (isset($value2['action_prefix'])) {
					$aReturn[$key2] = array(
						'icon' => $value2['icon']
						, 'action_prefix' => $value2['action_prefix']
						, 'view' => $value);
				} else {
					$aReturn[$key2] = array(
						'icon' => $value2['icon']
						, 'view' => $value);
				}
			}
		}
		return $this->setActionBlock($aReturn, $float);
	}
    /**
     * Replace space with break line for each key element
     *
     * @param array $aElements
     * @return array
     */
    public function setArray2ArrayKbr($aElements)
    {
        foreach ($aElements as $key => $value) {
            $aReturn[str_replace(' ', '<br/>', $key)] = $value;
        }
        return $aReturn;
    }
    /**
     * Replace space with break line for each key element
     *
     * @param array $aElements
     * @return array
     */
    public function setArray2ArrayVbr($aElements)
    {
        foreach ($aElements as $key => $value) {
            $aReturn[$key] = str_replace(' ', '<br/>', $value);
        }
        return $aReturn;
    }
	/**
	 * Transforms an array into usable filters
	 *
	 * @param array $entry_array
	 * @param string $reference_table
	 * @return array
	 */
	function setArray2FilterValues($entry_array, $reference_table = '') {
		$filters = "";
		if ($reference_table != '') {
		    $reference_table = "`".$reference_table."`.";
		}
		foreach($entry_array as $key => $value) {
			if (is_array($value)) {
				$filters2 = "";
				foreach($value as $value2) {
					if ($value2 != '') {
					    if ($filters2 != "") {
					    	$filters2 .= ", ";
					    }
						$filters2 .= "'".$value2."'";
					}
				}
				if ($filters2 != '') {
					if ($filters != "") {
						$filters .= " AND ";
					}
				    $filters .= " ".$reference_table."`".$key."` IN (".$filters2.")";
				}
			} else {
				if (($filters != "") && (!in_array($value, array("", '', '%%')))) {
					$filters .= " AND ";
				}
				if (!in_array($value, array('', '%%'))) {
					if ((substr($value, 0, 1) == '%') && (substr($value, -1) == '%')) {
						$filters .= " ".$key." LIKE '".$value."'";
					} else {
						$filters .= " ".$key." = '".$value."'";
					}
				} /*else {
					$filters .= " ".$key." LIKE '%'"; // elimintat la 12.09.2007
				}*/
			}
		}
		return $filters;
	}
	/**
	 * Builds a <select> based on a given array
	 *
	 * @version 20080618
	 * @param array $aElements
	 * @param string/array $sDefaultValue
	 * @param string $select_name
	 * @param array $features_array
	 * @return string
	 */
	public function setArray2Select($aElements, $sDefaultValue, $select_name, $features_array = null) {
		if (in_array($aElements, array(null, '', '??'))) {
		    return "";
		}
	    if (isset($features_array['id_no'])) {
	    	$select_id = str_replace(array('[',']'), array('', '')
	    		, $select_name) . $features_array['id_no'];
	    } else {
			$select_id = str_replace(array('[',']')
				, array('', ''), $select_name);
		}
		$temporary_string = '1';
		if (is_array($features_array)) {
			if (in_array('size', array_keys($features_array))) {
				if ($features_array['size'] == 0) {
					$temporary_string = count($aElements);
				} else {
				    $temporary_string = min(count($aElements)
				    	, $features_array['size']
				    );
				}
			}
		    if ((in_array('include_null', $features_array))
		    	&& ($temporary_string != '1')) {
		    	$temporary_string += 1;
		    }
		}
		if (strpos($select_name, '[]') === false) {
			$select_id = '" id="' . $select_id;
		} else {
            if (isset($features_array['id_no'])) {
                $select_id = '" id="' . $select_id;
                unset($features_array['id_no']);
            } else {
                $select_id = '';
            }
		}
		$string2return = '<select name="'
			. $select_name
			. $select_id
			. '" size="'
			. $temporary_string
			. '"';
		if (is_array($features_array)) {
			if (in_array('additional_javascript_action'
				, array_keys($features_array))) {
				$temporary_string =
					@$features_array['additional_javascript_action'];
			} else {
				$temporary_string = '';
			}
			if (in_array('autosubmit', $features_array)) {
				$string2return .= ' onchange="javascript:'
					. $temporary_string
					. 'submit();"';
			} else {
				if ($temporary_string != '') {
					$string2return .= ' onchange="javascript:'
						. $temporary_string
						. '"';
				}
			}
			if (in_array('disabled', $features_array)) {
				$string2return .= ' disabled="disabled"';
			}
			if (in_array('hidden', $features_array)) {
				$string2return .= ' style="visibility: hidden;"';
			}
			if (in_array('multiselect', $features_array)) {
				$string2return .= ' multiple="multiple"';
			}
		}
		$string2return .= '>';
		if (is_array($features_array)) {
		    /*if (in_array('grouping', $features_array)) {
		    	$current_group = '';
		    }*/
		    if (in_array('include_null', $features_array)) {
		    	$string2return .= '<option value="">&nbsp;</option>';
		    }
		    if (isset($features_array['defaultValue_isSubstring'])) {
		    	$default_value_array = explode(
		    		$features_array['defaultValue_isSubstring']
		    		, $sDefaultValue
		    	);
		    }
		}
		$current_group = null;
		foreach($aElements as $key => $value) {
		    if (isset($features_array['grouping'])) {
		        $temporary_string = substr($value, 0
		        	, strpos($value, $features_array['grouping'])+1);
		        if ($current_group != $temporary_string) {
		            if ($current_group != '') {
		            	$string2return .= '</optgroup>';
		            }
		            $current_group = $temporary_string;
		            $string2return .= '<optgroup label="'
		            	. str_replace($features_array['grouping']
		            		, '', $current_group)
		            	. '">';
		        }
		    } else {
		        $current_group = '';
		    }
			$string2return .= '<option value="' . $key . '"';
			if (is_array($sDefaultValue)) {
					if (in_array($key, $sDefaultValue)) {
						$string2return .= ' selected="selected"';
					}
				} else {
					if ($key == $sDefaultValue) {
						$string2return .= ' selected="selected"';
					}
					if (is_array(@$default_value_array)) {
						if (in_array($key, $default_value_array)) {
							$string2return .= ' selected="selected"';
						}
					}
			}
			$string2return .= '>'
				. str_replace(array('&', $current_group), array('&amp;', '')
					, $value)
				. '</option>';
		}
		if (isset($features_array['grouping'])) {
		    if ($current_group != '') {
		        $string2return .= '</optgroup>';
		    }
		}
		$string2return .= '</select>';
		return $string2return;
	}
	/**
	 * Converts an array to a string
	 *
	 * @version 20080423
	 * @param string $sSeparator
	 * @param array $aElements
	 * @param string $sPrefixSufix
	 * @return string
	 */
	public function setArray2String2($sSeparator, $aElements, $sPrefixSufix = "")
	{
	    $sReturn = null;
	    foreach($aElements as $value) {
		    if (is_array($value)) {
		        foreach($value as $value2) {
		            if ($sReturn != '') {
		            	$sReturn .= $sSeparator;
		            }
		            if ($sPrefixSufix != '') {
		            	$sReturn .= $sPrefixSufix;
		            }
		            $sReturn .= $value2;
		            if ($sPrefixSufix != '') {
		            	$sReturn .= $sPrefixSufix;
		            }
		        }
		    } else {
		        if ($sReturn != '') {
		        	$sReturn .= $sSeparator;
		        }
		        if ($sPrefixSufix != '') {
		        	$sReturn .= $sPrefixSufix;
		        }
		        $sReturn .= $value;
		        if ($sPrefixSufix != '') {
		        	$sReturn .= $sPrefixSufix;
		        }
		    }
		}
		return $sReturn;
	}
	/**
	 * Converts an array to string
	 *
	 * @version 20080504
	 * @param string $sSeparator
	 * @param array $aElements
	 * @return string
	 */
	public function setArray2String4Url($sSeparator, $aElements, $aExceptedElements = array('')) {
		$sReturn = null;
		$counter = 0;
		if (!is_array($aElements)) {
		    return false;
		}
		reset($aElements);
		while (list($key, $val) = each($aElements)) {
		    if (!in_array($key, $aExceptedElements)) {
		        $sSeparator_adding = false;
		        if (is_array($aElements[$key])) {
		            $aCounter = count($aElements[$key]);
		            if ($aCounter > 1) {
		                for ($counter2 = 0;
		                	$counter2 < $aCounter;
		                	$counter2++) {
		                    if ($val[$counter2] != '') {
		                        $sReturn .= $key . '[]='
		                        	. str_replace(' ', '%20', $val[$counter2]);
    							if ($counter2 < ($aCounter - 1)) {
    								$sReturn .= $sSeparator;
    							}
    						    $sSeparator_adding = true;
		                    }
		                }
					} else {
						if ($val[0] != '') {
						    $sReturn .= $key . '[]='
						    	. str_replace(' ', '%20', $val[0]);
						    $sSeparator_adding = true;
						}
					}
				} else {
					if ($val != '') {
					    $sReturn .= $key . '='
					    	. str_replace(' ', '%20', $val);
    				    $sSeparator_adding = true;
					}
				}
				if ($sSeparator_adding) {
					$sReturn .= $sSeparator;
				}
				$counter += 1;
			}
		}
		// trim last separator
		if (substr($sReturn, -strlen($sSeparator)) == $sSeparator) {
		    $sReturn = substr($sReturn, 0, (strlen($sReturn)-strlen($sSeparator)));
		}
		return $sReturn;
	}
	/**
	 * Build an array with keys from values of a given array
	 *
	 * @version 20080516
	 * @param array $aElements
	 * @param string $sName
	 * @return array
	 */
    public function setArrayValues2Keys($aElements, $value)
    {
        foreach($aElements as $value) {
            $aReturn[$value] = $value;
        }
        return $aReturn;
    }
	/**
	 * Displays a checkbox and glued label
	 *
	 * @version 20080423
	 * @param string $sName
	 * @param string $sMessage
	 * @param boolean $bChecked
	 * @return string
	 */
	public function setCheckBoxAndLabel($sName, $sMessage, $bChecked = true){
		if ($bChecked) {
			$sCheckingMarkup = ' checked="checked"';
		} else {
			$sCheckingMarkup = '';
		}
		return '<input type="checkbox" name="'
		    . $sName . '" id="' . $sName . '"'
		    . $sCheckingMarkup
			. ' />'
			. '<label for="' . $sName . '">'
			. $sMessage
			. '</label>';

	}
	public function setClearBoth1px() {
		return PHP_EOL . $this->setStringIntoTag('&nbsp;', 'div'
			, array('class' => 'clear_both'));
	}
	/**
	 * Returns proper result from a mathematical division
	 *
	 * in order to avoid Zero division erorr or Infinite results
	 * @version 20080423
	 * @param float $fAbove
	 * @param float $fBelow
	 * @param mixed $mArguments
	 * @return float
	 */
	public function setDividedResult($fAbove, $fBelow, $mArguments = 0)
	{
	    // prevent infinite result
		if ($fAbove == 0) {
			return 0;
		}
		// prevent division by 0
		if ($fBelow == 0) {
			return 0;
		}
		if (is_array($mArguments)) {
		    if ($mArguments[2] == '') {
		        return number_format( ($fAbove / $fBelow)
    			    , $mArguments[0]
    			    , $mArguments[1]);
		    } else {
    			return number_format( ($fAbove / $fBelow)
    			    , $mArguments[0]
    			    , $mArguments[1]
    			    , $mArguments[2] );
		    }
		} else {
            //echo  '<hr/>'.  $fAbove .'/'. $fBelow .' by '. $mArguments . '<hr/>';
	        return round( ($fAbove / $fBelow), $mArguments);
	    }
	}
	/**
	 * Builds a structured message
	 *
	 * @version 20080423
	 * @param int $iIndendation
	 * @param string $sType
	 * @param string $sMessage
	 */
	public function setFeedback($iIndendation, $sType, $sMessage)
	{
        if (!is_numeric($iIndendation)) {
            $iIndendation = 0;
        }
        if ($sType == 'error') {
            $format = 'background-color:red; color: white;';
        } else {
            $format = '';
        }
		return "<div style='padding-left: "
			. (20*$iIndendation) . "px;"
            . $format . "'>"
			. $this->setIcons($sType)
			. "&nbsp;" . $sMessage
			. "</div>";
	}
	/**
	 * Returns a fieldset with links inside
	 *
	 * @param string $title
	 * @param array $links
	 * @return string
	 */
	public function setFieldsetTitleLinks($title, $links) {
		$e = ':: ';
		foreach($links as $key => $value) {
			if (is_array($value)) {
				$link[] = $e . $this->setStringIntoTag($key
					, 'a', array(
						'href' => str_replace(' ', '%20', $value['href'])
						, 'title' => $value['title']));
			} else {
				$link[] = $e . $this->setStringIntoTag($key
					, 'a', array('href' => str_replace(' ', '%20', $value)));
			}
		}
		return $this->setStringIntoTag(
			$this->setStringIntoTag($title, 'legend'
				, array('class' => 'box_legend'))
			. PHP_EOL . implode('<br/>' . PHP_EOL, $link)
			, 'fieldset', array('class' => 'box_color'));
	}
    /**
     * Returns buttons usefull in forms
     *
	 * @version 20080423
     * @param string $sType
     * @return string
     */
    public function setFormButton($sType)
    {
        switch(strtolower($sType)) {
            case 'reset':
                $sReturn = '<input type="reset" value="Reset" />'
	                . '<span class="spacer70">&nbsp;</span>';
                break;
            case 'cancel':
                $sReturn = '<input type="button" value="Cancel" '
                	. ' onClick="javascript:history.back();"' . '/>';
                break;
            case 'submit':
            default:
                $sReturn = '<input type="submit" value="OK" />';
                break;
        }
        return '&nbsp;&nbsp;'.$sReturn;
    }
	/**
	 * Displays a regular line within a form
	 *
	 * @version 20080423
	 * @param string $label
	 * @param string $field
	 * @param string $value
	 * @param string $type
	 * @param array $features
	 * @return string
	 */
	public function setFormLine($label, $field, $value, $type = 'text', $features = null)
    {
        switch($type) {
            case 'text':
    			$string2return = '<tr><td>' . $label . '</td>'
    				. '<td><input type="' . $type
    				. '" name="' . $field
    				. '" value="' . $value
    				. '" size="' . $features['size']
    				. '" maxlength="' . $features['maxlength']
    				. '"/></td></tr>';
                break;
            case 'hidden':
    			$string2return = '<tr><td>&nbsp;</td>'
    				. '<td><input type="' . $type
    				. '" name="' . $field
    				. '" value="' . $value
    				. '" size="' . $features['size']
    				. '" maxlength="' . $features['maxlength']
    				. '"/></td></tr>';
                break;
            case 'select':
    			$string2return = '<tr><td>' . $label . '</td>'
    				. '<td>' . $this->setArray2Select(
                        $features['values'], $value, $field
                        , array('size' => 1))
    				. '</td></tr>';
                break;
            default:
                $string2return = null;
                break;
		}
        return $string2return;
	}
	/**
	 * Breaks a long string into pieces
	 *
	 * @version 20090114
	 * @param string $sAction
	 * @return array
	 */
	public function setGivenElements($sAction, $sSeparator = '_') {
		$result = explode($sSeparator, $sAction);
        return $result;
	}
	/**
	 * Displays a highlited text or link
	 *
	 * @version 20080423
	 * @param string $sAction
	 * @param string $sName
	 * @param array $aFeatures
	 * @return string
	 */
	public function setHighlightedLink($sAction, $sName, $aFeatures = null)
    {
        if (isset($_GET)) {
            $sTemplate = $this->setArray2String4Url('&amp;', $_GET);
        } else {
            $sTemplate = null;
        }
		if ($sTemplate == $sAction) {
			$sReturn = '<span class="cell_footer">&nbsp;&nbsp;&nbsp;'
			    . $sName . '</span>';
		} else {
			$sReturn  = '<a href="?' . $sAction . '"';
			if (isset($aFeatures['title'])) {
				$sReturn  .= ' title="' . $aFeatures['title'] . '"';
			}
			$sReturn  .= '>' . $sName . '</a>';
		}
		return $sReturn;
	}
	/**
	 * Returns apropriate image base on predefined labels
	 *
	 * @version 20080423
	 * @param string $sType
	 * @return string
	 */
	public function setIcons($sType)
	{
		return '<img src="images/' . $sType . '.gif" alt="' . $sType . '" />';
	}
	/**
	 * Returns a single cell preformated
	 *
	 * @version 20080725
	 * @param string $sRowClass
	 * @param string $sValue
	 * @param string $sStyle
	 * @return string
	 */
	public function setSingleCell($sRowClass, $sValue, $sStyle = null)
	{
        if (is_numeric(str_replace(array(',', '.', '%'), array('', '', ''), $sValue))) {
            $sStyle .= 'text-align: right;';
        }
	    if ($sStyle != null) {
	        $sStyle = ' style="' . $sStyle . '"';
	    } else {
	        $sStyle = ' style="' . $sStyle . '"';
        }
	    return '<td class="' . $sRowClass . '"'
	    	. $sStyle
		    . '>' . $sValue . '</td>';
	}
	/**
	 * Puts a given string into a specific short tag
	 *
	 * @param string $sTag
	 * @param array $features
	 * @return string
	 */
	public function setStringIntoShortTag($sTag, $features = null)
	{
	    $attributes = '';
	    if ($features != null) {
	        foreach($features as $key => $value) {
	            $attributes .= ' ' . $key . '="';
	            if (is_array($value)) {
	                foreach($value as $key2 => $value2) {
	                    $attributes .= $key2 . ':' . $value2 . ';';
	                }
	            } else {
	                $attributes .= $value;
	            }
	            $attributes .= '"';
	        }
	    }
	    return '<' . $sTag . $attributes . ' />';
	}
	/**
	 * Puts a given string into a specific tag
	 *
	 * @param string $sString
	 * @param string $sTag
	 * @param array $features
	 * @return string
	 */
	public function setStringIntoTag($sString, $sTag, $features = null) {
	    $attributes = '';
	    if ($features != null) {
	        foreach($features as $key => $value) {
	            $attributes .= ' ' . $key . '="';
	            if (is_array($value)) {
	                foreach($value as $key2 => $value2) {
	                    $attributes .= $key2 . ':' . $value2 . ';';
	                }
	            } else {
	                $attributes .= $value;
	            }
	            $attributes .= '"';
	        }
	    }
	    return '<' . $sTag . $attributes . '>' . $sString
	    	. '</' . $sTag . '>';
	}
}