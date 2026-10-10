<?php
/** 기존 검토된 w03 TEST 이름비교 workaround. 정상 installed HTTP에 사용하지 않는다. */
require_once __DIR__.'/guard.php';
$env=w04Env(false,true);travelLabApplyEnvironment($env);
if(getenv('APP_ENV')!=='testing'||getenv('CACHE_STORE')!=='array') { throw new RuntimeException('Ordinary PHPUnit adapters failed.'); }
require dirname(__DIR__).'/w03-support/test-bootstrap.php';
