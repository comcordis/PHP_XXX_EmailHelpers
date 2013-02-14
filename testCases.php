<?php

$email = new XXX_Email_Composer();

$email->setSender('service@comcordis.com', 'Comcordis service');
$email->addReceiver('v.w.a.meens@vendureka.com', 'Vince Meens');

$email->setSubject('Oh yeah');
$email->setBody('Hallo <b>Test</b>', 'html');

$email->send();

?>