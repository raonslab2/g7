<?php
/** TEST-only native entrypoint: retain all original tests/bootstrap.php guards. */
require_once __DIR__.'/guard.php';
$w04qEnv=w04Env(false,true); travelLabApplyEnvironment($w04qEnv);
$w04qRoot=w04fRoot(); $w04qEnvPath=$w04qRoot.'/.env'; $w04qBytes=file_get_contents($w04qEnvPath);
$w04qFixture=Dotenv\Dotenv::parse(file_get_contents($w04qRoot.'/.env.testing'));
if($w04qFixture['CACHE_STORE']!=='array' || $w04qFixture['DB_WRITE_DATABASE']!=='req81_travel_lab_test') { throw new RuntimeException('Ordinary fixture boundary.'); }
$w04qRestore=static function() use($w04qEnvPath,$w04qBytes): void {
    if(is_link($w04qEnvPath)) { throw new RuntimeException('Own env symlink refused.'); }
    clearstatcache(true,$w04qEnvPath);
    if(!is_file($w04qEnvPath)||file_get_contents($w04qEnvPath)!==$w04qBytes) { if(file_put_contents($w04qEnvPath,$w04qBytes)!==strlen($w04qBytes)) { throw new RuntimeException('Own env short restoration refused.'); } }
    if(!chmod($w04qEnvPath,0600) || file_get_contents($w04qEnvPath)!==$w04qBytes) { throw new RuntimeException('Own env restore failed.'); }
};
register_shutdown_function($w04qRestore);
if(!unlink($w04qEnvPath)) { throw new RuntimeException('Own TEST-only env hide failed.'); }
try { require $w04qRoot.'/tests/bootstrap.php'; } finally { $w04qRestore(); }
clearstatcache();
if(file_get_contents($w04qEnvPath)!==$w04qBytes || (fileperms($w04qEnvPath)&0777)!==0600 || (fileperms($w04qRoot.'/.env.testing')&0777)!==0600) { throw new RuntimeException('Pre-Laravel restored files failed.'); }
// Root bootstrap chooses its native test cache file aliases; apply no DB/cache driver override here.
echo "W04Q native TEST bootstrap: own local env restored; ordinary array fixture; original guards retained.\n";
