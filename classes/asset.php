<?php

namespace la_frontend;

use la_frontend;
use la_frontend\asset\order;

la_frontend::load('la_frontend\\asset\\order');

class asset {

	private static $base_uri = null;

	const type_internal = 1;
	const type_external = 2;

	private $config = array();
	private $html;

	private $registered_groups = array();
	private $groups = array();

	private $group = false;
	private $assets = array(
		'css' => array(),
		'js'  => array()
	);
	private $order = array(
		'css' => array(),
		'js'  => array()
	);
	private $jquery = false;

	public function __construct() {}

	public function debug() {
		foreach( array('registered_groups'=>'Registered Groups','groups'=>'Groups','assets'=>'Assets','order'=>'Order') as $var => $label ) {
			echo '<p>' . $label . '</p>';
			echo '<pre>' . print_r( $this->{$var},true ) . '</pre>';
		}
	}

	private function is_url( $data ) {
		foreach( array('http://','https://','//') as $str ) {
			if ( strpos( $data,$str ) === 0 ) {
				return true;
			}
		}
		return false;
	}

    private function get_extension( $file ) {
        return strtolower( pathinfo( $file,PATHINFO_EXTENSION ) );
    }

	private function get_path( $type,$file ) {
		$paths = array(
			la_frontend::$config["path_asset_{$type}"],
			la_frontend::$config['path_asset_third_party']
		);
		foreach( $paths as $path ) {
			$path = $path . $file;
			if ( file_exists( $path ) ) {
				return $path;
			}
		}
		return false;
	}

	public function method( $method,$args=array() ) {
		switch( $method ) {
			case 'add':
				if ( !isset( $args['add'] ) ) {
					throw new \Exception('add method requires a file name');
				}
				$info = array(
					'url'  => $this->is_url( $args['add'] ),
					'extn' => ( isset( $args['type'] ) ? $args['type'] : $this->get_extension( $args['add'] ) )
				);
				switch( $info['extn'] ) {
					case 'css':
						if ( $info['url'] === false && $this->get_path( 'css',$args['add'] ) === false ) {
							throw new \Exception( "CSS file '{$args['add']}' not found" );
						}
					break;
					case 'js':
						if ( $info['url'] === false && $this->get_path( 'js',$args['add'] ) === false ) {
							throw new \Exception( "JS file '{$args['add']}' not found" );
						}
					break;
					default:
						throw new \Exception('Invalid extension');
					break;
				}
				$info['file'] = $args['add'];
				$key = md5( $args['add'] );
				$info['priority']  = ( isset( $args['priority'] ) ? (int) $args['priority'] : 5 );
				$info['group']     = $this->group;
				if ( $this->group === false ) {
					$this->assets[$info['extn']][$key] = $info;
					$this->order[$info['extn']][] =& $this->assets[$info['extn']][$key];
				}
				elseif ( !isset( $this->registered_groups[$this->group] ) || !in_array( $key,$this->registered_groups[$this->group] ) ) {
					$this->registered_groups[$this->group][$key] = array(
						'type'  => $info['extn'],
						'asset' => $info
					);
				}
			break;
			case 'register_start':
				if ( !isset( $args['name'] ) ) {
					throw new \Exception('register_start method requires a name attribute');
				}
				$this->group = $args['name'];
			break;
			case 'register_end':
				$this->group = false;
			break;
			case 'group':
				if ( !isset( $args['group'] ) ) {
					throw new \Exception('group requires a valid name');
				}
				if ( !isset( $this->registered_groups[$args['group']] ) ) {
					throw new \Exception("Unable to find group '{$args['group']}'");
				}
				if ( isset( $this->groups[$args['group']] ) ) {
					return;
				}
				if ( $this->group === false ) {
					$this->groups[$args['group']] = true;
					foreach( $this->registered_groups[$args['group']] as $data ) {
						switch( $data['type'] ) {
							case 'css':
							case 'js':
								$this->order[$data['asset']['extn']][] =& $data['asset'];
							break;
							case 'group':
								$this->method('group',array(
									'group' => $data['name']
								));
							break;
						}
					}
				}
				else {
					$this->registered_groups[$this->group][] = array(
						'type' => 'group',
						'name' => $args['group']
					);
				}
			break;
			case 'load':
				switch( $args['load'] ) {
					case 'jquery':
						$this->jquery = true;
					break;
				}
			break;
			case 'image':
				if ( !isset( $args['image'] ) ) {
					throw new Exception('image method requires a file name');
				}
				return $this->get_public_path( $args['image'],'image' );
			break;
			default:
				throw new \Exception("Method '{$method}' does not exist");
			break;
		}
		return '';
	}

	public function handle() {
		if ( $this->jquery ) {
			$this->method('add',array(
				'add'      => la_frontend::$config['path_jquery'],
				'priority' => 8
			));
		}
		$types = array();
		foreach( $this->order as $extn => $order ) {
			foreach( $order as $asset ) {
				if ( !isset( $types[$extn] ) ) {
					$types[$extn] = array();
				}
				$types[$extn][$asset['priority']][] = $asset;
			}
			if ( isset( $types[$extn] ) ) {
				krsort( $types[$extn] );
			}
		}
		$assets = array();
		foreach( $types as $type => $_assets ) {
			if ( !isset( $assets[$type] ) ) {
				$assets[$type] = array();
			}
			foreach( $_assets as $priority => $__assets ) {
				foreach( $__assets as $asset ) {
					$assets[$type][] = $asset;
				}
			}
		}
		$production = la_frontend::$config['production'];
		foreach( $assets as $type => $_assets ) {
			$method = "{$type}_external";
			$order = new order( $_assets );
			$groups = $order->reorder();
			foreach( $groups as $group ) {
				switch( $group['type'] ) {
					case self::type_external:
						foreach( $group['assets'] as $asset ) {
							la_frontend::html()->method( $method,array(
								$method => $asset['file']
							) );
						}
					break;
					case self::type_internal:
						/*if ( $production === true ) {
							$group['assets'] = array_map( function( $data ) { return $data['file']; },$group['assets'] );
							html::$function( uri::create("app/{$type}/type:combine/files:" . uri::base64_encode( implode( '<|>',$group['assets'] ) ),"extn[{$type}]|csm[yes]") );
							break;
						}*/
						foreach( $group['assets'] as $asset ) {
							la_frontend::html()->method( $method,array(
								$method => $this->get_public_path( $asset['file'],$type )
							) );
						}
					break;
				}
			}
		}
	}

	/*public static function get_data( $path ) {
		ob_start();
		include $path;
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

	public function handle_css() {
		$production = la_frontend::$config['production'];
		try {
			if ( !uri::validate_csm() ) {
				throw new \Exception('Checksum failed');
			}
			switch( uri::segment('type') ) {
				case 'single':
					if ( !uri::is_set('file') ) {
						throw new \Exception('No file segment found in url');
					}
					$files = array( uri::base64_decode( uri::segment('file') ) );
				break;
				case 'combine':
					if ( !uri::is_set('files') ) {
						throw new \Exception('No files segment found in url');
					}
					$files = $mtimes = array_unique( array_filter( explode( '<|>',uri::base64_decode( uri::segment('files') ) ) ) );
				break;
				default:
					throw new \Exception('Invalid type');
				break;
			}
			$min_time = 0;
			foreach( $files as &$file ) {
				if ( ( $file = path::get( $file,'css' ) ) === false ) {
					throw new \Exception('CSS file not found');
				}
				$mtime = filemtime( $file );
				if ( $mtime > $min_time ) {
					$min_time = $mtime;
				}
			}
			if ( $production === true && config::get('css.caching.enabled') === '1' ) {
				$cache = new cache('storage-cache-css');
				$cache->id( implode( '|',$files ) )->serialize(false)->min_time( $min_time );
				$data = $cache->fetch( config::get('css.caching.days'),cache::day,function() use( $files ) {
					$data = '';
					foreach( $files as $path ) {
						$data .= css::handle_images( asset::get_data( $path ),dirname( $path ) );
					}
					if ( config::get('css.minify.enabled') === '1' ) {
						$data = css::minify( $data );
					}
					return $data;
				} );
			}
			else {
				$data = '';
				foreach( $files as $path ) {
					$data .= css::handle_images( self::get_data( $path ),dirname( $path ) );
				}
			}
			http::content_type('text/css',config::get('css.charset'));
			if ( $production === true ) {
				http::cache( time::now(),time::future( config::get('css.caching.days'),time::day ) );
			}
			output::set_data( $data );
		}
		catch( \Exception $e ) {
			if ( $production === false ) {
				throw $e;
			}
			http::status_code(404,'Not Found');
		}
	}

	public function handle_js() {
		$production = la_frontend::$config['production'];
		try {
			if ( !uri::validate_csm() ) {
				throw new \Exception('Checksum failed');
			}
			switch( uri::segment('type') ) {
				case 'single':
					if ( !uri::is_set('file') ) {
						throw new \Exception('No file segment found in url');
					}
					$files = array( uri::base64_decode( uri::segment('file') ) );
				break;
				case 'combine':
					if ( !uri::is_set('files') ) {
						throw new \Exception('No files segment found in url');
					}
					$files = $mtimes = array_unique( array_filter( explode( '<|>',uri::base64_decode( uri::segment('files') ) ) ) );
				break;
				default:
					throw new \Exception('Invalid type');
				break;
			}
			$min_time = 0;
			foreach( $files as &$file ) {
				if ( ( $file = path::get( $file,'js' ) ) === false ) {
					throw new \Exception('JS file not found');
				}
				$mtime = filemtime( $file );
				if ( $mtime > $min_time ) {
					$min_time = $mtime;
				}
			}
			if ( $production === true && config::get('js.caching.enabled') === '1' ) {
				$cache = new cache('storage-cache-javascript');
				$cache->id( implode( '|',$files ) )->serialize(false)->min_time( $min_time );
				$data = $cache->fetch( config::get('js.caching.days'),cache::day,function() use( $files ) {
					$data = '';
					foreach( $files as $path ) {
						$data .= asset::get_data( $path );
					}
					return $data;
				} );
			}
			else {
				$data = '';
				foreach( $files as $path ) {
					$data .= self::get_data( $path );
				}
			}
			http::content_type('text/javascript',config::get('js.charset'));
			if ( $production === true ) {
				http::cache( time::now(),time::future( config::get('js.caching.days'),time::day ) );
			}
			output::set_data( $data );
		}
		catch( \Exception $e ) {
			if ( $production === false ) {
				throw $e;
			}
			http::status_code(404,'Not Found');
		}
	}

	public function handle_image() {
		$production = la_frontend::$config['production'];
		try {
			if ( !uri::validate_csm() ) {
				throw new \Exception('Checksum failed');
			}
			$file = uri::base64_decode( uri::get('file') );
			if ( ( $path = path::get( $file,'image' ) ) === false ) {
				throw new \Exception('Image not found');
			}
			$extn = $this->get_extension( $file );
			if ( ( $mime_type = http::mime_type( $extn ) ) === false ) {
				throw new \Exception('Unable to find proper mime type');
			}
			if ( uri::is_set('resize') || uri::is_set('crop') ) {
				if ( $production === true && config::get('image.caching.enabled') === '1' ) {
					$cache = new cache('storage-cache-image');
					$id = $group = $file;
					foreach( array('resize','crop') as $action ) {
						if ( uri::is_set( $action ) ) {
							$id .= ':' . uri::segment( $action );
						}
					}
					$cache->id( $id )->group( $group )->serialize(false)->min_time( filemtime( $path ) );
					if ( !$cache->expired( config::get('image.caching.days'),cache::day ) ) {
						$output_path = $cache->get_path();
					}
				}
				if ( !isset( $output_path ) ) {
					$image = new image;
					$image->load_file( $path );
					$i = 0;
					if ( uri::is_set('resize') ) {
						$parts = explode( '-',uri::segment('resize') );
						if ( count( $parts ) == 4 ) {
							list( $width,$height,$prop,$box ) = $parts;
							$image->resize( (int) $width,(int) $height,( $prop === 'true' ? true : false ),( $box === 'true' ? true : false ) );
							$i++;
						}
					}
					if ( uri::is_set('crop') ) {
						$parts = explode( '-',uri::segment('crop') );
						if ( count( $parts ) == 4 ) {
							list( $from_x,$from_y,$to_x,$to_y ) = $parts;
							$image->crop( (int) $from_x,(int) $from_y,(int) $to_x,(int) $to_y );
							$i++;
						}
					}
					if ( $i > 0 ) {
						if ( isset( $cache ) ) {
							$image->save_image( ( $output_path = $cache->get_path() ),$this->get_extension( $file ) );
						}
						else {
							$output_data = $image->data();
						}
					}
				}
			}
			else {
				$output_path = $path;
			}
			image::output_headers( $mime_type );
			if ( isset( $output_path ) ) {
				output::set_data( $output_path,output::file );
			}
			elseif ( isset( $output_data ) ) {
				output::set_data( $output_data );
			}
			else {
				throw new \Exception('An error has occurred while getting image data');
			}
		}
		catch( \Exception $e ) {
			if ( $production === false ) {
				throw $e;
			}
			http::status_code(404,'Not Found');
		}
	}*/

	public function get_public_path( $file,$type=null ) {
		$extn = $this->get_extension( $file );
		if ( is_null( $type ) ) {
			$type = $extn;
			switch( $type ) {
				case 'jpeg':
				case 'jpg':
				case 'png':
				case 'gif':
					$type = 'image';
				break;
			}
		}
		$path = $this->get_path( $type,$file );
		if ( $path === false ) {
			throw new \Exception("Unable to find path for '{$file}'");
		}
		$path = str_replace( la_frontend::$config['path_public'],'',$path );
		return ee()->config->item('base_url') . $path;
	}

}

?>