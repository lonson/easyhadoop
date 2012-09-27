<?php
set_time_limit(0);
include_once "config.inc.php";

include_once "templates/header.html";
include_once "templates/node_monitor_sidebar.html";

$mysql = new Mysql();
$monitor = new NodeMonitor;

if(!$_GET['action'])
{
	$sql = "select * from ehm_hosts where role like 'namenode%'";
	$mysql->Query($sql);
	$arr = $mysql->FetchArray();
	$ip = $arr['ip'];
	$hostname = $arr['hostname'];
	$json = $monitor->GetJson($ip, "namenode");
	#var_dump($json);
	$total = $json->{"beans"}[20]->Total/1024/1024/1024;
	$free = $json->{"beans"}[20]->{"Free"}/1024/1024/1024;
	$nondfs = $json->{"beans"}[20]->{"NonDfsUsedSpace"}/1024/1024/1024;
	$dfs = $json->{"beans"}[20]->{"Used"}/1024/1024/1024;

	$perc_free = ceil(($free/$total)*100);
	$perc_nondfs = ceil(($nondfs/$total)*100);
	$perc_dfs = 100 - ($perc_free + $perc_nondfs);

	echo '<div class=span10>';
	echo '
        <div class="progress">
                <div class="bar bar-success" style="width: '.$perc_free.'%;">Free</div>
                <div class="bar bar-warning" style="width: '.$perc_nondfs.'%;">NonDFS</div>
                <div class="bar bar-danger" style="width: '.$perc_dfs.'%;">DFS</div>
        </div>';
	echo '</div>';
}

if ($_GET['action'] == "CheckHadoopProcess")
{
	$sql = "select * from ehm_hosts order by create_time desc";
	$mysql->Query($sql);
	echo '<div class=span10>';

	echo '<h2>'.$lang['CheckHadoopProcess'].'</h2>';
	echo '<table class="table table-striped">';
	echo '<thead>
               <tr>
                 <th>#</th>
                 <th>'.$lang['hostname'].'</th>
                 <th>'.$lang['ipAddr'].'</th>
                 <th>'.$lang['action'].'</th>
               </tr>
               </thead>
               <tbody>';
	$i = 1;
	while($arr = $mysql->FetchArray())
	{
		$role = $arr['role'];
		$arr_role = explode(",",$role);
		echo '<tr>
                 	<td>'.$i.'</td>
                 	<td>'.$arr['hostname'].'</td>
                 	<td>'.$arr['ip'].'</td>';
        $transport = new TSocket($arr['ip'], 30050);
		$protocol = new TBinaryProtocol($transport);
		$client = new EasyHadoopClient($protocol);
		foreach($arr_role as $key => $value)
		{
			try
			{
				$transport->open();
				$str = $monitor->CheckHadoopProcess($value, $protocol);
				$transport->close();
			}
			catch(exception $e)
			{
				echo "";
			}
			echo '<td>';
            
			if($str == "")
			{
				echo $value." <br /> <span class=\"label label-important\"><i class=\"icon-remove\"></i> ".$lang['notStarted']."</span>";
			}
			else
			{
				echo $value." <br /> <span class=\"label label-success\"><i class=\"icon-ok\"></i>".$lang['processId'].":".$str."</span>";
			}
			echo '</td>';
        }
			
           echo '</tr>';
		$i++;
	}
	echo '</tbody></table>';
	echo '</div>';
}


include_once "templates/footer.html";
?>