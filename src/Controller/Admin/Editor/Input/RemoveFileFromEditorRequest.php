<?php

namespace App\Controller\Admin\Editor\Input;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
readonly class RemoveFileFromEditorRequest
{
    public function __construct(
        private string $filename
    )
    {
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}