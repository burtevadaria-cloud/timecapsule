<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;
use DateTimeZone;
use finfo;

// Validates the "New capsule" form.
class CapsuleValidator
{
    // File types we accept. The key is the MIME type detected from the file CONTENT,
    // the value is the extension we save the file with.
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private readonly int $maxUploadMb)
    {
    }

    public function maxUploadMb(): int
    {
        return $this->maxUploadMb;
    }

    // Returns [$data, $errors, $upload]:
    //   $data   - cleaned values (also used to re-fill the form); "open_at" is the text the
    //             visitor typed, "open_at_utc" the parsed moment ("Y-m-d H:i:s", UTC) or null
    //   $errors - ['field' => 'first error message'], empty when everything is fine
    //   $upload - null or ['file' => $_FILES entry, 'mime' => ..., 'extension' => ...]
    public function validate(array $input, ?array $file): array
    {
        $data = [
            'title' => trim((string) ($input['title'] ?? '')),
            'message' => trim((string) ($input['message'] ?? '')),
            'open_at' => trim((string) ($input['open_at'] ?? '')),
            'open_at_utc' => null,
            'recipient_email' => trim((string) ($input['recipient_email'] ?? '')),
            'is_public' => !empty($input['is_public']),
        ];

        $openAt = $this->parseOpenAt((string) ($input['open_at_utc'] ?? ''), $data['open_at']);
        $data['open_at_utc'] = $openAt?->format('Y-m-d H:i:s');

        $errors = array_filter([
            'title' => $this->titleError($data['title']),
            'message' => $this->messageError($data['message']),
            'open_at' => $this->openAtError($openAt),
            'recipient_email' => $this->recipientError($data['recipient_email']),
        ]);

        [$upload, $fileError] = $this->checkUpload($file);
        if ($fileError !== null) {
            $errors['attachment'] = $fileError;
        }

        return [$data, $errors, $upload];
    }

    private function titleError(string $title): ?string
    {
        if ($title === '') {
            return 'Enter a title.';
        }
        if (mb_strlen($title) > 120) {
            return 'Title must be at most 120 characters.';
        }
        return null;
    }

    private function messageError(string $message): ?string
    {
        if ($message === '') {
            return 'Write a message.';
        }
        if (mb_strlen($message) > 5000) {
            return 'Message must be at most 5000 characters.';
        }
        return null;
    }

    // The moment the capsule opens, in UTC, or null when the form has no usable value.
    //   $utc   - "open_at_utc", filled by public/js/app.js: "2027-01-01T12:30:00.000Z"
    //   $local - "open_at", the visitor's local time: "2027-01-01T13:30". It is only used when
    //            JavaScript is off; then we cannot know the time zone and treat it as UTC.
    // Seconds are dropped: capsules open on a whole minute.
    public function parseOpenAt(string $utc, string $local): ?DateTimeImmutable
    {
        $utc = trim($utc);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})(:(\d{2})(\.\d{1,3})?)?Z$/', $utc, $m)) {
            $seconds = (int) ($m[4] ?? 0);
            $moment = $seconds < 60 ? $this->parseMinute($m[1] . 'T' . $m[2]) : null;
            if ($moment !== null) {
                return $moment;
            }
        }

        $local = trim($local);
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $local)) {
            return $this->parseMinute($local);
        }
        return null;
    }

    // "2027-01-01T13:30" (UTC) -> DateTimeImmutable, or null for dates like 2026-02-31 or 25:00.
    private function parseMinute(string $value): ?DateTimeImmutable
    {
        // "!" resets the unused fields (seconds); comparing the formatted value rejects
        // impossible dates, which PHP would otherwise roll over into the next month.
        $moment = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('UTC'));
        if ($moment === false || $moment->format('Y-m-d\TH:i') !== $value) {
            return null;
        }
        return $moment;
    }

    private function openAtError(?DateTimeImmutable $openAt): ?string
    {
        if ($openAt === null) {
            return 'Choose an open date and time.';
        }
        if ($openAt <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            return 'The open time must be in the future.';
        }
        return null;
    }

    private function recipientError(string $email): ?string
    {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }
        return null;
    }

    // Returns [$upload, $errorMessage]. Both are null when no file was chosen.
    private function checkUpload(?array $file): array
    {
        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        $tooBig = "File must be at most {$this->maxUploadMb} MB.";

        // PHP itself rejects files above upload_max_filesize (php.ini).
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return [null, $tooBig];
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return [null, 'Upload failed. Please try again.'];
        }
        if ($file['size'] > $this->maxUploadMb * 1024 * 1024) {
            return [null, $tooBig];
        }

        // Never trust the file name or the browser's Content-Type: look at the bytes instead.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::ALLOWED_MIME_TYPES[$mime])) {
            return [null, 'File must be JPG, PNG, GIF, WEBP or PDF.'];
        }

        return [['file' => $file, 'mime' => $mime, 'extension' => self::ALLOWED_MIME_TYPES[$mime]], null];
    }
}
