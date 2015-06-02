<?php

abstract class XXX_Email_Sender
{
	public static $intercept = false;
	public static $interceptReceivers = array();
	public static $interceptSubjectPrefix = 'Intercepted email for "%originalReceivers%" in %deployEnvironment% - ';

	public static $method = 'smtp';
	
	public static function sendEmail ($email, $save = true)
	{
		$result = false;
		
		if ($email)
		{
			if (self::$intercept)
			{
				$email->resetReceivers();
				$email->resetCCReceivers();
				$email->resetBCCReceivers();

				foreach (self::$interceptReceivers as $interceptReceiver)
				{
					$email->addReceiver($interceptReceiver);
				}

				$email->prependSubject(XXX_String::replaceVariables(self::$interceptSubjectPrefix, array('originalReceivers', 'deployEnvironment'), array($email->getAllReceiversAsString(), XXX::$deploymentInformation['deployEnvironment'])));
			}

			switch (self::$method)
			{
				case 'smtp':
					$email->correctRelaySenderForSMTP();
					
					$email->compose();

					// Fake sendmail doesn't work in this situation
					if (XXX::$deploymentInformation['localDevelopmentBox'] && XXX_PHP::$executionEnvironment == 'commandLine' && XXX_OperatingSystem::$platformName == 'windows')
					{
						$host = gethostbyaddr(XXX_HTTPServer::$ipAddress);
						$smtpServer = 'smtp.ziggo.nl';
						
						if (XXX_String::hasPart($host, 'ziggo'))
						{
							$smtpServer = 'smtp.ziggo.nl';
						}
						else if (XXX_String::hasPart($host, 'telfort'))
						{
							$smtpServer = 'smtp.telfort.nl';
						}
						else if (XXX_String::hasPart($host, 'kpn'))
						{
							$smtpServer = 'smtp.kpn-officedsl.nl';
						}
						else if (XXX_String::hasPart($host, 'xs4all'))
						{
							$smtpServer = 'smtp.xs4all.nl';
						}
						else if (XXX_String::hasPart($host, 'uniserver'))
						{
							$smtpServer = 'mail.uniserver.nl';
						}
						
						ini_set('SMTP', $smtpServer);
						//ini_set('smtp_port', '25');
						
						ini_set('sendmail_from', self::$systemSender['address']);
					}
					
					$result = mail($email->composed['receivers'], $email->composed['subject'], $email->composed['body'], $email->composed['headers']);
					break;
				case 'mailGunAPI':
					$email->correctSenderForSubDomain('mg');

					$email->compose();

					$temporaryFile = 'email_temporary.eml';
					$emailTemporaryFilePath = XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentDataPathPrefix, array('emails', 'temporary', $temporaryFile));

					XXX_FileSystem_Local::writeFileContent($emailTemporaryFilePath, $email->getEmailAsMIMEString());

					$data = array
					(
						'to' => $email->getAllReceiversAsString(),
						'message' => '@' . $emailTemporaryFilePath
					);

					XXX_MailGunAPI_SendEmailService::sendEmail($email->getSenderDomain(), $data);

					XXX_FileSystem_Local::deleteFile($emailTemporaryFilePath);
					break;
			}
		}

		if ($save)
		{
			$timestampPartsForPath = XXX_TimestampHelpers::getTimestampPartsForPath();
			
			$file = 'email_' . XXX_TimestampHelpers::getTimestampPartForFile() . '_' . XXX_String::getPart(XXX_String::getRandomHash(), 0, 8) . '.eml';
			
			$emailFilePath = XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentDataPathPrefix, array('emails', 'sent', $timestampPartsForPath['year'], $timestampPartsForPath['month'], $timestampPartsForPath['date'], $file));
			
			XXX_FileSystem_Local::writeFileContent($emailFilePath, $email->getEmailAsMIMEString());
		}
		
		return $result;
	}
	
	public static function sendSimpleEmail ($receiver = '', $subject = 'Testing', $body = 'This is a test')
	{
		$email = new XXX_Email_Composer();

		if (XXX_Type::isArray($receiver))
		{
			foreach ($receiver as $tempReceiver)
			{
				$email->addReceiver($tempReceiver);
			}
		}
		else
		{
			$email->addReceiver($receiver);
		}
		
		$email->setSubject($subject);
		$email->setBody($body, 'html');
		$email->send();
	}
	
	public static function correctOwnerAndPermissions ()
	{
		$tempPath = XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentDataPathPrefix, array('emails'));
		
		XXX_FileSystem_Local::setDirectoryOwnerAdvanced($tempPath, 'apache', 'apache', true, true);
		
		XXX_FileSystem_Local::setDirectoryPermissions($tempPath, '770', true);			
		XXX_FileSystem_Local::setFilePermissionsInDirectory($tempPath, '660', true);
	}
}

?>