<?php

namespace Modules\Raonslab\TravelLab\Enums;

/**
 * 여행 연구소 고객지원 채널.
 *
 * 각 채널은 그누보드7 게시판 모듈의 게시판 하나를 그대로 재사용한다.
 * 슬러그는 기존 raonslab-product 게시판과 겹치지 않는 travel-lab- 접두사로 고정한다.
 */
enum TravelSupportChannel: string
{
    /** 공지사항 (공개 읽기) */
    case Notices = 'notices';

    /** 자주 묻는 질문 (공개 읽기) */
    case Faqs = 'faqs';

    /** 1:1 문의 (작성자·관리자만 조회) */
    case Questions = 'questions';

    /**
     * 채널이 재사용하는 게시판 슬러그를 반환합니다.
     */
    public function boardSlug(): string
    {
        return match ($this) {
            self::Notices => 'travel-lab-notices',
            self::Faqs => 'travel-lab-faqs',
            self::Questions => 'travel-lab-questions',
        };
    }

    /**
     * 비로그인 방문자에게 공개되는 채널인지 반환합니다.
     */
    public function isPublic(): bool
    {
        return $this !== self::Questions;
    }

    /**
     * 모든 채널의 게시판 슬러그 목록을 반환합니다.
     *
     * @return array<int, string>
     */
    public static function boardSlugs(): array
    {
        return array_map(fn (self $channel): string => $channel->boardSlug(), self::cases());
    }

    /**
     * 게시판 슬러그로 채널을 찾습니다.
     */
    public static function fromBoardSlug(string $slug): ?self
    {
        foreach (self::cases() as $channel) {
            if ($channel->boardSlug() === $slug) {
                return $channel;
            }
        }

        return null;
    }
}
