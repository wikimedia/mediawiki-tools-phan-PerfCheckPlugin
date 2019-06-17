<?php

namespace Wikimedia\Rdbms;

class Database {
	/**
	 * @return array
	 */
	public function select( $x ) : array {
		return [];
	}
	/**
	 * @return array
	 */
	public function selectField( $x ) : array {
		return [];
	}
	/**
	 * @return array
	 */
	public function selectFieldValues( $x ) : array {
		return [];
	}
	/**
	 * @return array
	 */
	public function selectRow( $x ) : array {
		return [];
	}
	public function addQuotes( $x ) : string {
		return '';
	}
}
