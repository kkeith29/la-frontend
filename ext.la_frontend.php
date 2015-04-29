<?php

define('LA_FRONTEND_DIR',dirname(__FILE__) . '/');

include LA_FRONTEND_DIR . 'classes/la_frontend.php';

class La_frontend_ext {

	private static $instances = array();

	public $name           = 'LA Frontend Helper';
	public $version        = '1.0.0';
	public $description    = 'Provides tools for asset management and html document generation';
	public $docs_url       = '';
	public $settings_exist = 'n';
	public $settings       = array();

	public function __construct( $settings='' ) {
		$this->settings = $settings;
	}

	public function activate_extension() {
		ee()->load->dbforge();
		ee()->dbforge->add_field(array(
			'id' => array(
				'type'       => 'varchar',
				'constraint' => '50'
			),
			'data' => array(
				'type' => 'text'
			),
			'time' => array(
				'type'       => 'int',
				'constraint' => '11',
				'unsigned'   => true
			)
		));
		ee()->dbforge->add_key('id',true);
		ee()->dbforge->create_table('la_sessions');
		
		ee()->db->insert('extensions',array(
			'class'    => __CLASS__,
			'method'   => 'handle_template',
			'hook'     => 'template_post_parse',
			'settings' => serialize( $this->settings ),
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
	}

	public function update_extension() {
		return true;
	}

	public function disable_extension() {
		ee()->db->where('class',__CLASS__);
		ee()->db->delete('extensions');
		
		ee()->load->dbforge();
		ee()->dbforge->drop_table('la_sessions');
	}

	public function handle_template( $template,$sub,$site_id ) {
		if ( $sub == true ) {
			return $template;
		}
		
		ee()->config->load('la_frontend');
		
		try {
			$frontend = new la_frontend( ee()->config->item('la_frontend') );
			$frontend->init( $template );
			return $frontend->output();
		}
		catch( Exception $e ) {
			ee()->output->fatal_error( $e->getMessage() );
		}
	}

}

?>