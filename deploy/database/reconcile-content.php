<?php
/** Root/socket-only offline content reconciliation. No credentials or row bodies in output.
 * Default: transaction rehearsal, ALWAYS rolled back. Production apply requires a
 * verified target snapshot and an exact approved plan hash, from the same snapshot.
 */
declare(strict_types=1);

final class ContentReconcile
{
    public array $report = ['mapping' => [], 'operations' => [], 'preserved' => []];
    private array $maps = [];
    private array $new = [];
    private array $privateBoards = [];

    public function __construct(private PDO $db, private string $source, private string $target)
    {
        foreach ([$source, $target] as $name) {
            if (!preg_match('/^g7_[a-z0-9_]+$/D', $name)) {
                throw new RuntimeException('Invalid database name');
            }
        }
        if ($source === $target || !str_contains($source, 'reference')) {
            throw new RuntimeException('Source must be a separate reference database');
        }
    }

    private function rows(string $database, string $table): array
    {
        return $this->db->query("SELECT * FROM `$database`.`g7_$table` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function find(string $table, array $keys): ?array
    {
        $where = implode(' AND ', array_map(fn($k) => "`$k` <=> ?", array_keys($keys)));
        $q = $this->db->prepare("SELECT * FROM `{$this->target}`.`g7_$table` WHERE $where");
        $q->execute(array_values($keys));
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function insert(string $table, array $row): int
    {
        unset($row['id']);
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($row)));
        $params = implode(',', array_fill(0, count($row), '?'));
        $q = $this->db->prepare("INSERT INTO `{$this->target}`.`g7_$table` ($cols) VALUES ($params)");
        $q->execute(array_values($row));
        $this->report['operations'][$table]['insert'] = ($this->report['operations'][$table]['insert'] ?? 0) + 1;
        return (int)$this->db->lastInsertId();
    }

    private function update(string $table, int $id, array $row): void
    {
        unset($row['id']);
        $cols = implode(',', array_map(fn($k) => "`$k`=?", array_keys($row)));
        $q = $this->db->prepare("UPDATE `{$this->target}`.`g7_$table` SET $cols WHERE id=?");
        $q->execute([...array_values($row), $id]);
        $this->report['operations'][$table]['update'] = ($this->report['operations'][$table]['update'] ?? 0) + 1;
    }

    private function map(string $table, int|string|null $id): ?int
    {
        if ($id === null) return null;
        if (!isset($this->maps[$table][$id])) throw new RuntimeException("Unmapped relationship: $table");
        return $this->maps[$table][$id];
    }

    private function withoutIdentity(array $row): array
    {
        foreach (['created_by', 'updated_by', 'granted_by', 'user_id', 'password'] as $key) {
            if (array_key_exists($key, $row)) $row[$key] = null;
        }
        if (array_key_exists('ip_address', $row)) $row['ip_address'] = '0.0.0.0';
        return $row;
    }

    public function inventory(string $database): array
    {
        $q = $this->db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME');
        $q->execute([$database]);
        $out = [];
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $rows = $this->db->query("SELECT * FROM `$database`.`$table`")->fetchAll(PDO::FETCH_ASSOC);
            // Hashes stay internal; evidence contains counts only, not hashes of credentials.
            $serialized = array_map(fn($r) => json_encode($r, JSON_THROW_ON_ERROR), $rows);
            sort($serialized);
            $out[$table] = ['rows' => count($rows), 'digest' => hash('sha256', implode("\n", $serialized))];
        }
        return $out;
    }

    public function run(): array
    {
        foreach ($this->rows($this->source, 'boards') as $board) {
            if ($board['slug'] === 'raon-consultations' || $board['secret_mode'] === 'always') $this->privateBoards[$board['id']] = true;
        }
        $alreadyRestored = true;
        foreach ($this->rows($this->source, 'pages') as $page) {
            $existing = $this->find('pages', ['slug' => $page['slug']]);
            if (!$existing || $existing['content'] !== $page['content'] || $existing['title'] !== $page['title']) $alreadyRestored = false;
        }
        if ($alreadyRestored) throw new RuntimeException('Source content already present; refuse duplicate import');
        // Natural keys, never source numeric IDs; existing target rows are preserved.
        foreach (['roles' => 'identifier', 'boards' => 'slug', 'permissions' => 'identifier', 'menus' => 'slug'] as $table => $key) {
            foreach ($this->rows($this->source, $table) as $raw) {
                $value = $raw[$key];
                // Only installed public boards and their dynamic authorization surface.
                $isBoardSurface = $table === 'boards'
                    ? $value !== 'raon-consultations'
                    : ($table === 'menus' ? preg_match('/^board-(community|notice|questions|new-board)$/', $value)
                        : preg_match('/^sirsoft-board\.(community|notice|questions|new-board)(\.|$)/', $value));
                $existing = $this->find($table, [$key => $value]);
                if (!$existing && !$isBoardSurface) continue;
                $row = $this->withoutIdentity($raw);
                if ($table === 'boards') {
                    // Never overwrite target private board configuration.
                    if ($value === 'raon-consultations') {
                        if (!$existing) throw new RuntimeException('Target private board missing');
                    } else {
                        $row['posts_count'] = 0; $row['comments_count'] = 0;
                    }
                }
                if (isset($row['parent_id'])) {
                    $row['parent_id'] = $this->map($table, $row['parent_id']);
                }
                $id = $existing ? (int)$existing['id'] : $this->insert($table, $row);
                $this->maps[$table][$raw['id']] = $id;
                if (!$existing) $this->new[$table][$id] = true;
            }
        }
        // Append source versions after AWS history; no history rows are deleted.
        foreach ($this->rows($this->source, 'pages') as $raw) {
            $old = $this->find('pages', ['slug' => $raw['slug']]);
            $page = $this->withoutIdentity($raw);
            $sourceVersions = array_values(array_filter($this->rows($this->source, 'page_versions'), fn($v) => $v['page_id'] == $raw['id']));
            if (!$sourceVersions || !in_array($raw['current_version'], array_column($sourceVersions, 'version'))) {
                throw new RuntimeException('Source page current version is missing');
            }
            $current = array_values(array_filter($sourceVersions, fn($v) => $v['version'] == $raw['current_version']))[0];
            foreach (['title', 'content'] as $field) {
                // MySQL source rows and snapshots use different Unicode escaping.
                if (json_decode($raw[$field] ?? 'null', true, 512, JSON_THROW_ON_ERROR)
                    !== json_decode($current[$field] ?? 'null', true, 512, JSON_THROW_ON_ERROR)) {
                    throw new RuntimeException('Source current snapshot differs from page');
                }
            }
            $offset = 0;
            if ($old) {
                $q = $this->db->prepare("SELECT COALESCE(MAX(version),0) FROM `{$this->target}`.g7_page_versions WHERE page_id=?");
                $q->execute([$old['id']]); $offset = (int)$q->fetchColumn();
            }
            $page['current_version'] = (int)$raw['current_version'] + $offset;
            $id = $old ? (int)$old['id'] : $this->insert('pages', $page);
            if ($old) $this->update('pages', $id, $page);
            $this->maps['pages'][$raw['id']] = $id;
            foreach ($sourceVersions as $v) {
                $row = $this->withoutIdentity($v); $row['page_id'] = $id; $row['version'] += $offset;
                $vid = $this->insert('page_versions', $row);
                $this->maps['page_versions'][$v['id']] = $vid;
                $this->report['version_mapping'][$v['id']] = ['page_id' => $id, 'source_version' => (int)$v['version'], 'target_version' => (int)$row['version']];
            }
        }
        foreach (['board_posts', 'board_comments', 'board_attachments', 'page_attachments'] as $table) {
            foreach ($this->rows($this->source, $table) as $raw) {
                // Private source records require separate privacy/credential review.
                if (!empty($raw['is_secret']) || isset($this->privateBoards[$raw['board_id'] ?? 0])) throw new RuntimeException('Unexpected private source content');
                $row = $this->withoutIdentity($raw);
                foreach (['board_id' => 'boards', 'post_id' => 'board_posts', 'page_id' => 'pages'] as $fk => $to) {
                    if (isset($row[$fk])) $row[$fk] = $this->map($to, $row[$fk]);
                }
                if (isset($row['parent_id'])) $row['parent_id'] = $this->map($table, $row['parent_id']);
                $this->maps[$table][$raw['id']] = $this->insert($table, $row);
            }
        }
        // Only additive grants involving newly restored board authorization/menu rows.
        // Existing administrator and private-board grants remain byte-for-byte unchanged.
        foreach (['role_permissions' => ['role_id' => 'roles', 'permission_id' => 'permissions'], 'role_menus' => ['role_id' => 'roles', 'menu_id' => 'menus']] as $table => $fks) {
            foreach ($this->rows($this->source, $table) as $raw) {
                $row = $this->withoutIdentity($raw); $eligible = false; $mapped = true;
                foreach ($fks as $fk => $to) {
                    if (!isset($this->maps[$to][$raw[$fk]])) { $mapped = false; break; }
                    $row[$fk] = $this->map($to, $raw[$fk]);
                    $eligible |= isset($this->new[$to][$row[$fk]]);
                }
                if (!$mapped || !$eligible) continue;
                $keys = array_intersect_key($row, $fks);
                if ($table === 'role_menus') $keys['permission_type'] = $row['permission_type'];
                if (!$this->find($table, $keys)) $this->insert($table, $row);
            }
        }
        foreach (array_keys($this->new['boards'] ?? []) as $id) {
            $q = $this->db->prepare("SELECT COUNT(*) FROM `{$this->target}`.g7_board_posts WHERE board_id=? AND status='published'");
            $q->execute([$id]); $posts = (int)$q->fetchColumn();
            $q = $this->db->prepare("SELECT COUNT(*) FROM `{$this->target}`.g7_board_comments WHERE board_id=? AND status='published'");
            $q->execute([$id]); $comments = (int)$q->fetchColumn();
            $this->update('boards', $id, ['posts_count' => $posts, 'comments_count' => $comments]);
        }
        $this->report['mapping'] = $this->maps;
        return $this->report;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

try {
    $source = $argv[1] ?? ''; $target = $argv[2] ?? ''; $snapshot = $argv[3] ?? '';
    $apply = ($argv[4] ?? '') === '--apply'; $expected = $argv[5] ?? '';
    if (!preg_match('/^g7_[a-z0-9_]+$/D', $snapshot) || !str_contains($snapshot, 'check')) throw new RuntimeException('Verified target check DB required');
    $db = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tool = new ContentReconcile($db, $source, $target);
    $before = $tool->inventory($target); $check = $tool->inventory($snapshot); $sourceInventory = $tool->inventory($source);
    if ($before !== $check) throw new RuntimeException('Target differs from verified backup; create a fresh backup');
    $manifest = json_decode(file_get_contents(__DIR__.'/full-backup-20261004/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    if (count($sourceInventory) !== $manifest['table_count']) throw new RuntimeException('Source table count mismatch');
    foreach ($manifest['table_row_counts'] as $table => $count) {
        if (($sourceInventory[$table]['rows'] ?? null) !== $count) throw new RuntimeException('Source row count mismatch: '.$table);
    }
    $planHash = hash('sha256', json_encode([$sourceInventory, $before]));
    if ($apply && !hash_equals($planHash, $expected)) throw new RuntimeException('Exact rehearsed plan hash required');
    $db->beginTransaction();
    try {
        $report = $tool->run(); $after = $tool->inventory($target);
        $mutable = ['g7_pages','g7_page_versions','g7_boards','g7_board_posts','g7_board_comments','g7_board_attachments','g7_page_attachments','g7_roles','g7_permissions','g7_menus','g7_role_permissions','g7_role_menus'];
        foreach ($before as $table => $state) {
            if (!in_array($table, $mutable, true) && $state !== $after[$table]) throw new RuntimeException('Preserve-target violation: '.$table);
            if ($after[$table]['rows'] < $state['rows']) throw new RuntimeException('Row loss: '.$table);
        }
        // Existing posts/comments, authentication and authorization rows must be unchanged.
        foreach (['board_posts','board_comments','boards','roles','permissions','menus','role_permissions','role_menus'] as $table) {
            $q = $db->query("SELECT * FROM `$snapshot`.`g7_$table` ORDER BY id");
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stmt = $db->prepare("SELECT * FROM `$target`.`g7_$table` WHERE id=?");
                $stmt->execute([$row['id']]);
                if ($stmt->fetch(PDO::FETCH_ASSOC) !== $row) throw new RuntimeException('Existing row changed: '.$table);
            }
        }
        $report['plan_hash'] = $planHash; $report['applied'] = $apply;
        $report['table_classification'] = [];
        foreach ($sourceInventory as $table => $state) {
            $domain = substr($table, 3);
            $class = in_array($domain, ['pages','page_versions','page_attachments','board_posts','board_comments','board_attachments'], true) ? 'A'
                : (in_array($domain, ['users','roles','permissions','modules','plugins','menus','role_menus','menu_permissions','role_permissions','migrations','templates','template_layouts','template_layout_extensions','notification_definitions','notification_templates','boards','board_types'], true) ? 'C' : 'B');
            $report['table_classification'][$table] = ['class' => $class, 'source_rows' => $state['rows'], 'target_before' => $before[$table]['rows'] ?? null, 'target_after' => $after[$table]['rows'] ?? null,
                'decision' => in_array($table, $mutable, true) ? 'explicit_content_mapping_additive_merge' : 'preserve_target'];
        }
        if ($apply) $db->commit(); else $db->rollBack();
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
} catch (Throwable $e) {
    // PDO exception text may include offending data. Do not print it.
    fwrite(STDERR, $e instanceof PDOException ? "SQL reconciliation failed; transaction rolled back.\n" : $e->getMessage()."\n");
    exit(1);
}
