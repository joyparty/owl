<?php

namespace Owl\Http;

use Psr\Http\Message\StreamInterface;

abstract class Stream implements StreamInterface
{
    protected $stream;
    protected $seekable = false;
    protected $readable = false;
    protected $writable = false;

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        $this->detach();
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        if (!$this->stream) {
            return null;
        }

        $stream = $this->stream;

        $this->stream = null;
        $this->seekable = $this->readable = $this->writable = false;

        return $stream;
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \Exception('Stream is not seekable');
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function write($string)
    {
        if (!$this->writable) {
            throw new \Exception('Stream is not writable');
        }
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if (!$this->readable) {
            throw new \Exception('Stream is not readable');
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetaData($key = null)
    {
        return $key === null ? [] : null;
    }
}
