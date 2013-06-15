<?php

abstract class XXX_Email_Sender
{
	public static $systemSender = array
	(
		'name' => 'Comcordis_Email_Sender',
		'address' => 'no-reply@server.comcordis.com'
	);
	
	public static function sendEmail ($email, $save = true)
	{
		$result = false;
		
		if ($email)
		{
			// System sender
			
				if (XXX_String::hasPart(XXX_OperatingSystem::$hostname, '.'))
				{
					self::$systemSender['address'] = 'no-reply@' . XXX_OperatingSystem::$hostname;
				}
				
				$tempSender = $email->getSender();
				
				if (XXX_Type::isArray($tempSender))
				{
					$tempSender = $tempSender['address'];
				}
				
				$tempSender = XXX_String::replace($tempSender, '@', '.');
				
				$domain = XXX_String::getLastSeparatedPart(self::$systemSender['address'], '@');
				
				self::$systemSender['name'] = $tempSender;			
				self::$systemSender['address'] = $tempSender . '@' . $domain;			
			
			$email->setSystemSender(self::$systemSender);
			
			
			
			$email->compose();
			
			// Fake sendmail doesn't work in this situation
			if (XXX::$deploymentInformation['localDevelopmentBox'] && XXX_PHP::$executionEnvironment == 'commandLine' && XXX_OperatingSystem::$platformName == 'windows')
			{
				ini_set('SMTP', 'smtp.ziggo.nl');
				//ini_set('smtp_port', '25');
				
				ini_set('sendmail_from', self::$systemSender['address']);
			}
			
			if ($save)
			{
				$emailAsFileContent = $email->getEmailAsFileContent();
				
				$timestampPartsForPath = XXX_TimestampHelpers::getTimestampPartsForPath();
				
				$file = 'email_' . XXX_TimestampHelpers::getTimestampPartForFile() . '_' . XXX_String::getPart(XXX_String::getRandomHash(), 0, 8) . '.eml';
				
				$emailFilePath = XXX_Path_Local::extendPath(XXX_Path_Local::$deploymentDataPathPrefix, array('emails', 'sent', $timestampPartsForPath['year'], $timestampPartsForPath['month'], $timestampPartsForPath['date'], $file));
				
				XXX_FileSystem_Local::writeFileContent($emailFilePath, $emailAsFileContent);
			}
			
			$result = mail($email->composed['receivers'], $email->composed['subject'], $email->composed['body'], $email->composed['headers']); 
		}
		
		return $result;
	}
}

?>