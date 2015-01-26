<?php

define('ESC_KEY', chr(27));
class Screen {
	function clear() {
		echo ESC_KEY . '[2J';
	}
	function clearToEndOfScreen() { echo ESC_KEY . '[0J'; }
	function clearLine() { echo ESC_KEY . '[2K'; }
	function clearToEndOfLine() { echo ESC_KEY . '[K'; }
	
	function moveDirection($direction, $num) {
		$direction = strtoupper($direction);
		$direction_code = null;
		switch ($direction) {
			case 'U': // Up
				$direction_code = 'A';
				break;
			case 'D': // Down
				$direction_code = 'B';
				break;
			case 'L': // Left
				$direction_code = 'D';
				break;
			case 'R': // Right
				$direction_code = 'C';
				break;
			default:
				return;
				break;
		}
		echo ESC_KEY . '[' . $num . $direction_code;
	}
	// Convenience method for moveDirection
	function moveUp($num=1) { Screen::moveDirection('U', $num); }
	// Convenience method for moveDirection
	function moveDown($num=1) { Screen::moveDirection('D', $num); }
	// Convenience method for moveDirection
	function moveLeft($num=1) { Screen::moveDirection('L', $num); }
	// Convenience method for moveDirection
	function moveRight($num=1) { Screen::moveDirection('R', $num); }
	
	function moveTo($x, $y) {
		echo ESC_KEY . '[' . $y . ';' . $x . 'H';
	}
	function savePosition() { echo ESC_KEY . '[s'; }
	function restorePosition() { echo ESC_KEY . '[u'; }
	
	function stringAt($string, $x, $y) {
		self::moveTo($x, $y);
		echo $string;
	}
}
/**
Reference:
http://www.connectrf.com/Documents/vt220.html#v4
X X X X     set rendition       esc [ ** m
        [ codes for rendition ]
X X X X            normal  0
X X X X            bold    1
X X X X            underln 4
X X X X            blink   5
X X X X            inverse 7
_ _ X _            not bold     2 2
_ _ X _            not underln  2 4
_ _ X _            not blink    2 5
_ _ X _            not invers   2 7

X X X _     set mode            esc [ ** h
        [ codes for mode ]
X _ _ _             cursor              1
_ X X X             keyboard lock       2
X _ _ _             column              3
X _ _ _             scrolling           4
_ X X X             insert mode         4
X _ _ _             screen              5
X _ _ _             origin              6
X _ _ _             auto-wrap           7
X _ _ _             auto-repeat         8
X _ _ _             interlace           9
_ X X X             send/receive off    1 2
X X X X             line feed/new line  2 0
X X X _     reset mode          esc [ ** l

_ X X _     set expanded mode   esc [ ? * h
        [ codes for expanded mode ]
_ X X _             cursor              1
_ X X _             ansi/vt52           2
_ X X _             column              3
_ X X _             scrolling           4
_ X X _             reverse screen      5
_ X X _             origin relative     6
_ X X _             auto-wrap           7
_ X X _             auto-repeat         8
_ X X _             print form feed     1 8
_ X X _             print extent        1 9
_ X X _             text cursor         2 5
_ X X _     reset expanded mode esc [ ? * l

X X X _     application keypad  esc =
X X X _     numeric keypad      esc >

X X X _     scroll region       esc [ * ; * r
X X X X     cursor up           esc [ * A
X X X X     cursor down         esc [ * B
X X X X     cursor right        esc [ * C
X X X X     cursor left         esc [ * D
X X X X     cursor posn         esc [ * ; * H
X X X X     cursor posn         esc [ * ; * f
        [NOTE the above two are documented as equivalent ]
X X X X     home                esc [ H
X X X X     home                esc [ f
X X X X     index               esc D
X X X X     reverse index       esc M
X X X X     next line           esc E
X X X _     save                esc 7
X X X _     restore             esc 8
 
X X X X     horiz tab set       esc H
X X X X     horiz tab clear     esc g  
X X X X     horiz tab clear     esc 0 g
X X X X     clear all tabs      esc 3 g
X X X X     reset to init       esc c
_ _ X _     reset to powerup    esc [ ! p

X X X X     erase to EOL        esc [ K
X X X X     erase to EOL        esc [ 0 K
X X X X     erase to BOL        esc [ 1 K
X X X X     erase line          esc [ 2 K
X X X X     erase to end screen esc [ J
X X X X     erase to end screen esc [ 0 J
X X X X     erase to begin scrn esc [ 1 J
X X X X     erase screen        esc [ 2 J

X X _ X     get dev attributes  esc [ * c
X X X X     get dev status      esc [ * n

_ X X X     delete char         esc [ * P
_ X X X     insert line         esc [ * L
_ X X X     delete line         esc [ * M
_ _ X _     insert char         esc [ * @
_ _ X _     erase char          esc [ * X
**/
?>