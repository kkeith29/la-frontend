<?php

namespace la_frontend;

use la_frontend;

la_frontend::load('la_frontend\\func');
la_frontend::load('la_frontend\\html');
la_frontend::load('la_frontend\\igsr');
la_frontend::load('la_frontend\\config');

//maybe add group field tracking back in to remove some foreach loops

class form {

	const method_get      = 'get';
	const method_post     = 'post';
	const temp_expr       = 3600;
	const filename_length = 20;
	const error_group     = 'general';

	const enable  = true;
	const disable = false;

	const before_field   = 1;
	const before_label   = 2;
	const before_element = 3;
	const after_field    = 4;
	const after_label    = 5;
	const after_element  = 6;

	const align_left  = 'left';
	const align_right = 'right';

	protected static $forms = array();

	protected $EE;
	protected $config = array(
		'error_class' => 'error',
		'tabs'        => 0,
		'accept-charset' => 'utf-8',
		'upload_temp' => null
	);
	protected $state_idx = 0;
	protected $state = array(
		'order' => array(),
		'groups' => array(),
		'group' => null,
		'tab_groups' => array(),
		'tab_group' => null,
		'repeatable_sections' => array(),
		'fields' => array(),
		'field_key' => null,
		'hidden_fields' => array(),
		'error_group' => self::error_group,
		'error_groups' => 0,
		'prev_error_group' => null,
		'insert' => null
	);
	protected $states = array();
	protected $templates = array();
	protected $order  = array();
	protected $groups = array();
	protected $tab_groups = array();
	protected $repeatable_sections = array();
	protected $fields = array();
	protected $hidden_fields = array();
	protected $error_group = self::error_group;
	protected $error_groups = 0;
	protected $prev_error_group = null;
	protected $main_error_group_shown = false;
	protected $valid  = array();
	protected $errors = array();
	protected $errmsg = array(
		'required'            => '%s is required',
		'exact_length'        => '%s must be exactly %d characters long',
		'min_length'          => '%s must be longer than or equal to %d characters',
		'max_length'          => '%s must be shorter than or equal to %d characters',
		'numeric'             => '%s must be numeric',
		'in_array_multi'      => '%s contains a invalid value',
		'in_array'            => '%s is not a valid value',
		'matches'             => '%s does not match \'%s\'',
		'not_match'           => '%s should not match \'%s\'',
		'compare'             => '%s does not match %s',
		'date'                => '%s is invalid',
		'date_format_1'       => '%s must be in the format %s',
		'date_format_2'       => '%s is not a valid date',
		'date_before'         => '%s must be before %s',
		'date_after'          => '%s must be after %s',
		'email'               => '%s is invalid',
		'password'            => '%s is not valid - reason: %s',
		'password_reason_1'   => 'must be at least %s characters long',
		'password_reason_2'   => 'uppercase letter',
		'password_reason_3'   => 'lowercase letter',
		'password_reason_4'   => 'number',
		'password_reason_5'   => 'special character',
		'url'                 => '%s is not a valid url',
		'phone'               => '%s must be in the format: %s',
		'regex_phone'         => '%s must be in the format 555-555-5555',
		'regex'               => '%s is not valid',
		'decimal_invalid_1'   => '%s must contain a decimal point',
		'decimal_invalid_2'   => '%s must be in the format: %s.%s',
		'upload_choose_file'  => '%s - Please choose a file to upload',
		'upload_invalid_file' => '%s - File is not valid',
		'upload_invalid_extn' => '%s - File extension not allowed. Only %s allowed.',
		'upload_invalid_img'  => '%s - File is not a valid image',
		'upload_invalid_size' => '%s - File exceeds the maximum file size of %s',
		'upload_image_small'  => '%s - Image dimensions must be greater than %spx x %spx',
		'upload_image_small_width'  => '%s - Image width must be greater than %spx',
		'upload_image_small_height' => '%s - Image height must be greater than %spx',
		'upload_image_large'  => '%s - Image dimensions must be less than %spx x %spx',
		'upload_image_large_width'  => '%s - Image width must be less than %spx',
		'upload_image_large_height' => '%s - Image height must be less than %spx',
		'upload_copy_error'   => '%s - Could not upload the file, please try again',
		'token_invalid'       => 'An error occurred while processing this form. Please <a href="%s">click here</a> to resolve this problem.',
		'time_fast'           => 'Form was submitted too quickly, please slow down and try again.',
		'time_slow'           => 'Form has expired. Please <a href="%s">click here</a> to resolve this problem.',
		'group_required'      => 'At least %d %s is required'
	);
	protected $template  = null;
	protected $group     = null;
	protected $tab_group = null;
	protected $field     = null;
	protected $field_key = null;
	protected $tab       = null;
	protected $insert    = null;
	protected $submitted = false;
	protected $security  = array(
		'token'       => true,
		'timeout'     => true,
		'timeout-min' => true,
		'timeout-max' => true
	);
	protected $timeout = array('min'=>5,'max'=>60);
	protected $upload = array();
	protected $upload_directories = array();
	protected $upload_fields = array();
	protected $delete = array();
	protected $deleted_fields = array();
	protected $files = null;
	protected $script = '';

	public $data = null;
	public $input = array();
	public $storage = null;

	public function __construct( $config=array() ) {
		$this->EE =& get_instance();
		$config['action'] = ( !isset( $config['action'] ) ? $this->EE->functions->fetch_current_uri() : $this->EE->functions->create_url( $config['action'] ) );
		if ( !isset( $config['id'] ) ) {
			$config['id'] = 'F' . md5( $config['action'] ) . '-' . ( count( self::$forms ) + 1 );
			self::$forms[$config['id']] = $this;
		}
		$config['method'] = ( isset( $config['method'] ) && in_array( $config['method'],array( self::method_post,self::method_get ) ) ? $config['method'] : self::method_post );
		$this->config = array_merge( $this->config,$config );
		if ( ( $form_id = $this->request('form_id') ) !== false && $form_id == $this->config['id'] ) {
			$this->submitted = true;
		}
		$this->files = new igsr;
		$this->files->set_data( $_FILES );
		$this->data = new igsr;
		$this->storage = new igsr;
		if ( isset( la_frontend::session()->data['forms'][$this->config['id']] ) ) {
			$this->data->set_data( la_frontend::session()->data['forms'][$this->config['id']] );
			if ( !$this->data->is_set('field_data') ) {
				$this->data->set('field_data',array());
			}
			if ( !$this->data->is_set('upload') ) {
				$this->data->set('upload',array());
			}
			if ( !$this->data->is_set('delete') ) {
				$this->data->set('delete',array());
			}
			$data =& $this->data->get_data();
			$this->input =& $data['field_data'];
			$this->upload =& $data['upload'];
			$this->delete =& $data['delete'];
			if ( count( $this->input ) > 0 ) {
				$this->security['timeout'] = false;
			}
			if ( count( $this->upload ) > 0 ) {
				foreach( $this->upload as $id => $upload ) {
					if ( !is_null( $upload ) && !file_exists( $upload['path'] ) ) {
						unset( $this->upload[$id] );
					}
				}
			}
			if ( $this->data->is_set('errors') ) {
				$this->errors = $this->data->get('errors');
				$this->data->remove('errors');
			}
		}
		if ( !$this->data->is_set('curr_page') ) {
			$this->data->set('last_page',1);
			$this->data->set('curr_page',1);
		}
		$this->init_state('main');
	}

	private function init_state( $idx ) {
		$this->state_idx = $idx;
		if ( !isset( $this->states[$idx] ) ) {
			$this->states[$idx] = $this->state;
		}
		foreach( array_keys( $this->states[$idx] ) as $key ) {
			$this->{$key} =& $this->states[$idx][$key];
		}
	}

	public function tabs( $int ) {
		$this->config['tabs'] = $int;
		return $this;
	}

	public function id() {
		return $this->config['id'];
	}

	public function config( $key,$value ) {
		$this->config[$key] = $value;
		return $this;
	}

	public function security( $type,$bool ) {
		if ( !isset( $this->security[$type] ) || !is_bool( $bool ) ) {
			throw new \Exception('Invalid security setting');
		}
		$this->security[$type] = $bool;
		return $this;
	}

	public function time( $min,$max ) {
		if ( !is_numeric( $min ) || !is_numeric( $max ) || $min < 0 || $max < $min ) {
			throw new \Exception('Invalid time parameters');
		}
		$this->timeout = compact('min','max');
		return $this;
	}

	public function request( $data,$value=null ) {
		if ( strpos( $data,'[' ) !== false ) {
			$data = explode( '[',str_replace( array('[]',']['),array('','['),$data ) );
		}
		else {
			$data = array( $data );
		}
		foreach( $data as $i => $datum ) {
			$data[$i] = rtrim( $datum,']' );
		}
		$array = ( $this->config['method'] == self::method_post ? $_POST : $_GET );
		$array =& $array;
		foreach( $data as $i => $key ) {
			if ( !isset( $array[$key] ) || ( !is_array( $array[$key] ) && strlen( trim( $array[$key] ) ) == 0 ) || ( is_array( $array[$key] ) && count( $array[$key] ) == 0 ) ) {
				return false;
			}
			if ( is_array( $array[$key] ) && $i !== ( count( $data ) - 1 ) ) {
				$array =& $array[$key];
				continue;
			}
			if ( !is_null( $value ) ) {
				$array[$key] = $value;
				return true;
			}
			return $array[$key];
		}
	}

	public function request_file( $data ) {
		if ( strpos( $data,'[' ) !== false ) {
			$data = explode( '[',str_replace( array('[]',']['),array('','['),$data ) );
		}
		else {
			$data = array( $data );
		}
		foreach( $data as $i => $datum ) {
			$data[$i] = rtrim( $datum,']' );
		}
		$file = array();
		if ( count( $data ) == 1 ) {
			if ( !isset( $_FILES[$data[0]] ) ) {
				return false;
			}
			foreach( $_FILES[$data[0]] as $type => $_data ) {
				if ( !is_array( $_data ) ) {
					return $_FILES[$data[0]];
				}
				foreach( $_data as $key => $value ) {
					$file[$key][$type] = $value;
				}
			}
			return $file;
		}
		$main = array_shift( $data );
		$path = implode( '/',$data );
		$file = array();
		foreach( array('name','type','size','error','tmp_name') as $datum ) {
			if ( ( $str = $this->files->get("{$main}/{$datum}/{$path}") ) === false ) {
				return false;
			}
			$file[$datum] = $str;
		}
		return $file;
	}

	public function submitted() {
		return $this->submitted;
	}

	public function curr_page() {
		return $this->data->get('curr_page');
	}

	public function last_page() {
		return $this->data->get('last_page');
	}

	public function set_page( $page,$redirect=true ) {
		$this->data->set('last_page',$this->data->get('curr_page'));
		$this->data->set('curr_page',$page);
		$this->save(false);
		if ( $redirect == true ) {
			redirect( $this->EE->functions->fetch_current_uri() );
		}
	}

	public function prev_page( $redirect=true ) {
		$this->set_page( $this->data->get('last_page'),$redirect );
	}

	public function next_page( $redirect=true ) {
		$this->set_page( ( $this->data->get('curr_page') + 1 ),$redirect );
	}

	private function normalize_name( $name,$raw=false ) {
		$sep = ( $raw === false ? '_' : '|' );
		$name = rtrim( str_replace( array('][','[',']'),$sep,$name ),$sep );
		return ( $raw === false ? $name : explode( '|',$name ) );
	}

	private function build_name( $parts ) {
		$first = array_shift( $parts );
		$parts = array_map( function( $value ) {
			return '[' . $value . ']';
		},$parts );
		return $first . implode( '',$parts );
	}

	private function arg( $args,$idx,$retval=null ) {
		$idx--;
		if ( !isset( $args[$idx] ) ) {
			return $retval;
		}
		return $args[$idx];
	}

	private function arg_set( &$args,$idx,$value ) {
		$idx--;
		$args[$idx] = $value;
	}

	public function __call( $method,$args ) {
		if ( !is_null( $this->template ) ) {
			$this->templates[$this->template]['calls'][] = array(
				'func' => $method,
				'args' => $args
			);
			return $this;
		}
		switch( $method ) {
			case 'group_start':
				$name   = $this->arg($args,1);
				$config = $this->arg($args,2,'');
				if ( is_null( $name ) ) {
					throw new \Exception('Group name is required');
				}
				$this->group = $name;
				if ( isset( $this->groups[$name] ) ) {
					break;
				}
				$this->groups[$name] = array(
					'config' => config::parse_str( $config ),
					'order'  => array()
				);
				$this->order[] = array(
					'type' => 'group',
					'group' => $this->group
				);
				$this->order =& $this->groups[$name]['order'];
			break;
			case 'group_end':
				$this->group = null;
				$this->order =& $this->states[$this->state_idx]['order'];
			break;
			case 'field':
				$name   = $this->arg($args,1);
				$config = $this->arg($args,2,'');
				if ( is_null( $name ) ) {
					throw new \Exception('Field name is required');
				}
				$this->field_key = $this->normalize_name( $name );
				$this->order[] = array(
					'type' => 'field',
					'field' => $this->field_key
				);
				if ( !isset( $this->fields[$this->field_key] ) ) {
					$this->fields[$this->field_key] = array(
						'error_group' => $this->error_group
					);
				}
				$this->field =& $this->fields[$this->field_key];
				$this->field['data-id'] = count( $this->fields );
				$this->field['name'] = $name;
				$this->field['field_config'] = config::parse_str( $config );
				if ( !is_null( $this->group ) ) {
					//$this->groups[$this->group]['fields'][] = $this->field_key;
					$this->field['group'] = $this->group;
				}
				/*elseif ( !is_null( $this->tab_group ) ) {
					$this->tab_groups[$this->tab_group][$this->tab]['fields'][] = $this->field_key;
					$this->field['tab_group'] = $this->tab_group;
					$this->field['tab'] = $this->tab;
				}*/
				$this->insert = self::before_label;
			break;
			case 'insert':
				$data     = $this->arg($args,1);
				$position = $this->arg($args,2,null);
				if ( is_null( $data ) ) {
					throw new \Exception('Insert data is required');
				}
				if ( !is_null( $this->template ) ) {
					$this->templates[$this->template]['order'][] = array(
						'func' => __FUNCTION__,
						'args' => func_get_args()
					);
					return $this;
				}
				if ( is_null( $position ) ) {
					$position = $this->insert;
				}
				$this->field['insert'][$position][] = $data;
			break;
			case 'html':
				$data = $this->arg($args,1);
				if ( is_null( $data ) ) {
					throw new \Exception('HTML is required');
				}
				$this->order[] = array(
					'type' => 'html',
					'html' => $data
				);
			break;
			case 'label':
				$data   = $this->arg($args,1);
				$config = $this->arg($args,2,'');
				if ( is_null( $data ) ) {
					throw new \Exception('Label is required');
				}
				$this->field['label'] = $data;
				$this->field['label_config'] = config::parse_str( $config );
				$this->insert = self::after_label;
			break;
			case 'element':
				$type     = $this->arg($args,1);
				$config_2 = $this->arg($args,2,'');
				$config_1 = $this->arg($args,3,'');
				if ( is_null( $type ) ) {
					throw new \Exception('Element type is required');
				}
				$this->field['element'] = $type;
				$this->field['element_config_1'] = config::parse_str( $config_1 );
				$this->field['element_config_2'] = config::parse_str( $config_2 );
				if ( $type == 'input:hidden' ) {
					$this->hidden_fields[] = $this->field;
				}
				elseif ( $type == 'input:file' ) {
					if ( !isset( $this->config['enctype'] ) ) {
						$this->config['enctype'] = 'multipart/form-data';
						$this->clean_upload_temp();
					}
					if ( !in_array( $this->field_key,$this->upload_fields ) ) {
						$this->upload_fields[] = $this->field_key;
						if ( isset( $config['file'] ) && !array_key_exists( $this->field_key,$this->upload ) ) {
							$this->set_upload_field( $this->field_key,$config['file'] );
						}
						if ( $this->submitted == true && $this->request("delete-{$this->field_key}") !== false ) {
							$this->clear_upload_field( $this->field_key );
							$this->submitted = false;
						}
					}
				}
				$this->insert = self::after_element;
			break;
			case 'rules':
				$data      = $this->arg($args,1);
				$config    = $this->arg($args,2,array());
				$overwrite = $this->arg($args,3,false);
				if ( is_null( $data ) ) {
					throw new \Exception('Rules are required');
				}
				$rules = config::parse_str( $data );
				if ( !isset( $this->field['rules'] ) ) {
					$this->field['rules'] = array();
				}
				$this->field['rules'] = ( $overwrite == true ? $rules : func::array_merge_recursive_distinct( $this->field['rules'],$rules ) );
				if ( !isset( $this->field['rules_config'] ) ) {
					$this->field['rules_config'] = array();
				}
				$this->field['rules_config'] = ( $overwrite == true ? $config : func::array_merge_recursive_distinct( $this->field['rules_config'],$config ) );
			break;
			case 'error_group_start':
				$name = $this->arg($args,1);
				$show = $this->arg($args,2);
				if ( is_null( $name ) ) {
					throw new \Exception('Error group name is required');
				}
				if ( is_null( $show ) ) {
					$show = true;
				}
				$this->prev_error_group = $this->error_group;
				$this->error_groups++;
				$this->error_group = $name;
				if ( $show ) {
					$this->order[] = array(
						'type' => 'error_group',
						'error_group' => $name
					);
				}
			break;
			case 'error_group_end':
				$this->error_group = $this->prev_error_group;
			break;
			case 'error_group_show':
				$name = $this->arg($args,1);
				if ( is_null( $name ) ) {
					$name = self::error_group;
					$this->main_error_group_shown = true;
				}
				$this->order[] = array(
					'type' => 'error_group',
					'error_group' => $name
				);
			break;
			case 'set_field':
				$name   = $this->arg($args,1);
				$value  = $this->arg($args,2);
				$bypass = $this->arg($args,3,false);
				if ( is_null( $name ) ) {
					throw new \Exception('Field name is required');
				}
				if ( is_null( $value ) ) {
					throw new \Exception('Field value is required');
				}
				$name = $this->normalize_name( $name );
				if ( $bypass == true || !array_key_exists( $name,$this->input ) ) {
					$this->input[$name] = ( is_string( $value ) ? html::entity_decode( $value ) : $value );
				}
				return $this;
			break;
			case 'set_fields':
				
			break;
		}
		return $this;
	}

	public function template_start( $name,$config='',$values=array() ) {
		$this->template = $name;
		if ( !isset( $this->templates[$name] ) ) {
			$this->templates[$name] = array(
				'config' => config::parse_str( $config ),
				'values' => $values,
				'count'  => 0,
				'calls'  => array(),
				'order'  => array()
			);
		}
		return $this;
	}

	public function template_end() {
		if ( is_null( $this->template ) ) {
			throw new \Exception('template_start() must be called before this function');
		}
		$tname = $this->template;
		$template =& $this->templates[$tname];
		$this->template = null;
		if ( isset( $template['config']['repeatable'] ) && $template['config']['repeatable'] === true ) {
			$fields = array();
			foreach( $template['calls'] as &$item ) {
				switch( $item['func'] ) {
					case 'field':
						$name = $this->arg( $item['args'],1 );
						$parts = $this->normalize_name( $name,true );
						array_unshift( $parts,'{index}' );
						array_unshift( $parts,$tname );
						$new_name = $this->build_name( $parts );
						$this->arg_set( $item['args'],1,$new_name );
						$fields[$name] = $new_name;
					break;
					default:
						continue 2;
					break;
				}
			}
			if ( !isset( $template['config']['count_field'] ) ) {
				$template['config']['count_field'] = "{$this->template}_count";
				$this->field( $template['config']['count_field'] )->element('input:hidden')->rules('numeric');
			}
			$template['count'] = ( ( $value = $this->request( $template['config']['count_field'] ) ) === false ? ( ( $value = $this->input( $template['config']['count_field'],false ) ) !== false ? (int) $value : 0 ) : (int) $value );
			for( $i=1;$i <= $template['count'];$i++ ) {
				if ( isset( $template['values'][$i] ) ) {
					foreach( $template['values'][$i] as $field => $value ) {
						if ( !isset( $fields[$field] ) ) {
							continue;
						}
						array_unshift( $template['calls'],array(
							'func' => 'set_field',
							'args' => array( $fields[$field],$value )
						) );
					}
				}
				$this->order =& $this->templates[$tname]['order'];
				$this->template_fill( $tname,array(
					'template' => $tname,
					'index'    => $i
				) );
			}
			$this->order =& $this->states[$this->state_idx]['order'];
			$this->order[] = array(
				'type'     => 'template',
				'template' => $tname
			);
		}
		return $this;
	}

	public function template_info( $name ) {
		if ( !isset( $this->templates[$name] ) ) {
			throw new \Exception('Template not found');
		}
		return $this->templates[$name];
	}

	public function template_fill( $name,$vars=array() ) {
		if ( !isset( $this->templates[$name] ) ) {
			throw new \Exception('Template not found');
		}
		if ( isset( $this->templates[$name]['config']['repeatable'] ) && $this->templates[$name]['config']['repeatable'] === true ) {
			if ( !isset( $this->repeatable_sections[$name] ) ) {
				$this->repeatable_sections[$name] = array();
			}
			$idx = ( count( $this->repeatable_sections[$name] ) + 1 );
			if ( !isset( $this->repeatable_sections[$name][$idx] ) ) {
				$this->repeatable_sections[$name][$idx] = array();
			}
			$old_order =& $this->order;
			$this->order =& $this->repeatable_sections[$name][$idx];
		}
		if ( $vars !== false ) {
			$values = array_values( $vars );
			$vars = array_map( function( $var ) {
				return '{' . $var . '}';
			},array_keys( $vars ) );
		}
		foreach( $this->templates[$name]['calls'] as $data ) {
			if ( $vars !== false && count( $data['args'] ) > 0 ) {
				$data['args'] = array_map( function( $arg ) use ( $vars,$values ) {
					return ( is_string( $arg ) ? str_replace( $vars,$values,$arg ) : $arg );
				},$data['args'] );
			}
			call_user_func_array( array( $this,$data['func'] ),$data['args'] );
		}
		if ( isset( $idx ) ) {
			$this->order =& $old_order;
			$this->order[] = array(
				'type'     => 'repeatable_section',
				'template' => $name,
				'section'  => $idx
			);
		}
	}

	public function build_template( $name,$vars=false ) {
		$prev_state = $this->state_idx;
		$this->init_state( $name );
		$this->template_fill( $name,$vars );
		$html = $this->build_order( $this->order );
		unset( $this->states[$this->state_idx] );
		$this->init_state( $prev_state );
		return $html;
	}

	/*public function tab_group( $name ) {
		if ( !is_null( $this->template ) ) {
			$this->templates[$this->template]['order'][] = array(
				'func' => __FUNCTION__,
				'args' => func_get_args()
			);
			return $this;
		}
		$this->tab_group = $name;
		if ( $name === false ) {
			$this->tab_group = null;
			$this->tab = null;
		}
		elseif ( !isset( $this->tab_groups[$name] ) ) {
			$this->tab_groups[$name] = array();
			$this->order[] = array(
				'type' => 'tab_group',
				'tab_group' => $this->tab_group
			);
		}
		return $this;
	}

	public function tab( $name,$config='' ) {
		if ( !is_null( $this->template ) ) {
			$this->templates[$this->template]['order'][] = array(
				'func' => __FUNCTION__,
				'args' => func_get_args()
			);
			return $this;
		}
		if ( is_null( $this->tab_group ) ) {
			throw new \Exception('A tab group must be defined before adding a tab');
		}
		$this->tab = ( $name === false ? null : $name );
		if ( !isset( $this->tab_groups[$this->tab_group][$this->tab] ) ) {
			$this->tab_groups[$this->tab_group][$this->tab] = array(
				'config' => config::parse_str( $config ),
				'fields' => array()
			);
		}
		return $this;
	}*/

	public function set_field( $name,$value,$bypass=false ) {
		$name = $this->normalize_name( $name );
		if ( $bypass == true || !array_key_exists( $name,$this->input ) ) {
			$this->input[$name] = ( is_string( $value ) ? html::entity_decode( $value ) : $value );
		}
		return $this;
	}

	public function set_fields( $fields,$skip=array() ) {
		if ( is_object( $fields ) ) {
			$fields = $fields->to_array();
		}
		foreach( $fields as $name => $value ) {
			if ( in_array( $name,$skip ) ) {
				continue;
			}
			$this->set_field( $name,$value );
		}
		return $this;
	}

	public function upload_directory( $name,$config=array() ) {
		if ( count( ( $diff = array_diff( array('allowed_extns','maxsize','directory'),array_keys( $config ) ) ) ) > 0 ) {
			throw new \Exception('Missing config vars: ' . implode( ', ',$diff ));
		}
		if ( ( $maxsize = func::to_bytes( $config['maxsize'] ) ) === false ) {
			throw new \Exception('Invalid max file size');
		}
		$config['maxsize'] = $maxsize;
		if ( !is_array( $config['allowed_extns'] ) ) {
			$config['allowed_extns'] = explode( ',',$config['allowed_extns'] );
		}
		$this->upload_directories[$name] = $config;
		return $this;
	}

	public function get_errors( $group=null ) {
		if ( !is_null( $group ) ) {
			return ( isset( $this->errors[$group] ) ? $this->errors[$group] : array() );
		}
		$errors = array();
		foreach( $this->errors as $group => $data ) {
			foreach( array_filter( $data ) as $name => $errmsg ) {
				$errors[$name] = $errmsg;
			}
		}
		return $errors;
	}

	public function set_error( $name,$errmsg='',$group=self::error_group ) {
		$this->errors[$group][$name] = $errmsg;
		if ( isset( $this->fields[$name] ) ) {
			$this->fields[$name]['error'] = $errmsg;
		}
		return $this;
	}

	public function no_errors() {
		return ( count( $this->errors ) == 0 ? true : false );
	}

	public function pressed( $name,$value=null ) {
		if ( ( $field_value = $this->request( $name ) ) === false ) {
			return false;
		}
		if ( !is_null( $value ) && $field_value !== $value ) {
			return false;
		}
		return true;
	}

	public function validate( $field=false,$value=null,$group=null ) {
		if ( !is_null( $group ) && !isset( $this->groups[$group] ) ) {
			throw new \Exception("Group '{$group}' does not exist");
		}
		if ( $this->submitted == false || ( $field !== false && !is_null( $value ) && ( ( $fvalue = $this->request( $field ) ) === false || $fvalue !== $value ) ) ) {
			return false;
		}
		if ( $this->security['token'] == true && ( ( $token = $this->request('form_token') ) === false || ( $this->data->is_set('token') && $this->data->get('token') !== (string) $token ) ) ) {
			$errmsg = 'token_invalid';
			$errvar = array( $this->EE->functions->fetch_current_uri() );
		}
		elseif ( $this->security['timeout'] == true && $this->security['timeout-min'] == true && ( time() - $this->data->get('time') ) < $this->timeout['min'] ) {
			$errmsg = 'time_fast';
		}
		elseif ( $this->security['timeout'] == true && $this->security['timeout-max'] == true && ( time() - $this->data->get('time') ) > $this->timeout['max'] ) {
			$errmsg = 'time_slow';
			$errvar = array( $this->EE->functions->fetch_current_uri() );
		}
		if ( isset( $errmsg ) ) {
			$this->set_error('form',vsprintf( $this->errmsg[$errmsg],( isset( $errvar ) ? $errvar : array() )  ));
			return false;
		}
		if ( is_null( $group ) ) {
			$fields = $this->fields;
		}
		else {
			$fields = array();
			foreach( $this->groups[$group]['fields'] as $name ) {
				$fields[$name] = $this->fields[$name];
			}
		}
		$last_fields = array(
			'groups' => array()
		);
		if ( count( $this->groups ) > 0 ) {
			foreach( $this->groups as $name => $group ) {
				foreach( array_reverse( $group['order'] ) as $item ) {
					if ( $item['type'] !== 'field' ) {
						continue;
					}
					$last_fields['groups'][$name] = $item['field'];
					break;
				}
			}
		}
		foreach( $fields as $name => $data ) {
			if ( isset( $data['element'] ) && in_array( $data['element'],array('input:submit','input:image') ) ) {
				continue;
			}
			$field_value = ( $data['element'] == 'input:file' ? $this->request_file( $data['name'] ) : $this->request( $data['name'] ) );
			if ( $field_value !== false && $data['element'] !== 'input:file' ) {
				$this->input[$name] = $field_value;
			}
			elseif ( isset( $this->input[$name] ) ) {
				unset( $this->input[$name] );
			}
			if ( isset( $data['rules'] ) ) {
				if ( $data['element'] == 'input:file' && !isset( $data['rules']['upload'] ) ) {
					throw new \Exception("Configuration for upload missing for field: {$name}");
				}
				if ( ( $errmsg = $this->validate_field( $name,$data,$field_value ) ) !== true ) {
					$this->set_error( $name,$errmsg,$data['error_group'] );
				}
			}
			if ( isset( $data['group'] ) && $last_fields['groups'][$data['group']] == $name && isset( $this->groups[$data['group']]['config']['required'] ) ) {
				$required = (int) $this->groups[$data['group']]['config']['required'];
				foreach( array_reverse( $this->groups[$data['group']]['order'] ) as $item ) {
					if ( $item['type'] !== 'field' ) {
						continue;
					}
					if ( isset( $this->input[$item['field']] ) ) {
						$required--;
					}
					if ( $required <= 0 ) {
						break;
					}
				}
				if ( $required > 0 ) {
					$this->set_error( "group_{$data['group']}",sprintf( $this->errmsg['group_required'],$this->groups[$data['group']]['config']['required'],$this->groups[$data['group']]['config']['label'] ),$data['error_group'] );
				}
			}
		}
		if ( $this->no_errors() ) {
			return true;
		}
		return false;
	}

	protected function validate_field( $name,$data,$field_value ) {
		$rules = $data['rules'];
		if ( count( $rules ) == 1 && ( isset( $rules['required_if'] ) || isset( $rules['group_required'] ) ) ) {
			$rules['required_if_temp'] = false;
		}
		if ( $data['element'] !== 'input:file' && !isset( $rules['required'] ) && !isset( $rules['required_if'] ) && !isset( $rules['group_required'] ) && $field_value === false ) {
			if ( isset( $this->input[$name] ) ) {
				unset( $this->input[$name] );
			}
			return true;
		}
		elseif ( $data['element'] == 'input:file' ) {
			if ( !isset( $rules['required'] ) && ( $field_value === false || $field_value['error'] == UPLOAD_ERR_NO_FILE ) ) {
				return true;
			}
			if ( isset( $this->upload[$name] ) && !is_null( $this->upload[$name] ) ) {
				return true;
			}
		}
		$parts =& $rules;
		$config = $data['rules_config'];
		$errvar = array();
		$idx = 0;
		foreach( $parts as $datum => $param ) {
			switch( $datum ) {
				case 'required':
					if ( $data['element'] !== 'input:file' ) {
						if ( $field_value === false ) {
							$errmsg = 'required';
						}
					}
					else {
						if ( $field_value === false || $field_value['error'] !== UPLOAD_ERR_OK || empty( $field_value['name'] ) ) {
							$errmsg = 'upload_choose_file';
						}
					}
				break;
				case 'required_if_temp':
					continue 2;
				break;
				case 'required_if':
					foreach( $param as $type => $_param ) {
						switch( $type ) {
							case 'field_equals':
								$_param = array_chunk( $_param,2 );
								foreach( $_param as $__param ) {
									list( $field,$value ) = $__param;
									$_field_value = $this->request( $field );
									if ( $_field_value === false ) {
										continue;
									}
									if ( ( is_array( $_field_value ) && in_array( $value,$_field_value ) ) || ( !is_array( $_field_value ) && $_field_value == $value ) ) {
										array_splice( $rules,$idx,1,array('required'=>false) );
										continue 4;
									}
								}
								return true;
							break;
							case 'has_value':
								$fields = explode( ',',$_param );
								foreach( $fields as $field ) {
									if ( $this->normalize_name( $field ) == $name ) {
										continue;
									}
									if ( $this->request( $field ) !== false ) {
										array_splice( $rules,$idx,1,array('required'=>false) );
										break 4;
									}
								}
								return true;
							break;
						}
					}
				break;
				case 'group_required':
					//$group_fields = array();
					if ( !isset( $data['group'] ) && !isset( $data['tab_group'] ) ) {
						throw new \Exception('Field required to be part of group to use group_required rule');
					}
					$order = ( isset( $data['group'] ) ? $this->groups[$data['group']]['order'] : $this->tab_groups[$data['tab_group']][$data['tab']]['order'] );
					foreach( $order as $i => $item ) {
						if ( $item['type'] !== 'field' ) {
							continue;
						}
						if ( !isset( $this->fields[$item['field']]['rules']['group_required'] ) || $item['field'] == $name ) {
							//unset( $_fields[$i] );
							continue;
						}
						if ( $this->request( $this->fields[$item['field']]['name'] ) !== false ) {
							array_splice( $rules,$idx,1,array('required'=>false) );
							break 2;
						}
					}
					return true;
				break;
				case 'default':
					if ( !in_array( 'required',$rules ) && $field_value == $param ) {
						$this->request( $name,'' );
						return true;
					}
				break;
				case 'exact_length':
					if ( strlen( $field_value ) !== (int) $param ) {
						$errmsg = 'exact_length';
						$errvar = array( $param );
					}
				break;
				case 'min_length':
					if ( strlen( $field_value ) < $param ) {
						$errmsg = 'min_length';
						$errvar = array( $param );
					}
				break;
				case 'max_length':
					if ( strlen( $field_value ) > $param ) {
						$errmsg = 'max_length';
						$errvar = array( $param );
					}
				break;
				case 'numeric':
					if ( !is_numeric( $field_value ) ) {
						$errmsg = 'numeric';
					}
				break;
				case 'in_array':
				case 'in_array_keys':
					if ( !isset( $config[$param] ) && !isset( $data['element_config_2']['options'] ) && !isset( $data['element_config_2']['list'] ) ) {
						throw new \Exception('Array not found in config');
					}
					if ( isset( $config[$param] ) ) {
						$array = $config[$param];
					}
					elseif ( isset( $data['element_config_2']['list'] ) ) {
						$array = $this->storage->get( $data['element_config_2']['list'] );
					}
					elseif ( isset( $data['element_config_2']['options'] ) ) {
						if ( isset( $data['element_config_2']['has-optgroup'] ) && $data['element_config_2']['has-optgroup'] == true ) {
							$options = $this->storage->get( $data['element_config_2']['options'] );
							$array = array();
							foreach( $options as $group ) {
								foreach( $group as $key => $option ) {
									$array[$key] = $option;
								}
							}
							unset( $options );
						}
						else {
							$array = $this->storage->get( $data['element_config_2']['options'] );
						}
					}
					if ( $datum == 'in_array_keys' ) {
						$array = array_keys( $array );
					}
					if ( is_array( $field_value ) ) {
						foreach( $field_value as $val ) {
							if ( !in_array( $val,$array ) ) {
								$errmsg = 'in_array_multi';
								break;
							}
						}
						break;
					}
					if ( !in_array( $field_value,$array ) ) {
						$errmsg = 'in_array';
					}
				break;
				case 'matches':
					if ( $field_value !== $param ) {
						$errmsg = 'matches';
						$errvar = array( $param );
					}
				break;
				case 'not_match':
					if ( $field_value == $param ) {
						$errmsg = 'not_match';
						$errvar = array( $param );
					}
				break;
				case 'compare':
					if ( $param === true && !isset( $config['compare'] ) ) {
						throw new \Exception('Field required to compare with');
					}
					$param = $this->normalize_name(( $param === true ? $config['compare'] : $param ));
					if ( !in_array( $param,$this->valid ) || !isset( $this->input[$param] ) || $this->input[$param] !== $field_value ) {
						$errmsg = 'compare';
						$errvar = array( $this->fields[$param]['label'] );
					}
				break;
				case 'date':
					$error = false;
					if ( !is_numeric( $field_value ) ) {
						$error = true;
					}
					else {
						switch( $param ) {
							case 'month':
								if ( (int) $field_value < 1 || (int) $field_value > 12 ) {
									$error = true;
								}
							break;
							case 'day':
								if ( (int) $field_value < 1 || (int) $field_value > 31 ) {
									$error = true;
								}
							break;
							case 'year':
								if ( (int) $field_value < 1000 || (int) $field_value > 3000 ) {
									$error = true;
								}
							break;
							default:
								throw new \Exception('Invalid date part');
							break;
						}
					}
					if ( $error == true ) {
						$errmsg = 'date';
					}
				break;
				case 'date_format':
					$error = false;
					$format = $param;
					if ( $format === false ) {
						$format = 'MM-DD-YYYY';
					}
					else {
						$format = strtoupper( $format );
					}
					$sep = substr( str_replace( array( 'MM','DD','YYYY','YY' ),'',$format ),0,1 );
					$format_parts = explode( $sep,$format );
					$regexes = array(
						'MM'   => '([0-9]{1,2})',
						'DD'   => '([0-9]{1,2})',
						'YY'   => '([0-9]{2})',
						'YYYY' => '([0-9]{4})'
					);
					$regex = array();
					foreach( $format_parts as $part ) {
						$regex[] = $regexes[$part];
					}
					if ( preg_match( '#^' . implode( '[ /\-\.]{1}',$regex ) . '$#',$field_value,$matches ) !== 1 ) {
						$error = true;
						$_errmsg = 'date_format_1';
						$errvar  = array( $format );
					}
					else {
						$i = 1;
						foreach( $format_parts as $part ) {
							$value = (int) $matches[$i];
							switch( $part ) {
								case 'MM':
									if ( $value < 1 || $value > 12 ) {
										$error = true;
										break;
									}
									$matches[$i] = func::pad( $value,2,'0' );
								break;
								case 'DD':
									if ( $value < 1 || $value > 31 ) {
										$error = true;
										break;
									}
									$matches[$i] = func::pad( $value,2,'0' );
								break;
								case 'YY':
								case 'YYYY':
									if ( strlen( $matches[$i] ) == 3 || ( $part == 'YYYY' && ( $value < 1000 || $value > 3000 ) ) ) {
										$error = true;
										break;
									}
									if ( strlen( $value ) <= 2 ) {
										if ( $value > ( date('y') + 1 ) ) {
											$century = '19';
										}
										else {
											$century = '20';
										}
										$matches[$i] = $century . func::pad( $value,2,'0' );
									}
								break;
							}
							$i++;
						}
					}
					if ( $error == true ) {
						$errmsg = ( isset( $_errmsg ) ? $_errmsg : 'date_format_2' );
					}
					else {
						array_shift( $matches );
						$field_value = implode( $sep,$matches );
					}
				break;
				case 'date_before':
				case 'date_after':
					$time = ( $param !== false ? $param : ( isset( $config[$datum]['time'] ) ? $config[$datum]['time'] : false ) );
					if ( $time == 'now' || $time == false ) {
						$time = time();
					}
					elseif ( $time == 'field' ) {
						if ( !isset( $config[$datum]['field'] ) ) {
							throw new \Exception('Field and field_format required for this validator');
						}
						$field = $this->normalize_name( $config[$datum]['field'] );
						$field_format = ( !isset( $config[$datum]['field_format'] ) ? 'MM-DD-YYYY' : $config[$datum]['field_format'] );
						if ( !in_array( $field,$this->valid ) ) {
							break;
						}
						$time = strtotime( func::convert_date( $field_format,'YYYY-MM-DD',$this->input[$field] ) );
					}
					elseif ( is_numeric( $time ) ) {
						$time = (int) $time;
					}
					elseif ( is_string( $time ) ) {
						$time = strtotime( $time );
					}
					$format = ( isset( $config[$datum]['format'] ) ? $config[$datum]['format'] : 'MM-DD-YYYY' );
					$value = (int) strtotime( func::convert_date( $format,'YYYY-MM-DD',$field_value ) );
					if ( $datum == 'date_before' && $value > $time ) {
						$errmsg = 'date_before';
						$errvar = array( date('m-d-Y',$time) );
					}
					elseif ( $datum == 'date_after' && $value < $time ) {
						$errmsg = 'date_after';
						$errvar = array( date('m-d-Y',$time) );
					}
				break;
				case 'decimal':
					if ( $param == true || !is_array( $param ) || count( $param ) !== 2 ) {
						throw new \Exception('Invalid parameters');
					}
					list( $before,$after ) = $param;
					if ( strpos( $field_value,'.' ) === false ) {
						$errmsg = 'decimal_invalid_1';
					}
					else {
						list( $b,$a ) = explode( '.',$field_value,2 );
						if ( preg_match( '#^[0-9]+$#',$b ) !== 1 || preg_match( '#^[0-9]+$#',$a ) !== 1 || strlen( $b ) > $before || strlen( $a ) > $after ) {
							$errmsg = 'decimal_invalid_2';
							$errvar = array( str_repeat( 'X',$before ),str_repeat( 'X',$after ) );
						}
					}
				break;
				case 'email':
					if ( !func::validate_email( $field_value ) ) {
						$errmsg = 'email';
					}
				break;
				case 'password':
					$_errmsg = array();
					if ( strlen( $field_value ) < $param ) {
						$_errmsg['length'] = sprintf( $this->errmsg['password_reason_1'],$param );
					}
					if ( preg_match( '/[A-Z]/',$field_value ) === 0 ) {
						$_errmsg['uppercase'] = $this->errmsg['password_reason_2'];
					}
					if ( preg_match( '/[a-z]/',$field_value ) === 0 ) {
						$_errmsg['lowercase'] = $this->errmsg['password_reason_3'];
					}
					if ( preg_match( '/[0-9]/',$field_value ) === 0 ) {
						$_errmsg['number'] = $this->errmsg['password_reason_4'];
					}
					if ( preg_match( '/[^a-zA-Z0-9]/',$field_value ) === 0 ) {
						$_errmsg['special'] = $this->errmsg['password_reason_5'];
					}
					$errstr = '';
					if ( isset( $_errmsg['length'] ) ) {
						$errstr .= sprintf( $this->errmsg['password_reason_1'],$param );
					}
					if ( isset( $_errmsg['uppercase'] ) || isset( $_errmsg['lowercase'] ) || isset( $_errmsg['number'] ) || isset( $_errmsg['special'] ) ) {
						$errstr .= ( isset( $_errmsg['length'] ) ? ' and ' : ' must ' ) . 'have at least one '; //add these to language array
					}
					if ( isset( $_errmsg['length'] ) ) {
						unset( $_errmsg['length'] );
					}
					$errstr .= implode( ', ',$_errmsg );
					if ( $errstr !== '' ) {
						$errmsg = 'password';
						$errvar = array( $errstr );
					}
				break;
				case 'url':
					if ( strpos( $field_value,'http://' ) === false && strpos( $field_value,'https://' ) === false ) {
						$errmsg = 'url';
					}
				break;
				case 'phone':
					preg_match_all( '#[X]+#',$param,$matches );
					if ( !isset( $matches[0] ) || count( $matches[0] ) == 0 ) {
						throw new \Exception('Invalid phone number format');
					}
					$parts = preg_split( '#[X]+#',$param );
					if ( !isset( $parts[0] ) ) {
						throw new \Exception('Invalid phone number format');
					}
					$format = array();
					foreach( $parts as &$part ) {
						$part = str_replace( array('[','\\','^','$','.','|','?','*','+','(',')','{','}'),array('\[','\\\\','\^','\$','\.','\|','\?','\*','\+','\(','\)','\{','\}'),$part );
					}
					foreach( $matches[0] as &$match ) {
						$match = '[0-9]{' . strlen( $match ) . '}';
					}
					$array_one = $parts;
					$array_two = $matches[0];
					for( $i=0,$e_i=0,$o_i=0;$i < ( count( $array_one ) + count( $array_two ) );$i++ ) {
						if ( $i % 2 == 0 ) {
							if ( !isset( $array_one[$e_i] ) ) { //first
								continue;
							}
							$format[] = $array_one[$e_i];
							$e_i++;
						}
						else {
							if ( !isset( $array_two[$o_i] ) ) { //second
								continue;
							}
							$format[] = $array_two[$o_i];
							$o_i++;
						}
					}
					$format = implode( '',array_filter( $format ) );
					if ( preg_match( "#^{$format}$#",$field_value ) !== 1 ) {
						$errmsg = 'phone';
						$errvar = array( $param );
					}
				break;
				case 'regex':
					switch( $param ) {
						case 'alphanum':
							$regex = '^[a-zA-Z0-9]+$';
						break;
						case 'alphanum_dash':
							$regex = '^[a-zA-Z0-9_\-]+$';
						break;
						case 'alpha':
							$regex = '^[a-zA-Z]+$';
						break;
						case 'phone':
							$regex = '^([0-9]{3})[ \-\.]{1}([0-9]{3})[ \-\.]{1}([0-9]{4})$';
							$_errmsg = 'regex_phone';
						break;
						case 'datetime':
							$regex = '^(1[012]{1}|0[1-9]{1})[/\-\. ]{1}(0[1-9]{1}|[12]{1}[0-9]{1}|3[01]{1})[/\-\. ]{1}((19|20)[0-9]{2})[\s]+(0[1-9]{1}|1[012]{1})[\:]{1}([012345]{1}[0-9]{1})[\:]{1}([012345]{1}[0-9]{1})[\s]+([aApP]{1}[mM]{1})$';
						break;
						case 'ssn':
							$regex = '^[0-9]{3}-[0-9]{2}-[0-9]{4}$';
						break;
						default:
							if ( !isset( $config['regex'] ) ) {
								throw new \Exception('No regex provided in config array');
							}
							$regex = $config['regex'];
						break;
					}
					if ( preg_match( "#{$regex}#",$field_value,$matches ) !== 1 ) {
						$errmsg = ( isset( $_errmsg ) ? $_errmsg : 'regex' );
					}
					else {
						if ( $param == 'phone' ) {
							$field_value = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
						}
					}
				break;
				case 'xss_clean':
					if ( $field_value === false ) {
						break;
					}
					if ( is_array( $field_value ) ) {
						$field_value = array_map( 'strip_tags',$field_value );
						$field_value = array_map( 'htmlspecialchars',$field_value );
						break;
					}
					$field_value = htmlspecialchars( strip_tags( $field_value ) );
				break;
				case 'upload':
					if ( !isset( $this->upload_directories[$rules['upload']] ) ) {
						throw new \Exception("Unable to find upload directory config for {$rules['upload']}");
					}
					$upload_config = $this->upload_directories[$rules['upload']];
					if ( !is_uploaded_file( $field_value['tmp_name'] ) ) {
						$errmsg = 'upload_invalid_file';
					}
					else {
						$extn = explode( '.',$field_value['name'] );
						$extn = strtolower( end( $extn ) );
						if ( !in_array( $extn,$upload_config['allowed_extns'] ) ) {
							$errmsg = 'upload_invalid_extn';
							$errvar = array( implode( ', ',$upload_config['allowed_extns'] ) );
						}
						else {
							$image = false;
							if ( in_array( $extn,array('jpg','jpeg','gif','png') ) ) {
								$image = true;
							}
							if ( $image == true && false === ( $info = getimagesize( $field_value['tmp_name'] ) ) ) {
								$errmsg = 'upload_invalid_img';
							}
							elseif ( filesize( $field_value['tmp_name'] ) > $upload_config['maxsize'] ) {
								$errmsg = 'upload_invalid_size';
								$errvar = array( func::format_filesize( $upload_config['maxsize'] ) );
							}
							else {
								if ( $image == true && isset( $info ) ) {
									list( $width,$height ) = $info;
									//checking minimum dimensions
									if ( isset( $upload_config['min_dimensions'] ) ) {
										if ( count( $upload_config['min_dimensions'] ) !== 2 ) {
											throw new \Exception('Rule \'upload:min_dimensions\' requires two parameters: [width][height]');
										}
										list( $upload_config['min_width'],$upload_config['min_height'] ) = $upload_config['min_dimensions'];
									}
									$min_width = $min_height = false;
									if ( isset( $upload_config['min_width'] ) && $width < $upload_config['min_width'] ) {
										$min_width = true;
									}
									if ( isset( $upload_config['min_height'] ) && $height < $upload_config['min_height'] ) {
										$min_height = true;
									}
									if ( $min_width == true && $min_height == true ) {
										$errmsg = 'upload_image_small';
										$errvar = array( $upload_config['min_width'],$upload_config['min_height'] );
									}
									elseif ( $min_width == true ) {
										$errmsg = 'upload_image_small_width';
										$errvar = array( $upload_config['min_width'] );
									}
									elseif ( $min_height == true ) {
										$errmsg = 'upload_image_small_height';
										$errvar = array( $upload_config['min_height'] );
									}
									if ( $min_width == true || $min_height == true ) {
										break;
									}
									//checking maximum dimensions
									if ( isset( $upload_config['max_dimensions'] ) ) {
										if ( count( $upload_config['max_dimensions'] ) !== 2 ) {
											throw new \Exception('Rule \'upload:max_dimensions\' requires two parameters: [width][height]');
										}
										list( $upload_config['max_width'],$upload_config['max_height'] ) = $upload_config['max_dimensions'];
									}
									$max_width = $max_height = false;
									if ( isset( $upload_config['max_width'] ) && $width > $upload_config['max_width'] ) {
										$max_width = true;
									}
									if ( isset( $upload_config['max_height'] ) && $height > $upload_config['max_height'] ) {
										$max_height = true;
									}
									if ( $max_width == true && $max_height == true ) {
										$errmsg = 'upload_image_large';
										$errvar = array( $upload_config['max_width'],$upload_config['max_height'] );
									}
									elseif ( $max_width == true ) {
										$errmsg = 'upload_image_large_width';
										$errvar = array( $upload_config['max_width'] );
									}
									elseif ( $max_height == true ) {
										$errmsg = 'upload_image_large_height';
										$errvar = array( $upload_config['max_height'] );
									}
									if ( $max_width == true || $max_height == true ) {
										break;
									}
									$field_value['type'] = $info['mime'];
								}
								if ( !isset( $upload_config['preserve_name'] ) || $upload_config['preserve_name'] === false ) {
									$file = time() . '_' . func::rand_string( self::filename_length,'alpha,numeric' ) . ".{$extn}";
								}
								else {
									$file = trim( preg_replace( '#[_]{2,}#','_',preg_replace( '#[^a-zA-Z0-9_]+#','_',substr( $field_value['name'],0,( strlen( $field_value['name'] ) - ( strlen( $extn ) + 1 ) ) ) ) ),'_' ) . ".{$extn}";
								}
								if ( is_null( $this->config['upload_temp'] ) ) {
									throw new \Exception('Temp directory not set');
								}
								$temp = rtrim( $this->config['upload_temp'],'/' );
								if ( !is_readable( $temp ) || !is_writable( $temp ) ) {
									throw new \Exception('Temp directory permissions are incorrect');
								}
								$path = "{$temp}/{$file}";
								if ( !copy( $field_value['tmp_name'],$path ) ) {
									$errmsg = 'upload_copy_error';
								}
					           	else {
									$this->upload[$name] = array(
										'path' => $path,
										'file' => $file,
										'base' => basename( $field_value['name'] ),
										'type' => $field_value['type'],
										'size' => $field_value['size'],
										'move-to' => rtrim( $upload_config['directory'],'/' )
									);
									unlink( $field_value['tmp_name'] );
							    }
						    }
                        }
				    }
				break;
				case 'function':
					if ( !isset( $config['function'] ) && !is_array( $param ) ) {
						throw new \Exception('Must provide a function');
					}
					if ( !isset( $config['function'] ) ) {
						$config['function'] = $param;
					}
					if ( !isset( $config['function']['errmsg'] ) ) {
						throw new \Exception('No error message provided for error');
					}
					if ( isset( $config['function']['model'] ) ) {
						$config['function']['function'] = array( model::_get( $config['function']['model'][0] ),$config['function']['model'][1] );
						unset( $config['function']['model'] );
					}
					elseif ( isset( $config['function']['helper'] ) ) {
						$config['function']['function'] = array( helper::_get( $config['function']['helper'][0] ),$config['function']['helper'][1] );
						unset( $config['function']['helper'] );
					}
					if ( !call_user_func( $config['function']['function'],$field_value,$this ) ) {
						$errmsg = $config['function']['errmsg'];
					}
				break;
				default:
					$funcs = get_defined_functions();
					if ( in_array( $datum,$funcs['user'] ) ) {
						if ( $field_value !== false ) {
							$retval = $datum( $field_value );
							if ( $retval !== true ) {
								$errmsg = $retval;
							}
						}
					}
					elseif ( in_array( $datum,$funcs['internal'] ) ) {
						if ( $field_value !== false ) {
							$field_value = ( is_array( $field_value ) ? array_map( $datum,$field_value ) : $datum( $field_value ) );
						}
					}
					else {
						throw new \Exception('Invalid validator');
					}
				break;
			}
			if ( isset( $errmsg ) ) {
				$label = ( isset( $data['label_config']['error-label'] ) ? $data['label_config']['error-label'] : $data['label'] );
				if ( isset( $data['group'] ) && isset( $this->groups[$data['group']]['config']['field-label-prefix'] ) ) {
					$label = $this->groups[$data['group']]['config']['field-label-prefix'] . $label;
				}
				elseif ( isset( $data['tab_group'] ) && isset( $this->tab_groups[$data['tab_group']][$data['tab']]['config']['field-label-prefix'] ) ) {
					$label = $this->tab_groups[$data['tab_group']][$data['tab']]['config']['field-label-prefix'] . $label;
				}
				array_unshift( $errvar,$label );
				return ( isset( $this->errmsg[$errmsg] ) ? vsprintf( $this->errmsg[$errmsg],$errvar ) : $errmsg );
			}
			elseif ( !isset( $rules['upload'] ) ) {
				$this->request( $data['name'],$field_value );
				$this->input[$name] = $field_value;
			}
			$idx++;
		}
		$this->valid[] = $name;
		return true;
	}

	public function input( $name,$retval='' ) {
		$name = $this->normalize_name( $name );
		if ( isset( $this->input[$name] ) ) {
			return $this->input[$name];
		}
		return $retval;
	}

	public function input_list( $name,$list,$sep=', ',$retval='' ) {
		$name = $this->normalize_name( $name );
		if ( !isset( $this->input[$name] ) ) {
			return $retval;
		}
		$data = $this->input[$name];
		if ( !is_array( $data ) ) {
			$data = array( $data );
		}
		$values = array();
		foreach( $data as $datum ) {
			if ( isset( $list[$datum] ) ) {
				$values[] = $list[$datum];
			}
		}
		return ( count( $values ) == 0 ? $retval : implode( $sep,$values ) );
	}

	public function input_array( $name,$retval=array() ) {
		$name = $this->normalize_name( $name );
		$data = array();
		foreach( $this->input as $key => $val ) {
			if ( strpos( $key,$name ) === 0 ) {
				$k = explode( '_',trim( substr( $key,strlen( $name ) ),'_' ),2 );
				if ( !isset( $k[1] ) ) {
					$data[$k[0]] = $val;
					continue;
				}
				$data[$k[0]][$k[1]] = $val;
			}
		}
		return ( count( $data ) == 0 ? $retval : $data );
	}

	public function input_array_remove( $name,$key=null ) {
		$name = $this->normalize_name( $name );
		foreach( $this->input as $key => $val ) {
			if ( strpos( $key,$name ) === 0 ) {
				if ( strlen( $key ) == strlen( $name ) || substr( $key,strlen( $name ),1 ) == '_' ) {
					unset( $this->input[$key] );
				}
			}
		}
	}

	public function get_upload_field( $name,$delete=true,$move=true ) {
		$name = $this->normalize_name( $name );
		if ( array_key_exists( $name,$this->upload ) && !is_null( $this->upload[$name] ) ) {
			if ( $delete == true ) {
				$this->delete_upload_field( $name );
			}
			$data = $this->upload[$name];
			if ( isset( $data['move-to'] ) && $move == true ) {
				$move = "{$data['move-to']}/{$data['file']}";
				if ( !copy( $data['path'],$move ) ) {
					return -1;
				}
				$this->upload[$name]['path'] = $move;
				unset( $this->upload[$name]['move-to'] );
				unlink( $data['path'] );
			}
			return $this->upload[$name];
		}
		return false;
	}

	public function set_upload_field( $name,$data ) {
		$name = $this->normalize_name( $name );
		if ( !array_key_exists( $name,$this->upload ) ) {
			$array = array('base','file','path','type','size');
			$diff = array_diff( $array,array_keys( $data ) );
			if ( count( $diff ) > 0 ) {
				throw new \Exception('Invalid upload data - missing fields: ' . implode( ', ',$diff ));
			}
			$upload = array();
			foreach( $array as $idx ) {
				$upload[$idx] = $data[$idx];
			}
			$this->upload[$name] = $upload;
		}
	}

	public function clear_upload_field( $name ) {
		$name = $this->normalize_name( $name );
		if ( array_key_exists( $name,$this->upload ) && is_array( $this->upload[$name] ) && isset( $this->upload[$name]['path'] ) ) {
			$this->delete[$name][] = $this->upload[$name]['path'];
			$this->upload[$name] = null;
		}
	}

	public function delete_upload_field( $name ) {
		$name = $this->normalize_name( $name );
		if ( array_key_exists( $name,$this->delete ) ) {
			foreach( $this->delete[$name] as $file ) {
				if ( file_exists( $file ) ) {
					unlink( $file );
				}
			}
			$this->deleted_fields[] = $name;
			unset( $this->delete[$name] );
		}
	}

	public function deleted( $name ) {
		$name = $this->normalize_name( $name );
		return in_array( $name,$this->deleted_fields );
	}

	public function marked_for_deletion( $name ) {
		$name = $this->normalize_name( $name );
		return array_key_exists( $name,$this->delete );
	}

	public function clean_upload_temp() {
		if ( is_null( $this->config['upload_temp'] ) ) {
			return;
		}
		$temp = rtrim( $this->config['upload_temp'],'/' );
		if ( is_dir( $temp ) && ( $dh = @opendir( $temp ) ) ) {
			while( false !== ( $file = readdir( $dh ) ) ) {
				if ( $file !== '.' && $file !== '..' ) {
					$part = explode( '_',$file );
					if ( $part[0] < ( time() - self::temp_expr ) ) {
						unlink( "{$temp}/{$file}" );
					}
				}
			}
		}
	}

	public function clear() {
		$this->data->remove('curr_page');
		$this->data->remove('last_page');
		$args = func_get_args();
		if ( count( $args ) == 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		if ( count( $args ) == 0 ) {
			$this->input = array();
			$this->upload = array();
			$this->delete = array();
			return $this;
		}
		foreach( $args as $name ) {
			if ( array_key_exists( $name,$this->input ) ) {
				unset( $this->input[$name] );
			}
			else {
				if ( array_key_exists( $name,$this->upload ) ) {
					unset( $this->upload[$name] );
				}
				if ( array_key_exists( $name,$this->delete ) ) {
					unset( $this->delete[$name] );
				}
			}
		}
		return $this;
	}

	public function save( $clear=true,$error=false ) {
		if ( $clear == true ) {
			$this->clear();
		}
		if ( $error == true && count( $this->errors ) > 0 ) {
			$this->data->set('errors',$this->errors);
		}
		la_frontend::session()->data['forms'][$this->config['id']] = $this->data->get_data();
		return $this;
	}

	protected function create( $name,$config=array() ) {
		$field = $this->fields[$name];
		$elem =	$field['element'];
		$type = false;
		$data = func::array_merge_recursive_distinct( $field['element_config_2'],$config );
		if ( !isset( $data['attrs'] ) ) {
			$data['attrs'] = array();
		}
		if ( strpos( $elem,':' ) !== false ) {
			list( $elem,$type ) = explode( ':',$elem,2 );
		}
		if ( !isset( $data['attrs']['id'] ) ) {
			$data['attrs']['id'] = $name;
		}
		$data['attrs']['class'] = ( isset( $data['attrs']['class'] ) ? ( !is_array( $data['attrs']['class'] ) ? explode( ' ',$data['attrs']['class'] ) : $data['attrs']['class'] ) : array() );
		if ( array_key_exists( $name,$this->errors ) ) {
			$data['attrs']['class'][] = $this->config['error_class'];
		}
		$value = ( !isset( $field['error'] ) && isset( $this->input[$name] ) ? $this->input[$name] : ( isset( $data['attrs']['value'] ) ? $data['attrs']['value'] : '' ) );
		switch( $elem ) {
			case 'input':
				if ( $type === false ) {
					throw new \Exception('Type required for input element');
				}
				$multi = false;
				if ( strpos( $type,':' ) !== false ) {
					list( $type ) = explode( ':',$type,2 );
					$multi = true;
				}
				$data['attrs']['name'] = $field['name'];
				$data['attrs']['type'] = $type;
				switch( $type ) {
					case 'text':
					case 'password':
					case 'hidden':
						array_unshift( $data['attrs']['class'],'s-input-text' );
						$data['attrs']['value'] = html::entity_encode( $value );
					break;
					case 'file':
						array_unshift( $data['attrs']['class'],'s-input-file' );
						if ( isset( $this->upload[$name] ) ) {
							$file = $this->upload[$name];
							$templ = "<div class=\"file_info\"><div class=\"file_name\">" . func::shorten_filename( $file['base'],16 ) . "</div><div class=\"file_size\">" . func::format_filesize( $file['size'] ) . "</div><input class=\"file_delete\" type=\"submit\" name=\"delete-{$name}\" value=\"" . ( isset( $data['button'] ) ? $data['button'] : 'Delete' ) . '" /></div>';
						}
					break;
					case 'checkbox':
						array_unshift( $data['attrs']['class'],'s-input-checkbox' );
						if ( $multi == true ) {
							$data['attrs']['name'] = "{$data['attrs']['name']}[]";
						}
						if ( isset( $this->input[$name] ) ) {
							$field = $this->input[$name];
							if ( ( $multi == true && in_array( $data['attrs']['value'],(array) $field ) ) || $data['attrs']['value'] == $field ) {
								$data['attrs']['checked'] = 'checked';
							}
							elseif ( isset( $data['attrs']['checked'] ) ) {
								unset( $data['attrs']['checked'] );
							}
						}
						elseif ( $multi == false && array_key_exists( 'checked',$data ) && ( $data['checked'] == true || $data['attrs']['checked'] == 'checked' ) ) {
							$data['attrs']['checked'] = 'checked';
						}
						elseif ( $multi == true && array_key_exists( 'checked-array',$data ) && in_array( $data['attrs']['value'],$this->storage->get( $data['checked-array'] ) ) ) {
							$data['attrs']['checked'] = 'checked';
						}
						else {
							unset( $data['attrs']['checked'] );
						}
					break;
					case 'radio':
						array_unshift( $data['attrs']['class'],'s-input-radio' );
						if ( isset( $this->input[$name] ) ) {
							if ( $this->input[$name] == $data['attrs']['value'] ) {
								$data['attrs']['checked'] = 'checked';
							}
							elseif ( isset( $data['attrs']['checked'] ) ) {
								unset( $data['attrs']['checked'] );
							}
						}
						elseif ( array_key_exists( 'checked',$data ) && $data['checked'] == $data['attrs']['value'] ) {
							$data['attrs']['checked'] = 'checked';
						}
						else {
							unset( $data['attrs']['checked'] );
						}
					break;
					case 'submit':
						array_unshift( $data['attrs']['class'],'s-input-submit' );
					break;
					case 'image': break;
					default:
						throw new \Exception("Invalid type '{$data['attrs']['type']}' for input element");
					break;
				}
				if ( !isset( $templ ) ) {
					$templ = '<input' . html::build_attrs( $data['attrs'] ) . ' />';
					if ( isset( $append ) ) {
						$templ .= $append;
					}
				}
			break;
			case 'select':
				$data['attrs']['name'] = $field['name'];
				array_unshift( $data['attrs']['class'],'s-select' );
				if ( $type !== false ) {
					$data['attrs']['name'] = "{$data['attrs']['name']}[]";
					$data['attrs']['multiple'] = 'multiple';
					$data['add-empty'] = false;
				}
				$opts = array();
				if ( isset( $data['options'] ) ) {
					$opts = $this->storage->get( $data['options'] );
					unset( $data['options'] );
				}
				$selected = false;
				if ( !isset( $data['has-optgroup'] ) || $data['has-optgroup'] == false ) {
					if ( !isset( $data['add-empty'] ) || $data['add-empty'] == true ) {
						$opts = array(''=>( isset( $data['empty-value'] ) ? $data['empty-value'] : '' )) + $opts;
					}
					foreach( $opts as $key => $val ) {
						$opts[$key] = array( 'value'=>$val,'selected'=>false );
						if ( array_key_exists( $name,$this->input ) && ( ( !is_array( $this->input[$name] ) && $key == $this->input[$name] ) || ( is_array( $this->input[$name] ) && in_array( $key,$this->input[$name] ) ) ) ) {
							$opts[$key]['selected'] = $selected = true;
						}
					}
					if ( $selected == false && $type == false && isset( $data['selected'] ) && isset( $opts[$data['selected']] ) ) {
						$opts[$data['selected']]['selected'] = true;
					}
					elseif ( $selected == false && $type !== false && isset( $data['selected-array'] ) ) {
						foreach( $this->storage->get( $data['selected-array'] ) as $k ) {
							if ( !isset( $opts[$k] ) ) {
								continue;
							}
							$opts[$k]['selected'] = true;
						}
					}
				}
				else {
					if ( !isset( $data['add-empty'] ) || $data['add-empty'] == true ) {
						$opts = array(''=>array(''=>( isset( $data['empty-value'] ) ? $data['empty-value'] : '' ))) + $opts;
					}
					$_opts_r = array();
					foreach( $opts as $group => $_opts ) {
						foreach( $_opts as $key => $val ) {
							$opts[$group][$key] = array( 'value'=>$val,'selected'=>false );
							if ( array_key_exists( $name,$this->input ) && ( ( !is_array( $this->input[$name] ) && $key == $this->input[$name] ) || ( is_array( $this->input[$name] ) && in_array( $key,$this->input[$name] ) ) ) ) {
								$opts[$group][$key]['selected'] = $selected = true;
							}
							$_opts_r[$key] =& $opts[$group][$key];
						}
					}
					if ( $selected == false && $type == false && isset( $data['selected'] ) && isset( $_opts_r[$data['selected']] ) ) {
						$_opts_r[$data['selected']]['selected'] = true;
					}
					elseif ( $selected == false && $type !== false && isset( $data['selected-array'] ) ) {
						foreach( $this->storage->get( $data['selected-array'] ) as $k ) {
							if ( !isset( $_opts_r[$k] ) ) {
								continue;
							}
							$_opts_r[$k]['selected'] = true;
						}
					}
				}
				$options = '';
				foreach( $opts as $key => $opt ) {
					if ( isset( $data['has-optgroup'] ) && $data['has-optgroup'] == true ) {
						$options .= "<optgroup label=\"{$key}\">";
						foreach( $opt as $k => $v ) {
							$sel = '';
							if ( $v['selected'] == true ) {
								$sel = ' selected="selected"';
							}
							$options .= "<option value=\"{$k}\"{$sel}>{$v['value']}</option>";
						}
						$options .= '</optgroup>';
						continue;
					}
					$sel = '';
					if ( $opt['selected'] == true ) {
						$sel = ' selected="selected"';
					}
					$options .= "<option value=\"{$key}\"{$sel}>" . ( isset( $opt['value'] ) ? $opt['value'] : '' ) . '</option>';
				}
				$templ = '<select' . html::build_attrs( $data['attrs'] ) . ">{$options}</select>";
			break;
			case 'textarea':
				$data['attrs']['name'] = $field['name'];
				array_unshift( $data['attrs']['class'],'s-textarea' );
				$value = html::entity_encode( $value );
				$templ = '<textarea' . html::build_attrs( $data['attrs'] ) . ">{$value}</textarea>";
				//Add markdown editor here
			break;
			case 'custom':
				//expand on this
				$templ = $data['data'];
			break;
			default:
				throw new \Exception("Invalid element '{$elem}'");
			break;
		}
		return $templ;
	}

	public function open() {
		$tabs  = str_repeat( "\t",$this->config['tabs'] );
		$data  = "{$tabs}<form" . html::build_attrs( $this->config,array('id','action','method','enctype','accept-charset','autocomplete') ) . ">\n";
		$data .= "{$tabs}\t<div class=\"c-lf-hidden-fields\">\n{$tabs}\t\t<input type=\"hidden\" name=\"form_id\" value=\"{$this->config['id']}\" />\n";
		if ( $this->security['token'] == true ) {
			$token = md5(uniqid(rand(),true));
			$this->data->set('token',$token);
			$data .= "{$tabs}\t\t<input type=\"hidden\" name=\"form_token\" value=\"{$token}\" />\n";
		}
		$data .= "{$tabs}\t\t<input type=\"hidden\" name=\"XID\" value=\"{XID_HASH}\" />\n";
		if ( count( $this->hidden_fields ) ) {
			foreach( $this->hidden_fields as $field ) {
				$data .= "{$tabs}\t\t" . $this->create( $field['name'] ) . "\n";
			}
		}
		if ( $this->security['timeout'] == true ) {
			$this->data->set('time',time());
		}
		return $data . "{$tabs}\t</div>\n";
	}

	public function close() {
		$tabs = str_repeat( "\t",$this->config['tabs'] );
		$this->save(false);
		$data = "{$tabs}</form>";
		if ( $this->script !== '' ) {
			$data .= "\n<script type=\"text/javascript\">\n{$this->script}</script>";
		}
		return $data;
	}

	public function get_label( $name,$html=true ) {
		$name = $this->normalize_name( $name );
		if ( !isset( $this->fields[$name] ) || !isset( $this->fields[$name]['label'] ) ) {
			return '';
		}
		if ( $html === true ) {
			return "<label for=\"{$name}\">{$this->fields[$name]['label']}</label>" . ( isset( $this->fields[$name]['rules']['required'] ) ? '<span class="required">*</span>' : '' );
		}
		
		return $this->fields[$name]['label'];
	}

	public function get_element( $name ) {
		$name = $this->normalize_name( $name );
		if ( !isset( $this->fields[$name] ) || !isset( $this->fields[$name]['element'] ) ) {
			return '';
		}
		if ( in_array( $this->fields[$name]['element'],array('input:checkbox:multi','input:radio') ) && isset( $this->fields[$name]['element_config_2']['list'] ) ) {
			$retval = array();
			$list = $this->storage->get( $this->fields[$name]['element_config_2']['list'] );
			$i = 1;
			foreach( $list as $value => $label ) {
				$retval["{$name}-{$i}"] = array(
					'label' => $label,
					'element' => $this->create( $name,array(
						'attrs' => array(
							'id' => "{$name}-{$i}",
							'value' => $value
						)
					))
				);
				$i++;
			}
			return $retval;
		}
		return $this->create( $name );
	}

	public function get_field( $name ) {
		$name = $this->normalize_name( $name );
		if ( !isset( $this->fields[$name] ) ) {
			return '';
		}
		return array(
			'field'   => $this->fields[$name]['field_config'],
			'label'   => $this->get_label( $name ),
			'element' => $this->get_element( $name ),
			'error'   => ( isset( $this->fields[$name]['error'] ) ? $this->fields[$name]['error'] : false )
		);
	}

	public function get_fields() {
		$fields = func_get_args();
		$fields = func::array_flatten( $fields );
		$retval = array();
		foreach( $fields as $field ) {
			$retval[] = $this->get_field( $field );
		}
		return $retval;
	}

	public function get_raw_fields() {
		return $this->fields;
	}

	public function build_fields( $fields ) {
		$tabs = str_repeat( "\t",$this->config['tabs'] );
		$html = '';
		$c = count( $this->fields );
		$i = 1;
		$inline = false;
		foreach( $fields as $key ) {
			$key = $this->normalize_name( $key );
			$field = $this->fields[$key];
			if ( !isset( $field['element'] ) || $field['element'] == 'input:hidden' ) {
				continue;
			}
			if ( isset( $field['insert'][self::before_field] ) ) {
				$html .= implode( "\n",$field['insert'][self::before_field] ) . "\n";
			}
			$field_attrs = array();
			if ( isset( $field['field_config']['attrs'] ) ) {
				$field_attrs = $field['field_config']['attrs'];
			}
			if ( isset( $field['field_config']['show_if'] ) ) {
				$display = 'none';
				if ( isset( $field['field_config']['show_if']['field_equals'] ) ) {
					$field_values = array_chunk( $field['field_config']['show_if']['field_equals'],2 );
					foreach( $field_values as $field_value ) {
						list( $_field_key,$_value ) = $field_value;
						$_field_key = $this->normalize_name( $_field_key );
						if ( !isset( $this->fields[$_field_key] ) ) {
							throw new \Exception('Field does not exist');
						}
						$_field = $this->fields[$_field_key];
						$_field_value = $this->input( $_field['name'] );
						if ( ( is_array( $_field_value ) && in_array( $_value,$_field_value ) ) || ( !is_array( $_field_value ) && $_field_value == $_value ) ) {
							$display = 'block';
						}
						$element = '';
						if ( strpos( $_field['element'],':' ) !== false ) {
							list( $element,$type ) = explode( ':',$_field['element'] );
							if ( $type !== 'multi' ) {
								$element = "{$element}[type=\"{$type}\"]";
							}
						}
						$check = false;
						$event = '';
						switch( $_field['element'] ) {
							case 'input:text':
							case 'input:password':
							case 'textarea':
								$event = 'focusout';
							break;
							case 'input:checkbox':
							case 'input:checkbox:multi':
								$check = true;
								$event = 'click';
							break;
							case 'input:radio':
							case 'input:submit':
								$event = 'click';
							break;
							case 'select':
							case 'select:multi':
								$event = 'change';
							break;
							default:
								throw new \Exception('Unable to find event for element type');
							break;
						}
						$if_cond = "$(this).val() == '{$_value}'";
						if ( $check === true ) {
							$if_cond .= " && $(this).is(':checked')";
						}
						$this->script .= <<<JS
$('#{$this->config['id']} .field[data-id="{$_field['data-id']}"]').find('{$element}').{$event}(function() {
	var field = $('#{$this->config['id']} .field[data-id="{$field['data-id']}"]');
	if ( {$if_cond} ) {
		field.fadeIn();
	}
	else {
		field.fadeOut();
	}
});

JS;
					}
				}
				$field_attrs['style'] = "display:{$display};";
			}
			$field_attrs['class'] = ( !isset( $field_attrs['class'] ) ? array() : ( !is_array( $field_attrs['class'] ) ? explode( ' ',$field_attrs['class'] ) : $field_attrs['class'] ) );
			array_unshift( $field_attrs['class'],'c-lf-field' );
			if ( isset( $field['field_config']['inline'] ) && $field['field_config']['inline'] == true ) {
				$field_attrs['class'][] = 't-' . ( !isset( $field['field_config']['align'] ) || $field['field_config']['align'] == 'left' ? 'left' : 'right' );
				$inline = true;
			}
			$field_attrs['data-id'] = $field['data-id'];
			$html .= "{$tabs}\t\t<div" . html::build_attrs( $field_attrs ) . ">\n";
			$label = '';
			if ( isset( $field['insert'][self::before_label] ) ) {
				$label .= implode( "\n",$field['insert'][self::before_label] ) . "\n";
			}
			if ( isset( $field['label'] ) && ( !isset( $field['label_config']['show'] ) || $field['label_config']['show'] == true ) ) {
				$label_attrs = array();
				if ( isset( $field['label_config']['attrs'] ) ) {
					$label_attrs = $field['label_config']['attrs'];
				}
				$label_attrs['class'] = ( !isset( $label_attrs['class'] ) ? array() : ( !is_array( $label_attrs['class'] ) ? explode( ' ',$label_attrs['class'] ) : $label_attrs['class'] ) );
				array_unshift( $label_attrs['class'],'c-lff-label' );
				if ( isset( $field['label_config']['inline'] ) && $field['label_config']['inline'] == true ) {
					$label_attrs['class'][] = 't-' . ( !isset( $field['label_config']['align'] ) || $field['label_config']['align'] == 'left' ? 'left' : 'right' );
				}
				$label .= "{$tabs}\t\t\t<div" . html::build_attrs( $label_attrs ) . "><label class=\"c-lffl-label\" for=\"{$key}\">{$field['label']}</label>" . ( isset( $field['rules']['required'] ) ? '<span class="c-lffl-required-marker">*</span>' : '' ) . "</div>\n";
			}
			if ( isset( $field['insert'][self::after_label] ) ) {
				$label .= implode( "\n",$field['insert'][self::after_label] ) . "\n";
			}
			$element = '';
			if ( isset( $field['insert'][self::before_element] ) ) {
				$element .= implode( "\n",$field['insert'][self::before_element] ) . "\n";
			}
			$elem_attrs = array();
			if ( isset( $field['element_config_1']['attrs'] ) ) {
				$elem_attrs = $field['element_config_1']['attrs'];
			}
			$elem_attrs['class'] = ( !isset( $elem_attrs['class'] ) ? array() : ( !is_array( $elem_attrs['class'] ) ? explode( ' ',$elem_attrs['class'] ) : $elem_attrs['class'] ) );
			array_unshift( $elem_attrs['class'],'c-lff-element' );
			if ( isset( $field['element_config_1']['inline'] ) && $field['element_config_1']['inline'] == true ) {
				$elem_attrs['class'][] = 't-' . ( !isset( $field['element_config_1']['align'] ) || $field['element_config_1']['align'] == 'left' ? 'left' : 'right' );
			}
			$element .= "{$tabs}\t\t\t<div" . html::build_attrs( $elem_attrs ) . '>';
			$_element = $this->get_element( $key );
			if ( is_array( $_element ) ) {
				if ( isset( $field['element_config_1']['columns'] ) ) {
					$elements = func::array_split( $_element,$field['element_config_1']['columns'],true );
					$element .= "\n";
					foreach( $elements as $column => $elems ) {
						$element .= "{$tabs}\t\t\t\t<div class=\"c-lffe-column\">\n";
						foreach( $elems as $id => $elem ) {
							$element .= "{$tabs}\t\t\t\t\t<div class=\"c-lffec-element\">{$elem['element']}</div>\n";
							$element .= "{$tabs}\t\t\t\t\t<div class=\"c-lffec-label\"><label class=\"c-lffecl-label\" for=\"{$id}\">{$elem['label']}</label></div>\n";
							$element .= "{$tabs}\t\t\t\t\t<div class=\"s-clear\"></div>\n";
						}
						$element .= "{$tabs}\t\t\t\t</div>\n";
					}
					$element .= "{$tabs}\t\t\t\t<div class=\"s-clear\"></div>\n{$tabs}\t\t\t";
				}
			}
			else {
				$element .= $_element;
			}
			$element .= "</div>\n";
			if ( isset( $field['insert'][self::after_element] ) ) {
				$element .= implode( "\n",$field['insert'][self::after_element] ) . "\n";
			}
			if ( !isset( $field['label_config']['position'] ) || $field['label_config']['position'] == 'before-element' ) {
				$html .= $label . $element;
			}
			elseif ( ( isset( $field['label_config']['position'] ) && $field['label_config']['position'] == 'after-element' ) || ( isset( $field['element_config_1']['position'] ) && $field['element_config_1']['position'] == 'before-label' ) ) {
				$html .= $element . $label;
			}
			if ( isset( $field['element_config_1']['inline'] ) && $field['element_config_1']['inline'] == true ) {
				$html .= "{$tabs}\t\t\t<div class=\"s-clear\"></div>\n";
			}
			$html .= "{$tabs}\t\t</div>\n";
			if ( $inline == true && ( !isset( $field['field_config']['no_clear'] ) || $field['field_config']['no_clear'] == false ) && ( ( !isset( $field['field_config']['inline'] ) || $field['field_config']['inline'] == false ) || $i == $c || ( isset( $field['field_config']['clear'] ) && $field['field_config']['clear'] == true ) ) ) {
				$html .= "{$tabs}\t\t<div class=\"s-clear\"></div>\n";
				$inline = false;
			}
			if ( isset( $field['insert'][self::after_field] ) ) {
				$html .= implode( "\n",$field['insert'][self::after_field] ) . "\n";
			}
			$i++;
		}
		return $html;
	}

	public function get_error_html( $group=null ) {
		$errors = $this->get_errors( $group );
		if ( count( $errors ) === 0 ) {
			return '';
		}
		$tabs = str_repeat( "\t",$this->config['tabs'] );
		$html = "{$tabs}\t\t<div class=\"c-lf-errors\">\n{$tabs}\t\t\t<div class=\"c-lfe-title\">The following errors have been encountered:</div>\n{$tabs}\t\t\t<ul class=\"c-lfe-list\">\n";
		foreach( $errors as $error ) {
			$html .= "{$tabs}\t\t\t\t<li class=\"c-lfel-error\">{$error}</li>\n";
		}
		$html .= "{$tabs}\t\t\t</ul>\n{$tabs}\t\t</div>\n";
		return $html;
	}

	public function build_order( $order ) {
		$html = '';
		foreach( $order as $info ) {
			switch( $info['type'] ) {
				case 'field':
					$html .= $this->build_fields(array($info['field']));
				break;
				case 'repeatable_section':
					$html .= "<div class=\"repeatable-section\">" . $this->build_order( $this->repeatable_sections[$info['template']][$info['section']] ) . "</div>\n";
				break;
				case 'template':
					$html .= "<div class=\"repeatable\" data-name=\"{$info['template']}\">" . $this->build_order( $this->templates[$info['template']]['order'] ) . "</div>\n";
					$template = str_replace( array("\n","\t","'"),array('','',"\'"),$this->build_template( $info['template'],array(
						'template' => $info['template']
					) ) );
					$field = $this->fields[$this->normalize_name($this->templates[$info['template']]['config']['count_field'])];
					$html .=<<<JS
<script type="text/javascript">
	//javascript needs updating
	var form = $('#{$this->config['id']}');
	var template = '{$template}';
	var count = form.find('div[class*="field"][data-id="{$field['data-id']}"] input');
	var prev_count = ( count.val() !== '' ? parseInt( count.val() ) : 0 );
	var container = form.find('div[class="repeatable"][data-name="{$info['template']}"]');
	var timer = null;
	count.keyup(function() {
		if ( timer !== null ) {
			clearTimeout( timer );
		}
		var timer = setTimeout(function() {
			var total = 0;
			if ( isNaN( count.val() ) || parseInt( count.val() ) > 20 ) {
				count.val('');
				return;
			}
			if ( count.val() !== '' ) {
				total = parseInt( count.val() );
			}
			var data = ( total - prev_count );
			var index = prev_count;
			if ( data < 0 ) {
				for( var i=data;i < 0;i++ ) {
					container.find('.repeatable-section:last').remove();
				}
			}
			else {
				for( var i=0;i < data;i++ ) {
					index++;
					container.append(template.replace(/{index}/g,index));
				}
			}
			prev_count = total;
			timer = null;
		},750);
	});
</script>
JS;
				break;
				case 'group':
					$data = $this->build_order( $this->groups[$info['group']]['order'] );
					if ( !isset( $info['generated'] ) || $info['generated'] === false ) {
						$data = "<div class=\"group\" data-name=\"{$info['group']}\">\n{$data}</div>\n";
					}
					$html .= $data;
				break;
				/*case 'tab_group':
					$html .= "<div id=\"tabs-{$info['tab_group']}\" class=\"tab_wrapper\">\n";
					$html .= "\t<div class=\"tabs\">\n";
					$i = 1;
					foreach( $this->tab_groups[$info['tab_group']] as $key => $tab ) {
						$html .= "\t\t<a class=\"tab" . ( isset( $tab['config']['selected'] ) && $tab['config']['selected'] == true ? ' selected' : '' ) . "\" href=\"#{$key}\">" . ( isset( $tab['config']['label'] ) ? $tab['config']['label'] : "Tab {$i}" ) . "</a>\n";
						$i++;
					}
					$html .= "\t</div>\n\t<div class=\"panels\">\n";
					foreach( $this->tab_groups[$info['tab_group']] as $key => $tab ) {
						$html .= "\t\t<div id=\"{$key}\" class=\"panel" . ( isset( $tab['config']['selected'] ) && $tab['config']['selected'] == true ? ' visible' : '' ) . "\">\n";
						$html .= $this->build( $tab['fields'] );
						$html .= "\t\t</div>\n";
					}
					$html .= "\t</div>\n</div>\n";
					$html .= "<script type=\"text/javascript\">tabs.init('#tabs-{$info['tab_group']}');</script>\n";
				break;*/
				case 'html':
					$html .= $info['html'];
				break;
				case 'error_group':
					$html .= $this->get_error_html( $info['error_group'] );
				break;
			}
		}
		return $html;
	}

	public function output() {
		$tabs = str_repeat( "\t",$this->config['tabs'] );
		$html = "{$tabs}<div class=\"m-la-form\">\n\t" . $this->open();
		if ( $this->main_error_group_shown === false ) {
			array_unshift( $this->order,array(
				'type' => 'error_group',
				'error_group' => self::error_group
			) );
		}
		$html .= $this->build_order( $this->order );
		$otab = $this->config['tabs'];
		$this->config['tabs'] = ( $this->config['tabs'] + 1 );
		$html .= $this->close() . "\n{$tabs}</div>\n";
		$this->config['tabs'] = $otab;
		return $html;
	}

}

?>