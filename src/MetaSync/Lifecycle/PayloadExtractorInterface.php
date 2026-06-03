<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Lifecycle;

use OliTheme\MetaSync\Publisher\PublishPayload;

/**
 * @package OliTheme\MetaSync\Lifecycle
 *
 * @since 1.3.0
 */
interface PayloadExtractorInterface
{
    public function fromPost(int $postId): ?PublishPayload;
}
