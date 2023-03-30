<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Store\Model\StoreResolver;

/**
 * Reader implementation for website.
 */
class Website implements ReaderInterface
{
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \Magento\Store\Api\GroupRepositoryInterface $groupRepository
     */
    public function __construct(
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepository
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->groupRepository = $groupRepository;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedStoreIds($scopeCode)
    {
        $stores = [];
        //echo "DB2MOD PASO AQUI 5 " . PHP_EOL;
        //var_dump($scopeCode); //null
        $website = $scopeCode ? $this->websiteRepository->get($scopeCode) : $this->websiteRepository->getDefault();
        foreach ($this->storeRepository->getList() as $store) {
            if ($store->getIsActive()) {
                if (($scopeCode && $store->getWebsiteId() == $website->getId()) || (!$scopeCode)) {
                    $stores[$store->getId()] = $store->getId();
                }
            }
        }
        sort($stores);
        return $stores;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultStoreId($scopeCode)
    {
        //echo "DB2MOD PASO AQUI 6";
        //22/02/2023 Se inentara solucion desde este punto
        $website = $scopeCode ? $this->websiteRepository->get($scopeCode) : $this->websiteRepository->getDefault();
        //EL RETURN ES LA LINEA DEL ERROR getDefaultGroupId
        //var_dump(get_class($website));
        //var_dump($website->getDefaultGroupId());
        //22/02/2023 se encuentra error con interceptor metodo getDefaultGroupId
        //retornaba null por buscar campo default_group_id != DEFAULT_GROUP_ID
        //otro error de mayusculas de DB2MOD
        return $this->groupRepository->get($website->getDefaultGroupId())->getDefaultStoreId();
    }
}
