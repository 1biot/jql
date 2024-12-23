<?php

namespace UQL\Stream;

use UQL\Exceptions;

/**
 * @phpstan-type StreamProviderArrayIteratorValue array<int|string, array<int|string, mixed>|scalar|null>
 * @codingStandardsIgnoreStart
 * @phpstan-type StreamProviderArrayIterator \ArrayIterator<int|string, StreamProviderArrayIteratorValue>|\ArrayIterator<int, StreamProviderArrayIteratorValue>|\ArrayIterator<string, StreamProviderArrayIteratorValue>
 * @codingStandardsIgnoreEnd
 * @implements Stream<StreamProviderArrayIterator>
 */
abstract class ArrayStreamProvider extends StreamProvider implements Stream
{
    /**
     * @var StreamProviderArrayIterator $stream
     */
    private \ArrayIterator $stream;

    /**
     * @param StreamProviderArrayIterator $stream
     */
    protected function __construct(\ArrayIterator $stream)
    {
        $this->setStream($stream);
    }

    /**
     * @param StreamProviderArrayIterator $stream
     */
    public function setStream($stream): void
    {
        if (!$stream instanceof \ArrayIterator) {
            throw new \InvalidArgumentException('Expected ArrayIterator');
        }

        $this->stream = $stream;
    }

    /**
     * @return StreamProviderArrayIterator
     */
    public function getStream(?string $query): \ArrayIterator
    {
        $keys = $query !== null ? explode('.', $query) : [];
        $lastKey = array_key_last($keys);
        $stream = new \ArrayIterator($this->stream->getArrayCopy());
        foreach ($keys as $index => $key) {
            $stream = $this->applyKeyFilter($stream, $key, ($index === $lastKey));
        }
        return $stream;
    }

    /**
     * @param \ArrayIterator<int|string, mixed> $stream
     * @param string $key
     * @param bool $isLast
     * @return StreamProviderArrayIterator
     */
    protected function applyKeyFilter(\ArrayIterator $stream, string $key, bool $isLast): \ArrayIterator
    {
        foreach ($stream as $k => $v) {
            if ($k === $key) {
                if ($isLast) {
                    // Final iteration: check if it's a list
                    if (is_array($v) && array_is_list($v)) {
                        return new \ArrayIterator($v);
                    }

                    // Last iteration: check if it's a list
                    return is_array($v) ? new \ArrayIterator([$v]) : new \ArrayIterator([$key => $v]);
                }

                // Other iterations: always return an iterator
                return is_iterable($v) ? new \ArrayIterator($v) : new \ArrayIterator([$v]);
            }
        }
        throw new Exceptions\InvalidArgumentException("Key '$key' not found.");
    }
}