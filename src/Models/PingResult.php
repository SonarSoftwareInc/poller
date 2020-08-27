<?php

namespace Poller\Models;

class PingResult implements CoroutineResultInterface
{
    private float $loss;
    private float $low;
    private float $high;
    private float $median;
    private int $id;

    public function __construct(int $id, float $loss, float $low, float $high, float $median)
    {
        $this->id = $id;
        $this->loss = $loss;
        $this->low = $low;
        $this->high = $high;
        $this->median = $median;
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
    public function getID(): int
    {
        return $this->id;
    }

    public function toArray():array
    {
        return [
            'low' => $this->low,
            'high' => $this->high,
            'median' => $this->median,
            'loss_percentage' => $this->loss,
        ];
    }
}
