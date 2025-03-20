<?php

namespace Hanafalah\MicroTenant\Contracts;

interface MicroTenant
{
    public function useSchema(string $className): self;
    public function callCustomMethod(): mixed;
    public function add(?array $attributes = []): self;
    public function adds(?array $attributes = [], array $parent_id = []): self;
    public function outsideFilter(array $attributes, array ...$data): array;
    public function beforeResolve(array $attributes, array $add, array $guard = []): self;
    public function change(array $attributes = []): self;
    public function result(): object;
    public function getMessages(): array;
    public function pushMessage(string $message): self;
}
