"""민감한 덤프를 출력하지 않고 네이티브 테이블 덤프의 경계를 검증한다."""
import collections
import json
import re
import sys
from pathlib import Path


def tokens(sql):
    i = 0
    while i < len(sql):
        if sql[i].isspace():
            i += 1
        elif sql.startswith('--', i) or sql[i] == '#':
            end = sql.find('\n', i)
            i = len(sql) if end < 0 else end + 1
        elif sql.startswith('/*', i):
            end = sql.find('*/', i + 2)
            assert end >= 0, 'Unclosed comment'
            body = sql[i + 2:end]
            if body.startswith('!'):
                yield from tokens(re.sub(r'^!\d*\s*', '', body))
            elif body == 'M!999999\\- enable the sandbox mode ':
                pass  # MariaDB 클라이언트의 안전한 샌드박스 헤더.
            elif body.startswith('M!'):
                raise ValueError('Unsupported executable directive')
            i = end + 2
        elif sql[i] in "'\"":
            quote = sql[i]
            i += 1
            while i < len(sql):
                if sql[i] == '\\':
                    i += 2
                elif sql[i] == quote:
                    if i + 1 < len(sql) and sql[i + 1] == quote:
                        i += 2
                    else:
                        i += 1
                        break
                else:
                    i += 1
            else:
                raise ValueError('Unclosed value')
            yield 'VALUE'
        elif sql[i] == '`':
            end = sql.find('`', i + 1)
            assert end >= 0
            name = sql[i + 1:end]
            assert re.fullmatch(r'[A-Za-z0-9_]+', name), 'Unsupported identifier'
            yield '`' + name + '`'
            i = end + 1
        else:
            match = re.compile(r'[A-Za-z0-9_@]+').match(sql, i)
            if match:
                yield match.group().upper()
                i += len(match.group())
            else:
                yield sql[i]
                i += 1


def check(dump, inventory):
    expected = set(inventory['tables'])
    created, dropped, operations = [], [], collections.Counter()
    statement = []
    for token in tokens(dump):
        if token != ';':
            statement.append(token)
            continue
        s, statement = statement, []
        if not s:
            continue
        kind = s[0]
        if s[:2] == ['CREATE', 'TABLE'] and len(s) > 3:
            assert s[3] == '('
            created.append(s[2].strip('`'))
            kind = 'CREATE TABLE'
        elif s[:4] == ['DROP', 'TABLE', 'IF', 'EXISTS']:
            assert len(s) == 5
            dropped.append(s[4].strip('`'))
            kind = 'DROP TABLE'
        elif s[:2] in (['INSERT', 'INTO'], ['LOCK', 'TABLES']):
            assert s[2].strip('`') in expected
            assert s[3] == ('VALUES' if s[0] == 'INSERT' else 'WRITE')
            kind = s[0]
        elif s[:2] == ['ALTER', 'TABLE']:
            assert s[2].strip('`') in expected
            assert s[3:] in (['DISABLE', 'KEYS'], ['ENABLE', 'KEYS'])
            kind = 'ALTER KEYS'
        elif s == ['UNLOCK', 'TABLES']:
            kind = 'UNLOCK'
        elif kind == 'SET':
            # 세션 변수/덤프 복원 변수만 허용; GLOBAL/PERSIST/SQL_LOG_BIN 금지.
            joined = ' '.join(s)
            assert not any(x in s for x in ['GLOBAL', 'PERSIST', 'PASSWORD', 'SQL_LOG_BIN'])
            assert not re.search(r'@@(?:GLOBAL|SQL_LOG_BIN)', joined)
            assert not any(t.strip('@') in ['GLOBAL', 'SQL_LOG_BIN'] for t in s)
            assert all(t in {'SET', '=', ',', '@@', '@', '.', 'VALUE', '+', '-'}
                       or re.fullmatch(r'\d+', t)
                       or t.lstrip('@') in {'OLD_CHARACTER_SET_CLIENT', 'OLD_CHARACTER_SET_RESULTS',
                          'OLD_COLLATION_CONNECTION', 'CHARACTER_SET_CLIENT', 'CHARACTER_SET_RESULTS',
                          'COLLATION_CONNECTION', 'NAMES', 'UTF8MB4', 'UTF8', 'SAVED_CS_CLIENT', 'OLD_TIME_ZONE',
                          'TIME_ZONE', 'OLD_UNIQUE_CHECKS', 'UNIQUE_CHECKS', 'OLD_FOREIGN_KEY_CHECKS',
                          'FOREIGN_KEY_CHECKS', 'OLD_SQL_MODE', 'SQL_MODE', 'OLD_SQL_NOTES', 'SQL_NOTES'}
                       for t in s), 'Unsupported SET'
        else:
            raise ValueError('Unsupported SQL statement')
        # 덤프의 테이블/참조 대상은 비한정 로컬 식별자여야 한다.
        for i, t in enumerate(s[:-1]):
            assert not (t.startswith('`') and s[i + 1] == '.'), 'Qualified identifier'
            if t == 'REFERENCES':
                assert s[i + 1].strip('`') in expected, 'Foreign reference'
        operations[kind] += 1
    assert not statement, 'Incomplete SQL statement'
    assert len(created) == len(expected) and set(created) == expected
    assert len(dropped) == len(expected) and set(dropped) == expected
    return {'exact_create_drop_table_inventory': True, 'table_count': len(expected),
            'other_schema_objects': inventory['other_schema_objects'],
            'statement_counts': dict(operations)}


if __name__ == '__main__':
    try:
        result = check(Path(sys.argv[1]).read_text(), json.loads(Path(sys.argv[2]).read_text()))
        print(json.dumps(result, sort_keys=True))
    except Exception as error:
        # 예외 값에는 사용자 데이터가 포함될 수 있으므로 클래스만 출력한다.
        print(json.dumps({'status': 'BLOCKED', 'class': type(error).__name__}))
        sys.exit(1)
