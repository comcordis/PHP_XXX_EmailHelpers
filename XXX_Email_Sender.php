<?php

abstract class XXX_Email_Sender
{
	public static function sendEmail ($email)
	{
		$result = false;
		
		if ($email)
		{
			$email->compose();
			
			$result = mail($email->composed['receivers'], $email->composed['subject'], $email->composed['body'], $email->composed['headers']); 
		}
		
		return $result;
	}
}

?>