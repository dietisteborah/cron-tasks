<?php
	require_once '../vendor/autoload.php';
	putenv('GOOGLE_APPLICATION_CREDENTIALS=/home/borahv1q/borah-secrets/client_secret.json');
		
	function getClient()
	{
		$client = new Google_Client();
		$client->setApplicationName('Dietiste Borah');
		$client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
		$client->setAuthConfig('/home/borahv1q/borah-secrets/client_secret.json');
		$client->setAccessType('offline');

		// Load previously authorized credentials from a file.
		$credentialsPath = '/home/borahv1q/borah-secrets/credentials.json';
		if (file_exists($credentialsPath)) {
			$accessToken = json_decode(file_get_contents($credentialsPath), true);
		} else {
			printf("Er is een probleem met de kalender. \n Gelieve een mail te sturen naar dietiste.borah@gmail.com");
		}
		$client->setAccessToken($accessToken);

		// Refresh the token if it's expired.
		if ($client->isAccessTokenExpired()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
		}
		return $client;
	}
	
	function getAvailable($strdate,$opvolg){
		$today = new DateTime(); // This object represents current date/time
		$today->setTime( 0, 0, 0 ); // reset time part, to prevent partial comparison
		$match_date = DateTime::createFromFormat( "Y-m-d", $strdate );
		$match_date->setTime( 0, 0, 0 ); // reset time part, to prevent partial comparison
		$diff = $today->diff( $match_date );
		$diffDays = (integer)$diff->format( "%R%a" ); // Extract days count in interval
		$appType = false;
		//check appointment type
		if($opvolg=="opvolg"){
			$appType=true;
		}
		if($diffDays > 0){
			$open = false;
			$startOpen = "";
			$endOpen = "";
			$timeslots = array();
			$notime = true;
			$client = getClient();
			$service = new Google_Service_Calendar($client);
			$calendarId = 'dietiste.borah@gmail.com';
			
			//time max -> selected day + 1
			$nextdate = new DateTime($strdate);
			$nextdate->add(new DateInterval('P1D'));
						
			$optParams = array(
			  'orderBy' => 'startTime',
			  'singleEvents' => true,
			  'timeMax' => $nextdate->format('Y-m-d') . 'T00:00:00Z',
			  'timeMin' => $strdate . 'T00:00:00Z',
			);
			$results = $service->events->listEvents($calendarId, $optParams);
			if (!($results->getItems())) {
				print "Geen tijdstippen vrij op deze datum.\n";
			} else 
			{
				foreach ($results->getItems() as $event) {
					if($event->getSummary() == "Open"){
						$open=true;
						$startOpen = $event->start->dateTime;
						$endOpen = $event->getEnd()->dateTime;
						break;
					}
				}
				if($open){
					//Query the events during the opening times
					$optParams = array(
					  'orderBy' => 'startTime',
					  'singleEvents' => true,
					  'timeMax' => $endOpen,
					  'timeMin' => $startOpen,
					);
					$results = $service->events->listEvents($calendarId, $optParams);
					
					$startOpen=substr($startOpen, 11, 5);
					$previousEndTime = $startOpen; //First time, difference between Open "openingtime" and first appointment has to be found
					//$opvolg=false;
					foreach ($results->getItems() as $event) {
						if(!($event->getSummary() == "Open")){
							//Check begintijd met eind tijd vorige afspraak. Daarna "eindtijd" op eigen eindtijd zetten. 
							//Op basis daarvan vrije momenten toevoegen aan de lijst met vrije uren (aantal minuten delen door 30 of 90)
							$startDateTime = $event->start->dateTime;
							$start = substr($startDateTime, 11, 5);						
							if(strtotime($start) > strtotime($previousEndTime)){
								$timeDifferenceInMinutes = (strtotime($start) - strtotime($previousEndTime))/60;
								if($appType && ($timeDifferenceInMinutes/30) >= 1){ //afspraak 30 min
									$noTime = false;
									$amountOfAppointments = $timeDifferenceInMinutes/30;
									for($i=0;$i<$amountOfAppointments;$i++){
										$add = 30 + (30*$i);
										$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
										printf("%s;", date("H:i",$newStartTime));
									}
								}
								elseif((!$appType && ($timeDifferenceInMinutes/90) >= 1)){ //afspraak van 90 min
									$noTime = false;
									$amountOfAppointments = $timeDifferenceInMinutes/30; //elke 30 min een afspraak
									for($i=0;$i<($amountOfAppointments-2);$i++){
										$add = 30 + (30*$i);
										$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
										printf("%s;", date("H:i",$newStartTime));
									}
								}
							}
							else{
								//Do nothing, no time left
							}
							$previousEndTime = substr($event->getEnd()->dateTime,11,5);
						}
					}
					//do the check for the last appointment & closing time
					$endOpen=substr($endOpen, 11, 5);
					if(strtotime($endOpen) > strtotime($previousEndTime)){
						$timeDifferenceInMinutes = (strtotime($endOpen) - strtotime($previousEndTime))/60;
						if($appType && ($timeDifferenceInMinutes/30) >= 1){ //afspraak 30 min
							$noTime = false;
							$amountOfAppointments = $timeDifferenceInMinutes/30;
							for($i=0;$i<$amountOfAppointments;$i++){
								$add = 30 + (30*$i);
								$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
								printf("%s;", date("H:i",$newStartTime));
							}
						}
						elseif((!$appType && ($timeDifferenceInMinutes/90) >= 1)){ //afspraak van 90 min
							$noTime = false;
							$amountOfAppointments = $timeDifferenceInMinutes/30; //elke 30 min een afspraak
							for($i=0;$i<($amountOfAppointments-2);$i++){
								$add = 30 + (30*$i);
								$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
								printf("%s;", date("H:i",$newStartTime));
							}
						}
					}		
					if($noTime){
						print "Geen tijdstippen vrij op deze datum.\n";
					}
				}
				else{
					print "Geen tijdstippen vrij op deze datum.\n";
				}
			}
		}
		else{
			print "Geen tijdstippen vrij op deze datum.\n";
		}
	}

	function createOpvolg($results,$previousEndTime,$endOpen){
		//Database functionality
		$string = file_get_contents("pw.txt");
		$string = str_replace(array("\r", "\n"), '', $string);
		$link = mysqli_connect("localhost", "borahv1q", $string , "borahv1q_Agenda");
		if (!$link) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}
		echo "Connect to mysql.\n" . PHP_EOL;

		$app_date_end = "";
		$open=false;
		foreach ($results->getItems() as $event) {
			if(!($event->getSummary() == "Open")){
				//Check begintijd met eind tijd vorige afspraak. Daarna "eindtijd" op eigen eindtijd zetten. 
				//Op basis daarvan vrije momenten toevoegen aan de lijst met vrije uren (aantal minuten delen door 30 of 90)
				$startDateTime = $event->start->dateTime;
				$start = substr($startDateTime, 11, 5);		
				$app_date = substr($startDateTime, 0, 10);
				$app_date_end = substr($startDateTime, 0, 10);
				printf("SD: %s; ST: %s;", $app_date,$start);
				if(strtotime($start) > strtotime($previousEndTime)){
					$timeDifferenceInMinutes = (strtotime($start) - strtotime($previousEndTime))/60;
					if(($timeDifferenceInMinutes/30) >= 1){ //afspraak 30 min
						$noTime = false;
						$amountOfAppointments = $timeDifferenceInMinutes/30;
						for($i=0;$i<$amountOfAppointments;$i++){
							$add = 30 + (30*$i);
							$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
							$db_endTime = $newStartTime + (30*60);
							//printf("%s;", date("H:i",$newStartTime)); //TODO -> insert naar DB
									$sql = "INSERT INTO afspraken (opvolg, date, startTime, endTime)
									VALUES (1,'".$app_date."','".date("H:i",$newStartTime).":00','".date("H:i",$db_endTime).":00')";
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
		if($open){
			//do the check for the last appointment & closing time
			$endOpen=substr($endOpen, 11, 5);
			printf("ED: %s; ET: %s;", $app_date_end,$endOpen);
			if(strtotime($endOpen) > strtotime($previousEndTime)){
				$timeDifferenceInMinutes = (strtotime($endOpen) - strtotime($previousEndTime))/60;
				if(($timeDifferenceInMinutes/30) >= 1){ //afspraak 30 min
					$noTime = false;
					$amountOfAppointments = $timeDifferenceInMinutes/30;
					for($i=0;$i<$amountOfAppointments;$i++){
						$add = 30 + (30*$i);
						$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
						//printf("%s;", date("H:i",$newStartTime)); //TODO -> insert naar DB
						$db_endTime = $newStartTime + (30*60);
						$sql = "INSERT INTO afspraken (opvolg, date, startTime, endTime)
						VALUES (1,'".$app_date_end."','".date("H:i",$newStartTime).":00','".date("H:i",$db_endTime).":00')";
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
	function createEerste($results,$previousEndTime,$endOpen){
		foreach ($results->getItems() as $event) {
			if(!($event->getSummary() == "Open")){
				//Check begintijd met eind tijd vorige afspraak. Daarna "eindtijd" op eigen eindtijd zetten. 
				//Op basis daarvan vrije momenten toevoegen aan de lijst met vrije uren (aantal minuten delen door 30 of 90)
				$startDateTime = $event->start->dateTime;
				$start = substr($startDateTime, 11, 5);						
				if(strtotime($start) > strtotime($previousEndTime)){
					$timeDifferenceInMinutes = (strtotime($start) - strtotime($previousEndTime))/60;
					if(($timeDifferenceInMinutes/90) >= 1){ //afspraak van 90 min
						$noTime = false;
						$amountOfAppointments = $timeDifferenceInMinutes/30; //elke 30 min een afspraak
						for($i=0;$i<($amountOfAppointments-2);$i++){
							$add = 30 + (30*$i);
							$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
							printf("%s;", date("H:i",$newStartTime)); //TODO -> insert naar DB
						}
					}
				}
				else{
					//Do nothing, no time left
				}
				$previousEndTime = substr($event->getEnd()->dateTime,11,5);
			}
		}
		//do the check for the last appointment & closing time
		$endOpen=substr($endOpen, 11, 5);
		if(strtotime($endOpen) > strtotime($previousEndTime)){
			$timeDifferenceInMinutes = (strtotime($endOpen) - strtotime($previousEndTime))/60;
			if(($timeDifferenceInMinutes/90) >= 1){ //afspraak van 90 min
				$noTime = false;
				$amountOfAppointments = $timeDifferenceInMinutes/30; //elke 30 min een afspraak
				for($i=0;$i<($amountOfAppointments-2);$i++){
					$add = 30 + (30*$i);
					$newStartTime = strtotime($previousEndTime) + (30*60*$i); 
					printf("%s;", date("H:i",$newStartTime)); //TODO -> insert naar DB
				}
			} 
		}	
	}
	function connectToDB(){
		$string = file_get_contents("pw.txt");
		$string = str_replace(array("\r", "\n"), '', $string);
		$link = mysqli_connect("localhost", "borahv1q", $string , "borahv1q_Agenda");
		if (!$link) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}

		echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
		echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;

		$sql = "INSERT INTO afspraken (opvolg, date, startTime, endTime)
		VALUES (1, '2018-06-25','10:00:00','10:30:00')";

		if (mysqli_query($link, $sql)) {
			echo "New record created successfully";
		} else {
			echo "Error: " . $sql . "<br>" . mysqli_error($link);
		}

		
		mysqli_close($link);
	}
	// Get the API client and construct the service object.
	$client = getClient();
	$service = new Google_Service_Calendar($client);
	$calendarId = 'dietiste.borah@gmail.com';
	
	//today
	//$today = DateTime::createFromFormat('j-M-Y', time());
	$today = new DateTime(); // This object represents current date/time
	$today->setTime( 0, 0, 0 ); // reset time part, to prevent partial comparison	
	
	$open = false;
	$startOpen = "";
	$endOpen = "";
	
	//Query the events for the next month
	$optParams = array(
	  'orderBy' => 'startTime',
	  'singleEvents' => true,
	  'timeMin' => $today->format('Y-m-d').'T23:00:00Z',
	  'timeMax' => date('Y-m-d', strtotime('+3 months')).'T23:00:00Z',
	);
	$results = $service->events->listEvents($calendarId, $optParams);	
	
	if (empty($results->getItems())) {
		print "No upcoming events found.\n";
	} else {
		foreach ($results->getItems() as $event) {
			if($event->getSummary() == "Open"){
				$open=true;
				$startOpen = $event->start->dateTime;
				$endOpen = $event->getEnd()->dateTime;

				//Query the events during the opening times
				$optParams = array(
				  'orderBy' => 'startTime',
				  'singleEvents' => true,
				  'timeMax' => $endOpen,
				  'timeMin' => $startOpen,
				);
				$resultsOpen = $service->events->listEvents($calendarId, $optParams);
				
				$startOpen=substr($startOpen, 11, 5);
				print "open: ".$startOpen."\n";
				$previousEndTime = $startOpen; //First time, difference between Open "openingtime" and first appointment has to be found
				createOpvolg($resultsOpen,$previousEndTime,$endOpen);
				//createEerste($resultsOpen,$previousEndTime,$endOpen);
			}
			else{
				print "\nGeen afspraak met titel open.\n";
			}
		}

    }
	//connectToDB();
?>