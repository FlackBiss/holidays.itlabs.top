<?php

namespace App\Controller\Admin\Trait;

enum UploadFieldType: string
{
    case FILE = 'file';
    case IMAGE = 'image';
}