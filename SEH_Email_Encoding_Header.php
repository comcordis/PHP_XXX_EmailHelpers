<?php

abstract class SEH_Email_Encoding_Header
{
	public static function encodeHeader ($header, $headerData, $encoding = 'B')
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$encoded = $headerData;

		if (XXX_PHP::hasExtension('mb'))
		{
			$encoded = mb_encode_mimeheader($header . ': ' . $headerData, 'UTF-8', $encoding, XXX_String::$lineSeparator);
		}

		if (!$encoded)
		{
			if (XXX_PHP::hasExtension('iconv'))
			{
				$preferences = array
				(
					'input-charset' => 'UTF-8',
					'internal-charset' => 'UTF-8',
					'output-charset' => 'UTF-8',
					'line-length' => 74,
					'line-break-chars' => XXX_String::$lineSeparator,
					'scheme' => $encoding
				);

				$encoded = iconv_mime_encode($header, $encoded, $preferences);
			}
		}

		return $encoded;
	}

	public static function decodeHeader ($headerData, $encoding = 'B')
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$decoded = $headerData;

		if (XXX_PHP::hasExtension('mb'))
		{
			$decoded = mb_decode_mimeheader($headerData);
		}

		if (!$decoded)
		{
			if (XXX_PHP::hasExtension('iconv'))
			{
				$decoded = iconv_mime_decode($decoded, 2, 'UTF-8');
			}
		}

		return $decoded;
	}

	/*
	Q

	(RFC 2047)

	The Q encoding is similar to the Quoted-Printable encoding. It is designed to allow text containing mostly ASCII characters to be decipherable on an ASCII terminal without decoding.

	1) Any 8bit value may be represented by a '=' followed by 2 hexadecimal digits.

	2) The 8bit hexadecimal value 20 (SPACE) may be represented as '_' (UNDERSCORE). Note that the '_" always represents a SPACE characters, even if the SPACE character occupies a different code position in the character set in use.

	3) 8bit values which correspond to printable ASCII characters other than '=', '?', '_' and SPACE may be represented by those characters.
	*/

	public static function encodeQ ($header, $headerData)
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$encoded = self::encodeHeader($header, $headerData, 'Q');

		return $encoded;
	}

	public static function decodeQ ($headerData)
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$decoded = self::decodeHeader($headerData, 'Q');

		return $decoded;
	}

	/*
	B

	(RFC 2047, 3548)

	Identical to Base64
	*/

	public static function encodeB ($header, $headerData)
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$encoded = self::encodeHeader($header, $headerData, 'B');

		return $encoded;
	}

	public static function decodeB ($headerData)
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$decoded = self::decodeHeader($headerData, 'B');

		return $decoded;
	}
}

?>