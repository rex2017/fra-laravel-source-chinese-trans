<?php
/**
 * Http，文件类
 */

namespace Illuminate\Http;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class File extends SymfonyFile
{
    use FileHelpers;
}
