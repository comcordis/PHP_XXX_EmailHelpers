<?php

abstract class XXX_Email_Encoding_Body
{
	public static function encode ($bodyData = '', $encoding = 'base64')
	{
		$encoded = '';
		
		switch ($encoding)
		{
			case '7Bit':
				$encoded = self::encode7Bit($bodyData);
				break;
			case '8Bit':
				$encoded = self::encode8Bit($bodyData);
				break;
			case 'binary':
				$encoded = self::encodeBinary($bodyData);
				break;
			case 'base64':
				$encoded = self::encodeBase64($bodyData);
				break;
			case 'quotedPrintable':
				$encoded = self::encodeQuotedPrintable($bodyData);
				break;
		}
		
		return $encoded;
	}
	
	public static function decode ($bodyData = '', $encoding = 'base64')
	{
		$decoded = '';
		
		switch ($encoding)
		{
			case '7Bit':
				$decoded = self::decode7Bit($bodyData);
				break;
			case '8Bit':
				$decoded = self::decode8Bit($bodyData);
				break;
			case 'binary':
				$decoded = self::decodeBinary($bodyData);
				break;
			case 'base64':
				$decoded = self::decodeBase64($bodyData);
				break;
			case 'quotedPrintable':
				$decoded = self::decodeQuotedPrintable($bodyData);
				break;
		}
		
		return $encoded;
	}
	
	// 7 Bit
	
		public static function encode7Bit ($bodyData = '')
		{
			$encoded = $bodyData;
			
			$encoded = XXX_String::normalizeLineSeparators($encoded);
			
			return $encoded;
		}
		
		public static function decode7Bit ($bodyData = '')
		{
			$decoded = $bodyData;
			
			$decoded = XXX_String::normalizeLineSeparators($decoded);
			
			return $decoded;
		}	
	
	// 8 Bit
	
		public static function encode8Bit ($bodyData = '')
		{
			return self::encode7Bit($bodyData);
		}
		
		public static function decode8Bit ($bodyData = '')
		{
			return self::decode7Bit($bodyData);
		}
	
	// Base64
	
		/*
		Base64
		
		(RFC 2045, 3548)
			
		The Base64 encoding is designed to represent arbitrary sequences of octets in a form that need not be humanly readable.
		The encoding and decoding algorithms are simple, but the encoded data are aconsistently only about 33% larger than the unencoded data.
		
		A 65-character subset of US-ASCII is used, enabling 6 bits to be represented per printable character. (The extra 65th character, '=', is used to signify a special processing function.)
		
		NOTE: This subset has the important property that it is represented identically in all versions of ISO 646, including US-ASCII, and all characters in the subset are also represented identically in all versions of EBCDIC.
		
		The encoding process represents 24bit groups of input bits as output strings of 4 encoded characters. Proceeding from left to right, a 24bit input group is formed by concatenating 3 8bit input groups. These 24bits are then treated as 4 concatenated 6bit groups, each of which is translated into a single digit in the Base64 alphabet.
		
		The encoded output stream must be represented in lines of no more than 76 characters each.
		
		Each 6-bit group is used as an index into an array of 64 printable characters.
			
		0 A
		1 B
		2 C
		3 D
		4 E
		5 F
		6 G
		7 H
		8 I
		9 J
		10 K
		11 L
		12 M
		13 N
		14 O
		15 P
		16 Q
		17 R
		18 S
		19 T
		20 U
		21 V
		22 W
		23 X
		24 Y
		25 Z
		26 a
		27 b 
		28 c
		29 d
		30 e
		31 f
		32 g
		33 h
		34 i
		35 j
		36 k
		37 l
		38 m
		39 n
		40 o
		41 p
		42 q
		43 r
		44 s
		45 t
		46 u
		47 v
		48 w
		49 x
		50 y
		51 z
		52 0
		53 1
		54 2
		55 3
		56 4
		57 5
		58 6
		59 7
		60 8
		61 9
		62 +
		63 /
		*/
		
		public static function encodeBase64 ($bodyData = '', $maximumLineLength = 75)
		{
			//$encoded = XXX_String::normalizeLineSeparators($bodyData);
			
			$encoded = $bodyData;
			
			$encoded = XXX_String_Base64::encode($encoded);
			
			$encoded = chunk_split($encoded, $maximumLineLength, XXX_Email_Composer::$lineSeparator);
			
			$encoded = XXX_String::trim($encoded);
			
			return $encoded;
		}
		
		public static function decodeBase64 ($bodyData = '')
		{
			$decoded = XXX_String::trim($bodyData);
			
			$decoded = XXX_String::normalizeLineSeparators($decoded);
			
			$decoded = XXX_String::removeLineSeparators($decoded);
			
			$decoded = XXX_String_Base64::decode($decoded);
			
			return $decoded;
		}
		
	// Binary
		
		public static function encodeBinary ($bodyData = '')
		{
			return $bodyData;
		}
		
		public static function decodeBinary ($bodyData = '')
		{
			return $bodyData;
		}
	
	// Quoted printable
		
		/*
		Quoted-Printable
		
		(RFC 2045)
		
		The Quoted-Printable encoding is intended to represent data that largely consists of octets that correspond to printable characters in the US-ASCII character set.
			
		1) General 8-bit representation
		
		Any octet, except a CR or LF that is part of a CRLF line break of the canonical (standard) form of the data being encoded, may be represented by an '=' followed by a two digit gexadecimal representation of the octet's value.
		The digits of the hexadecimal alphabet for this purpose are '0123456789ABCDEF'. Uppercase letters must be used; lowercase letters are not allowed.
		This rule must be followed except when the following rules allow an alternative encoding.
			
		2) Literal representation
		
		Octets with the decimal values of 33 trough 60 inclusive, and 62 trough 126 inclusive MAY be represented as the US-ASCII characters which correspond to those octets. (EXCLAMATION POINT through LESS THAN, and GREATER THAN trough TILDE, respectively)/
			
		3) White space
		
		Octets with values of 9 and 32 MAY be represented as US-ASCII TAB (XXX_String::$tab) and SPACE characters, respectively, but MUST NOT be so represented at the end of an encoded line. Any TAB (XXX_String::$tab) or SPACE characters on an encoded line MUST thus be followed on that line by a printable character. In particular, an '=' at the end of an encoded line, indicating a soft line break may follow one or more TAB (XXX_String::$tab) or SPACE characters.
		This is done because certain MTA's pad lines and others trim lines so in the end all trailing whitespace get's deleted.
			
		4) Line breaks
		
		A line break in a text body, represented as a CRLF sequence in the text canonical form, must be represented by a line break, which is also a CRLF sequence, in the Quoted-Printable encoding.
			
		5) Soft line breaks
		
		The Quoted-Printable encoding REQUIRES that encoded lines be no more than 76 characters long. If longer lines are to be encoded with the Quoted-Printable encoding, 'soft' line breaks must be used. An equal sign as the last character on a encoded line indicates such a non-significant ('soft') line break in the encoded text.
			
		NOTE: The Quoted-Printable encoding represents something of a compromise between readability and reliability in transport. Bodies encoded with the Quoted-Printable encoding will work reliably over most mail gateways, but may not work perfectly over a few gateways, notably those involving translation into EBCDIC. A higher level of confidence is offered by the Base64 encoding. A way to get reasonably reliable transport trough EBCDIC gateways is to also encode the US-ASCII characters: !"#$@[\]^`{|}~
		*/
		
		public static function encodeQuotedPrintable ($bodyData = '')
		{
			return self::encodeQuotedPrintableNative($bodyData);
		}
		
		public static function encodeQuotedPrintableNative ($bodyData = '')
		{
			return quoted_printable_encode($bodyData);	
		}
		
		public static function encodeQuotedPrintableStateMachine ($bodyData = '', $maximumLineLength = 75, $ebcdicReliable = true)
		{
			$encoded = XXX_String::normalizeLineSeparators($bodyData);
			
			$lines = XXX_String::splitToArray($encoded, XXX_Email_Composer::$lineSeparator);
			$linesTotal = XXX_Array::getFirstLevelItemTotal($lines);
			
			$encoded = '';
			
			for ($i = 0; $i < $linesTotal; ++$i)
			{
				$line = $lines[$i];
				$charactersTotal = XXX_String::getByteSize($line);
				$lastCharacter = ($charactersTotal - 1);
				$newLine = '';
				
				for ($j = 0; $j < $charactersTotal; ++$j)
				{
					$character = XXX_String::getByteSize($line, $j, 1);
					$asciiCodePoint = XXX_String::characterToASCIICodePoint($character);
					
					// 0x20
					// Convert SPACE at end of a line only
					if ($asciiCodePoint == 0x20 && $j == $lastCharacter)
					{
						$character = '=20';
					}
					// Encode: 0x09
					// Encode: XXX_String::$tab
					// At end of a line only
					elseif ($asciiCodePoint == 0x09 && $j == $lastCharacter)
					{
						$character = '=09';
					}
					elseif ($asciiCodePoint == 0x09)
					{
					}
					// Encode: 0x3D & 0x00 - 0x1F & 0x7F - 0xFF
					// Encode: =    & NUL  - US   & DEL  - 255
					elseif ($asciiCodePoint == 0x3D || $asciiCodePoint < 0x20 || ($asciiCodePoint >= 0x7F && $asciiCodePoint <= 0xFF))
					{
						$character = sprintf('=%02X', $asciiCodePoint);
					}
					// Encode: 0x2E
					// Encode: .
					// At begin of a line only, some Windows servers need this, won't break anything
					elseif ($asciiCodePoint == 0x2E && $newLine == '')
					{
						$character = '=2E';
					}
					
					if ($ebcdicReliable)
					{
						// Encode: 0x21 - 0x24 & 0x40 & 0x5B - 0x5E & 0x60 & 0x7B - 0x7E
						// Encode: !    - $    & @    & [    - ^    & `    & {    - ~					
						if (($asciiCodePoint >= 0x21 && $asciiCodePoint <= 0x24) || $asciiCodePoint == 0x40 || ($asciiCodePoint >= 0x5B && $asciiCodePoint <= 0x5E) || $asciiCodePoint == 0x60 || ($asciiCodePoint >= 0x7B && $asciiCodePoint <= 0x7E))
						{
							$character = sprintf('=%02X', $asciiCodePoint);
						}
					}
					
					$newLineLength = (XXX_String::getByteSize($newLine) + XXX_String::getByteSize($character));
					
					// If the line length exceeds the maximumLineLength, output the line so far with a soft line break
					if ($newLineLength >= $maximumLineLength)
					{
						// Soft line break
						$encoded .= $newLine . '=' . XXX_Email_Composer::$lineSeparator;
						$newLine = '';
					}
					$newLine .= $character;
				}
				
				$encoded .= $newLine . XXX_Email_Composer::$lineSeparator;
			}
			// Remove trailing line separator
			$encoded = XXX_String::getPart($encoded, 0, (-1 * XXX_String::getCharacterLength(XXX_Email_Composer::$lineSeparator)));
			
			return $encoded;
		}
		
		public static function decodeQuotedPrintable ($bodyData = '')
		{
			return self::decodeQuotedPrintableNative($bodyData);
		}
		
		public static function decodeQuotedPrintableNative ($bodyData = '')
		{
			return quoted_printable_decode($bodyData);	
		}
		
		public static function decodeQuotedPrintableStateMachine ($bodyData = '')
		{
			$decoded = XXX_String::normalizeLineSeparators($bodyData);
			
			// Remove soft line breaks
			$decoded = XXX_String_Pattern::replace($decoded, '=' . XXX_Email_Composer::$lineSeparator, '');
					
			// Decode all characters
			$decoded = XXX_String_Pattern::replace($decoded, '(=([0-9a-f]{2}))', 'ie', 'XXX_String::asciiCodePointToCharacter(XXX_Number::convertHexadecimalToDecimal("\\1"))');
			
			return $decoded;
		}
}
?>