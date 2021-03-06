<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DataProvider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductShoppingListsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ShoppingListManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingListManager;

    /** @var LineItemRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItemRepository;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var ProductShoppingListsDataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->shoppingListManager = $this
            ->getMockBuilder(ShoppingListManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemRepository = $this
            ->getMockBuilder(LineItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder(AclHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ProductShoppingListsDataProvider(
            $this->shoppingListManager,
            $this->lineItemRepository,
            $this->aclHelper
        );
    }

    protected function tearDown()
    {
        unset(
            $this->provider,
            $this->shoppingListManager,
            $this->lineItemRepository
        );
    }

    /**
     * @dataProvider getProductUnitsQuantityDataProvider
     *
     * @param Product|null $product
     * @param ShoppingList|null $shoppingList
     * @param array $lineItems
     * @param array|null $expected
     */
    public function testGetProductUnitsQuantity(
        $product,
        $shoppingList,
        array $lineItems = [],
        array $expected = null
    ) {
        $this->shoppingListManager
            ->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getProductItemsWithShoppingListNames')
            ->with($this->aclHelper, [$product])
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getProductUnitsQuantity($product));
    }

    /**
     * @return array
     */
    public function getProductUnitsQuantityDataProvider()
    {
        /** @var  ShoppingList $activeShoppingList */
        $activeShoppingList = $this->createShoppingList(1, 'ShoppingList 1', true);
        /** @var  ShoppingList $activeShoppingListSecond */
        $activeShoppingListSecond = $this->createShoppingList(1, 'ShoppingList 2', true);
        /** @var  ShoppingList $otherShoppingList */
        $otherShoppingList = $this->createShoppingList(2, 'ShoppingList 3', false);
        return [
            'no_product_no_shopping_list' => [
                'product' => null,
                'shoppingList' => null,
            ],
            'no_shopping_list' => [
                'product' => new Product(),
                'shoppingList' => null
            ],
            'no_prices' => [
                'product' => new Product(),
                'shoppingList' => $otherShoppingList,
                'lineItems' => []
            ],
            'single_shopping_list' => [
                'product' => new Product(),
                'shoppingList' => $activeShoppingList,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingList),
                    $this->createLineItem(2, 'code2', 100, $activeShoppingList)
                ],
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['id' => 2, 'unit' => 'code2', 'quantity' => 100],
                        ]
                    ]
                ]
            ],
            'a_few_shopping_lists' => [
                'product' => new Product(),
                'shoppingList' => $activeShoppingListSecond,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingListSecond),
                    $this->createLineItem(2, 'code2', 100, $activeShoppingListSecond),
                    $this->createLineItem(3, 'code3', 30, $otherShoppingList),
                ],
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 2',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['id' => 2,'unit' => 'code2', 'quantity' => 100],
                        ]
                    ],
                    [
                        'id' => 2,
                        'label' => 'ShoppingList 3',
                        'is_current' => false,
                        'line_items' => [
                            ['id' => 3, 'unit' => 'code3', 'quantity' => 30],
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @param int $id
     * @param string $unit
     * @param int $quantity
     * @param ShoppingList $shoppingList
     * @return LineItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createLineItem($id, $unit, $quantity, $shoppingList)
    {
        $lineItem = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\LineItem')
            ->setMethods(['getId', 'getUnit', 'getQuantity', 'getShoppingList', 'getProduct'])
            ->getMock();
        $lineItem ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $lineItem ->expects($this->any())
            ->method('getUnit')
            ->will($this->returnValue((new ProductUnit())->setCode($unit)));
        $lineItem ->expects($this->any())
            ->method('getQuantity')
            ->will($this->returnValue($quantity));
        $lineItem ->expects($this->any())
            ->method('getShoppingList')
            ->will($this->returnValue($shoppingList));
        $lineItem ->expects($this->any())
            ->method('getProduct')
            ->willReturn(new Product());

        return $lineItem;
    }

    /**
     * @param int $id
     * @param string $label
     * @param boolean $isCurrent
     * @return ShoppingList
     */
    protected function createShoppingList($id, $label, $isCurrent)
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => $id, 'customerUser' => new CustomerUser()]);

        $shoppingList
            ->setLabel($label)
            ->setCurrent($isCurrent);


        return $shoppingList;
    }

    public function testGetProductUnitsQuantityWithoutCustomerInShoppingList()
    {
        $shoppingList = new ShoppingList();

        $this->shoppingListManager
            ->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($this->never())
            ->method('getProductItemsWithShoppingListNames');


        $this->assertEquals(null, $this->provider->getProductUnitsQuantity(new Product()));
    }
}
