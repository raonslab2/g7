<?php
require __DIR__.'/guard.php';
$dir=w04fRequireSnapshot('original');
$expected=json_decode(file_get_contents($dir.'/before.json'),true,flags:JSON_THROW_ON_ERROR);
$p=w04Pdo(); $exclusive=w04fExclusive($p); $actual=w04Measure($p); $p=null;
if($expected!==$actual) { throw new RuntimeException('Final measured baseline mismatch.'); }
w04Save(w04fEvidence('measure').'/final-independent-before-release.json',['status'=>'PASS','inventory'=>$actual,'full_digest'=>w04fDigest($actual),'exclusive'=>$exclusive,'connections_after_close'=>w04fTestIds(),'snapshot_expected_digest'=>w04fDigest($expected),'utc'=>gmdate('c')]);
echo json_encode(['status'=>'PASS','tables'=>$actual['table_count'],'rows'=>$actual['row_count'],'digest'=>w04fDigest($actual)]).PHP_EOL;
