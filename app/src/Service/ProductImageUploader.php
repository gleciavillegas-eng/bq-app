<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductImageUploader
{
    private const IMAGE_DIR = 'assets/uploads/products/';

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {
    }

    /**
     * Mo-upload sa bag-ong picture. Kung walay gipili, magpabilin ang karaan.
     */
    public function upload(?UploadedFile $file, ?string $oldImage): ?string
    {
        if (!$file) {
            return $oldImage;
        }

        if (!$file->isValid()) {
            throw new \RuntimeException('Unable to upload the product image. Make sure it is 5 MB or smaller.');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \RuntimeException('Product image must be 5 MB or smaller.');
        }

        $info = @getimagesize($file->getPathname());

        if ($info === false) {
            throw new \RuntimeException('Please upload a valid JPG or PNG image.');
        }

        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = $info['mime'] ?? '';

        if (!isset($types[$mime])) {
            throw new \RuntimeException('Only JPG and PNG images are allowed.');
        }

        $directory = $this->projectDir . '/public/' . self::IMAGE_DIR;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the product image folder.');
        }

        $safeName = trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), '-');

        if ($safeName === '') {
            $safeName = 'product';
        }

        $filename = strtolower($safeName) . '-' . bin2hex(random_bytes(6)) . '.' . $types[$mime];

        try {
            $file->move($directory, $filename);
        } catch (\Throwable) {
            throw new \RuntimeException('Unable to save the product image.');
        }

        $this->delete($oldImage);

        return self::IMAGE_DIR . $filename;
    }

    /**
     * Ang uploads/products folder lang ang pwede i-delete.
     */
    public function delete(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || !str_starts_with($path, self::IMAGE_DIR)) {
            return;
        }

        $full = $this->projectDir . '/public/' . self::IMAGE_DIR . basename($path);

        if (is_file($full)) {
            @unlink($full);
        }
    }
}