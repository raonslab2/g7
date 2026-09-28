<?php

namespace Modules\Raonslab\Product\Services;

use InvalidArgumentException;
use JsonException;

/**
 * 콘텐츠 lane 이 만든 승인 content pack(`raonslab-product.native-page-content-pack.v1`)을 읽는다.
 *
 * envelope 은 정확히 `schema`·`pack_id`·`base_commit`·`pages` 네 키다. 이 클래스는 파일 전체 SHA-256,
 * envelope 형식, base_commit 정책만 판정하고 `pages` 내용 검증은 소비자(InfoPageRemediator)가 한다.
 * 본문은 저장소 밖 파일에만 존재한다.
 */
final class NativePageContentPack
{
    public const SCHEMA = 'raonslab-product.native-page-content-pack.v1';

    private const ENVELOPE_KEYS = ['base_commit', 'pack_id', 'pages', 'schema'];

    /**
     * @param  array<string, mixed>  $pages
     */
    private function __construct(
        public readonly string $sha256,
        public readonly string $packId,
        public readonly string $baseCommit,
        public readonly array $pages,
    ) {}

    /**
     * @param  string  $path  절대 경로
     * @param  string  $expectedBaseCommit  pack 이 기준으로 삼아야 하는 감사 기준 commit
     * @param  string|null  $expectedSha256  승인된 파일 전체 SHA-256 (지정 시 불일치면 거부)
     *
     * @throws InvalidArgumentException
     */
    public static function fromFile(string $path, string $expectedBaseCommit, ?string $expectedSha256 = null): self
    {
        if (! str_starts_with($path, '/') || ! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('The content pack must be an absolute path to a readable file.');
        }

        $raw = (string) file_get_contents($path);
        $sha256 = hash('sha256', $raw);
        if ($expectedSha256 !== null) {
            $expected = strtolower(trim($expectedSha256));
            if (preg_match('/^[0-9a-f]{64}$/', $expected) !== 1) {
                throw new InvalidArgumentException('The expected SHA-256 must be 64 hexadecimal characters.');
            }
            if (! hash_equals($expected, $sha256)) {
                throw new InvalidArgumentException("The content pack SHA-256 {$sha256} does not match the approved value.");
            }
        }

        try {
            $envelope = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The content pack is not valid JSON: '.$exception->getMessage());
        }

        if (! is_array($envelope) || array_is_list($envelope)) {
            throw new InvalidArgumentException('The content pack root must be an object.');
        }

        $keys = array_keys($envelope);
        sort($keys);
        if ($keys !== self::ENVELOPE_KEYS) {
            throw new InvalidArgumentException('The content pack root must contain exactly schema, pack_id, base_commit and pages.');
        }
        if ($envelope['schema'] !== self::SCHEMA) {
            throw new InvalidArgumentException('Unsupported content pack schema; expected '.self::SCHEMA.'.');
        }
        if (! is_string($envelope['pack_id']) || trim($envelope['pack_id']) === '' || mb_strlen($envelope['pack_id']) > 100) {
            throw new InvalidArgumentException('The content pack pack_id must be a non-empty string of at most 100 characters.');
        }
        if (! is_string($envelope['base_commit']) || preg_match('/^[0-9a-f]{40}$/', $envelope['base_commit']) !== 1) {
            throw new InvalidArgumentException('The content pack base_commit must be a 40-character lowercase commit SHA.');
        }
        if (! hash_equals($expectedBaseCommit, $envelope['base_commit'])) {
            throw new InvalidArgumentException(
                "The content pack base_commit {$envelope['base_commit']} is not the audited baseline {$expectedBaseCommit}."
            );
        }
        if (! is_array($envelope['pages']) || array_is_list($envelope['pages'])) {
            throw new InvalidArgumentException('The content pack pages must be an object keyed by slug.');
        }

        return new self($sha256, $envelope['pack_id'], $envelope['base_commit'], $envelope['pages']);
    }
}
