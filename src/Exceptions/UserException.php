<?php
declare(strict_types=1);

namespace Lethe\Exceptions;

/**
 * Exception whose message is safe to display to the end user.
 * Any other exception type is logged and reported generically.
 */
class UserException extends \RuntimeException
{
}
