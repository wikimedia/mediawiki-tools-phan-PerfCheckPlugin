<?php

namespace NS_Elseif;

// Too short
if ( rand() ) {

} elseif ( rand() ) {

} else {

}

// Not switchable
if ( rand() ) {

} elseif ( rand() === 45 ) {

} elseif ( rand() !== 43 ) {

} else {

}

// Not switchable because of strict comparisons
if ( rand() === 15 ) {

} elseif ( rand() === 12 ) {

} elseif ( rand() === 3 ) {

} else {

}

if ( rand() == 15 ) {

} elseif ( rand() == 12 ) {

} elseif ( rand() == 3 ) {

} else {

}

if ( rand() == 1 ) {

} elseif ( rand() == 15 ) {

} elseif ( rand() == 7 ) {

} elseif ( rand() == 9 ) {

}
