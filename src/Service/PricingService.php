<?php

namespace App\Service;

use App\Entity\Space;
use App\Enum\Periodicity;
use App\Repository\PricingRuleRepository;

class PricingService
{
    public function __construct(
        private PricingRuleRepository $pricingRuleRepository
    ) {
    }

    public function calculatePrice(Space $space, Periodicity $periodicity, int $duration): ?string
    {
        $pricingRule = $this->pricingRuleRepository->findBySpaceAndPeriodicity($space, $periodicity);
        
        if (!$pricingRule) {
            return null;
        }

        // Validate duration against min/max
        if ($duration < $pricingRule->getMinDuration()) {
            throw new \InvalidArgumentException(
                sprintf('Duration must be at least %d', $pricingRule->getMinDuration())
            );
        }

        if ($pricingRule->getMaxDuration() && $duration > $pricingRule->getMaxDuration()) {
            throw new \InvalidArgumentException(
                sprintf('Duration cannot exceed %d', $pricingRule->getMaxDuration())
            );
        }

        // Calculate total price
        $unitPrice = (float) $pricingRule->getPrice();
        $totalPrice = $unitPrice * $duration;

        return number_format($totalPrice, 2, '.', '');
    }

    public function getAvailablePeriodicitiesForSpace(Space $space): array
    {
        $rules = $this->pricingRuleRepository->findActiveBySpace($space);
        
        $periodicities = [];
        foreach ($rules as $rule) {
            $periodicities[] = [
                'periodicity' => $rule->getPeriodicity(),
                'price' => $rule->getPrice(),
                'currency' => $rule->getCurrency()?->getCode() ?? 'CDF',
                'min_duration' => $rule->getMinDuration(),
                'max_duration' => $rule->getMaxDuration(),
            ];
        }

        return $periodicities;
    }
}
