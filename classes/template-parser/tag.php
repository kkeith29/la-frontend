<?php

namespace la_frontend\template_parser;

use la_frontend;
use la_frontend\template_parser;
use la_frontend\template_parser\if_statement;

la_frontend::load('la_frontend\\template_parser\\if_statement');

class tag {

	const name_asset      = 'asset';
	const name_breadcrumb = 'breadcrumb';
	const name_form       = 'form';
	const name_html       = 'html';
	const name_lb         = 'lb';
	const name_nav        = 'nav';
	const name_script     = 'script';

	const type_start  = 1;
	const type_end    = 2;
	const type_single = 3;

	private static $priorities = array(
		self::name_breadcrumb => array(
			'output' => 3
		)
	);

	public $type;
	private $parser;
	private $data = array();
	private $closing_tag;
	private $parent = false;

	public static function get_list() {
		return array(
			self::name_asset,
			self::name_breadcrumb,
			self::name_form,
			self::name_html,
			self::name_lb,
			self::name_nav,
			self::name_script
		);
	}

	public function __construct( $parser,$type,$data ) {
		$this->parser = $parser;
		$this->type   = $type;
		$this->data   = $data;
		switch( $type ) {
			case self::type_start:
				$this->data['length'] = ( ( $this->data['position_end'] - $this->data['position_start'] ) + 1 );
				$this->data['tag'] = substr( $this->parser->template,$this->data['position_start'],$this->data['length'] );
				$tag = substr( trim( $this->data['tag'],'{}' ),( strlen( $this->data['name'] ) + 1 ) );
				$attrs = array();
				switch( $this->data['name'] ) {
					case self::name_lb:
						if ( preg_match( '#^(if(:else(if)?)?)(\s+(.*))?$#is',$tag,$match ) !== 1 ) {
							throw new \Exception('Invalid lb tag call');
						}
						switch( $match[1] ) {
							case 'if':
								$this->data['call']     = $match[1];
								$this->data['closable'] = true;
								$this->data['if']       = new if_statement( $this,$match[5] );
							break;
							case 'if:elseif':
								$this->data['call']     = $match[1];
								$this->data['closable'] = false;
								$this->data['if']       = new if_statement( $this,$match[5] );
								$this->type             = self::type_single;
							break;
							case 'if:else':
								$this->data['call']     = $match[1];
								$this->data['closable'] = false;
								$this->type             = self::type_single;
							break;
							default:
								throw new \Exception("Invalid lb tag call '{$match[1]}'");
							break;
						}
					break;
					default:
						$tag = trim( preg_replace_callback( '#([a-zA-Z:\-_]+)\s*=\s*([\'"])([^\2]*)\2#Us',function( $match ) use ( &$attrs ) {
							$attrs[$match[1]] = $match[3];
							return '';
						},$tag ) );
						if ( strlen( $tag ) === 0 ) {
							reset( $attrs );
							$this->data['call']     = key( $attrs );
							$this->data['closable'] = false;
							$this->type = self::type_single;
						}
						else {
							$this->data['call']     = $tag;
							$this->data['closable'] = true;
						}
					break;
				}
				$this->data['attrs'] = $attrs;
			break;
			case self::type_end:
				$this->data['tag'] = "{/{$this->data['name']}:{$this->data['call']}}";
				$this->data['length'] = strlen( $this->data['tag'] );
			break;
		}
	}

	public function position( $pos_s ) {
		$this->data['position_start'] = $pos_s;
		$this->data['position_end'] = $pos_s + $this->data['length'];
	}

	public function __get( $key ) {
		return ( isset( $this->data[$key] ) ? $this->data[$key] : null );
	}

	public function __set( $key,$value ) {
		$this->data[$key] = $value;
	}

	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	public function priority() {
		if ( isset( self::$priorities[$this->data['name']][$this->data['call']] ) ) {
			return self::$priorities[$this->data['name']][$this->data['call']];
		}
		return 5;
	}

	public function parent( tag $tag ) {
		$this->data['parent'] = $tag;
	}

	public function nested_tag( tag $tag ) {
		if ( $this->type !== self::type_start && $this->type !== self::type_single ) {
			throw new \Exception('Tag nesting only allowed for start tags');
		}
		$tag->parent( $this );
		$tag->relative_position_start = ( $tag->position_start - ( $this->data['position_start'] + $this->data['length'] ) );
		$tag->relative_position_end = ( $tag->relative_position_start + $tag->length );
		if ( !isset( $this->data['nested_tags'] ) ) {
			$this->data['nested_tags'] = array();
		}
		$this->data['nested_tags'][] = $tag;
	}

	public function close( tag $tag ) {
		$this->closing_tag = $tag;
		$this->data['inner_content'] = substr( $this->parser->template,( $this->data['position_end'] + 1 ),( $tag->position_start - $this->data['position_end'] - 1 ) );
		$this->data['position_end'] = ( $tag->position_start + $tag->length );
		$this->data['length'] = ( $this->data['position_end'] - $this->data['position_start'] );
	}

	public function str() {
		return $this->data['tag'];
	}

}

?>