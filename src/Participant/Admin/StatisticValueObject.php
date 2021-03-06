<?php

namespace kissj\Participant\Admin;

use kissj\Participant\Participant;
use kissj\User\User;

class StatisticValueObject {
    /** @var int */
    protected $openCount;
    /** @var int */
    protected $closedCount;
    /** @var int */
    protected $approvedCount;
    /** @var int */
    protected $paidCount;

    /**
     * @param Participant[] $participants
     */
    public function __construct(array $participants) {
        $this->openCount = 0;
        $this->closedCount = 0;
        $this->approvedCount = 0;
        $this->paidCount = 0;

        foreach ($participants as $participant) {
            switch ($participant->user->status) {
                case User::STATUS_OPEN:
                    $this->openCount++;
                    break;

                case User::STATUS_CLOSED:
                    $this->closedCount++;
                    break;

                case User::STATUS_APPROVED:
                    $this->approvedCount++;
                    break;

                case User::STATUS_PAID:
                    $this->paidCount++;
                    break;
            }
        }
    }

    public function getOpenCount(): int {
        return $this->openCount;
    }

    public function getClosedCount(): int {
        return $this->closedCount;
    }

    public function getApprovedCount(): int {
        return $this->approvedCount;
    }

    public function getPaidCount(): int {
        return $this->paidCount;
    }
}
