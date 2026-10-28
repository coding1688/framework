<?php

namespace App\Libs\File;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FileReferenceOwnerRegistry
{
    /** @var array<class-string<Model>, true> */
    private array $owners = [];

    /** @param class-string<Model> $owner */
    public function register(string $owner): void
    {
        if (! is_subclass_of($owner, Model::class) || ! method_exists($owner, 'fileReferenceFields')) {
            throw new InvalidArgumentException('Invalid file reference owner: '.$owner);
        }
        $this->owners[$owner] = true;
    }

    /** @return list<class-string<Model>> */
    public function classes(): array
    {
        return array_keys($this->owners);
    }
}
