<?php

use la_frontend\form;
use la_frontend\template_parser;
use la_frontend\template_parser\tag;

la_frontend::load('la_frontend\\template_parser');
la_frontend::load('la_frontend\\template_parser\\tag');

class la_frontend {

	private static $instances = array();

	public static $config = array();

	public static function __callStatic( $method,$args ) {
		return self::instance( $method,$args );
	}

	public static function load( $class ) {
		if ( class_exists( $class,false ) ) {
			return;
		}
		$parts = explode( '\\',$class );
		array_shift( $parts ); //remove la_frontend
		$name = array_pop( $parts );
		$path = str_replace( '_','-',implode( '/',$parts ) );
		$class_path = LA_FRONTEND_DIR . 'classes/' . ( $path !== '' ? "{$path}/" : '' ) . "{$name}.php";
		if ( !file_exists( $class_path ) ) {
			throw new Exception("Unable to load class '{$class}'");
		}
		include $class_path;
	}

	public static function instance( $name,$args=array() ) {
		if ( !isset( self::$instances[$name] ) ) {
			$class = "la_frontend\\{$name}";
			self::load( $class );
			$class = new ReflectionClass( $class );
			self::$instances[$name] = $class->newInstanceArgs( $args );
		}
		return self::$instances[$name];
	}

	public function __construct( $config ) {
		self::$config = $config;
	}

	private function handle_tag( $tag,$vars=array() ) {
		if ( count( $tag->attrs ) > 0 ) {
			$oattrs = $attrs = $tag->attrs;
			foreach( $attrs as &$attr ) {
				template_parser::replace_vars( $attr,$vars );
				unset( $attr );
			}
			$tag->attrs = $attrs;
		}
		switch( $tag->name ) {
			case tag::name_asset:
				switch( $tag->call ) {
					case 'register':
						if ( !isset( $tag->nested_tags ) ) {
							throw new Exception('asset:register requires nested tags');
						}
						self::asset()->method( 'register_start',$tag->attrs );
						foreach( $tag->nested_tags as $_tag ) {
							$this->handle_tag( $_tag );
						}
						self::asset()->method('register_end');
					break;
					default:
						$tag->replacement = self::asset()->method( $tag->call,$tag->attrs );
					break;
				}
			break;
			case tag::name_breadcrumb:
				$breadcrumb = self::breadcrumb();
				switch( $tag->call ) {
					case 'output':
						$links = $breadcrumb->links();
						$output = '';
						$c = 1;
						$t = count( $links );
						foreach( $links as $link ) {
							$template = $tag->inner_content;
							$_vars = array(
								'breadcrumb_title' => $link['name'],
								'breadcrumb_url'   => $link['url'],
								'count'            => $c++,
								'total_results'    => $t
							);
							$_vars = array_merge( $vars,$_vars );
							if ( isset( $tag->nested_tags ) ) {
								foreach( $tag->nested_tags as $_tag ) {
									$this->handle_tag( $_tag,$_vars );
								}
								template_parser::remove_tags( $template,$tag->nested_tags );
							}
							template_parser::replace_vars( $template,$_vars );
							$output .= $template;
						}
						$tag->replacement = $output;
					break;
					default:
						$tag->replacement = $breadcrumb->method( $tag->call,$tag->attrs );
					break;
				}
			break;
			case tag::name_form:
				switch( $tag->call ) {
					case 'embed':
						if ( !isset( $tag->attrs['embed'] ) ) {
							throw new Exception('form:embed call requires a name');
						}
						$path = self::$config['path_form'] . $tag->attrs['embed'] . '.php';
						if ( !file_exists( $path ) ) {
							throw new Exception("Unable to find form with name {$tag->attrs['embed']}");
						}
						self::load('la_frontend\\form');
						$tag->replacement = $this->load_file( $path,array(
							'tag_attrs' => $tag->attrs
						) );
					break;
				}
			break;
			case tag::name_html:
				$tag->replacement = self::html()->method( $tag->call,$tag->attrs );
			break;
			case tag::name_lb:
				switch( $tag->call ) {
					case 'if':
						$info = $tag->if->evaluate( $vars );
						if ( isset( $info['template'] ) ) {
							if ( isset( $info['nested_tags'] ) ) {
								foreach( $info['nested_tags'] as $_tag ) {
									$this->handle_tag( $_tag,$vars );
								}
								template_parser::remove_tags( $info['template'],$info['nested_tags'] );
							}
							$tag->replacement = $info['template'];
							break;
						}
						$tag->replacement = '';
					break;
				}
			break;
			case tag::name_nav:
				$nav = self::nav();
				switch( $tag->call ) {
					case 'group':
						if ( !isset( $tag->nested_tags ) ) {
							throw new Exception('nav:group requires nested tags');
						}
						$nav->method( 'group_start',$tag->attrs );
						foreach( $tag->nested_tags as $_tag ) {
							$this->handle_tag( $_tag );
						}
						$nav->method('group_end');
					break;
					case 'output':
						$group = ( isset( $tag->attrs['id'] ) ? $tag->attrs['id'] : false );
						$links = $nav->links( $group );
						$output = '';
						$c = 1;
						$t = count( $links );
						foreach( $links as $link ) {
							$template = $tag->inner_content;
							$_vars = array(
								'nav_title'        => $link['name'],
								'nav_url'          => $link['url'],
								'nav_has_subgroup' => ( $link['subgroup'] !== false ),
								'count'            => $c++,
								'total_results'    => $t
							);
							if ( $_vars['nav_has_subgroup'] ) {
								$_vars['nav_subgroup'] = $link['subgroup'];
							}
							$_vars = array_merge( $vars,$_vars );
							if ( isset( $tag->nested_tags ) ) {
								foreach( $tag->nested_tags as $_tag ) {
									$this->handle_tag( $_tag,$_vars );
								}
								template_parser::remove_tags( $template,$tag->nested_tags );
							}
							template_parser::replace_vars( $template,$_vars );
							$output .= $template;
						}
						$tag->replacement = $output;
					break;
					default:
						$tag->replacement = $nav->method( $tag->call,$tag->attrs );
					break;
				}
			break;
			case tag::name_script:
				switch( $tag->call ) {
					case 'embed':
						if ( !isset( $tag->attrs['embed'] ) ) {
							throw new Exception('script:embed call requires a name');
						}
						$path = self::$config['path_script'] . $tag->attrs['embed'] . '.php';
						if ( !file_exists( $path ) ) {
							throw new Exception("Unable to find script with name {$tag->attrs['embed']}");
						}
						$tag->replacement = $this->load_file( $path,array(
							'tag_attrs' => $tag->attrs
						) );
					break;
				}
			break;
		}
		if ( isset( $oattrs ) ) {
			$tag->attrs = $oattrs;
		}
	}

	public function load_file( $path,$vars=array() ) {
		extract( $vars );
		ob_start();
		include $path;
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	public function init( $template ) {
		$tags = self::template_parser( $template )->tags_by_priority();
		
		foreach( $tags as $priority => $_tags ) {
			foreach( $_tags as $tag ) {
				$this->handle_tag( $tag );
			}
		}
		if ( isset( self::$instances['asset'] ) ) {
			self::asset()->handle();
		}
	}

	public function output() {
		$html = self::html();
		return $html->output_header() . self::template_parser()->template() . $html->output_footer();
	}

}

?>