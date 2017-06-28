<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Form\Type\PriceListScheduleType;

class SchedulesIntersectionValidator extends ConstraintValidator
{
    /**
     * @param PriceListSchedule $value The value that should be validated
     * @param Constraint|SchedulesIntersection $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PriceListSchedule) {
            throw new \InvalidArgumentException('Constraint value should be of type ' . PriceListSchedule::class);
        }

        $this->validateSchedules($value, $constraint);
    }

    protected function validateSchedules(PriceListSchedule $validatedSchedule, Constraint $constraint)
    {
        if (null === $validatedSchedule->getPriceList()) {
            return;
        }

        $schedules = $validatedSchedule->getPriceList()->getSchedules();

        foreach ($schedules as $index => $schedule) {
            if ($this->hasIntersection($schedules, $validatedSchedule)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath(PriceListScheduleType::ACTIVE_AT_FIELD)
                    ->addViolation();

                break;
            }
        }
    }

    /**
     * @param PriceListSchedule[] $collection
     * @param PriceListSchedule $schedule
     * @return bool
     */
    protected function hasIntersection($collection, PriceListSchedule $schedule)
    {
        $aLeft = $schedule->getActiveAt();
        $aRight = $schedule->getDeactivateAt();

        foreach ($collection as $item) {
            if ($item === $schedule) {
                continue;
            }

            $bLeft = $item->getActiveAt();
            $bRight = $item->getDeactivateAt();

            if ($this->isSegmentsIntersected($aLeft, $aRight, $bLeft, $bRight)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param \DateTime|null $aLeft
     * @param \DateTime|null $aRight
     * @param \DateTime|null $bLeft
     * @param \DateTime|null $bRight
     * @return bool
     */
    protected function isSegmentsIntersected($aLeft, $aRight, $bLeft, $bRight)
    {
        if (($aRight === null && $bRight === null)
            || (null === $aRight && $bRight >= $aLeft)
            || (null === $bRight && $aRight >= $bLeft)
        ) {
            return true;
        }

        if ($aLeft === null && $bRight === null && $aRight < $bLeft) {
            return false;
        }

        return ((null === $aLeft || $aLeft <= $bRight) && (null === $bRight || $aRight >= $bLeft));
    }

    /**
     * @param mixed $var
     * @return bool
     */
    protected function isIterable($var)
    {
        return is_array($var) || $var instanceof \Traversable;
    }
}
