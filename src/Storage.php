<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpException;
use RuntimeException;

// File storage for capsule attachments, in a folder on the local disk.
// This is the only class that reads or writes uploaded files.
class Storage
{
    public function __construct(private readonly string $directory)
    {
    }

    // Save an uploaded file ($_FILES['...'] entry) and return its stored name, e.g. "3f9a...e1.png".
    // The name is random, so nobody can guess other people's file names.
    public function save(array $uploadedFile, string $extension): string
    {
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $this->path($storedName))) {
            throw new RuntimeException('Could not save the uploaded file.');
        }
        return $storedName;
    }

    public function delete(?string $storedName): void
    {
        if ($storedName === null || $storedName === '') {
            return;
        }

        $path = $this->path($storedName);
        if (is_file($path)) {
            unlink($path);
        }
    }

    // Send a stored file to the browser.
    // $disposition: "inline" (show in the browser) or "attachment" (download).
    public function stream(string $storedName, string $mime, string $downloadName, string $disposition): void
    {
        $path = $this->path($storedName);
        if (!is_file($path)) {
            throw new HttpException(404);
        }

        // Keep only safe characters for the plain filename; the UTF-8 name goes in filename*.
        $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header(sprintf(
            'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
            $disposition,
            $asciiName,
            rawurlencode($downloadName)
        ));
        header('X-Content-Type-Options: nosniff'); // the browser must trust our Content-Type

        readfile($path);
    }

    // Full path of a stored file. The folder is created on first use.
    private function path(string $storedName): string
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }

        // basename() makes sure we never touch anything outside the upload folder.
        return rtrim($this->directory, '/') . '/' . basename($storedName);
    }
}
