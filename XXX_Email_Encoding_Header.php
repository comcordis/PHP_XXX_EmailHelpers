<?php

abstract class XXX_Email_Encoding_Header
{
	public static function encodeHeader ($header, $headerData, $encoding = 'B')
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$result = false;
		
		if (!$result)
		{
			if (XXX_PHP::hasExtension('iconv'))
			{
				$preferences = array
				(
					'input-charset' => 'UTF-8',
					'internal-charset' => 'UTF-8',
					'output-charset' => 'UTF-8',
					'line-length' => 74,
					'line-break-chars' => XXX_Email_Composer::$lineSeparator,
					'scheme' => $encoding
				);

				$encoded = iconv_mime_encode($header, $headerData, $preferences);
				
				if ($encoded)
				{
					$result = $encoded;
				}
			}
		}
		
		if (!$result)
		{
			if (XXX_PHP::hasExtension('mb'))
			{
				$encoded = mb_encode_mimeheader($header . ': ' . $headerData, 'UTF-8', $encoding, XXX_Email_Composer::$lineSeparator);
				
				if ($encoded)
				{
					$result = $encoded;
				}
			}
		}

		return $result;
	}

	public static function decodeHeader ($headerData, $encoding = 'B')
	{
		$headerData = XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($headerData));

		$result = false;

		if (!$result)
		{
			if (XXX_PHP::hasExtension('mb'))
			{
				$decoded = mb_decode_mimeheader($headerData);
				
				if ($decoded)
				{
					$result = $decoded;
				}
			}
		}

		if (!$result)
		{
			if (XXX_PHP::hasExtension('iconv'))
			{
				$decoded = iconv_mime_decode($headerData, 2, 'UTF-8');
				
				if ($decoded)
				{
					$result = $decoded;
				}
			}
		}

		return $result;
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