<?php

namespace Owl\Http;

use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    protected $moved;
    protected $file;

    public function __construct(array $file)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('File is invalid or not upload file via POST');
        }

        $this->file = $file;
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException('File was moved to other directory');
        }

        return new ResourceStream(fopen($this->file['tmp_name'], 'r'));
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('File was moved to other directory');
        }

        if (!$target_path = realpath($targetPath)) {
            throw new \InvalidArgumentException('Invalid target path, ' . $target_path);
        }

        $target = $targetPath . '/' . ($this->getClientFilename() ?: $this->file['tmp_name']);
        if (!move_uploaded_file($this->file['tmp_name'], $target)) {
            throw new \RuntimeException('Unable to move upload file');
        }

        $this->moved = true;

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return isset($this->file['size']) ? $this->file['size'] : null;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->file['error'];
    }

    public function getTmpName()
    {
        if ($this->moved) {
            return '';
        }

        return $this->file['tmp_name'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {
        return $this->file['name'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->file['type'] ?? null;
    }

    public function isError()
    {
        return $this->file['error'] !== UPLOAD_ERR_OK;
    }
}
