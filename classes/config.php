<?php

namespace la_frontend;

class config {

	private $replacements = array();

	private function replace_value( $match ) {
		$i = count( $this->replacements );
		$this->replacements[$i] = $match[1];
		return "{R:{$i}}";
	}

	private function prepare_value( $data ) {
		if ( substr( $data,0,3 ) == '{R:' && ( $pos = strpos( $data,'}' ) ) !== false ) {
			$i = substr( $data,3,( $pos - 3 ) );
			if ( isset( $this->replacements[$i] ) ) {
				$data = $this->replacements[$i];
				unset( $this->replacements[$i] );
				return $data;
			}
		}
		return rtrim( $data,']' );
	}

	public function _parse_str( $data ) {
		if ( is_array( $data ) ) {
			return $data;
		}
		if ( $data == '' ) {
			return array();
		}
		$replacements = false;
		if ( strpos( $data,'{' ) !== false ) {
			$data = preg_replace_callback( '#\{([^\}]+)\}#',array( $this,'replace_value' ),$data );
			$replacments = true;
		}
		$data = explode( '|',$data );
		$config = array();
		foreach( $data as $datum ) {
			$values = true;
			if ( strpos( $datum,'[' ) !== false ) {
				$values = array_map( array( $this,'prepare_value' ),explode( '[',$datum ) );
				$datum = array_shift( $values );
				foreach( $values as &$value ) {
					if ( $value == 'yes' ) {
						$value = true;
					}
					elseif ( $value == 'no' ) {
						$value = false;
					}
				}
				if ( count( $values ) == 1 ) {
					$values = $values[0];
				}
			}
			if ( strpos( $datum,':' ) !== false ) {
				$parts = explode( ':',$datum );
				$data = array();
				$_data =& $data;
				foreach( $parts as $part ) {
					$_data[$part] = array();
					$_data =& $_data[$part];
				}
				$_data = $values;
				$config = func::array_merge_recursive_distinct( $config,$data );
				continue;
			}
			$config[$datum] = $values;
		}
		return $config;
	}

	public static function parse_str( $data ) {
		$class = __CLASS__;
		$config = new $class;
		return $config->_parse_str( $data );
	}

}

?>