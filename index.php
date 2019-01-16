<!DOCTYPE html>
<html>
<head>
    <title>Disponibilidade</title>
</head>
<body>

<?php

    #==============================================================================
    # Set the default timezone
    #============================================================================== 
    date_default_timezone_set('America/Sao_Paulo');

    #==============================================================================
    # PHP Zabbix API
    #==============================================================================
    require_once("ZabbixApi.php");
    require_once("ZabbixApiConf.php");

    #==============================================================================
    # Periodo em Unix Timestamp
    # Conversor: https://www.unixtimestamp.com/index.php
    #==============================================================================
    $date_from = "1515715200"; // 01/01/2018
    $date_till = "1546300800"; // 01/01/2019

    #==============================================================================
    # Consulta eventos de uma trigger
    #==============================================================================
    function getEvent($api, $triggerId, $val, $countOut) {
    	
    	$method = 'event.get';
    	$params = array(
    		'objectids' => $triggerId,
    		'output' => 'extend',
            'time_from' => $date_from,
            'time_till' => $date_till,
            'value' => $val,
            'sortorder' => 'desc',
            'countOutput' => $countOut
        );

    	$response = $api->executeRequest($method, $params);
    	return $response['result'];
    }

    #==============================================================================
    # Procura o evento de recuperação e retorna a data
    #==============================================================================
    function findEvent($array, $eventSearch){
        foreach ($array as $key => $value) {
            if ($value["eventid"] == $eventSearch) {
                return $value["clock"];
            }
    	}
    };

    #==============================================================================
    # Calcula a diferença de tempo entre incidente e a recuperação
    #==============================================================================
    function eventDuration($arrayProblem,$arrayRecovery){
        $total = array();
    	foreach ($arrayProblem as $key => $value) {
    		//Data do incidente
            $dateProblem = $value["clock"];
            //Data da recuperação
            $dateRecovery = findEvent($arrayRecovery,$value["r_eventid"]);
            //Calcula a diferenca
            $subTime = $dateRecovery - $dateProblem;
            //Joga a diferenca em um array
            $total[] = $subTime;
    	}
    	
    	$subTime = array_sum($total);
    	
        $y = ($subTime/(60*60*24*365));
        if ($y < 1){$y = "0";}
        $d = ($subTime/(60*60*24))%365;
        if ($d < 1){$d = "0";}
        $h = ($subTime/(60*60))%24;
        if ($h < 1){$h = "0";}
        $m = ($subTime/60)%60;
        if ($m < 1){$m = "0";}
        return $y." years ".$d." days ".$h." hours ".$m." minutes";

    };


    /* Call API */
    $api = new ZabbixApi($zbx_host);

    try {

    	$api->login($zbx_user, $zbx_pass);

	#==============================================================================
	# Function getEvent(conexão,triggerid,1-incidente | 2-recuperacao, Contador)
	# Function eventDuration($array incidente, $array recuperação)
	#==============================================================================       
    	$link1Problem = getEvent($api, "34649", 1, false); 
    	$link1Recovery = getEvent($api, "34649", 0, false); 
        $link1Count = getEvent($api, "34649", 1, true);
    	$link1Duration = eventDuration($link1Problem,$link1Recovery);
    	
    	$link2Problem = getEvent($api, "34648", 1, false);
    	$link2Recovery = getEvent($api, "34648", 0, false);
        $link2Count = getEvent($api, "34648", 1, true);
    	$link2Duration = eventDuration($link2Problem,$link2Recovery);
    	
    	$linkGoogleProblem = getEvent($api, "37965", 1, false);
    	$linkGoogleRecovery = getEvent($api, "37965", 0, false);
        $linkGoogleCount = getEvent($api, "37965", 1, true);
    	$linkGoogleDuration = eventDuration($linkGoogleProblem,$linkGoogleRecovery);
    	
    	$api->logout();

    } catch(ZabbixException $e) {
    	echo "ERROR: " . $e->getMessage() . "\n";
    	exit;
    };

                  
?>
		
    <h3>Internet</h3>      	
    <p>link1</p>
    <ul>
        <li>Interrupções da conectividade: <?=$link1Count?></li>
        <li>Tempo sem conexão: <?=$link1Duration?></li>
    </ul>
    <p>Link2</p>
    <ul>
        <li>Interrupções da conectividade: <?=$link2Count?></li>
        <li>Tempo sem conexão: <?=$link2Duration?></li>
    </ul>

    <p>Ping Google</p>
    <ul>
        <li>Interrupções da conectividade: <?=$linkGoogleCount?></li>
        <li>Tempo sem conexão: <?=$linkGoogleDuration?></li>
    </ul>

</body>
</html>
