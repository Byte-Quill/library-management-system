<?php
declare(strict_types=1);

final class UploadService
{
    private const MIME_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct(private string $directory, private int $maxBytes)
    {
    }

    public function store(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size']) || (int) $file['size'] > $this->maxBytes || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('The cover upload is invalid or too large.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::MIME_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new InvalidArgumentException('Only valid JPEG, PNG, and WebP cover images are allowed.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) throw new RuntimeException('Upload storage is unavailable.');
        $filename = bin2hex(random_bytes(16)) . '.' . self::MIME_TYPES[$mime];
        $path = $this->directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) throw new RuntimeException('The cover could not be stored.');
        chmod($path, 0640);
        return $filename;
    }
}