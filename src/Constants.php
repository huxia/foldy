<?php
namespace Foldy;

class Constants
{
    const DI_KEY_FUNC_IS_DIR = 'is_dir';
    const DI_KEY_FUNC_SCANDIR = 'scandir';
    const DI_KEY_FUNC_INCLUDE = 'include';
    const DI_KEY_CLASS_REQUEST = Request::class;
    const DI_KEY_CLASS_RESPONSE = Response::class;

    const DI_KEY_EXCEPTION_CHECK_INPUT_ERROR = 'error:check_input';

    const DI_KEY_LOGGER = 'logger';
}