<?php
	require_once '/home/borahv1q/vendor/autoload.php';
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

	function createOpvolg($results,$previousEndTime,$endOpen){
		//Database functionality
		$string = file_get_contents("/home/borahv1q/borah-secrets/pw.txt");
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
		$appointmentsInList = 0;
		foreach ($results->getItems() as $event) {
			$appointmentsInList = $appointmentsInList +1;
			$startDateTime = $event->start->dateTime; //needed if there is only 1 appointment
			$app_date_end = substr($startDateTime, 0, 10); //needed if there is only 1 appointment
			if(!($event->getSummary() == "Open")){
				//Check begintijd met eind tijd vorige afspraak. Daarna "eindtijd" op eigen eindtijd zetten. 
				//Op basis daarvan vrije momenten toevoegen aan de lijst met vrije uren (aantal minuten delen door 30 of 90)
				
				$start = substr($startDateTime, 11, 5);		
				$app_date = substr($startDateTime, 0, 10);
				
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
		if($open || $appointmentsInList ==1){
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
		//Database functionality
		$string = file_get_contents("/home/borahv1q/borah-secrets/pw.txt");
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
		$appointmentsInList = 0;
		foreach ($results->getItems() as $event) {
			$appointmentsInList = $appointmentsInList +1;
			$startDateTime = $event->start->dateTime; //needed if there is only 1 appointment
			$app_date_end = substr($startDateTime, 0, 10); //needed if there is only 1 appointment
			if(!($event->getSummary() == "Open")){
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
				createEerste($resultsOpen,$previousEndTime,$endOpen);
			}
			else{
				print "\nGeen afspraak met titel open.\n";
			}
		}

    }
?>