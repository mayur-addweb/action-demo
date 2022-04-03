<?php

namespace Algolia\AlgoliaSearch\Model\Observer\CatalogPermissions;

use Algolia\AlgoliaSearch\Factory\CatalogPermissionsFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CategoryPermissions implements ObserverInterface
{
    private $permissionsFactory;
    private $customerGroupCollection;

    public function __construct(
        CustomerGroupCollection $customerGroupCollection,
        CatalogPermissionsFactory $permissionsFactory
    ) {
        $this->customerGroupCollection = $customerGroupCollection;
        $this->permissionsFactory = $permissionsFactory;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\DataObject $transport */
        $transport = $observer->getData('categoryObject');
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $observer->getData('category');
        $storeId = $category->getStoreId();

        if (!$this->permissionsFactory->isCatalogPermissionsEnabled($storeId)) {
            return $this;
        }

        $permissions = [];
        $collection = $this->customerGroupCollection;

        foreach ($collection as $customerGroup) {
            $customerGroupId = $customerGroup->getCustomerGroupId();

            $isVisible = (int) $this->permissionsFactory->getCatalogPermissionsHelper()->isAllowedCategoryView($storeId, $customerGroupId);
            if (!is_null($category->getData('shared_catalog_permission_' . $customerGroupId))) {
                $isVisible = (int) $category->getData('shared_catalog_permission_' . $customerGroupId);
            } elseif (!is_null($category->getData('customer_group_permission_' . $customerGroupId))) {
                $isVisible = (int) $category->getData('customer_group_permission_' . $customerGroupId);
            }

            $permissions['customer_group_' . $customerGroupId] = $isVisible;
        }

        $transport->setData('catalog_permissions', $permissions);

        return $this;
    }
}
