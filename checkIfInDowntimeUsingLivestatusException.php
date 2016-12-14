<?php

// The livestatus socket is defiend in your nagios.cfg:
// broker_module=/opt/openitc/nagios/livestatus/lib/mk-livestatus/livestatus.o /opt/openitc/nagios/livestatus/run/livestatus.sock

require_once "Livestatus.php";
var_dump(checkIfInDowntimeUsingLivestatus('srvcom10'));
var_dump(checkIfInDowntimeUsingLivestatus('srvcom10', 'Ping'));


function checkIfInDowntimeUsingLivestatus($hostname, $servicedesc = null){
	$livestatus = new Livestatus('/opt/openitc/nagios/livestatus/run/livestatus.sock');
	$livestatus->bind();

	$isService = false;
	if($servicedesc !== null){
		$isService = true;
	}

	if($isService === false){
		try{
		  $result = $livestatus->query(array(
		  		'GET hosts',
		  		'Filter: host_name = '.$hostname
		  	),
		  	array(
		  		'host_name',
		  		'scheduled_downtime_depth'
		  	)
		  );
		}catch(Exception $e){
			die($e->getMessage());
		}
	}else{
		try{
		  $result = $livestatus->query(array(
		  		'GET services',
		  		'Filter: host_name = '.$hostname,
		  		'Filter: description = '.$servicedesc
		  	),
		  	array(
		  		'description',
		  		'scheduled_downtime_depth'
		  	)
		  );
    }catch(Exception $e){
      die($e->getMessage());
    }
	}

	if(isset($result['data'][0]['scheduled_downtime_depth']) && $result['data'][0]['scheduled_downtime_depth'] > 0){
		return true;
	}
	return false;
}
