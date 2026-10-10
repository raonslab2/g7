<?php
require __DIR__.'/guard.php';
w04fRequireSnapshot('original');$results=[];
$only=array_slice($argv,1);
foreach([['offline','offline-installed.php',[]],['reference','reference.php',[]],['html','html-product.php',['original']],['kernel','installed-http.php',['original']],['server','server-http.php',['original']],['rollback','recovery-runtime.php',['original','normal']],['fallback','recovery-runtime.php',['original','fault']]] as [$name,$script,$args]) {
    if($only!==[] && !in_array($name,$only,true)) { continue; }
    $log=w04fPrivate('private').'/probe-'.$name.($only!==[]?'-retry':'').'.log';
    $status=w04fChild([PHP_BINARY,__DIR__.'/'.$script,...$args],$log);
    $r=json_decode(file_get_contents($log.'.result.json'),true,flags:JSON_THROW_ON_ERROR);
    $results[$name]=$r+['output_sha256'=>hash_file('sha256',$log),'stderr_sha256'=>hash_file('sha256',$log.'.stderr')];
    w04Save(w04fEvidence('probes').($only!==[]?'/'.implode('-', $only).'-commands.json':'/commands.json'),$results);
    echo $name.' exit='.$status.PHP_EOL;
    if($status!==0) { exit(1); }
}
