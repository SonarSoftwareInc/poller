<?php

namespace Poller\Models;

class PingResult implements CoroutineResultInterface
{
    private float $loss;
    private float $low;
    private float $high;
    private float $median;
    private string $ip;

    public function __construct(string $ip, float $loss, float $low, float $high, float $median)
    {
        $this->loss = $loss;
        $this->low = $low;
        $this->high = $high;
        $this->median = $median;
        $this->ip = $ip;
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

    public function toArray()
    {
        return [
            'low' => $this->low,
            'high' => $this->high,
            'median' => $this->median,
            'loss' => $this->loss,
        ];
    }
}
