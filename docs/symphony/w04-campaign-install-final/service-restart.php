<?php
/** Own TEST public-index service twice; terminate only proc_open-owned single PHP child. */
require __DIR__.'/guard.php';
w04fRequireSnapshot($argv[1]??'install3');$env=w04Env();$p=w04Pdo();w04fExclusive($p);$p=null;
unset($env['PHP_CLI_SERVER_WORKERS']);$records=[];$private=w04fPrivate('private');
$port=@fsockopen('127.0.0.1',18879,$err,$message,0.2);if($port){fclose($port);throw new RuntimeException('Foreign port occupied; no kills.');}
for($run=1;$run<=2;$run++){
 $proc=proc_open([PHP_BINARY,'-S','127.0.0.1:18879','-t','public'],[0=>['file','/dev/null','r'],1=>['file',$private.'/service-'.$run.'.log','w'],2=>['file',$private.'/service-'.$run.'.stderr','w']],$pipes,w04fRoot(),$env);$pid=proc_get_status($proc)['pid'];
 try {
  usleep(300000);
  $ctx=stream_context_create(['http'=>['header'=>"Accept: application/json\r\n",'ignore_errors'=>true,'timeout'=>10]]);
  $body=file_get_contents('http://127.0.0.1:18879/api/modules/raonslab-travel_lab/campaigns/travel-lab-campaign-autumn-escape',false,$ctx);
  $status=$http_response_header[0]??'';$data=json_decode($body,true,flags:JSON_THROW_ON_ERROR);
  $records[]=['run'=>$run,'pid'=>$pid,'status'=>$status,'version'=>$data['data']['current_version']??null,'body_sha256'=>hash('sha256',$body)];
  if(!str_contains($status,'200') || ($data['data']['current_version']??null)!==3){throw new RuntimeException('Own actual public-index HTTP failed.');}
 } finally {proc_terminate($proc,15);$exit=proc_close($proc);$records[array_key_last($records)]['server_exit']=$exit;$records[array_key_last($records)]['pid_gone']=!file_exists('/proc/'.$pid);}
 if(file_exists('/proc/'.$pid)){throw new RuntimeException('Own service process remains; BLOCK.');}
 w04fForeignHandles();w04fQuiesce();
}
if($records[0]['body_sha256']!==$records[1]['body_sha256']){throw new RuntimeException('Restart changed published body.');}
w04Save(w04fEvidence('service').'/restart.json',['status'=>'PASS_BOUNDED','records'=>$records,'service_port'=>18879,'parent_service_touched'=>false,'env_loss'=>'NOT_RUN','connections_after_stop'=>w04fTestIds()]);echo 'PASS own public-index restart2'.PHP_EOL;
