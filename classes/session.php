<?php

namespace la_frontend;

class session {

	const table   = 'exp_lb_sessions';
	const cookie  = 'lb_session';
	const max_age = 3600;

	private $session_id = false;
	private $exists = false;

	public $data = array();

	public function __construct() {
		$this->clean();
		if ( ( $session_id = ee()->input->cookie( self::cookie ) ) !== '' ) {
			if ( !$this->find( $session_id ) ) {
				$this->exists = false;
				$session_id = false;
			}
			else {
				$this->exists = true;
				$this->session_id = $session_id;
			}
		}
		if ( $session_id === false ) {
			while(true) {
				$session_id = func::rand_string( 50,'alpha,numeric' );
				if ( (int) ee()->db->query(sprintf( "SELECT COUNT(*) AS `count` FROM `%s` WHERE `id` = '%s'",self::table,$session_id ))->row()->count === 0 ) {
					break;
				}
			}
			ee()->input->set_cookie( self::cookie,$session_id,3600 );
			$this->session_id = $session_id;
		}
		register_shutdown_function(array( $this,'save' ));
	}

	private function clean() {
		if ( rand(1,15) !== 5 ) {
			return;
		}
		ee()->db->query(sprintf("DELETE FROM `%s` WHERE `time` < %d",self::table,( time() - self::max_age )));
	}

	private function find( $session_id ) {
		$query = ee()->db->query(sprintf("SELECT `data` FROM `%s` WHERE `id` = '%s' AND `time` > %d LIMIT 1",self::table,ee()->db->escape_str( $session_id ),( time() - self::max_age )));
		if ( $query->num_rows() !== 1 ) {
			return false;
		}
		$this->data = unserialize( base64_decode( $query->row()->data ) );
		return true;
	}

	public function save() {
		$data = base64_encode( serialize( $this->data ) );
		if ( !$this->exists ) {
			ee()->db->insert( self::table,array(
				'id' => $this->session_id,
				'data' => $data,
				'time' => time()
			) );
			return;
		}
		ee()->db->update( self::table,array(
			'data'    => $data,
			'time' => time()
		),sprintf( "`id` = '%s'",ee()->db->escape_str( $this->session_id ) ) );
	}

}

?>