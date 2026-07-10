<?php
namespace App\Exceptions;

use Exception;


class CustomExceptions extends Exception
{

public static function globalException(string $error,$statusCode=500)
{
return new self($error,$statusCode);
}


}
