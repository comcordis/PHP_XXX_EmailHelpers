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
	// Should remain "\n" because some unix/linux mailer convert \r\n to \n\n and thus making the headers have an extra empty line triggering body to start
	public static $lineSeparator = "\r\n";

	protected $boundaries = array
	(
		'end' => '-- End --'
	);

	protected $bodies = array
	(
		'plain' => '',
		'html' => ''
	);

	protected $files = array
	(
		'embedded' => array(),
		'attached' => array()
	);

	public $messageType = array
	(
		'plain' => false,
		'html' => true,
		'embedded' => false,
		'attached' => false
	);

	protected $mimeWarning = 'This is a multi-part message in MIME format. If you see this message it means that your mail client doesn\'t support multi-part messages (Messages with different versions and parts of the message, like for example a plain text or rich HTML version, or attachments etc.';

	public $subject = '';

	protected $defaultBodyEncoding = 'base64';
	protected $defaultHeaderEncoding = 'B';

	public $composed = array
	(
		'headers' => '',
		'body' => ''
	);
	
	public $isComposed = false;
	
	protected $priority = 'normal';
	
	protected $organisation = 'Comcordis B.V.';
	
	protected $customDomain = 'comcordis.com';
	
	public $overwrittenTimestamp = false;
	
	// $relaySender on behalf of $sender
	public $sender = '';
	protected $relaySender = '';
	
	protected $errorReceiver = '';
	protected $replyReceiver = '';

	/*
		'john.doe@domain.com'
		array('name' => 'John Doe', 'address' => 'john.doe@domain.com')
	*/
	protected $receivers = array();
	protected $ccReceivers = array();
	protected $bccReceivers = array();

	public function __construct ()
	{
		//self::$lineSeparator = XXX_OperatingSystem::$lineSeparator;
		//self::$lineSeparator = "\n";
		
		$this->createBoundaries();
		
		$this->resetOrganisation();
		$this->resetCustomDomain();
		$this->resetSender();
		$this->resetRelaySender();
		$this->resetErrorReceiver();
		$this->resetReplyReceiver();
		
		$this->resetReceivers();
		$this->resetCCReceivers();
		$this->resetBCCReceivers();
	}
	
	////////////////////
	// Content ID
	////////////////////
		
	public function createContentID ($customDomain = '')
	{
		$uniqueHash = XXX_String::getRandomHash();

		if ($customDomain == '')
		{
			if ($this->customDomain != '')
			{
				$customDomain = $this->customDomain;
			}

			if ($customDomain == '')
			{
				$customDomain = XXX_OperatingSystem::$hostname;
			
				if (!XXX_String::hasPart($customDomain, '.'))
				{
					$customDomain = XXX_String::getLastSeparatedPart(XXX_Email_Sender::$relaySender['address'], '@');
				}
			}
		}

		$result = 'ID' . XXX_String::getPart($uniqueHash, 0, 16) . '@' . $customDomain;
				
		return $result;
		
		//XXX_String::getPart(XXX_String::getRandomHash(), 0, 16) . '@' . XXX_String::getPart(XXX_String::getRandomHash(), 0, 16) . '.mail';
	}
	
	////////////////////
	// Part boundaries & headers
	////////////////////

	/*
	
	boundary := 0*69<bchars> bcharsnospace
	bchars := bcharsnospace / " "
	bcharsnospace := DIGIT / ALPHA / "'" / "(" / ")" / "+" / "_" / "," / "-" / "." / "/" / ":" / "=" / "?"
	
	*/

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

			$result .= $this->startAttachedFilePartHeader($this->files['attached'][$i]['file'], $this->files['attached'][$i]['mimeType'], $this->files['attached'][$i]['encoding']);

			$result .= XXX_Email_Encoding_Body::encode($this->files['attached'][$i]['data'], $this->files['attached'][$i]['encoding']);

			$result .= self::$lineSeparator . self::$lineSeparator;
		}

		return $result;
	}

	public function composeHeaders ()
	{
		$this->composed['sender'] = $this->composeAddress($this->sender);
		$this->composed['relaySender'] = $this->composeAddress($this->relaySender);
		
		// Avoid spaces with the comma
		$this->composed['receivers'] = XXX_Array::joinValuesToString($this->composeAddresses($this->receivers), ',');
		$this->composed['ccReceivers'] = XXX_Array::joinValuesToString($this->composeAddresses($this->ccReceivers), ',');
		$this->composed['bccReceivers'] = XXX_Array::joinValuesToString($this->composeAddresses($this->bccReceivers), ',');
		
		$this->composed['errorReceiver'] = $this->composeAddress($this->errorReceiver);
		$this->composed['replyReceiver'] = $this->composeAddress($this->replyReceiver);
		
		$this->determineMessageType();

		// Plain only
		if ($this->messageType['plain'] && !$this->messageType['html'])
		{
			if (!$this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startMainDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
			}
			// With embedded files
			else if ($this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/related');
			}
			// With attached files
			else if ($this->messageType['attached'] && !$this->messageType['embedded'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/mixed');
			}
			// With embedded and attached files
			else if ($this->messageType['embedded'] && $this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/mixed');
			}
		}

		// HTML only
		else if ($this->messageType['html'] && !$this->messageType['plain'])
		{
			if (!$this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startMainDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
			}
			// With embedded files
			else if ($this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/related');
			}
			// With attached files
			else if ($this->messageType['attached'] && !$this->messageType['embedded'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/mixed');
			}
			// With embedded and attached files
			else if ($this->messageType['embedded'] && $this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/mixed');
			}
		}

		// Plain and HTML
		else if ($this->messageType['plain'] && $this->messageType['html'])
		{		
			if (!$this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/alternative');
			}
			// With embedded files
			else if ($this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/related');
			}
			// With attached files
			else if ($this->messageType['attached'] && !$this->messageType['embedded'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/mixed');
			}
			// With embedded and attached files
			else if ($this->messageType['embedded'] && $this->messageType['attached'])
			{
				$this->composed['contentType'] = $this->startLevel(0, 'multipart/mixed');
			}
		}

		$this->composed['contentType'] = XXX_String::removeTrailingLineSeparators($this->composed['contentType']);

		$result = '';

		$result .= 'MIME-Version: 1.0' . self::$lineSeparator;
		$result .= $this->composed['contentType'] . self::$lineSeparator;

		$timestamp = XXX_TimestampHelpers::getCurrentTimestamp();
		
		if ($this->overwrittenTimestamp > 0)
		{
			$timestamp = $this->overwrittenTimestamp;
		}
		
		$result .= 'Date: ' . XXX_I18n_Formatter::formatRFC2822($timestamp) . self::$lineSeparator;

		//$result .= 'Message-ID: <' . $this->createContentID() . '>' . self::$lineSeparator;

		// Human readable from
		$result .= 'From: ' . $this->composed['sender'] . self::$lineSeparator;
		
		// Sending service
		//$result .= 'Sender: ' . $this->composed['relaySender'] . self::$lineSeparator;
		
		// http://www.sitecrafting.com/blog/aol-denying-email/
		$result .= 'Organization: ' . $this->organisation . self::$lineSeparator;
		
		if ($this->composed['errorReceiver'] != '')
		{
			$result .= 'Errors-To: ' . $this->composed['errorReceiver'] . self::$lineSeparator;
		}
		//$result .= 'Return-Path: ' . $this->composed['errorReceiver'] . self::$lineSeparator;
		
		if ($this->composed['replyReceiver'] != '')
		{
			$result .= 'Reply-To: ' . $this->composed['replyReceiver'] . self::$lineSeparator;
		}
		
		if ($this->composed['ccReceivers'] != '')
		{
			$result .= 'Cc: ' . $this->composed['ccReceivers'] . self::$lineSeparator;
		}
		
		if ($this->composed['bccReceivers'] != '')
		{
			$result .= 'Bcc: ' . $this->composed['bccReceivers'] . self::$lineSeparator;
		}
		
		switch ($this->priority)
		{
			case 'high':
				$result .= 'X-Priority: 1 (High)' . self::$lineSeparator;
				$result .= 'X-MSMail-Priority: High' . self::$lineSeparator;
				$result .= 'Importance: High' . self::$lineSeparator;
				break;
			case 'low':
				$result .= 'X-Priority: 5 (Low)' . self::$lineSeparator;
				$result .= 'X-MSMail-Priority: Low' . self::$lineSeparator;
				$result .= 'Importance: Low' . self::$lineSeparator;
				break;
			case 'normal':
				/*
				$result .= 'X-Priority: 3 (Normal)' . self::$lineSeparator;
				$result .= 'X-MSMail-Priority: Normal' . self::$lineSeparator;
				$result .= 'Importance: Normal' . self::$lineSeparator;
				*/
				break;
		}
		
		$this->composed['headers'] = $result;		
		
		return $result;
	}

	public function composeBody ()
	{
		$this->determineMessageType();

		$result = '';

		// Plain only
		if ($this->messageType['plain'] && !$this->messageType['html'])
		{
			if (!$this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
			}
			// With embedded files
			else if ($this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$result .= $this->startBoundary(0);
				$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
				$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
				$result .= self::$lineSeparator . self::$lineSeparator;
	
				$result .= $this->composeEmbeddedFiles(0);
	
				$result .= $this->endBoundary(0);
			}
			// With attached files
			else if ($this->messageType['attached'] && !$this->messageType['embedded'])
			{
				$result .= $this->startBoundary(0);
				$result .= $this->startDataPartHeader('utf-8', 'text/plain', $this->defaultBodyEncoding);
				$result .= XXX_Email_Encoding_Body::encode($this->bodies['plain'], $this->defaultBodyEncoding);
				$result .= self::$lineSeparator . self::$lineSeparator;
	
				$result .= $this->composeAttachedFiles(0);
	
				$result .= $this->endBoundary(0);
			}
			// With embedded and attached files
			else if ($this->messageType['embedded'] && $this->messageType['attached'])
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
		}

		// HTML only
		else if ($this->messageType['html'] && !$this->messageType['plain'])
		{		
			if (!$this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
			}
			// With embedded files
			else if ($this->messageType['embedded'] && !$this->messageType['attached'])
			{
				$result .= $this->startBoundary(0);
				$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
				$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
				$result .= self::$lineSeparator . self::$lineSeparator;
	
				$result .= $this->composeEmbeddedFiles(0);
	
				$result .= $this->endBoundary(0);
			}
			// With attached files
			else if ($this->messageType['attached'] && !$this->messageType['embedded'])
			{
				$result .= $this->startBoundary(0);
				$result .= $this->startDataPartHeader('utf-8', 'text/html', $this->defaultBodyEncoding);
				$result .= XXX_Email_Encoding_Body::encode($this->bodies['html'], $this->defaultBodyEncoding);
				$result .= self::$lineSeparator . self::$lineSeparator;
	
				$result .= $this->composeAttachedFiles(0);
	
				$result .= $this->endBoundary(0);
			}
			// With embedded and attached files
			else if ($this->messageType['embedded'] && $this->messageType['attached'])
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
		}

		// Plain and HTML
		else if ($this->messageType['plain'] && $this->messageType['html'])
		{		
			if (!$this->messageType['embedded'] && !$this->messageType['attached'])
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
			// With embedded files
			else if ($this->messageType['embedded'] && !$this->messageType['attached'])
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
			// With attached files
			else if ($this->messageType['attached'] && !$this->messageType['embedded'])
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
			// With embedded and attached files
			else if ($this->messageType['embedded'] && $this->messageType['attached'])
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
		}

		$this->composed['body'] = $result;

		return $result;
	}
	
	public function composeSingleLineHeaderValue ($key = '', $value = '')
	{
		$result = XXX_Email_Encoding_Header::encodeHeader($key, XXX_String::removeLineSeparators(XXX_String::normalizeLineSeparators($value)), $this->defaultHeaderEncoding);

		$result = XXX_String::getPart($result, XXX_String::getCharacterLength($key) + 1, XXX_String::getCharacterLength($result));

		return $result;
	}
	
	public function composeSubject ()
	{
		$this->composed['subject'] = $this->composeSingleLineHeaderValue('Subject', $this->subject);

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
			$result .= '"' . $address['name'] . '"' . ' <' . $address['address'] . '>';
		}
		else
		{
			$result = '<' . $address . '>';
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
	
	public function setOrganisation ($organisation)
	{
		$this->organisation = $organisation;
	}
	
	public function resetOrganisation ()
	{
		$this->organisation = 'Comcordis B.V.';
	}
	
	public function setCustomDomain ($customDomain)
	{
		$this->customDomain = $customDomain;

		$this->resetSender();
		$this->resetRelaySender();
		$this->resetReplyReceiver();
		$this->resetErrorReceiver();
	}
	
	public function resetCustomDomain ()
	{
		$this->setCustomDomain('comcordis.com');
	}
	
	public function normalizeAddressParameters ($address, $name = '')
	{
		$result = array();
		
		if (XXX_Type::isArray($address))
		{
			if ($address['address'] != '')
			{
				$result['address'] = XXX_String::convertToLowerCase($address['address']);
			}
			if ($address['emailAddress'] != '')
			{
				$result['address'] = XXX_String::convertToLowerCase($address['emailAddress']);
			}
			if ($address['email'] != '')
			{
				$result['address'] = XXX_String::convertToLowerCase($address['email']);
			}
			if ($address['name'] != '')
			{
				$result['name'] = $address['name'];
			}
		}
		else
		{
			$result['address'] = XXX_String::convertToLowerCase($address);
		}
		
		if ($name != '')
		{
			$result['name'] = $name;
		}
		
		if ($result['name'] == '')
		{
			$result = $result['address'];
		}
		
		return $result;
	}
	
	public function setSender ($address, $name = '')
	{
		$this->sender = $this->normalizeAddressParameters($address, $name);
	}
	
	public function getSender ()
	{
		return $this->sender;
	}
	
	public function resetSender ()
	{
		$this->sender = 'service@' . $this->customDomain;
	}
	
	public function setRelaySender ($address, $name = '')
	{
		$this->relaySender = $this->normalizeAddressParameters($address, $name);
	}
	
	public function resetRelaySender ()
	{
		$this->relaySender = 'service@' . $this->customDomain;
	}

	public function correctRelaySenderForSMTP ()
	{
		$relaySender = array
		(
			'name' => 'Comcordis_Email',
			'address' => 'no-reply@comcordis.com'
		);

		// Correct for the respective host
		if (XXX_String::hasPart(XXX_OperatingSystem::$hostname, '.'))
		{
			$relaySender['address'] = 'no-reply@' . XXX_OperatingSystem::$hostname;
		}

		// Convert the original sender email address as if it were an email address under this host
		$originalSender = $email->getSender();
						
		if (XXX_Type::isArray($originalSender))
		{
			$originalSenderAddress = $originalSender['address'];
		}
		else
		{
			$originalSenderAddress = $originalSender;
		}
		
		$originalSenderAddress = XXX_String::replace($originalSenderAddress, '@', '.');
		
		$domain = XXX_String::getLastSeparatedPart($relaySender['address'], '@');
		
		$relaySender['name'] = $originalSenderAddress;
		$relaySender['address'] = $originalSenderAddress . '@' . $domain;

		$this->setRelaySender($relaySender);
	}

	public function addSubDomainToAddress ($address = '', $subDomain = '')
	{
		if (XXX_Type::isArray($address))
		{
			$address['address'] = XXX_String_Pattern::replace($address['address'], '@(?!' . $subDomain . '\\.)', '', '@' . $subDomain . '.');
		}
		else
		{
			$address = XXX_String_Pattern::replace($address, '@(?!' . $subDomain . '\\.)', '', '@' . $subDomain . '.');
		}

		return $address;
	}

	public function correctSenderForSubDomain ($subDomain = '')
	{
		$this->sender = $this->addSubDomainToAddress($this->sender, $subDomain);
		$this->relaySender = $this->addSubDomainToAddress($this->relaySender, $subDomain);
	}

	public function getSenderDomain ()
	{
		$result = '';

		if (XXX_Type::isArray($this->sender))
		{
			$addressParts = XXX_String::splitToArray($this->sender['address'], '@');

			$result = $addressParts[1];
		}
		else
		{
			$addressParts = XXX_String::splitToArray($this->sender, '@');

			$result = $addressParts[1];
		}

		return $result;
	}

	public function setErrorReceiver ($address, $name = '')
	{
		$this->errorReceiver = $this->normalizeAddressParameters($address, $name);
	}
	
	public function resetErrorReceiver ()
	{
		$this->errorReceiver = 'error@' . $this->customDomain;
	}
	
	public function setReplyReceiver ($address, $name = '')
	{
		$this->replyReceiver = $this->normalizeAddressParameters($address, $name);
	}
	
	public function resetReplyReceiver ()
	{
		$this->replyReceiver = 'reply@' . $this->customDomain;
	}
	
	public function setReceiver ($address, $name = '')
	{
		return $this->addReceiver($address, $name);
	}
	
	public function addReceiver ($address, $name = '')
	{
		$this->receivers[] = $this->normalizeAddressParameters($address, $name);

		//XXX_Type::peakAtVariable($this->receivers);
	}
	
	public function resetReceivers ()
	{
		$this->receivers = array();
	}
	
	public function resetReceiver ()
	{
		return $this->resetReceivers();
	}
	
	public function getReceivers ()
	{
		return $this->receivers;
	}
	
	public function setCCReceiver ($address, $name = '')
	{
		return $this->addCCReceiver($address, $name);
	}
	
	public function addCCReceiver ($address, $name = '')
	{
		$this->ccReceivers[] = $this->normalizeAddressParameters($address, $name);
	}
	
	public function resetCCReceivers ()
	{
		$this->ccReceivers = array();
	}
	
	public function resetCCReceiver ()
	{
		return $this->resetCCReceivers();
	}
	
	public function getCCReceivers ()
	{
		return $this->ccReceivers;
	}
	
	public function setBCCReceiver ($address, $name = '')
	{
		return $this->addBCCReceiver($address, $name);
	}
	
	public function addBCCReceiver ($address, $name = '')
	{
		$this->bccReceivers[] = $this->normalizeAddressParameters($address, $name);
	}
	
	public function resetBCCReceivers ()
	{
		$this->bccReceivers = array();
	}
	
	public function resetBCCReceiver ()
	{
		return $this->resetBCCReceivers();
	}
	
	public function getBCCReceivers ()
	{
		return $this->bccReceivers;
	}

	public function setBody ($data, $type = 'html')
	{
		$this->bodies[$type] = $data;
	}
	
	public function prependBody ($data, $type = 'html')
	{
		$this->bodies[$type] = $data . $this->bodies[$type];
	}
	
	public function appendBody ($data, $type = 'html')
	{
		$this->bodies[$type] = $this->bodies[$type] . $data;
	}
	
	public function getBody ($type = 'html')
	{
		return $this->bodies[$type];
	}

	public function setSubject ($subject)
	{
		$this->subject = $subject;
	}
	
	public function prependSubject ($subject)
	{
		$this->subject = $subject . $this->subject;
	}
	
	public function appendSubject ($subject)
	{
		$this->subject = $this->subject . $subject;
	}
	
	public function getSubject ()
	{
		return $this->subject;
	}

	public function send ($save = true)
	{
		return XXX_Email_Sender::sendEmail($this, $save);
	}
	
	public function convertAddressesToSimpleString ($addresses)
	{
		$result = '';
		
		$i = 0;
		
		foreach ($addresses as $address)
		{
			if ($i > 0)
			{
				$result .= ', ';
			}
			
			if (XXX_Type::isArray($address))
			{
				$result .= $address['name'] . ' - ' . $address['address'];
			}
			else
			{
				$result .= $address;
			}
			
			++$i;
		}
		
		return $result;
	}

	public function getAllReceiversAsString ()
	{
		$composedReceivers = XXX_Array::joinValuesToString($this->composeAddresses($this->receivers), ',');
		$composedCCReceivers = XXX_Array::joinValuesToString($this->composeAddresses($this->ccReceivers), ',');
		$composedBCCReceivers = XXX_Array::joinValuesToString($this->composeAddresses($this->bccReceivers), ',');

		$result = '';
		if ($composedReceivers != '')
		{
			$result .= $composedReceivers;
		}
		if ($composedCCReceivers != '')
		{
			if ($result != '')
			{
				$result .= ',';
			}
			$result .= $composedCCReceivers;
		}
		if ($composedBCCReceivers != '')
		{
			if ($result != '')
			{
				$result .= ',';
			}
			$result .= $composedBCCReceivers;
		}

		return $result;
	}

	public function getEmailAsMIMEString ()
	{
		$this->compose();
		
		$content = '';
		$content .= XXX_String::removeTrailingLineSeparators($this->composed['headers']) . self::$lineSeparator;
		$content .= 'To: ' . $this->composed['receivers'] . self::$lineSeparator;
		$content .= 'Subject: ' . $this->composed['subject'] . self::$lineSeparator;
		$content .= self::$lineSeparator;
		$content .= $this->composed['body'];

		//echo $content;
		
		return $content;
	}
}

?>