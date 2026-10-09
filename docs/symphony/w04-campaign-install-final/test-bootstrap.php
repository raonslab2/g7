<?php
/** TEST-only native entrypoint: retain all original tests/bootstrap.php guards. */
require_once __DIR__.'/guard.php';
$w04cEnv=w04Env(false,true); travelLabApplyEnvironment($w04cEnv);
$w04cRoot=w04fRoot(); $w04cEnvPath=$w04cRoot.'/.env'; $w04cBytes=file_get_contents($w04cEnvPath);
$w04cFixture=Dotenv\Dotenv::parse(file_get_contents($w04cRoot.'/.env.testing'));
if($w04cFixture['CACHE_STORE']!=='array' || $w04cFixture['DB_WRITE_DATABASE']!=='req81_travel_lab_test') { throw new RuntimeException('Ordinary fixture boundary.'); }
$w04cRestore=static function() use($w04cEnvPath,$w04cBytes): void {
    if(is_link($w04cEnvPath)) { throw new RuntimeException('Own env symlink refused.'); }
    clearstatcache(true,$w04cEnvPath);
    if(!is_file($w04cEnvPath)||file_get_contents($w04cEnvPath)!==$w04cBytes) { if(file_put_contents($w04cEnvPath,$w04cBytes)!==strlen($w04cBytes)) { throw new RuntimeException('Own env short restoration refused.'); } }
    if(!chmod($w04cEnvPath,0600) || file_get_contents($w04cEnvPath)!==$w04cBytes) { throw new RuntimeException('Own env restore failed.'); }
};
register_shutdown_function($w04cRestore);
if(!unlink($w04cEnvPath)) { throw new RuntimeException('Own TEST-only env hide failed.'); }
try { require $w04cRoot.'/tests/bootstrap.php'; } finally { $w04cRestore(); }
clearstatcache();
if(file_get_contents($w04cEnvPath)!==$w04cBytes || (fileperms($w04cEnvPath)&0777)!==0600 || (fileperms($w04cRoot.'/.env.testing')&0777)!==0600) { throw new RuntimeException('Pre-Laravel restored files failed.'); }
// Root bootstrap chooses its native test cache file aliases; apply no DB/cache driver override here.
echo "W04C native TEST bootstrap: own local env restored; ordinary array fixture; original guards retained.\n";
