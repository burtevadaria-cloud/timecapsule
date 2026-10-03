<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

// Thrown to stop the request and send the browser to another URL.
// App::run() catches it and sends "302 Found" with a Location header.
// Using an exception means code deep inside (e.g. Auth::requireUser()) can redirect too.
class RedirectException extends RuntimeException
{
    public function __construct(public readonly string $location)
    {
        parent::__construct("Redirect to $location");
    }
}
