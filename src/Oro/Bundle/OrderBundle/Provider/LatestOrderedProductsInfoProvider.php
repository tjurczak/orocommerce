<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;

class LatestOrderedProductsInfoProvider
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     ** @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Returns information about who and when bought those products
     * [
     *      product id => [
     *          'customer_id' => customer user who bought,
     *          'created_at'  => order create \DateTime,
     *      ],
     *      ...
     * ]
     *
     * @param array $productIds
     * @param int   $websiteId
     *
     * @return array
     */
    public function getLatestOrderedProductsInfo(array $productIds, $websiteId)
    {
        $orderRepository = $this->getOrderRepository();
        $qb = $orderRepository->getLatestOrderedProductsInfo($productIds, $websiteId);

        return $this->getResultFromQB($qb);
    }

    /**
     * Executes query form query bulder and convert result to special format
     *
     * @param QueryBuilder $qb
     *
     * @return array
     */
    protected function getResultFromQB(QueryBuilder $qb)
    {
        $productIndexOrderInfo = [];
        $queryResult = $qb->getQuery()->getArrayResult();
        foreach ($queryResult as $item) {
            $productIndexOrderInfo[$item['product_id']][] = [
                'customer_id' => $item['customer_id'],
                'created_at'  => new \DateTime($item['created_at'])
            ];
        }

        unset($queryResult);

        return $productIndexOrderInfo;
    }

    /**
     * Returns Order repository from the registry
     *
     * @return OrderRepository
     */
    protected function getOrderRepository()
    {
        return $this->registry
            ->getManagerForClass('OroOrderBundle:Order')
            ->getRepository('OroOrderBundle:Order');
    }
}