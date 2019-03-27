<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;

class UserAlertsTriggersLogic
{
    const CONDITION_GREATER_THAN = 0;
    const CONDITION_LESS_THAN = 1;
    const CONDITION_EQUAL_TO = 2;

    /**
     * States if a UserAlert and a Price match up with HQ/NQ settings.
     */
    public function isCorrectQuality(UserAlert $userAlert, $price)
    {
        if ($userAlert->isTriggerHq() !== $userAlert->isTriggerNq()) {
            if (
                $userAlert->isTriggerHq() && $price->IsHQ == false ||
                $userAlert->isTriggerNq() && $price->IsHQ == true
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Handle the tri condition trigger checking
     */
    public function handleTriCondition($acceptedOptions, $triggerOption, $fieldValue, $triggerValue)
    {
        $pass1 = ($triggerOption === $acceptedOptions[self::CONDITION_GREATER_THAN] && $fieldValue > $triggerValue);
        $pass2 = ($triggerOption === $acceptedOptions[self::CONDITION_LESS_THAN] && $fieldValue < $triggerValue);
        $pass3 = ($triggerOption === $acceptedOptions[self::CONDITION_EQUAL_TO] && $fieldValue == $triggerValue);

        // if any pass, return true else false
        return ($pass1 || $pass2 || $pass3) ?: false;
    }
}
