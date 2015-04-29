<?php

namespace la_frontend;

use la_frontend;

class html {

	const expire = 3600;

	const before = 1;
	const after = 2;

	private static $output = true;

	private $doctypes = array(
		'html5'              => '<!DOCTYPE html>',
		'html-strict'        => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
		'html-transitional'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
		'xhtml-strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml-transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml-basic'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">'
	);
	private $config = array();
	private $head = array(
		'title'  => array(),
		'meta'   => array(),
		'link'   => array(),
		'style'  => array(),
		'script' => array(),
		'insert' => array()
	);
	private $body = array(
		'events'  => array(),
		'prepend' => array(),
		'append'  => array(),
		'js-eof'  => array()
	);

	public function __construct() {}

	public function method( $method,$args ) {
		switch( $method ) {
			case 'title':
				if ( !isset( $args['title'] ) ) {
					throw new \Exception('title method requires title text');
				}
				$this->head['title'][] = $args['title'];
			break;
			case 'meta_name':
				if ( !isset( $args['meta_name'] ) ) {
					throw new \Exception('meta_name method requires a name');
				}
				if ( !isset( $args['content'] ) ) {
					throw new \Exception('meta_name method requires a content attribute');
				}
				$this->head['meta'][] = "<meta name=\"{$args['meta_name']}\" content=\"{$args['content']}\" />";
			break;
			case 'meta_http_equiv':
				if ( $args['meta_http_equiv'] == 'refresh' ) {
					if ( !isset( $args['seconds'] ) ) {
						throw new \Exception('meta_http_equiv method refresh requires seconds attribute');
					}
					$args['content'] = $args['seconds'] . ( isset( $args['url'] ) ? ";url={$args['url']}" : '' );
				}
				if ( !isset( $args['content'] ) ) {
					throw new \Exception('meta_http_equiv method requires a content attribute');
				}
				$this->head['meta'][] = "<meta http-equiv=\"{$args['meta_http_equiv']}\" content=\"{$args['content']}\" />";
			break;
			case 'meta_charset':
				if ( !isset( $args['meta_charset'] ) ) {
					throw new \Exception('meta_charset method requires a charset');
				}
				$this->head['meta'][] = "<meta charset=\"{$args['meta_charset']}\" />";
			break;
			case 'link':
				$this->head['link'][] = '<link' . self::build_attrs( $args ) . ' />';
			break;
			case 'css_external':
				if ( !isset( $args['css_external'] ) ) {
					throw new \Exception('css_external method requires a url');
				}
				return $this->method('link',array(
					'rel'  => 'stylesheet',
					'type' => 'text/css',
					'href' => $args['css_external']
				));
			break;
			case 'favicon':
				if ( !isset( $args['favicon'] ) ) {
					throw new \Exception('favicon method requires a url');
				}
				return $this->method('link',array(
					'rel'  => 'shortcut icon',
					'type' => 'image/x-icon',
					'href' => $args['favicon']
				));
			break;
			/*case 'css_embed':
				$this->head['style'][] = "<style type=\"text/css\"><!--\n{$args[0]}\n\t--></style>";
			break;*/
			case 'js_external':
				if ( !isset( $args['js_external'] ) ) {
					throw new \Exception('js_external method requires a url');
				}
				$this->head['script'][] = "<script type=\"text/javascript\" src=\"{$args['js_external']}\"></script>";
			break;
			/*case 'js_embed':
				$this->head['script'][] = "<script type=\"text/javascript\"><!--\n{$args[0]}\n\t--></script>";
			break;
			case 'head_insert':
				$this->head['insert'][$args[0]][$args[1]][] = $args[3];
			break;
			case 'body_event':
				$this->body['events'][$args[0]][] = $args[1];
			break;
			case 'body_prepend':
				$this->body['prepend'][] = $args[0];
			break;
			case 'body_append':
				$this->body['append'][] = $args[0];
			break;
			case 'js_eof_external':
				$this->body['js-eof'][] = "<script type=\"text/javascript\" src=\"{$args[0]}\"></script>";
			break;
			case 'js_eof_embed':
				$this->body['js-eof'][] = "<script type=\"text/javascript\"><!--\n{$args[0]}\n\t--></script>";
			break;*/
			default:
				throw new \Exception("Method '{$method}' not found");
			break;
		}
		return '';
	}

	private function format( $data,$format=false ) {
		$output = '';
		if ( count( $data ) > 0 ) {
			foreach( $data as $str ) {
				$output .= ( $format ? "\t{$str}\n" : $str );
			}
		}
		return $output;
	}

	public function get( $var,$type='head',$format=false ) {
		if ( isset( $this->{$type}[$var] ) ) {
			switch( $type ) {
				case 'head':
					switch( $var ) {
						case 'title':
							if ( la_frontend::$config['html_title_reverse'] ) {
								$this->head['title'] = array_reverse( $this->head['title'] );
							}
							return implode( la_frontend::$config['html_title_sep'],$this->head['title'] );
						break;
						default:
							if ( is_array( $this->head[$var] ) ) {
								return $this->format( $this->head[$var],$format );
							}
						break;
					}
				break;
				case 'body':
					switch( $var ) {
						case 'prepend':
							$this->body[$var] = array_reverse( $this->body[$var] );
							return $this->format( $this->body[$var] );
						break;
						default:
							if ( is_array( $this->body[$var] ) ) {
								return $this->format( $this->body[$var] );
							}
						break;
					}
				break;
			}
			return $this->{$type}[$var];
		}
		return false;
	}

	public function output_header() {
		$doctype = la_frontend::$config['html_doctype'];
		if ( isset( $this->doctypes[$doctype] ) ) {
			$html = $this->doctypes[$doctype];
			$charset = la_frontend::$config['html_charset'];
			if ( $doctype == 'html5' ) {
				$this->method('meta_charset',array(
					'meta_charset' => $charset
				));
			}
			else {
				$this->method('meta_http_equiv',array(
					'meta_http_equiv' => 'Content-Type',
					'content'         => "text/html; charset={$charset}"
				));
			}
		}
		else {
			$html = $doctype;
		}
		$lang  = la_frontend::$config['html_lang'];
		$html .= "\n<html" . ( strpos( $doctype,'xhtml' ) !== false ? " xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lang}\"" : '' ) . " lang=\"{$lang}\">\n";
		$html .= "<head>\n\t<title>" . $this->get('title') . "</title>\n";
		foreach( array('meta','link','style','script') as $type ) {
			if ( isset( $this->head['insert'][$type][self::before] ) ) {
				$html .= implode( '',$this->head['insert'][$type][self::before] );
			}
			$html .= $this->get( $type,'head',true );
			if ( isset( $this->head['insert'][$type][self::after] ) ) {
				$html .= implode( '',$this->head['insert'][$type][self::after] );
			}
		}
		$html .= "</head>\n";
		$events = array();
		foreach( $this->body['events'] as $type => $data ) {
			$events[] = " {$type}=\"" . implode( ';',$data ) . "\"";
		}
		$html .= "<body" . implode( '',$events ) . ">\n";
		foreach( array('prepend') as $type ) {
			$html .= $this->get( $type,'body' );
		}
		return $html;
	}
	
	public function output_footer() {
		$html = '';
		foreach( array('append','js-eof') as $type ) {
			$html .= $this->get( $type,'body' );
		}
		$html .= "\n</body>\n";
		$html .= "</html>";
		return $html;
	}

	public static function build_attrs( $data,$allow=null,$bypass=false,$default=null ) {
		if ( !is_array( $data ) ) { return ''; }
		if ( is_null( $allow ) && $bypass == false ) {
			$allow = array( 'class','id','type','name','rows','cols','size','maxlength','onclick','onmouseover','onmouseout','onblur','onfocus','onkeyup','onkeypress','checked','selected','value','action','method','enctype','style','src','rel','multiple','disabled','cellpadding','cellspacing','colspan','rowspan','href','alt','title','data-id' );
		}
		if ( isset( $data['_attrs'] ) ) {
			$data = $data['_attrs'];	
		}
		if ( !is_null( $default ) ) {
			$data = array_merge( $default,$data );
		}
		$attrs = array();
		foreach( $data as $attr => $value ) {
			switch( $attr ) {
				case 'uri':
				case 'href-uri':
					$attr = 'href';
					$value = uri::create( $value );
				break;
				case 'url':
					$attr = 'href';
				break;
				case 'class':
					if ( is_array( $value ) ) {
						$value = implode( ' ',$value );
					}
				break;
			}
			if ( $bypass == false && !in_array( $attr,$allow ) ) {
				continue;	
			}
			if ( $attr == 'value' || $value !== '' ) {
				$attrs[$attr] = $value;
			}
		}
		$str = '';
		foreach( $attrs as $attr => $value ) {
			$str .= " {$attr}=\"{$value}\"";	
		}
		return $str;
	}

	public static function entity_encode( $data ) {
		return htmlentities( $data,ENT_QUOTES,'UTF-8' );
	}

	public static function entity_decode( $data ) {
		return html_entity_decode( $data,ENT_QUOTES,'UTF-8' );
	}

}

?>