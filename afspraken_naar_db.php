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

	/*
	function create_calendar_appointment($date,$time,$name,$email,$phone,$remark,$type){
		$client = getClient();
		$service = new Google_Service_Calendar($client);
		$calendarId = 'dietiste.borah@gmail.com';		
		
		if($type=="opvolg"){
			$endTime = strtotime($time) + (30*60);
		}
		else{
			$endTime = strtotime($time) + (90*60);
		}
		$event = new Google_Service_Calendar_Event(array(
		  'summary' => $name . ' '. $type,
		  'description' => $name . ' - '.$remark.' - '.$email.' '.$phone.' '.$type,
		  'start' => array(
			'dateTime' => $date.'T'.$time.':00',
			'timeZone' => 'Europe/Brussels',
		  ),
		  'end' => array(
			'dateTime' => $date.'T'.date("H:i:s",$endTime),
			'timeZone' => 'Europe/Brussels',
		  ),
		));

		$calendarId = 'primary';
		$event = $service->events->insert($calendarId, $event);
	} */

	// Get the API client and construct the service object.
	$client = getClient();
	$service = new Google_Service_Calendar($client);
	$calendarId = 'dietiste.borah@gmail.com';
	
	//today
	//$today = DateTime::createFromFormat('j-M-Y', time());
	$today = new DateTime(); // This object represents current date/time
	$today->setTime( 0, 0, 0 ); // reset time part, to prevent partial comparison	
	
	print "today: ".$today->format('Y-m-d').'T23:00:00';
	print "3months: ".$date('Y-m-d', strtotime('+2 months')).'T23:00:00'
	//Query the events for the next month
	$optParams = array(
	  'orderBy' => 'startTime',
	  'singleEvents' => true,
	  'timeMax' => $today->format('Y-m-d').'T23:00:00',
	  'timeMin' => date('Y-m-d', strtotime('+2 months')).'T23:00:00',
	);
	$results = $service->events->listEvents($calendarId, $optParams);	
	
	if (empty($results->getItems())) {
		print "No upcoming events found.\n";
	} else {
		print "Upcoming events:\n";
		foreach ($results->getItems() as $event) {
			$start = $event->start->dateTime;
			if (empty($start)) {
				$start = $event->start->date;
			}
			printf("%s (%s)\n", $event->getSummary(), $start);
    }
}
	
?>