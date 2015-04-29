<?php

namespace la_frontend;

class breadcrumb {

	private $links = array();

	public function __construct() {}

	public function method( $method,$args=array() ) {
		switch( $method ) {
			case 'add':
				if ( !isset( $args['add'] ) ) {
					throw new \Exception('add method requires a link name');
				}
				if ( !isset( $args['url'] ) ) {
					throw new \Exception('add method requires a url param');
				}
				$this->links[] = array(
					'name' => $args['add'],
					'url'  => $args['url']
				);
			break;
			default:
				throw new \Exception("Method '{$method}' does not exist");
			break;
		}
		return '';
	}

	public function links() {
		return $this->links;
	}

}

?>