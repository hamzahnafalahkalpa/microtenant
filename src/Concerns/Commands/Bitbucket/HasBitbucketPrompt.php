<?php

namespace Hanafalah\MicroTenant\Concerns\Commands\Bitbucket;

use Hanafalah\BitbucketLaravel\Traits\BitbucketTrait;

trait HasBitbucketPrompt
{
    use BitbucketTrait;

    protected array $__repo = [];

    protected ?string $__slug;

    protected function askWannaMakeRepo(string $suggest = ''): self
    {
        $need_repo = $this->confirm('Do you want create repository in bitbucket ?', true);
        if ($need_repo) {
            $this->__slug = $this->ask('Enter Repository slug (for create repository in bitbucket)', $suggest);
            $this->__repo[] = $this->__slug;
        } else {
            $this->__slug = null;
        }
        return $this;
    }
}
