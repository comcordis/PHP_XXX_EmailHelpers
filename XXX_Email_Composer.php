<?php

/*

When not passing spam filters, make sure the From header matches the domain of the server sending it.
Escpecially on shared hosting environments. Or request users to add the address to their address book.
Put a reason why you email them.
And a form on how to unsubscribe.
And a notice to add the sending address to the address book.

To fix this, you need to log in to your DNS settings, wherever they may be, and set up MX records that point to the same IP address PHP sends mail from. You can check the IP address of your current records at www.mxtoolbox.com.

*/
class XXX_Email_Composer
{
	protected $messageID;
	
	public static $lineSeparator = "\r\n";

	protected $boundaries = array
	(
		'end' => '-- End --'
	);

	protected $bodies = array
	(
		'plain' => ' ',
		'html' => ' '
	);

	protected $files = array
	(
		'embedded' => array(),
		'attached' => array()
	);

	protected $messageType = array
	(
		'plain' => false,
		'html' => true,
		'embedded' => false,
		'attached' => false
	);

	protected $mimeWarning = 'This is a multi-part message in MIME format. If you see this message it means that your mail client doesn\'t support multi-part messages (Messages with different versions and parts of the message, like for example a plain text or rich HTML version, or attachments etc.';

	protected $subject = '';

	protected $defaultBodyEncoding = 'base64';
	protected $defaultHeaderEncoding = 'B';

	public $composed = array
	(
		'headers' => '',
		'body' => ''
	);
	
	public $isComposed = false;
	

	protected $priority = 'normal';
	
	protected $organization = 'Organization';
	
	protected $sender = 'service@example.com';
	
	protected $errorReceiver = 'error@example.com';
	protected $replyReceiver = 'reply@example.com';

	/*
		'john.doe@domain.com'
		array('name' => 'John Doe', 'address' => 'john.doe@domain.com')
	*/
	protected $receivers = array();
	protected $ccReceivers = array();
	protected $bccReceivers = array();

	public function __construct ()
	{
		self::$lineSeparator = XXX_OperatingSystem::$lineSeparator;
		//self::$lineSeparator = "\n";
		
		$this->createMessageID();
		$this->createBoundaries();
	}
	
	////////////////////
	// Message ID & Date
	////////////////////
	
	protected function createMessageID ()
	{
		$uniqueHash = XXX_String::getRandomHash();
		
		$this->messageID = $uniqueHash . '@' . XXX_OperatingSystem::$hostname;
	}

	public static function createDate ()
	{
		$result = date(DATE_RFC2822);

		if ($result === false || empty($result))
		{
			$result = date('r');
		}

		if ($result === false || empty($result))
		{
			// Get timezone offset in seconds
			$timezoneOffset = date('Z');
			// Determine wether it's a negative or positive number
			$timezonePrefix = ($timezoneOffset < 0) ? "-" : "+";
			// Get the absolute number without prefixed - or +
			$timezoneOffset = abs($timezoneOffset);
			// Calculate the number of hours
			$timezoneOffset = ((($timezoneOffset / 3600) * 100) + (($timezoneOffset % 3600) / 60));
			// Construct the full date string
			$result = sprintf("%s %s%04d", date("D, j M Y H:i:s"), $timezonePrefix, $timezoneOffset);
		}

		return $result;
	}

	////////////////////
	// Part boundaries & headers
	////////////////////

	protected function createBoundaries ($total = 3)
	{
		$this->boundaries = array();

		for ($i = 0; $i < $total; ++$i)
		{
			$uniqueHash = XXX_String::getRandomHash();

			$boundary = '--=_MIME_Boundary_' . $i . '_' . XXX_String::getPart($uniqueHash, 2, 4) . '_' . XXX_String::getPart($uniqueHash, -8) . '_=';

			$this->boundaries[$i] = array
			(
				'normal' => '--' . $boundary,
				'end' => '--' . $boundary . '--'
			);
		}

		$this->boundaries['end'] = '-- End --';
	}

	protected function startBoundary ($boundaryID)
	{
		return '--' . $this->boundaries[$boundaryID]['normal'] . self::$lineSeparator;
	}

	protected function endBoundary ($boundaryID)
	{
		return self::$lineSeparator . '--' . $this->boundaries[$boundaryID]['end'] . self::$lineSeparator . self::$lineSeparator;
	}

	/*
	multipart/mixed (multipart/related and file attachments)

	multipart/related (multipart/alternative and embedded attachments)

	multipart/alternative (bodies)
	*/
	protected function startLevel ($boundaryID, $mimeType = 'multipart/mixed')
	{
		$result = '';

		$result .= 'Content-Type: ' . $mimeType . '; ' . self::$lineSeparator;
		$result .= XXX_String::$tab . 'boundary="' . $this->boundaries[$boundaryID]['normal'] . '"' . self::$lineSeparator . self::$lineSeparator;

		return $result;
	}

	protected function startMainDataPartHeader ($characterSet = 'utf-8', $mimeType = 'application/octet-stream', $encoding = 'base64')
	{
		$result = '';

		$result .= 'Content-Type: ' . $mimeType . ';' . self::$lineSeparator . XXX_String::$tab . 'charset="' . $characterSet . '"' . self::$lineSeparator;
		$result .= 'Content-Transfer-Encoding: ' . $encoding . self::$lineSeparator;

		return $result;
	}

	protected function startDataPartHeader ($characterSet = 'utf-8', $mimeType = 'application/octet-stream', $encoding = 'base64')
	{
		$result = '';

		$result .= 'Content-Type: ' . $mimeType . ';' . self::$lineSeparator . XXX_String::$tab . 'charset="' . $characterSet . '"' . self::$lineSeparator;
		$result .= 'Content-Transfer-Encoding: ' . $encoding . self::$lineSeparator . self::$lineSeparator;

		return $result;
	}

	protected function startEmbeddedFilePartHeader ($file, $contentID = '', $mimeType = 'application/octet-stream', $encoding = 'base64')
	{
		$result = '';

		$result .= 'Content-Type: ' . $mimeType . ';' . self::$lineSeparator;
		$result .= XXX_String::$tab . 'name="' . $file . '"' . self::$lineSeparator;

		$result .= 'Content-Transfer-Encoding: ' . $encoding . self::$lineSeparator;

		$result .= 'Content-ID: <' . $contentID . '>' . self::$lineSeparator;

		$result .= 'Content-Disposition: inline;' . self::$lineSeparator;
		$result .= XXX_String::$tab . 'filename="' . $file . '"' . self::$lineSeparator . self::$lineSeparator;

		return $result;
	}

	protected function startAttachedFilePartHeader ($file, $mimeType = 'application/octet-stream', $encoding = 'base64')
	{
		$result = '';

		$result .= 'Content-Type: ' . $mimeType . ';' . self::$lineSeparator;
		$result .= XXX_String::$tab . 'name="' . $file . '"' . self::$lineSeparator;

		$result .= 'Content-Transfer-Encoding: ' . $encoding . self::$lineSeparator;

		$result .= 'Content-Disposition: attachment;' . self::$lineSeparator;
		$result .= XXX_String::$tab . 'filename="' . $file . '"' . self::$lineSeparator . self::$lineSeparator;

		return $result;
	}

	////////////////////
	// Files
	////////////////////

	public function addEmbeddedFile ($data, $file, $contentID, $mimeType = 'application/octet-stream', $encoding = 'base64')
	{
		$embeddedFile = array();

		$embeddedFile['data'] = $data;

		$embeddedFile['file'] = $file;

		$embeddedFile['name'] = $file;

		$embeddedFile['contentID'] = $contentID;

		$embeddedFile['mimeType'] = $mimeType;

		$embeddedFile['encoding'] = $encoding;

		$this->files['embedded'][] = $embeddedFile;
	}

	public function addAttachedFile ($data, $file, $mimeType = 'application/octet-stream', $encoding = 'base64')
	{
		$attachedFile = array();

		$attachedFile['data'] = $data;

		$attachedFile['file'] = $file;

		$attachedFile['name'] = $file;

		$attachedFile['contentID'] = $file;

		$attachedFile['mimeType'] = $mimeType;

		$attachedFile['encoding'] = $encoding;

		$this->files['attached'][] = $attachedFile;
	}

	////////////////////
	// Compose
	////////////////////

	public function composeEmbeddedFiles ($boundaryID = 0)
	{
		$result = '';

		for ($i = 0, $embeddedFilesTotal = XXX_Array::getFirstLevelItemTotal($this->files['embedded']); $i < $embeddedFilesTotal; ++$i)
		{
			$result .= $this->startBoundary($boundaryID);

			$result .= $this->startEmbeddedFilePartHeader($this->files['embedded'][$i]['file'], $this->files['embedded'][$i]['contentID'], $this->files['embedded'][$i]['mimeType'], $this->files['embedded'][$i]['encoding']);

			$result .= XXX_Email_Encoding_Body::encode($this->files['embedded'][$i]['data'], $this->files['embedded'][$i]['encoding']);

			$result .= self::$lineSeparator . self::$lineSeparator;
		}

		return $result;
	}

	public function composeAttachedFiles ($boundaryID = 0)
	{
		$result = '';

		for ($i = 0, $attachedFilesTotal = XXX_Array::getFirstLevelItemTotal($this->files['attached']); $i < $attachedFilesTotal; ++$i)
		{
			$result .= $this->startBoundary($boundaryID);

			$result .= $this->startEmbeddedFilePartHeader($this->files['attached'][$i]['file'], $this->files['attached'][$i]['mimeType'], $this->files['attached'][$i]['encoding']);

			$result .= XXX_Email_Encoding_Body::encode($this->files['attached'][$i]['data'], $this->files['attached'][$i]['encoding']);

			$result .= self::$lineSeparator . self::$lineSeparator;
		}

		return $result;
	}

	public function composeHeaders ()
	{
		$this->determineMessageType();

		// Avoid spaces with the comma
		$this->composed['receivers'] = XXX_Array::joinValuesToString($this->composeAddresses($this->receivers), ',');
		$this->composed['ccReceivers'] = XXX_Array::joinValuesToString($this->composeAddresses($this->ccReceivers), ',');
		$this->composed['bccReceivers'] = XXX_Array::joinValuesToString($this->composeAddresses($this->bccReceivers), ',');
		
		$result = '';

		$result .= 'Date: ' . $this->createDate() . self::$lineSeparator;

		$result .= 'Message-ID: <' . $this->messageID . '>' . self::$lineSeparator;

		$result .= 'From: ' . $this->composeAddress($this->sender) . self::$lineSeparator;
		
		// http://www.sitecrafting.com/blog/aol-denying-email/
		$result .= 'Organization: ' . $this->organization . self::$lineSeparator;
		
		$result .= 'Errors-To: ' . $this->composeAddress($this->errorReceiver) . self::$lineSeparator;
		$result .= 'Return-Path: ' . $this->composeAddress($this->errorReceiver) . self::$lineSeparator;
		
		$result .= 'Reply-To: ' . $this->composeAddress($this->replyReceiver) . self::$lineSeparator;
		
		if ($this->composed['ccReceivers'] != '')
		{
			$result .= 'CC: ' . $this->composed['ccReceivers'] . self::$lineSeparator;
		}
		
		if ($this->composed['bccReceivers'] != '')
		{
			$result .= 'BCC: ' . $this->composed['bccReceivers'] . self::$lineSeparator;
		}
		
		$result .= 'MIME-Version: 1.0' . self::$lineSeparator;

		switch ($this->priority)
		{
			case 'high':
				$result .= 'X-Priority: 1 (High)' . self::$lineSeparator;
				$result .= 'X-MSMail-Priority: High' . self::$lineSeparator;
				$result .= 'Importance: High' . self::$lineSeparator;
			break;
			case 'normal':
				$result .= 'X-Priority: 3 (Normal)' . self::$lineSeparator;
				$result .= 'X-MSMail-Priority: Normal' . self::$lineSeparator;
				$result .= 'Importance: Normal' . self::$lineSeparator;
			break;
			case 'low':
				$result .= 'X-Priority: 5 (Low)' . self::$lineSeparator;
				$result .= 'X-MSMail-Priority: Low' . self::$lineSeparator;
				$result .= 'Importance: Low' . self::$lineSeparator;
			break;
		}

		// Plain only
		if ($this->messageType['plain'] && (!$this->messageType['html'] && !$this->messageType['embedded'] && !$this->messageType['attached']))
		{
			$result .= $this->startMainDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
		}
		// Plain only with embedded files
		else if ($this->messageType['plain'] && $this->messageType['embedded'] && (!$this->messageType['html'] && !$this->messageType['attached']))
		{
			$result .= $this->startLevel(0, 'multipart/related');
		}
		// Plain only with attached files
		else if ($this->messageType['plain'] && $this->messageType['attached'] && (!$this->messageType['html'] && !$this->messageType['embedded']))
		{
			$result .= $this->startLevel(0, 'multipart/mixed');
		}
		// Plain only with embedded and attached files
		else if ($this->messageType['plain'] && $this->messageType['embedded'] && $this->messageType['attached'] && (!$this->messageType['html']))
		{
			$result .= $this->startLevel(0, 'multipart/mixed');
		}

		// HTML only
		else if ($this->messageType['html'] && (!$this->messageType['plain'] && !$this->messageType['embedded'] && !$this->messageType['attached']))
		{
			$result .= $this->startMainDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
		}
		// HTML only with embedded files
		else if ($this->messageType['html'] && $this->messageType['embedded'] && (!$this->messageType['plain'] && !$this->messageType['attached']))
		{
			$result .= $this->startLevel(0, 'multipart/related');
		}
		// HTML only with attached files
		else if ($this->messageType['html'] && $this->messageType['attached'] && (!$this->messageType['plain'] && !$this->messageType['embedded']))
		{
			$result .= $this->startLevel(0, 'multipart/mixed');
		}
		// HTML only with embedded and attached files
		else if ($this->messageType['html'] && $this->messageType['embedded'] && $this->messageType['attached'] && (!$this->messageType['plain']))
		{
			$result .= $this->startLevel(0, 'multipart/mixed');
		}

		// Plain and HTML
		else if ($this->messageType['plain'] && $this->messageType['html'] && (!$this->messageType['embedded'] && !$this->messageType['attached']))
		{
			$result .= $this->startLevel(0, 'multipart/alternative');
		}
		// Plain and HTML with embedded files
		else if ($this->messageType['plain'] && $this->messageType['html'] && $this->messageType['embedded'] && (!$this->messageType['attached']))
		{
			$result .= $this->startLevel(0, 'multipart/related');
		}
		// Plain and HTML with attached files
		else if ($this->messageType['plain'] && $this->messageType['html'] && $this->messageType['attached'] && (!$this->messageType['embedded']))
		{
			$result .= $this->startLevel(0, 'multipart/mixed');
		}
		// Plain and HTML with embedded and attached files
		else if ($this->messageType['plain'] && $this->messageType['html'] && $this->messageType['embedded'] && $this->messageType['attached'])
		{
			$result .= $this->startLevel(0, 'multipart/mixed');
		}

		$this->composed['headers'] = $result;		
		
		return $result;
	}

	public function composeBody ()
	{
		$this->determineMessageType();

		$result = '';

		// Plain only
		if ($this->messageType['plain'] && (!$this->messageType['html'] && !$this->messageType['embedded'] && !$this->messageType['attached']))
		{
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
		}
		// Plain only with embedded files
		else if ($this->messageType['plain'] && $this->messageType['embedded'] && (!$this->messageType['html'] && !$this->messageType['attached']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->composeEmbeddedFiles(0);

			$result .= $this->endBoundary(0);
		}
		// Plain only with attached files
		else if ($this->messageType['plain'] && $this->messageType['attached'] && (!$this->messageType['html'] && !$this->messageType['embedded']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->composeAttachedFiles(0);

			$result .= $this->endBoundary(0);
		}
		// Plain only with embedded and attached files
		else if ($this->messageType['plain'] && $this->messageType['embedded'] && $this->messageType['attached'] && (!$this->messageType['html']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startLevel(1, 'multipart/related');

			$result .= $this->startBoundary(1);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->composeEmbeddedFiles(1);

			$result .= $this->endBoundary(1);

			$result .= $this->composeAttachedFiles(0);

			$result .= $this->endBoundary(0);
		}

		// HTML only
		else if ($this->messageType['html'] && (!$this->messageType['plain'] && !$this->messageType['embedded'] && !$this->messageType['attached']))
		{
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
		}
		// HTML only with embedded files
		else if ($this->messageType['html'] && $this->messageType['embedded'] && (!$this->messageType['plain'] && !$this->messageType['attached']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->composeEmbeddedFiles(0);

			$result .= $this->endBoundary(0);
		}
		// HTML only with attached files
		else if ($this->messageType['html'] && $this->messageType['attached'] && (!$this->messageType['plain'] && !$this->messageType['embedded']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->composeAttachedFiles(0);

			$result .= $this->endBoundary(0);
		}
		// HTML only with embedded and attached files
		else if ($this->messageType['html'] && $this->messageType['embedded'] && $this->messageType['attached'] && (!$this->messageType['plain']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startLevel(1, 'multipart/related');

			$result .= $this->startBoundary(1);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->composeEmbeddedFiles(1);

			$result .= $this->endBoundary(1);

			$result .= $this->composeAttachedFiles(0);

			$result .= $this->endBoundary(0);
		}

		// Plain and HTML
		else if ($this->messageType['plain'] && $this->messageType['html'] && (!$this->messageType['embedded'] && !$this->messageType['attached']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->startBoundary(0);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->endBoundary(0);
		}
		// Plain and HTML with embedded files
		else if ($this->messageType['plain'] && $this->messageType['html'] && $this->messageType['embedded'] && (!$this->messageType['attached']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startLevel(1, 'multipart/alternative');

			$result .= $this->startBoundary(1);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->startBoundary(1);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->endBoundary(1);

			$result .= $this->composeEmbeddedFiles(0);

			$result .= $this->endBoundary(0);
		}
		// Plain and HTML with attached files
		else if ($this->messageType['plain'] && $this->messageType['html'] && $this->messageType['attached'] && (!$this->messageType['embedded']))
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startLevel(1, 'multipart/alternative');

			$result .= $this->startBoundary(1);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->startBoundary(1);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->endBoundary(1);

			$result .= $this->composeAttachedFiles(0);

			$result .= $this->endBoundary(0);
		}
		// Plain and HTML with embedded and attached files
		else if ($this->messageType['plain'] && $this->messageType['html'] && $this->messageType['embedded'] && $this->messageType['attached'])
		{
			$result .= $this->startBoundary(0);
			$result .= $this->startLevel(1, 'multipart/related');

			$result .= $this->startBoundary(1);
			$result .= $this->startLevel(2, 'multipart/alternative');

			$result .= $this->startBoundary(2);
			$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->startBoundary(2);
			$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			$result .= self::$lineSeparator . self::$lineSeparator;

			$result .= $this->endBoundary(2);

			$result .= $this->composeEmbeddedFiles(1);

			$result .= $this->endBoundary(1);

			$result .= $this->composeAttachedFiles(0);

			$result .= $this->endBoundary(0);
		}

		$this->composed['body'] = $result;

		return $result;
	}

	public function composeSubject ()
	{
		$result = $this->subject;

		$result = XXX_Email_Encoding_Header::encodeHeader('Subject', XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($result)), $this->defaultHeaderEncoding);

		$result = XXX_String::getPart($result, 8, XXX_String::getCharacterLength($result));

		$this->composed['subject'] = $result;

		return $result;
	}
	
	public function composeAddresses (array $addresses = array())
	{
		$result = array();
		
		foreach ($addresses as $address)
		{
			$result[] = $this->composeAddress($address);
		}
		
		return $result;
	}
	
	public function composeAddress ($address)
	{
		$result = '';
		
		if (XXX_Type::isArray($address))
		{
			$result = $address['name'] . '<' . $address['address'] . '>';
		}
		else
		{
			$result = $address;
		}
		
		return $result;
	}

	public function compose ($force = false)
	{
		if (!$this->isComposed || $force)
		{
			$this->composeSubject();
			$this->composeHeaders();
			$this->composeBody();
			
			$this->isComposed = true;
		}
	}
	
	////////////////////
	// Other
	////////////////////

	public function determineMessageType ()
	{
		$this->messageType = array
		(
			'plain' => ($this->bodies['plain'] != ''),
			'html' => ($this->bodies['html'] != ''),
			'embedded' => (XXX_Array::getFirstLevelItemTotal($this->files['embedded']) > 0),
			'attached' => (XXX_Array::getFirstLevelItemTotal($this->files['attached']) > 0)
		);
	}
	
	public function setOrganization ($organization)
	{
		$this->organization = $organization;
	}

	public function setSender ($address, $name = '')
	{
		if ($name != '')
		{
			$sender = array('address' => $address, 'name' => $name);
		}
		else
		{
			$sender = $address;
		}
		
		$this->sender = $sender;
	}
	
	public function setReceiver ($address, $name = '')
	{
		return $this->addReceiver($address, $name);
	}
	
	public function addReceiver ($address, $name = '')
	{
		if ($name != '')
		{
			$receiver = array('address' => $address, 'name' => $name);
		}
		else
		{
			$receiver = $address;
		}
		
		$this->receivers[] = $receiver;
	}
	
	public function setCCReceiver ($address, $name = '')
	{
		return $this->addCCReceiver($address, $name);
	}
	
	public function addCCReceiver ($address, $name = '')
	{
		if ($name != '')
		{
			$receiver = array('address' => $address, 'name' => $name);
		}
		else
		{
			$receiver = $address;
		}
		
		$this->ccReceivers[] = $receiver;
	}
	
	public function setBCCReceiver ($address, $name = '')
	{
		return $this->addBCCReceiver($address, $name);
	}
	
	public function addBCCReceiver ($address, $name = '')
	{
		if ($name != '')
		{
			$receiver = array('address' => $address, 'name' => $name);
		}
		else
		{
			$receiver = $address;
		}
		
		$this->bccReceivers[] = $receiver;
	}

	public function setBody ($data, $type = 'html')
	{
		$this->bodies[$type] = $data;
	}

	public function setSubject ($subject)
	{
		$this->subject = $subject;
	}

	public function send ()
	{
		return XXX_Email_Sender::sendEmail($this);
	}
	
	public function getEmailAsFileContent ()
	{
		$this->compose();
		
		$content = '';
		//$content .= 'To:' . $this->composed['receivers'] . self::$lineSeparator;
		$content .= 'Subject:' . $this->composed['subject'] . self::$lineSeparator;
		$content .= $this->composed['headers'] . self::$lineSeparator;
		$content .= $this->composed['body'];
		
		return $content;
	}
}

?>