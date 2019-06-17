<?php

namespace NS_QueryLoop;

use Wikimedia\Rdbms\Database;

$dbr = new Database;

for ( $i = 0; $i < 20; $i++ ) {
	$dbr->select( 'a' ); // Bad
}

while ( true ) {
	$dbr->select( 'This is fine' );
}

function func() {
	$db = new Database();
	if ( rand() ) {
		$arr = $db->select( 'xxx' ); // Ok
		foreach ( $arr as $el ) {
			$db->select( $el ); // Bad
		}
	}
}

$x = [];
if ( rand() ) {
	foreach ( $x as $item ) {
		foreach ( $item as $v ) {
			if ( $dbr->selectRow( $v ) ) {
				$dbr->selectFieldValues( $v );
			}
			$dbr->addQuotes( $v );
		}
	}
}

do {
	$x = $dbr->select( 'fine' );
	foreach ( $x as $v ) {
		$dbr->select( $v ); // Bad
	}
} while( rand() );

for ( $i = 0; $i < 5; $i++ ) {
	while ( $i ) {
		$dbr->select( 'bad' ); // @todo Is this likely to be bad in real code?
	}
}
