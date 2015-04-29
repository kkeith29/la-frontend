<?php

namespace la_frontend;

class igsr {

	const is_set = 1;
	const get    = 2;
	const set    = 3;
	const remove = 4;

	private $data = array();
	private $delim = '/';
	private $value = null;
	private $changed = false;
	private $callbacks = array();

	private $path_function = null;

	public function __construct( $data=array() ) {
		$this->set_data( $data );
	}

	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	public function __get( $key ) {
		return ( array_key_exists( $key,$this->data ) ? $this->data[$key] : null );
	}

	public function __set( $key,$value ) {
		$this->data[$key] = $value;
	}

	public function __unset( $key ) {
		if ( isset( $this->data[$key] ) ) {
			unset( $this->data[$key] );
		}
	}

	public function set_data( $data ) {
		is_array( $data ) or show_error('Data must be an array');
		$this->data = $data;
	}

	public function &get_data() {
		return $this->data;
	}

	public function set_path_function( $func ) {
		$this->path_function = $func;
	}

	public function merge( $data,$recursive=false ) {
		if ( $recursive == false ) {
			$this->data = array_merge( $this->data,$data );
		}
		else {
			$this->data = func::array_merge_recursive_distinct( $this->data,$data );
		}
	}

	public function callback( $type,$function ) {
		if ( isset( $this->callbacks[$type] ) ) {
			$this->callbacks[$type] = array();
		}
		$this->callbacks[$type][] = $function;
		return $this;
	}

	private function filter( $data ) {
		return ( strlen( $data ) > 0 ? true : false );
	}

	private function traverse( $path,$act ) {
		$paths = array_values( array_filter( explode( $this->delim,$path ),array( $this,'filter' ) ) );
		if ( !is_null( $this->path_function ) ) {
			$paths = call_user_func( $this->path_function,$paths );
		}
		$adata =& $this->data;
		$end = ( count( $paths ) - 1 );
		foreach( $paths as $i => $path ) {
			$is_end = ( $i == $end ? true : false );
			switch( $act ) {
				case self::is_set:
					if ( ( !$is_end && !is_array( $adata ) ) || !isset( $adata[$path] ) ) {
						return false;
					}
				break;
				case self::set:
					if ( $is_end == true ) {
						if ( func::str_at_end( '[]',$path,true ) ) {
							$adata[$path][] = $this->value;
						}
						else {
							$adata[$path] = $this->value;
						}
						$this->value = null;
						return true;
					}
					elseif ( $is_end == false && ( isset( $adata[$path] ) && !is_array( $adata[$path] ) ) ) {
						$adata[$path] = array();
					}
				break;
				case self::get:
					if ( $is_end == true ) {
						if ( is_array( $adata ) && strpos( $path,',' ) !== false ) {
							$retval = array();
							foreach( explode( ',',$path ) as $k ) {
								$retval[] = ( isset( $adata[$k] ) ? $adata[$k] : null );
							}
							return $retval;
						}
						return ( isset( $adata[$path] ) ? $adata[$path] : false );
					}
					elseif ( ( $is_end == false && !is_array( $adata ) ) || !isset( $adata[$path] ) ) {
						return false;
					}
				break;
				case self::remove:
					if ( $is_end == true && isset( $adata[$path] ) ) {
						$parts = '';
						foreach( $paths as $part ) {
							$parts .= "['{$part}']";
						}
						eval("unset( \$this->data{$parts} );");
						return true;
					}
					elseif ( ( $is_end == false && !is_array( $adata ) ) || !isset( $adata[$path] ) ) {
						return false;
					}
				break;
			}
			$adata =& $adata[$path];
		}
		return true;
	}

	public function is_set() {
		$args = func_get_args();
		$args = func::array_flatten( $args );
		foreach( $args as $arg ) {
			$value = $this->traverse( $arg,self::is_set );
			if ( isset( $this->callbacks[self::is_set] ) ) {
				foreach( $this->callbacks[self::is_set] as $function ) {
					$value = call_user_func( $function,self::is_set,$arg,$value,$this );
				}
			}
			if ( $value === false ) {
				return false;
			}
		}
		return true;
	}

	public function get() {
		$args = func_get_args();
		$args = func::array_flatten( $args );
		$retval = array();
		foreach( $args as $arg ) {
			$value = $this->traverse( $arg,self::get );
			if ( isset( $this->callbacks[self::get] ) ) {
				foreach( $this->callbacks[self::get] as $function ) {
					$value = call_user_func( $function,self::get,$arg,$value,$this );
				}
			}
			$retval[] = $value;
		}
		if ( count( $retval ) == 1 ) {
			return $retval[0];
		}
		return $retval;
	}

	public function set( $path,$val ) {
		$this->changed = true;
		$this->value = $val;
		return $this->traverse( $path,self::set );
	}

	public function remove() {
		$this->changed = true;
		$args = func_get_args();
		$args = func::array_flatten( $args );
		foreach( $args as $arg ) {
			$this->traverse( $arg,self::remove );
		}
	}

	public function data_changed() {
		return $this->changed;
	}

	public function debug() {
		func::debug( $this->data );
	}

}

?>