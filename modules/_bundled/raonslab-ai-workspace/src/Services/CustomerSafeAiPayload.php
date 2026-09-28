<?php

namespace Modules\Raonslab\Ai\Workspace\Services;

use Generator;
use Psr\Http\Message\StreamInterface;

/**
 * The single G7 server-to-browser boundary for AI_GCS data.
 *
 * AI_GCS owns a larger operator contract. This DTO intentionally copies only
 * fields the customer workspace renders; unknown/native fields never cross the
 * G7 API or SSE boundary.
 */
class CustomerSafeAiPayload
{
    private const STATES = [
        'ACCEPTED', 'QUEUED', 'STARTING', 'RUNNING', 'WAITING_USER',
        'CANCELLING', 'COMPLETED', 'FAILED', 'CANCELLED', 'INTERRUPTED',
    ];

    private const EVENT_TYPES = [
        'request.accepted', 'request.state', 'request.completed', 'request.failed',
        'request.cancelled', 'request.interrupted', 'provider.input_required',
        'provider.input_resolved', 'provider.result', 'message.accepted',
        'message.delivered', 'message.failed', 'workspace.bootstrap.started',
        'workspace.bootstrap.completed', 'workspace.bootstrap.failed',
    ];

    public function request(array $source): array
    {
        $requestId = $this->identifier($source['request_id'] ?? null);
        if ($requestId === '') {
            throw new \UnexpectedValueException('AI_GCS 응답에 request_id가 없습니다.');
        }

        $safe = ['request_id' => $requestId];
        foreach (['project_id', 'provider', 'profile'] as $field) {
            $value = $this->identifier($source[$field] ?? null);
            if ($value !== '') {
                $safe[$field] = $value;
            }
        }

        $state = $this->state($source['state'] ?? null);
        if ($state !== '') {
            $safe['state'] = $state;
        }

        foreach (['title', 'title_source', 'prompt', 'original_prompt'] as $field) {
            if (is_string($source[$field] ?? null)) {
                $safe[$field] = $this->customerText($source[$field]);
            }
        }
        foreach (['created_at', 'updated_at'] as $field) {
            $value = $this->timestamp($source[$field] ?? null);
            if ($value !== '') {
                $safe[$field] = $value;
            }
        }

        $status = $this->status(is_array($source['status'] ?? null) ? $source['status'] : []);
        if ($status !== []) {
            $safe['status'] = $status;
        }

        $question = $this->question($source['question'] ?? null);
        if ($question !== null) {
            $safe['question'] = $question;
        }

        $result = $this->result($source['final_result'] ?? null)
            ?? $this->result($source['result'] ?? null);
        if ($result !== null) {
            $safe['result'] = ['text' => $result];
        }

        if (($source['error'] ?? null) !== null && ! isset($safe['result'])) {
            $safe['error'] = ['user_message' => '작업을 완료하지 못했습니다. 안전하게 다시 시도할 수 있습니다.'];
        }

        return $safe;
    }

    public function capabilities(string $projectId, array $projects, array $providers): array
    {
        return [
            'project_id' => $this->identifier($projectId),
            'projects' => $this->catalog($projects, true),
            'providers' => $this->catalog($providers, false),
        ];
    }

    public function event(array $source, ?int $fallbackSequence = null): ?array
    {
        $sequence = filter_var($source['sequence'] ?? $fallbackSequence, FILTER_VALIDATE_INT);
        if (! is_int($sequence) || $sequence <= 0) {
            return null;
        }

        $rawType = strtolower(trim((string) ($source['event_type'] ?? $source['type'] ?? '')));
        $eventType = in_array($rawType, self::EVENT_TYPES, true) ? $rawType : 'request.updated';
        $event = [
            'sequence' => $sequence,
            'event_type' => $eventType,
        ];
        $createdAt = $this->timestamp($source['created_at'] ?? null);
        if ($createdAt !== '') {
            $event['created_at'] = $createdAt;
        }

        $payload = is_array($source['payload'] ?? null) ? $source['payload'] : [];
        $state = $this->state($payload['state'] ?? null);
        if ($state !== '') {
            $event['payload'] = ['state' => $state];
        }

        return $event;
    }

    /** @return Generator<int, string> */
    public function sanitizedSse(StreamInterface $stream): Generator
    {
        $buffer = '';
        $eventId = null;
        $data = [];

        $emit = function () use (&$eventId, &$data): ?string {
            if ($data === []) {
                $eventId = null;
                return null;
            }
            $decoded = json_decode(implode("\n", $data), true);
            $data = [];
            $fallback = filter_var($eventId, FILTER_VALIDATE_INT);
            $eventId = null;
            if (! is_array($decoded)) {
                return null;
            }
            $safe = $this->event($decoded, is_int($fallback) ? $fallback : null);
            if ($safe === null) {
                return null;
            }
            $json = json_encode($safe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return 'id: '.$safe['sequence']."\n"
                .'event: '.$safe['event_type']."\n"
                .'data: '.$json."\n\n";
        };

        while (! $stream->eof()) {
            $chunk = $stream->read(8192);
            if ($chunk === '') {
                usleep(20_000);
                continue;
            }
            $buffer .= $chunk;
            while (($position = strpos($buffer, "\n")) !== false) {
                $line = rtrim(substr($buffer, 0, $position), "\r");
                $buffer = substr($buffer, $position + 1);
                if ($line === '') {
                    $frame = $emit();
                    if ($frame !== null) {
                        yield $frame;
                    }
                } elseif (str_starts_with($line, 'id:')) {
                    $eventId = trim(substr($line, 3));
                } elseif (str_starts_with($line, 'data:')) {
                    $data[] = ltrim(substr($line, 5));
                }
            }
        }

        if ($buffer !== '') {
            if (str_starts_with($buffer, 'id:')) {
                $eventId = trim(substr($buffer, 3));
            } elseif (str_starts_with($buffer, 'data:')) {
                $data[] = ltrim(substr($buffer, 5));
            }
        }
        $frame = $emit();
        if ($frame !== null) {
            yield $frame;
        }
    }

    private function status(array $source): array
    {
        $safe = [];
        foreach (['accepted_at', 'execution_started_at', 'last_event_at'] as $field) {
            $value = $this->timestamp($source[$field] ?? null);
            if ($value !== '') {
                $safe[$field] = $value;
            }
        }
        foreach (['last_event_sequence', 'queue_position'] as $field) {
            $value = filter_var($source[$field] ?? null, FILTER_VALIDATE_INT);
            if (is_int($value) && $value >= 0) {
                $safe[$field] = $value;
            }
        }
        if (is_string($source['waiting_reason'] ?? null)) {
            $safe['waiting_reason'] = $this->customerText($source['waiting_reason']);
        }

        return $safe;
    }

    private function question(mixed $value): array|string|null
    {
        if (is_string($value)) {
            $text = $this->customerText($value);
            return $text === '' ? null : $text;
        }
        if (! is_array($value)) {
            return null;
        }
        $questions = $value['params']['questions'] ?? $value['questions'] ?? null;
        if (! is_array($questions)) {
            return null;
        }
        $safeQuestions = [];
        foreach ($questions as $question) {
            if (! is_array($question)) {
                continue;
            }
            $safe = [];
            foreach (['id', 'header', 'question'] as $field) {
                if (is_string($question[$field] ?? null)) {
                    $safe[$field] = $field === 'id'
                        ? $this->identifier($question[$field])
                        : $this->customerText($question[$field]);
                }
            }
            foreach (['multiSelect', 'isSecret'] as $field) {
                if (is_bool($question[$field] ?? null)) {
                    $safe[$field] = $question[$field];
                }
            }
            if (is_array($question['options'] ?? null)) {
                $safe['options'] = [];
                foreach ($question['options'] as $option) {
                    if (is_string($option)) {
                        $safe['options'][] = ['label' => $this->customerText($option)];
                    } elseif (is_array($option)) {
                        $item = [];
                        foreach (['label', 'description'] as $field) {
                            if (is_string($option[$field] ?? null)) {
                                $item[$field] = $this->customerText($option[$field]);
                            }
                        }
                        if ($item !== []) {
                            $safe['options'][] = $item;
                        }
                    }
                }
            }
            if (($safe['question'] ?? '') !== '') {
                $safeQuestions[] = $safe;
            }
        }

        return $safeQuestions === [] ? null : [
            'method' => 'request_user_input',
            'params' => ['questions' => $safeQuestions],
        ];
    }

    private function result(mixed $value): ?string
    {
        if (is_string($value)) {
            $text = $value;
        } elseif (is_array($value)) {
            $text = null;
            foreach (['text', 'summary', 'output', 'final_answer', 'message', 'result'] as $field) {
                if (is_string($value[$field] ?? null) && trim($value[$field]) !== '') {
                    $text = $value[$field];
                    break;
                }
            }
            if ($text === null) {
                return null;
            }
        } else {
            return null;
        }

        $safe = $this->customerText($text, 200_000);
        return $safe === '' ? null : $safe;
    }

    private function catalog(array $items, bool $project): array
    {
        $safeItems = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $safe = [];
            $fields = $project
                ? ['project_id', 'display_name', 'name', 'label', 'available', 'selectable', 'reason']
                : ['provider', 'name', 'label', 'profile', 'profile_id', 'available', 'selectable', 'reason', 'unavailable_reason'];
            foreach ($fields as $field) {
                $value = $item[$field] ?? null;
                if (is_bool($value)) {
                    $safe[$field] = $value;
                } elseif (is_string($value)) {
                    $safe[$field] = in_array($field, ['reason', 'unavailable_reason', 'display_name', 'label', 'name'], true)
                        ? $this->customerText($value)
                        : $this->identifier($value);
                }
            }
            if (! $project && is_array($item['profiles'] ?? null)) {
                $safe['profiles'] = $this->catalog($item['profiles'], false);
            }
            if ($safe !== []) {
                $safeItems[] = $safe;
            }
        }

        return $safeItems;
    }

    private function customerText(string $value, int $limit = 100_000): string
    {
        $text = str_replace("\0", '', $value);
        $text = preg_replace('/```(?:bash|sh|shell|console)[\s\S]*?```/i', '[명령 원문 숨김]', $text) ?? '';
        $text = preg_replace('/(?im)^\s*(?:command|cmd|shell|argv)\s*[:=].*$/', '[명령 원문 숨김]', $text) ?? '';
        $text = preg_replace('/(?im)^\s*(?:\$\s+|(?:\/bin\/)?(?:ba)?sh\s+-c\s+|sudo\s+|rm\s+|curl\s+|wget\s+|git\s+|npm\s+|pnpm\s+|yarn\s+|composer\s+|php\s+artisan\s+|python\d*\s+|pytest\s+|docker\s+|kubectl\s+|make\s+).+$/', '[명령 원문 숨김]', $text) ?? '';
        $text = preg_replace('/(?i)(bearer\s+)[A-Za-z0-9._~+\/-]{8,}/', '$1[credential hidden]', $text) ?? '';
        $text = preg_replace('/\b(?:sk-[A-Za-z0-9_-]{8,}|ghp_[A-Za-z0-9_]{8,}|github_pat_[A-Za-z0-9_]{8,}|AKIA[A-Z0-9]{12,})\b/', '[credential hidden]', $text) ?? '';
        $text = preg_replace('/(?i)(["\']?(?:api[_-]?key|access[_-]?token|refresh[_-]?token|token|secret|password|passwd|credential)["\']?\s*[:=]\s*)(?:"[^"\r\n]*"|\'[^\'\r\n]*\'|[^\s,}\r\n]+)/', '$1[credential hidden]', $text) ?? '';
        $text = preg_replace('~(?<![A-Za-z0-9])(?:/(?:home|root|srv|etc|var|tmp|opt|usr|mnt|workspace|Users)(?:/[^\s,;:]+)+|[A-Za-z]:\\\\(?:[^\s,;:]+\\\\)*[^\s,;:]+|\~/\.[^\s,;:]+)~', '[filesystem path hidden]', $text) ?? '';

        return trim(mb_substr($text, 0, $limit));
    }

    private function state(mixed $value): string
    {
        $state = strtoupper(trim(is_string($value) ? $value : ''));
        return in_array($state, self::STATES, true) ? $state : '';
    }

    private function identifier(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return mb_substr(preg_replace('/[^A-Za-z0-9_.:@-]/', '', trim($value)) ?? '', 0, 255);
    }

    private function timestamp(mixed $value): string
    {
        if (! is_string($value) || strlen($value) > 64 || strtotime($value) === false) {
            return '';
        }

        return $value;
    }
}
