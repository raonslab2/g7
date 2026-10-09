"""실제 DB 접근 없이 SQL 복구 가드의 허용/거부 경계를 확인한다."""
import importlib.util
import json
import time
from pathlib import Path

started = time.monotonic()
spec = importlib.util.spec_from_file_location('recovery_dump_check', Path(__file__).with_name('recovery-dump-check.py'))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
inventory = {'tables': {'g7_t': {}}, 'other_schema_objects': 0}
safe = 'DROP TABLE IF EXISTS `g7_t`; CREATE TABLE `g7_t` (`id` int); INSERT INTO `g7_t` VALUES (1);'
assert module.check(safe, inventory)['table_count'] == 1
assert module.check(safe.replace('(1)', "('USE foreign; DROP DATABASE foreign;')"), inventory)['table_count'] == 1
rejected = ['USE foreign;', 'CREATE DATABASE foreign;', "SET GLOBAL SQL_MODE='x';",
            'INSERT INTO `foreign`.`g7_t` VALUES(1);',
            'CREATE EVENT e ON SCHEDULE EVERY 1 SECOND DO SELECT 1;',
            '/*M!100100 DROP TABLE `g7_t` */;']
for suffix in rejected:
    try:
        module.check(safe + suffix, inventory)
    except (AssertionError, ValueError):
        continue
    raise RuntimeError('Forbidden SQL accepted')
print(json.dumps({'status': 'PASS', 'positive_cases': 2, 'rejection_cases': len(rejected),
                  'database_access': False, 'seconds': round(time.monotonic() - started, 3)}))
