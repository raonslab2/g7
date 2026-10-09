<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Modules\Raonslab\TravelLab\Support\CampaignSlot;

/**
 * 캠페인 고객 투영 (native Page + 고정 슬롯).
 *
 * native 관리자 PageResource/PublicPageResource 를 쓰지 않는다 — 작성자/수정자, 버전 목록,
 * 첨부·서명 URL, 미리보기 플래그, seo_meta 는 내보내지 않는다. 목록 표현은 본문 대신 서버가 만든
 * 평문 발췌만 싣고, 상세 표현만 현재 로케일 본문과 content_mode 를 싣는다.
 */
class CampaignResource extends BaseApiResource
{
    /** 목록 발췌 최대 글자 수 */
    public const EXCERPT_LENGTH = 140;

    private ?CampaignSlot $slot = null;

    /**
     * 슬롯을 지정한 리소스를 만듭니다.
     */
    public static function forSlot(mixed $page, CampaignSlot $slot): self
    {
        $instance = new self($page);
        $instance->slot = $slot;

        return $instance;
    }

    /**
     * 상세 표현 (본문 포함).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->toListArray($request),
            'content' => $this->localizedContent(),
            'content_mode' => $this->contentMode(),
            'updated_at' => $this->updated_at ? $this->formatDateTimeStringForUser($this->updated_at) : null,
        ];
    }

    /**
     * 목록 표현 (본문 제외, 평문 발췌).
     *
     * @return array<string, mixed>
     */
    public function toListArray(Request $request): array
    {
        $slot = $this->slot ?? throw new \LogicException('CampaignResource requires a registry slot.');

        return [
            'slug' => $slot->slug,
            'kind' => 'campaign',
            'theme' => $slot->theme->value,
            'title' => $this->getLocalizedTitle(),
            'excerpt' => self::plainExcerpt($this->localizedContent(), $this->contentMode()),
            'current_version' => (int) $this->current_version,
            'published_at' => $this->published_at ? $this->formatDateTimeStringForUser($this->published_at) : null,
            'path' => $slot->path(),
            'catalog_query' => $slot->catalogQuery(),
            'art_variant' => $slot->artVariant,
        ];
    }

    /**
     * 본문을 태그·엔티티·연속 공백이 없는 평문 발췌로 만듭니다.
     */
    public static function plainExcerpt(string $content, string $mode, int $limit = self::EXCERPT_LENGTH): string
    {
        $text = $content;
        if ($mode === 'html') {
            // 블록 경계가 붙어 단어가 이어지지 않도록 태그를 공백으로 바꾼 뒤 제거한다.
            $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $text) ?? '';
            $text = strip_tags(preg_replace('/</', ' <', $text) ?? '');
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)).'…' : $text;
    }

    /**
     * native PublicPageResource 와 같은 로케일 → fallback 로케일 → 첫 값 규칙.
     */
    private function localizedContent(): string
    {
        $content = $this->content;
        if (! is_array($content)) {
            return (string) ($content ?? '');
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'ko');

        return (string) ($content[$locale] ?? $content[$fallback] ?? ($content !== [] ? array_values($content)[0] : ''));
    }

    private function contentMode(): string
    {
        return $this->content_mode === 'text' ? 'text' : 'html';
    }
}
