<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Index;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\IceCatUpdateProduct;
use Icecat\DataFeed\Service\IcecatApiService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Bootstrap;
//use GDW\Core\Helper\Data as Gdw;
class ProductData extends Action
{
    private Data $data;
    private IcecatApiService $icecatApiService;
    private ProductRepository $productRepository;
    private IceCatUpdateProduct $iceCatUpdateProduct;
    private StoreRepositoryInterface $storeRepository;
    private StoreManagerInterface $storeManager;
    private Processor $processor;

    /**
     * @var string
     * */
    private $galleryEntitytable;

    /**
     * @var string
     * */
    private $galleryTable;

    /**
     * @var string
     * */
    private $videoTable;

    /**
     * @var AdapterInterface
     * */
    private $db;

    private $columnExists;

    /**
     * @var Config|ConfigInterface
     */
    private $config;

    public $gdw;

    /**
     * @param Context $context
     * @param Data $data
     * @param IcecatApiService $icecatApiService
     * @param ProductRepository $productRepository
     * @param IceCatUpdateProduct $iceCatUpdateProduct
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param Processor $processor
     * @param ResourceConnection $resourceConnection
     * @param ObjectManagerInterface $objectManager
     * @param ConfigInterface $config
     */
    public function __construct(
        Context                  $context,
        Data                     $data,
        IcecatApiService         $icecatApiService,
        ProductRepository        $productRepository,
        IceCatUpdateProduct      $iceCatUpdateProduct,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface    $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ConfigInterface $config,
        Processor $processor,
        ResourceConnection $resourceConnection,
//	Gdw $gdw,
        ObjectManagerInterface $objectManager

    ) {
        parent::__construct($context);
        $this->data = $data;
        $this->icecatApiService = $icecatApiService;
        $this->productRepository = $productRepository;
        $this->iceCatUpdateProduct = $iceCatUpdateProduct;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->processor = $processor;
        $this->galleryEntitytable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $this->galleryTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $this->videoTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value_video');
        $this->db = $objectManager->create(ResourceConnection::class)->getConnection('core_write');

        $this->columnExists = $resourceConnection->getConnection()->tableColumnExists('catalog_product_entity_media_gallery_value', 'entity_id');
	//$this->gdw = $gdw;
    }

    public function execute()
    {
        // Ajusta la ruta a tu instalación de Magento
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        // Cargar el estado del objeto
        $state = $objectManager->get('Magento\Framework\App\State');
        $state->setAreaCode('frontend');
        // Obtener la colección de productos
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        // Opcional: Aplicar filtros o condiciones a la colección si es necesario
        // Por ejemplo, para obtener solo productos habilitados:
        $productCollection->addFieldToSelect('entity_id');
	$productCollection->addFieldToSelect('icecat_run');
        // Cargar todas las atributos del producto
        //$productCollection->addAttributeToSelect('icecat_run');

        $response = $this->data->getUserSessionId();
        $configurationSelectedStores = explode(",", $this->_scopeConfig->getValue('datafeed/icecat_store_config/stores', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $configWebsiteId = [];
        foreach ($configurationSelectedStores as $configurationSelectedStore) {
            $configWebsiteId[] = (int)$this->storeManager->getStore($configurationSelectedStore)->getWebsiteId();
        }
        $confidWebsiteIds = array_unique($configWebsiteId);

                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/productData.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info("Inicio");
        // Iterar a través de la colección de productos
        foreach ($productCollection as $product) {
            $logger->info($product->getId()." Id del producto");
            //Obtener toda la información del producto
            $productData = $product->getData();
            $productId = $product->getId();
            try {
                if(!empty($response) && array_key_exists("Code",$response) ) {
                    $result = ['success'=>0,'message'=>$response['Message']];
                    $this->getResponse()->setBody(json_encode($result));
                } else {
                $logger->info("1");

                    $icecatStores = $this->data->getIcecatStoreConfig();
                    $storeArray = explode(',', $icecatStores);
                    $storeArrayForImage = explode(',', $icecatStores);
                    $storeArrayForImage[] = 0; // Admin store
                    $globalMediaArray =[];
                    $updatedStore = [];
                    $errorMessage = null;
                    $globalImageArray = [];
                    $globalVideoArray = [];
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
                    $productWebsiteIds = $product->getWebsiteIds();
                    $storeDifferencess = array_diff($confidWebsiteIds, $productWebsiteIds);
                    // Check for icecat root category from all root categories, create it if not there
                    $rootCats = [];
                    if ($this->data->isCategoryImportEnabled()) {
                $logger->info("2");

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $collection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory')->create();
                        $collection->addAttributeToFilter('level', ['eq' => 1]);
                        foreach ($collection as $coll) {
                            $rootCatId = $coll->getId();
                            $rootCat = $objectManager->get('Magento\Catalog\Model\Category');
                            $rootCatData = $rootCat->load($rootCatId);
                            $rootCats[] = strtolower($rootCatData->getName());
                        }
                        $myRoot=strtolower('Icecat Categories');
                        if (!in_array($myRoot, $rootCats)) {

                $logger->info("3");
                            $store = $this->storeManager->getStore();
                            $storeId = $store->getStoreId();
                            $rootNodeId = 1;
                            $rootCat = $objectManager->get('Magento\Catalog\Model\Category');
                            $cat_info = $rootCat->load($rootNodeId);
                            $myRoot='Icecat Categories';
                            $name=ucfirst($myRoot);
                            $url=strtolower($myRoot);
                            $cleanurl = trim(preg_replace('/ +/', '', preg_replace('/[^A-Za-z0-9 ]/', '', urldecode(html_entity_decode(strip_tags($url))))));
                            $categoryFactory=$objectManager->get('\Magento\Catalog\Model\CategoryFactory');
                            $categoryTmp = $categoryFactory->create();
                            $categoryTmp->setName($name);
                            $categoryTmp->setIsActive(true);
                            $categoryTmp->setIncludeInMenu(false);
                            $categoryTmp->setUrlKey($cleanurl);
                            $categoryTmp->setData('description', 'description');
                            $categoryTmp->setParentId($rootCat->getId());
                            $categoryTmp->setStoreId($storeId);
                            $categoryTmp->setPath($rootCat->getPath());
                            $savedCategory = $categoryTmp->save();
                            $icecatCid = $savedCategory->getId();
                            $this->config->saveConfig('datafeed/icecat/root_category_id', $icecatCid, 'default', 0);
                        } else {
                $logger->info("4");
                            $categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
                            $collection = $categoryFactory->create()->getCollection()->addAttributeToFilter('name', "Icecat Categories")->setPageSize(1);
                            $icecatCid = $collection->getFirstItem()->getId();
                            $icecatRootCategoryExist = $this->_scopeConfig->getValue('datafeed/icecat/root_category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                            if(empty($icecatRootCategoryExist))
                            {
                                $this->config->saveConfig('datafeed/icecat/root_category_id', $icecatCid, 'default', 0);
                            }
                        }
    
                        $allstores = $this->storeRepository->getList();
                        foreach ($allstores as $eachstore) {
                            if ($eachstore->getCode() == 'admin') {
                                continue;
                            }
                            $allstoreArr[] = $eachstore->getId();
                        }
                        if (empty($storeDifferencess)) {
                $logger->info("5");
                            foreach ($configurationSelectedStores as $eachstore) {
                                $storeData = $this->storeRepository->getById($eachstore);
                                $storeManager = $objectManager->get(StoreManagerInterface::class);
                                $storeGroup = $objectManager->get(GroupInterfaceFactory::class)->create()->load($storeData->getData('group_id'));
                                if (in_array($eachstore, $storeArray)) {
                                    $storeGroup->setRootCategoryId($icecatCid);
                                } else {
                                    $storeGroup->setRootCategoryId(2);
                                }
                                $objectManager->create(GroupResource::class)->save($storeGroup);
                            }
                        } else {
                $logger->info("6");
                            $logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
                            $logger->info("ProductID :".$productId." does not exist in the website's: ". json_encode($storeDifferencess));
                        }
    
                    }
    
                    // foreach ($storeArray as $store) {
                        $productIcecat = $this->productRepository->getById($productId, false, 1);
                        $language = $this->data->getStoreLanguage(1);
                        $icecatUri = $this->data->getIcecatUri($product, $language);
                        $response = $this->icecatApiService->execute($icecatUri);
                        if($productIcecat->getIcecatRun() == "0"){
            		  $logger->info("Pasa la variable de icecat_run");
                            if ($icecatUri) {
                                if (!empty($response) && !empty($response['Code'])) {
                                    $errorMessage = $response['Message'];                            
                                } else {                                
                                    $globalMediaArray = $this->iceCatUpdateProduct->updateProductWithIceCatResponse($productIcecat, $response, 1, $globalMediaArray);
                                    $globalImageArray = array_key_exists('image', $globalMediaArray)?$globalMediaArray['image']:[];
                                    $globalVideoArray = array_key_exists('video', $globalMediaArray)?$globalMediaArray['video']:[];
                                    $storeData = $this->storeRepository->getById(1);
                                    $updatedStore[] = $storeData->getName();
                		    $logger->info($product->getSku()." Sku del producto actualizado");

                                }
                            } else {
                                $this->messageManager->addErrorMessage('There is no matching criteria - GTIN or Brand Name & Product Code values are empty.');
                                $result = ['success'=>0,'message'=>'There is no matching criteria - GTIN or Brand Name & Product Code values are empty.'];
                                die;
                            }
    
                            // Hide images from non-required stores
                            if ($this->columnExists === false) {
                                $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id where A.row_id=" . $productId . " and B.media_type='image'";
                            } else {
                                $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id where A.entity_id=" . $productId . " and B.media_type='image'";
                            }
                            $data = $this->db->query($query)->fetchAll();
                            foreach ($globalImageArray as $key => $imageArray) {
                                foreach ($imageArray as $image) {
                                    $imageData = explode('.', $image);
                                    $imageName = $imageData[0];
                                    foreach ($data as $k => $value) {
                                        if ($key != $value['store_id']) {
                                            if (strpos($value['value'], $imageName) !== false) {
                                                $updateQuery = "UPDATE " . $this->galleryEntitytable . " SET disabled=1 WHERE value_id=" . $value['value_id'] . " AND store_id=" . $value['store_id'];
                                                $this->db->query($updateQuery);
                                            }
                                        }
                                    }
                                }
                            }
    
                            // Hide video from non-required stores
                            if (!empty($globalVideoArray)) {                                    
                                if ($this->columnExists === false) {
                                    $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id
                                    left join " . $this->videoTable . "  C on C.value_id = A.value_id
                                    where A.row_id=" . $productId . " and B.media_type='external-video'";
                                } else {
                                    $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id
                                    left join " . $this->videoTable . "  C on C.value_id = A.value_id
                                    where A.entity_id=" . $productId . " and B.media_type='external-video'";
                                }
                                $videoData = $this->db->query($query)->fetchAll();
                                foreach ($globalVideoArray as $key => $videoArray) {
                                    foreach ($videoArray as $video) {
                                        $videoUrl = $video;
                                        foreach ($videoData as $k => $value) {
                                            if ((int)$value['metadata'] != (int)$value['store_id']) {
                                                if ($value['url'] == $videoUrl) {
                                                    $updateQuery = "UPDATE " . $this->galleryEntitytable . " SET disabled=1 WHERE value_id=" . $value['value_id'] . " AND store_id =" . $value['store_id'];
                                                    $this->db->query($updateQuery);
                                                }
                                            }
                                        }
                                    }
                                }
                            }                        
    
                            if (count($updatedStore) > 0) {
                                $result = ['success'=>1,'message'=>'Product updated successfully'];
                            } elseif (!empty($errorMessage)) {
                                $result = ['success'=>0,'message'=>$errorMessage];
                            }
                            $this->getResponse()->setBody(json_encode($result));
                        }
                        else{
                            if(count($response["data"]["Multimedia"]) > 0){
                                foreach($response["data"]["Multimedia"] as $multimedia){
                                    if(strpos($multimedia['URL'], 'youtube')){
                                        $this->iceCatUpdateProduct->updateProductHasVideo($product, $response, 1, $globalMediaArray);
                                    } 
                                }
                            }
                            $result = ['success'=>1,'message'=>'El producto ya ejecuto IceCat'];
                            $this->getResponse()->setBody(json_encode($result));                        
                        }                    
                    //}
                } 
            } catch (NoSuchEntityException $noSuchEntityException) {
            }            
        }
        $logger->info("fin");

    }
}
