<?php

namespace la_frontend;

use la_frontend\template_parser\if_statement;
use la_frontend\template_parser\tag;

class template_parser {

	public $template = '';

	private $tags = array();

	public function __construct( $template ) {
		$this->template = $template;
		$this->find_tags( tag::get_list() );
		usort( $this->tags,function( $a,$b ) {
			if ( $a->position_start === $b->position_start ) {
				return 0;
			}
			if ( $a->position_start < $b->position_start ) {
				return -1;
			}
			return 1;
		} );
		$tags = $parents = $ifs = array();
		$level = 0;
		foreach( $this->tags as $idx => $tag ) {
			if ( $level > 0 && $tag->type === tag::type_end ) {
				if ( $tag->name === tag::name_lb && $tag->call === 'if' ) {
					$ifs[$level]->close( $tag );
					unset( $ifs[$level] );
				}
				else {
					$parents[$level]->close( $tag );
				}
				unset( $parents[$level] );
				$level--;
			}
			if ( $tag->type !== tag::type_end ) {
				if ( $level === 0 ) {
					$tags[] = $tag;
				}
				elseif ( $tag->name === tag::name_lb && ( $tag->call === 'if:elseif' || $tag->call === 'if:else' ) ) {
					$ifs[$level]->nested_tag( $tag );
				}
				else {
					$parents[$level]->nested_tag( $tag );
				}
			}
			if ( $tag->type === tag::type_start || ( $tag->type === tag::type_single && $tag->name === tag::name_lb && ( $tag->call === 'if:elseif' || $tag->call === 'if:else' ) ) ) {
				if ( $tag->type === tag::type_start ) {
					$level++;
					if ( $tag->name === tag::name_lb && $tag->call === 'if' ) {
						$ifs[$level] = $tag;
					}
				}
				$parents[$level] = $tag;
			}
		}
		if ( $level !== 0 || count( $parents ) > 0 || count( $ifs ) > 0 ) {
			throw new \Exception('Tag mismatch found');
		}
		$this->tags = $tags;
		if_statement::prepare_all();
	}

	public function tags() {
		return $this->tags;
	}

	public function tags_by_priority() {
		$tags = array();
		foreach( $this->tags as $tag ) {
			$priority = $tag->priority();
			if ( !isset( $tags[$priority] ) ) {
				$tags[$priority] = array();
			}
			$tags[$priority][] = $tag;
		}
		krsort( $tags,SORT_NUMERIC );
		return $tags;
	}

	private function find_tags( $names ) {
		foreach( $names as $name ) {
			$closing_calls = array();
			$i = 0;
			$offset = 0;
			while( ( $pos_s = strpos( $this->template,"{{$name}:",$offset ) ) !== false ) {
				if ( $i > 100 ) {
					break;
				}
				$pos_e = strpos( $this->template,'}',$pos_s );
				$_offset = ( $pos_s + 1 );
				while( ( $_pos_s = strpos( $this->template,'{',$_offset ) ) !== false ) {
					if ( $_pos_s > $pos_e ) {
						break;
					}
					$pos_e = strpos( $this->template,'}',( $pos_e + 1 ) );
					$_offset = ( $_pos_s + 1 );
				}
				if ( $pos_e === false ) {
					throw new \Exception('Tag bracket mismatch found');
				}
				$offset = ( $pos_e + 1 );
				$tag = new tag( $this,tag::type_start,array(
					'name'           => $name,
					'position_start' => $pos_s,
					'position_end'   => $pos_e
				) );
				if ( $tag->closable && !in_array( $tag->call,$closing_calls ) ) {
					$closing_calls[] = $tag->call;
				}
				$this->tags[] = $tag;
				$i++;
			}
			if ( count( $closing_calls ) > 0 ) {
				foreach( $closing_calls as $closing_call ) {
					$tag = new tag( $this,tag::type_end,array(
						'name' => $name,
						'call' => $closing_call
					) );
					$offset = 0;
					while( ( $pos = strpos( $this->template,$tag->str(),$offset ) ) !== false ) {
						$_tag = clone $tag;
						$_tag->position( $pos );
						$offset = ( $pos + $tag->length );
						$this->tags[] = $_tag;
					}
				}
				unset( $tag );
			}
		}
	}

	public static function remove_tags( &$content,$tags ) {
		usort( $tags,function( $a,$b ) {
			$a_pos = ( isset( $a->relative_position_start ) ? $a->relative_position_start : $a->position_start );
			$b_pos = ( isset( $b->relative_position_start ) ? $b->relative_position_start : $b->position_start );
			if ( $a_pos === $b_pos ) {
				return 0;
			}
			if ( $a_pos < $b_pos ) {
				return 1;
			}
			return -1;
		} );
		foreach( $tags as $tag ) {
			$content = substr_replace( $content,( isset( $tag->replacement ) ? $tag->replacement : '' ),( isset( $tag->relative_position_start ) ? $tag->relative_position_start : $tag->position_start ),$tag->length );
		}
	}

	public static function replace_vars( &$content,$vars ) {
		$content = str_replace( array_map( function( $var ) {
			return "{{$var}}";
		},array_keys( $vars ) ),array_values( $vars ),$content );
	}

	public function template() {
		self::remove_tags( $this->template,$this->tags );
		return $this->template;
	}

	public function debug() {
		echo '<pre>' . print_r( $this->tags,true ) . '</pre>';
	}

}

?>