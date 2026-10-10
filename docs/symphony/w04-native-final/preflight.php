<?php
require __DIR__.'/guard.php';
$env=w04Env(); $records=[];
foreach([['vendor-bundle',['scripts/travel-lab/vendor-check.php','--bundled']],['guard',['scripts/travel-lab/guard-test.php']],['recovery-flow',['scripts/travel-lab/recovery-failure-test.php']]] as [$name,$args]) {
    $log=w04fPrivate('private').'/preflight-'.$name.'.log';
    $status=w04fChild([PHP_BINARY,...$args],$log,$env);
    $r=json_decode(file_get_contents($log.'.result.json'),true,flags:JSON_THROW_ON_ERROR);
    $records[$name]=$r+['output_sha256'=>hash_file('sha256',$log),'safe_summary'=>trim(file_get_contents($log))];
    w04Save(w04fEvidence('intake').'/preflight.json',$records);
    echo $name.' exit='.$status."\n";
    if($status!==0) { exit(1); }
}
