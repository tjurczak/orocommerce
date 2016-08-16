<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\PricingBundle\Model\PriceRuleChangeTriggerHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractRuleEntityListener
{
    /**
     * @var PriceRuleChangeTriggerHandler
     */
    protected $priceRuleTriggersFiller;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldProvider;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param PriceRuleChangeTriggerHandler $priceRuleTriggersFiller
     * @param PriceRuleFieldsProvider $fieldsProvider
     * @param RegistryInterface $registry
     */
    public function __construct(
        PriceRuleChangeTriggerHandler $priceRuleTriggersFiller,
        PriceRuleFieldsProvider $fieldsProvider,
        RegistryInterface $registry
    ) {
        $this->priceRuleTriggersFiller = $priceRuleTriggersFiller;
        $this->fieldsProvider = $fieldsProvider;
        $this->registry = $registry;
    }

    /**
     * @return string
     */
    abstract protected function getEntityClassName();

    /**
     * @param PriceRuleLexeme[] $lexemes
     * @param Product|null $product
     */
    protected function addTriggersByLexemes(array $lexemes, Product $product = null)
    {
        $priceLists = [];

        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            $priceLists[$priceList->getId()] = $priceList;
        }

        $this->priceRuleTriggersFiller->addTriggersForPriceLists($priceLists, $product);
    }

    /**
     * @param array $updatedFields
     * @param null|int $relationId
     * @return array|\OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme[]
     */
    protected function findEntityLexemes(array $updatedFields = [], $relationId = null)
    {
        $criteria = ['className' => $this->getEntityClassName()];
        if ($updatedFields) {
            $criteria['fieldName'] = $updatedFields;
        }
        if ($relationId) {
            $criteria['relationId'] = $relationId;
        }
        $lexemes = $this->registry->getManagerForClass(PriceRuleLexeme::class)
            ->getRepository(PriceRuleLexeme::class)
            ->findBy($criteria);

        return $lexemes;
    }

    /**
     * @param array $changeSet
     * @param Product $product
     * @param int|null $relationId
     */
    protected function recalculateByEntityFieldsUpdate(array $changeSet, Product $product = null, $relationId = null)
    {
        $fields = $this->getEntityFields();
        $updatedFields = array_intersect($fields, array_keys($changeSet));

        if ($updatedFields) {
            $lexemes = $this->findEntityLexemes($updatedFields, $relationId);
            $this->addTriggersByLexemes($lexemes, $product);
        }
    }

    /**
     * @param Product|null $product
     * @param int|null $relationId
     */
    protected function recalculateByEntity(Product $product = null, $relationId = null)
    {
        $lexemes = $this->findEntityLexemes([], $relationId);
        $this->addTriggersByLexemes($lexemes, $product);
    }

    /**
     * @return array
     */
    protected function getEntityFields()
    {
        return $this->fieldsProvider->getFields($this->getEntityClassName(), false, true);
    }
}
