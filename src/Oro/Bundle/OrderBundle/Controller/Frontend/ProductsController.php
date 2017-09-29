<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\OrderBundle\Controller\AbstractOrderController;

class ProductsController extends AbstractOrderController
{
    /**
     * @Route("/previously-purchased", name="oro_order_products_frontend_previously_purchased")
     * @AclAncestor("oro_order_frontend_view")
     * @Layout(vars={"entity_class", "grid_name"})
     *
     * @return array
     */
    public function previouslyPurchasedAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_product.entity.product.class'),
            'grid_name' => 'order-products-previously-purchased-grid'
        ];
    }
}