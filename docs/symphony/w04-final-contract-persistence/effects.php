<?php
require __DIR__.'/guard.php';$p=w04Pdo();$inventory=w04Measure($p);$effects=[];
foreach(['ecommerce_orders','ecommerce_order_payments','ecommerce_order_options','ecommerce_temp_orders','jobs','failed_jobs','mail_send_logs','notification_logs','notifications'] as $t){$effects['g7_'.$t]=$inventory['tables']['g7_'.$t]??null;}
w04Save(w04fEvidence('effects').'/'.$argv[1].'.json',['utc'=>gmdate('c'),'effects'=>$effects]);echo 'PASS bounded effects observation'.PHP_EOL;
