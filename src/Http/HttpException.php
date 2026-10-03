<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

// Throw it anywhere to stop the request and show an error page:
//
//   throw new HttpException(404);
//   throw new HttpException(403, 'Your session expired. Please reload the page.');
//
// App::run() catches it and renders views/error.php with this status code.
class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly ?string $text = null, // null = the default text for this status
    ) {
        parent::__construct($text ?? "HTTP $status", $status);
    }
}
