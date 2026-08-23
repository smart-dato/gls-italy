<?php

namespace SmartDato\GlsItaly\Exceptions;

class StorageException extends GlsItalyException
{
    public static function writeFailed(string $path, ?string $disk): self
    {
        $target = $disk === null ? $path : "{$disk}:{$path}";

        return new self("Could not write {$target} — the filesystem returned false.");
    }
}
