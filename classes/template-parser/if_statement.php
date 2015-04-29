<?php

namespace la_frontend\template_parser;

use la_frontend\template_parser\tag;

class if_statement {

	private static $instances = array();

	private $tag;
	private $vars = array();
	private $available_vars = array();
	private $statement = '';

	private $parts = array();

	public function __construct( tag $tag,$str ) {
		$this->tag = $tag;
		if ( $this->tag->call === 'if' ) {
			self::$instances[] = $this;
		}
		$i = 0;
		$vars = array();
		$str = preg_replace_callback( '#("|\')[^\1\\\]*(?:\\.[^\1\\\]*)*\1#s',function( $match ) use ( &$vars,&$i ) {
			$i++;
			$var = "__quote:{$i}";
			$vars[$var] = $match[0];
			return $var;
		},$str );
		$this->statement = $str;
		$this->vars = $vars;
		$str = preg_match_all( '#(?:[a-zA-Z_]+)(?:[a-zA-Z0-9_:]+)#s',$str,$matches );
		$this->available_vars = $matches[0];
	}

	public function statement() {
		return $this->statement;
	}

	public function prepare() {
		$tags = array();
		if ( isset( $this->tag->nested_tags ) ) {
			$has_else = false;
			foreach( $this->tag->nested_tags as $tag ) {
				if ( $tag->name !== tag::name_lb || ( $tag->call !== 'if:elseif' && $tag->call !== 'if:else' ) ) {
					continue;
				}
				if ( $tag->call === 'if:else' ) {
					$has_else = true;
				}
				$tags[] = $tag;
				unset( $tag );
			}
			if ( $has_else ) {
				$last_tag = end( $tags );
				if ( $last_tag->call !== 'if:else' ) {
					throw new \Exception('if:else statement cannot come before if:elseif');
				}
				unset( $last_tag );
			}
		}
		array_unshift( $tags,$this->tag );
		$pos_s = 0;
		foreach( $tags as $idx => $tag ) {
			$next_idx = ( $idx + 1 );
			$next = ( isset( $tags[$next_idx] ) ? $tags[$next_idx] : false );
			$part = array(
				'statement' => ( isset( $tag->if ) ? $tag->if : false ),
				'content'   => substr( $this->tag->inner_content,$pos_s,( $next === false ? strlen( $this->tag->inner_content ) : $next->relative_position_start ) )
			);
			if ( isset( $tag->nested_tags ) ) {
				$part['nested_tags'] = $tag->nested_tags;
			}
			$this->parts[] = $part;
			if ( $next !== false ) {
				$pos_s = ( $next->relative_position_start + $next->length );
			}
			unset( $tag,$next,$part );
		}
	}

	private function validate_vars( $vars ) {
		$vars = array_merge( ee()->config->_global_vars,$vars,$this->vars );
		if ( count( ( $diff = array_diff( $this->available_vars,array_keys( $vars ) ) ) ) > 0 ) {
			throw new \Exception('The following vars are not set: ' . implode( ', ',$diff ));
		}
		return array(
			'keys' => array_keys( $vars ),
			'values' => array_map( function( $var ) {
				if ( is_numeric( $var ) ) {
					return $var;
				}
				if ( is_string( $var ) ) {
					return "'" . str_replace( "'","\'",$var ) . "'";
				}
				if ( is_bool( $var ) ) {
					return ( $var ? 'true' : 'false' );
				}
			},array_values( $vars ) )
		);
	}

	public function evaluate( $vars ) {
		$code = '';
		foreach( $this->parts as $idx => $part ) {
			if ( $part['statement'] === false ) {
				$code .= "return {$idx};";
				continue;
			}
			$_vars = $part['statement']->validate_vars( $vars );
			$part['statement'] = str_replace( $_vars['keys'],$_vars['values'],$part['statement']->statement() );
			$code .= "if ( {$part['statement']} ) { return {$idx}; } ";
		}
		$idx = eval( $code );
		$retval = array();
		if ( !is_null( $idx ) ) {
			$retval['template'] = $this->parts[$idx]['content'];
			if ( isset( $this->parts[$idx]['nested_tags'] ) ) {
				$retval['nested_tags'] = $this->parts[$idx]['nested_tags'];
			}
		}
		return $retval;
	}

	public static function prepare_all() {
		foreach( self::$instances as $instance ) {
			$instance->prepare();
		}
	}

}

?>