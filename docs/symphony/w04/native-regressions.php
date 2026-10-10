<?php
require __DIR__.'/guard.php';
$root=dirname(__DIR__,3);
foreach (['.env','.env.testing'] as $name) {
    $path=$root.'/'.$name; $bytes=file_get_contents($path);
    $bytes=preg_replace('/^INSTALLER_COMPLETED=true$/m','INSTALLER_COMPLETED=false',$bytes);
    file_put_contents($path,$bytes); chmod($path,0600);
}
$batches=[
    'mysql-workflow'=>['scripts/travel-lab/LiveMysqlTest.php'],
    'support-api'=>['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportApiTest.php'],
    'support-provision'=>['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportProvisionerTest.php'],
    'support-notifications'=>['modules/_bundled/raonslab-travel_lab/tests/Feature/TravelSupportNotificationTest.php'],
    'board-secret'=>['modules/_bundled/sirsoft-board/tests/Feature/User/SecretPostCommentAccessTest.php'],
    'commerce-cart'=>['modules/_bundled/sirsoft-ecommerce/tests/Feature/Cart/CartQuantityAndPurchaseLimitTest.php'],
    'native-auth'=>['tests/Feature/Api/Auth/UserAuthControllerTest.php'],
    'installation'=>['--testsuite=Installation'],
    'installer-context'=>['tests/Unit/Support/InstallerContextTest.php'],
];
$results=is_file(__DIR__.'/evidence/regression-progress.json')?json_decode(file_get_contents(__DIR__.'/evidence/regression-progress.json'),true,flags:JSON_THROW_ON_ERROR):[];
$only=array_slice($argv,1);
if ($only !== []) { $batches=array_intersect_key($batches,array_flip($only)); }
foreach ($batches as $label=>$args) {
    $log=$root.'/storage/framework/testing/w04-regression-'.$label.'.log';
    $status=w04Child([PHP_BINARY,__DIR__.'/snapshot.php',$label,'run',PHP_BINARY,'vendor/bin/phpunit','--bootstrap','docs/symphony/w03-support/test-bootstrap.php',...$args],$log);
    $native=$root.'/storage/framework/testing/w04-'.$label.'-native.log';
    $output=is_file($native)?file_get_contents($native):'';
    preg_match('/(?:OK \(|Tests:\s*)(\d+)/',$output,$count);
    preg_match('/(?:, (\d+) assertions?|Assertions:\s*(\d+))/',$output,$assertions);
    preg_match('/Memory:\s+([0-9.]+ MB)/',$output,$memory);
    $results[$label]=['exit'=>$status,'tests'=>(int)($count[1]??0),'assertions'=>(int)(($assertions[1]??'')?:($assertions[2]??0)),'memory'=>$memory[1]??null,'native_output_sha256'=>hash('sha256',$output)];
    // Only native summary lines, never test traces containing SQL or credentials.
    $lines=array_filter(explode("\n",$output),fn($line)=>preg_match('/^(PHPUnit |Runtime:|Time:|OK \(|Tests:|FAILURES!|ERRORS!|There (was|were)|.*tests were not finished)/',$line));
    $results[$label]['summary']=array_values($lines);
    w04Save(__DIR__.'/evidence/regression-progress.json',$results);
    echo $label.' exit='.$status.' tests='.$results[$label]['tests']."\n";
    if (is_file($root.'/storage/framework/testing/w04-'.$label.'/BLOCKED')) { exit(1); }
}
exit(in_array(1,array_column($results,'exit'),true)?1:0);
