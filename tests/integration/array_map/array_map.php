<?php

namespace NS_ArrayMap;

$a = range( 1, 10 );

array_map( 'strval', $a ); // Fine

$cb = function ( $x ) {
	return strval( $x );
};
array_map( $cb, $a ); // Acceptable

// Bad
array_map( function ( $x ) {
	return strval( $x );
}, $a );

// Seems equally bad
array_map( static function ( $x ) {
	return strval( $x );
}, $a );
