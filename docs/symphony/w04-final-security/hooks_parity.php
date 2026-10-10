<?php
// W04 final security: source getSubscribedHooks() of travel listeners vs installed hooks.php text (read-only parse).
// Usage: php hooks_parity.php <installed hooks.php> <out.json>
[$s, $cache, $out] = $argv;
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$l = new Composer\Autoload\ClassLoader; $l->addPsr4('Modules\\Raonslab\\TravelLab\\', $root.'/modules/_bundled/raonslab-travel_lab/src/'); $l->register(true);
$src = [];
foreach (glob($root.'/modules/_bundled/raonslab-travel_lab/src/Listeners/*.php') as $f) {
    $c = 'Modules\\Raonslab\\TravelLab\\Listeners\\'.basename($f, '.php');
    foreach ($c::getSubscribedHooks() as $hook => $cfg) {
        $src[] = basename($f, '.php').'|'.$hook.'|'.($cfg['method'] ?? '').'|'.($cfg['type'] ?? 'action').'|'.(int) ($cfg['sync'] ?? 0).'|'.($cfg['priority'] ?? '');
    }
}
$t = file_get_contents($cache);
$start = strpos($t, "'raonslab-travel_lab' =>", strpos($t, "'modules' =>"));
$seg = substr($t, $start, 6000);
$inst = [];
preg_match_all("/'listener' => 'Modules\\\\\\\\Raonslab\\\\\\\\TravelLab\\\\\\\\Listeners\\\\\\\\(\\w+)',\\s*'hooks' =>\\s*array \\((.*?)\\),\\s*'dynamic'/s", $seg, $m, PREG_SET_ORDER);
foreach ($m as [$all, $cls, $body]) {
    preg_match_all("/'([a-z0-9_.\\-]+)' =>\\s*array \\((.*?)\\),/s", $body, $hs, PREG_SET_ORDER);
    foreach ($hs as [$x, $hook, $cfg]) {
        $g = fn ($k) => preg_match("/'$k' => '?([^',\\n]+)'?,/", $cfg, $mm) ? $mm[1] : null;
        $sync = $g('sync') === 'true' ? 1 : 0;
        $inst[] = $cls.'|'.$hook.'|'.$g('method').'|'.($g('type') ?? 'action').'|'.$sync.'|'.$g('priority');
    }
}
sort($src); sort($inst);
$r = ['source' => $src, 'installed' => $inst, 'listeners_source' => count(array_unique(array_map(fn ($x) => explode('|', $x)[0], $src))), 'subscriptions_source' => count($src), 'subscriptions_installed' => count($inst), 'equal' => $src === $inst, 'hooks_cache_sha256' => hash_file('sha256', $cache)];
file_put_contents($out, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(['equal' => $r['equal'], 'src' => count($src), 'inst' => count($inst)]), "\n";
