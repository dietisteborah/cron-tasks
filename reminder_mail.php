<?php
	require_once '/home/borahv1q/vendor/autoload.php';
	putenv('GOOGLE_APPLICATION_CREDENTIALS=/home/borahv1q/borah-secrets/client_secret.json');
	
	$errordate = date('d.m.Y h:i:s'); 
	error_log($errordate."--"."Starting reminder_mail.php script.\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
	
	//today
	$today = new DateTime(); // This object represents current date/time
	$today->setTime( 0, 0, 0 ); // reset time part, to prevent partial comparison	
	$today_string = date_format($today, 'Y-m-d');
	//connect to database
	//Database functionality
	$string = file_get_contents("/home/borahv1q/borah-secrets/pw.txt");
	$string = str_replace(array("\r", "\n"), '', $string);
	$link = mysqli_connect("localhost", "borahv1q", $string , "borahv1q_Agenda");
	if (!$link) {
		echo "Error: Unable to connect to MySQL." . PHP_EOL;
		echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		$errordate = date('d.m.Y h:i:s'); 
		error_log($errordate."--"."Error: Unable to connect to MySQL." . PHP_EOL ."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
		error_log($errordate."--"."Debugging errno: " . mysqli_connect_errno() . PHP_EOL ."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
		error_log($errordate."--"."Debugging error: " . mysqli_connect_error() . PHP_EOL ."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
		exit;
	}
	//get all to be send reminders
	error_log($errordate."--"."today_string: " . $today_string ."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
	$sql = "SELECT * FROM reminders where reminder_date <=\"".$today_string;
	$result = mysqli_query($link, $sql);
	if (mysqli_num_rows($result) > 0) {
		// connect to google mail
		$client = new Google_Client();
		$client->setApplicationName('Gmail API PHP reminder mail');
		// All Permissions
		$client->addScope("https://mail.google.com/");
		$client->setAuthConfig('/home/borahv1q/borah-secrets/client_secret_gmail.json');
		$client->setAccessType('offline');

		// Load previously authorized credentials from a file.
		$credentialsPath = '/home/borahv1q/borah-secrets/credentials_gmail.json';
		if (file_exists($credentialsPath)) {
			$accessToken = json_decode(file_get_contents($credentialsPath), true);
		} else {
			printf("Er is een probleem met de mailing functionaliteit. \n Gelieve een mail te sturen naar dietiste.borah@gmail.com");
			$errordate = date('d.m.Y h:i:s'); 
			error_log($errordate."--"."mail-issue in send_email.\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
		}
		$client->setAccessToken($accessToken);

		// Refresh the token if it's expired.
		if ($client->isAccessTokenExpired()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
		}

		$service = new Google_Service_Gmail($client);
		/*
		 * Get all data from query and send correct mail
		 */
		while($row = mysqli_fetch_assoc($result)) {
			//printf("%s;", substr($row["startTime"], 0, 5));
			try {
				// The message needs to be encoded in Base64URL
				$mime = rtrim(strtr(base64_encode($row["body"]), '+/', '-_'), '=');
				$msg = new Google_Service_Gmail_Message();
				$msg->setRaw($mime);
				$objSentMsg = $service->users_messages->send("me", $msg);

				//print('Hartelijk dank voor het maken van een afspraak op '.$date.' om '.$time);

			} catch (Exception $e) {
				$errordate = date('d.m.Y h:i:s'); 
				error_log($errordate."--"."mail-issue:".$e->getMessage()."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
			}					
		}
	} else {
		print "Geen afspraken beschikbaar op deze datum.\n";
	}
	
	$errordate = date('d.m.Y h:i:s'); 
	error_log($errordate."--"."End of reminder_mail.php script.\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
?>