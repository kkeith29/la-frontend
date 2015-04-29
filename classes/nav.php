<?php

namespace la_frontend;

class nav {

	private $groups = array();
	private $group = null;

	public function __construct() {}

	public function method( $method,$args=array() ) {
		switch( $method ) {
			case 'group_start':
				if ( !isset( $args['id'] ) ) {
					throw new \Exception('group method requires an id attribute');
				}
				$this->group = $args['id'];
			break;
			case 'group_end':
				$this->group = null;
			break;
			case 'add':
				if ( is_null( $this->group ) && !isset( $args['group-id'] ) ) {
					throw new \Exception('add method should be contained within a group or group-id should be provided');
				}
				if ( !isset( $args['add'] ) ) {
					throw new \Exception('add method requires a link name');
				}
				if ( !isset( $args['url'] ) ) {
					throw new \Exception('add method requires a url param');
				}
				$group = ( is_null( $this->group ) ? $args['group-id'] : $this->group );
				if ( !isset( $this->groups[$group] ) ) {
					$this->groups[$group] = array();
				}
				$this->groups[$group][] = array(
					'name'     => $args['add'],
					'url'      => $args['url'],
					'subgroup' => ( isset( $args['subgroup'] ) ? $args['subgroup'] : false )
				);
			break;
			default:
				throw new \Exception("Method '{$method}' does not exist");
			break;
		}
		return '';
	}

	public function links( $group ) {
		if ( !isset( $this->groups[$group] ) ) {
			throw new \Exception("Unable to find nav group with id '{$group}'");
		}
		return $this->groups[$group];
	}

}

?>