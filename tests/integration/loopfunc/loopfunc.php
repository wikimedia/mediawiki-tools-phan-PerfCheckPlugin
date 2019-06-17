<?php

namespace NS_LoopFunc;

$arr = range( 1, 5 );

foreach ( $arr as $el ) {
	rand();
}

for ( $i = 0; $i < count( $arr ); $i++ ) {
	rand();
}

// Here the variable is overwritten inside the loop, so no issues
for ( $i = 0; $i < count( $arr ); $i++ ) {
	$arr = [];
}

for ( $i = 0; $i < count( $arr ); $i++ ) {
	array_splice( $arr, 0 );
}

for ( $i = 0; $i < count( $arr ); $i++ ) {
	unset( $arr[$i] );
}

for ( $i = 0; $i < rand(); $i++ ) {
	rand();
}

$i = 0;
$str = 'foobar';
while ( strlen( $str ) > $i ) {
	$i++;
}

// Here the variable is overwritten inside the loop, so no issues
while ( strlen( $str ) > $i ) {
	$str = substr( $str, 0, -1 );
}

while ( count( $arr ) ) {
	array_shift( $arr );
}

$i = 0;
$str = 'stringy';
while ( strpos( $str, 's' ) ) {
	$i++;
}

$i = 0;
do {
	$i++;
} while ( $i < sizeof( $arr ) );

$i = 0;
do {
	// Here the variable is overwritten inside the loop, so no issues
	$arr[$i] = $i;
	$i++;
} while ( sizeof( $arr ) );

do {
	preg_match( '|fo+bar|', 'b', $str );
} while ( $str );

$arr = [ 1 ];
do {
	unset( $arr[0] );
} while ( $arr );
