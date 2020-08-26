<?php

namespace Poller\Models;

class PingResult implements CoroutineResultInterface
{
    private float $loss;
    private float $low;
    private float $high;
    private float $median;
    private string $ip;
    private int $timeTaken;

    public function __construct(string $ip, float $loss, float $low, float $high, float $median, int $timeTaken)
    {
        $this->loss = $loss;
        $this->low = $low;
        $this->high = $high;
        $this->median = $median;
        $this->ip = $ip;
        $this->timeTaken = $timeTaken;
    }

    /**
     * @return float
     */
    public function getLow(): float
    {
        return $this->low;
    }

    /**
     * @return float
     */
    public function getHigh(): float
    {
        return $this->high;
    }

    /**
     * @return float
     */
    public function getMedian(): float
    {
        return $this->median;
    }


    /**
     * @return float
     */
    public function getLoss(): float
    {
        return $this->loss;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    public function getTimeTaken() :int
    {
        return $this->timeTaken;
    }

    public function toArray():array
    {
        return [
            'low' => $this->low,
            'high' => $this->high,
            'median' => $this->median,
            'loss' => $this->loss,
        ];
    }
}
