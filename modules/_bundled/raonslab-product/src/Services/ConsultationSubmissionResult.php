<?php

namespace Modules\Raonslab\Product\Services;

use Modules\Sirsoft\Board\Models\Post;

final readonly class ConsultationSubmissionResult
{
    public function __construct(
        public Post $post,
        public bool $created,
        public string $reference,
    ) {}
}
