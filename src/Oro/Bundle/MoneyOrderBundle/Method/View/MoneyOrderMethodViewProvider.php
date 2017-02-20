<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

class MoneyOrderMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /**
     * @var  PaymentMethodViewInterface[]
     */
    protected $methodViews;

    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    /**
     * @param MoneyOrderConfigInterface $config
     */
    public function __construct(MoneyOrderConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodViews(array $identifiers)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        $matchedViews = [];
        foreach ($identifiers as $paymentMethod) {
            if ($this->hasPaymentMethodView($paymentMethod)) {
                $matchedViews[$paymentMethod] = $this->getPaymentMethodView($paymentMethod);
            }
        }
        return $matchedViews;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        if ($this->hasPaymentMethodView($identifier)) {
            return $this->methodViews[$identifier];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return array_key_exists($identifier, $this->methodViews);
    }
    
    protected function collectPaymentMethodViews()
    {
        $methodView = new MoneyOrderView($this->config);
        $this->methodViews = [MoneyOrder::TYPE => $methodView];
    }
}
