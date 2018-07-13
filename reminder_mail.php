<?php
	require_once '/home/borahv1q/vendor/autoload.php';
	putenv('GOOGLE_APPLICATION_CREDENTIALS=/home/borahv1q/borah-secrets/client_secret.json');

	function createEerste($results,$previousEndTime,$endOpen){
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
		//echo "Connect to mysql.\n" . PHP_EOL;
		$errordate = date('d.m.Y h:i:s'); 
		error_log($errordate."--"."Executing function createEerste and connected to DB\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");


		$app_date_end = "";
		$open=false;
		$appointmentsInList = 0;
		foreach ($results->getItems() as $event) {
			$appointmentsInList = $appointmentsInList +1;
			$startDateTime = $event->start->dateTime; //needed if there is only 1 appointment
			$app_date_end = substr($startDateTime, 0, 10); //needed if there is only 1 appointment
			if(!($event->getSummary() == "Open")){
				$errordate = date('d.m.Y h:i:s'); 
				error_log($errordate."--"."(createEerste - $event->getSummary() == \"Open\")\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");	
				//Check begintijd met eind tijd vorige afspraak. Daarna "eindtijd" op eigen eindtijd zetten. 
				//Op basis daarvan vrije momenten toevoegen aan de lijst met vrije uren (aantal minuten delen door 30 of 90)
				$start = substr($startDateTime, 11, 5);		
				$app_date = substr($startDateTime, 0, 10);
				printf("SDL: %s; STL: %s;", $app_date,$start);
				if(strtotime($start) > strtotime($previousEndTime)){
					$timeDifferenceInMinutes = (strtotime($start) - strtotime($previousEndTime))/60;
					if(($timeDifferenceInMinutes/30) >= 1){ //afspraak 90 min
						$noTime = false;
						$amountOfAppointments = $timeDifferenceInMinutes/30; //afspraak kan elke 30min geplaatst worden
						for($i=0;$i<$amountOfAppointments-2;$i++){ //-2 om te zorgen dat er op tijd gestopt wordt met afspraken maken
							$add = 30 + (30*$i);
							$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
							$db_endTime = $newStartTime + (90*60);
							//printf("%s;", date("H:i",$newStartTime)); //TODO -> insert naar DB
									$sql = "INSERT INTO afspraken (opvolg, date, startTime, endTime)
									VALUES (0,'".$app_date."','".date("H:i",$newStartTime).":00','".date("H:i",$db_endTime).":00')"; //opvolg = 0 want geen opvolg afspr
									if (mysqli_query($link, $sql)) {
										echo "_OK_";
									} else {
										echo "Error: " . $sql . "<br>" . mysqli_error($link);
									}
						}
					}
				}
				else{
					//Do nothing, no time left
				}
				$previousEndTime = substr($event->getEnd()->dateTime,11,5);
				$open=true;
			}
		}
		if($open || $appointmentsInList ==1){
			//do the check for the last appointment & closing time
			$endOpen=substr($endOpen, 11, 5);
			printf("EDL: %s; ETL: %s;", $app_date_end,$endOpen);
			if(strtotime($endOpen) > strtotime($previousEndTime)){
				$errordate = date('d.m.Y h:i:s'); 
				error_log($errordate."--"."createEerste - strtotime($endOpen) > strtotime($previousEndTime)\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");	
				$timeDifferenceInMinutes = (strtotime($endOpen) - strtotime($previousEndTime))/60;
				if(($timeDifferenceInMinutes/90) >= 1){ //afspraak 90 min
					$noTime = false;
					$amountOfAppointments = $timeDifferenceInMinutes/30; //afspraak kan elke 30min geplaatst worden
					for($i=0;$i<$amountOfAppointments-2;$i++){ //-2 om te zorgen dat er op tijd gestopt wordt met afspraken maken
						$add = 30 + (30*$i);
						$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
						$db_endTime = $newStartTime + (90*60);
						$sql = "INSERT INTO afspraken (opvolg, date, startTime, endTime)
						VALUES (0,'".$app_date_end."','".date("H:i",$newStartTime).":00','".date("H:i",$db_endTime).":00')"; //opvolg = 0 want geen opvolg afspr
						if (mysqli_query($link, $sql)) {
							echo "_OK_";
						} else {
							echo "Error: " . $sql . "<br>" . mysqli_error($link);
						}
					}
				}
			}
		}
		mysqli_close($link);
	}
	function cleanDB(){
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
		$sql = "DELETE FROM afspraken";
		$errordate = date('d.m.Y h:i:s'); 
		if (mysqli_query($link, $sql)) {
			error_log($errordate."--"."OK: query".$sql."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
		} else {
			error_log($errordate."--"."Error: query".$sql."--Message--". mysqli_connect_error() . PHP_EOL ."\n", 3, "/home/borahv1q/logs/php-reminder_mail.log");
		}

		mysqli_close($link);
	}	
	
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