<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Unilane\SyncProducts\Helper;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Adminhtml Catalog helper
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class ImportC 
{
    private $productFactory;
    private $productRepository;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context     
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;

    }
    /**
     * Set Custom Attribute Tab Block Name for Category Edit
     *
     * @param string $attributeTabBlock
     * @return $this
     */
    public function importProduct()
    {
        
        $data         = file_get_contents("C:\Users\luis.olivarria\Desktop\productsjson\dataPrueba.json");
        $dataCatagory = file_get_contents("C:\Users\luis.olivarria\Desktop\productsjson\arregloCategorias.json");
        if($data){
            try {
                $products  = json_decode($data, true);
                $categorys = json_decode($dataCatagory, true);
                foreach($products as $product){
                    $items = $this->productFactory->create();
                    $sumaExistencia = 0;
                    $pro = $product['existencia'];
                    foreach($pro as $existencia){
                        $sumaExistencia += $existencia;
                    }
                    $items->setAttributeSetId(4);
                    $items->setName($product['nombre']);
                    $items->setSku($product['clave']);
                    $items->setPrice($product['precio']);
                    $items->setVisibility(4);
                    $items->setStatus(1);
                    $items->setTypeId('simple');
                    $items->setTaxClassId(1);
                    $items->setWebsiteIds([1]);
                    $items->setProductPageType('custom');
                    //ICECAT
                    $items->setGtinEan($product['upc']);
                    $items->setBrandName($product['marca']);
                    $items->setProductCode($product['numParte']);                    
                    $items->setCategoryIds([
                        2,24,31,243
                    ]);
                    $items->setStockData(
                        array( 
                        'use_config_manage_stock' => 1,                       
                        'manage_stock' => 1,
                        'is_in_stock' => 1,   
                        'qty' => $sumaExistencia
                        )
                    );
                    $items->save();
                } 
            } catch (Exception $e) {
                // No Ip found in database
                $data = [];
            }           
        }
    }
    
    public function updateStock() {
        $data = file_get_contents("C:\Users\luis.olivarria\Desktop\productsjson\dataPrueba.json");
        try{
            $products  = json_decode($data, true);
            foreach($products as $product){                
                $producto = $this->productRepository->get($product['clave']);
                if($producto){
                    $sumaExistencia = 0;
                $pro = $product['existencia'];
                foreach($pro as $existencia){
                    $sumaExistencia += $existencia;
                }
                //$precio5porciento = $product['precio'] * 0.05;
                $precioIva = $product['precio'] * 1.16;                
                $precioivaxtipocambio = $precioIva * $product['tipoCambio'];
                $precioReal = $precioivaxtipocambio * 1.05;
                $producto->setName($product['nombre']."-".$product['clave']);
                $producto->setPrice($precioReal);
                if($sumaExistencia <= 10){
                    $producto->setStockData(
                        array( 
                        'use_config_manage_stock' => 1,                       
                        'manage_stock' => 1,
                        'is_in_stock' => 0,   
                        'qty' => 0
                        )
                    );
                }
                else{
                    $producto->setStockData(
                        array( 
                        'use_config_manage_stock' => 1,                       
                        'manage_stock' => 1,
                        'is_in_stock' => 1,   
                        'qty' => $sumaExistencia - 10
                        )
                    );
                }                
                if(count($productdata['promociones']) > 0){
                    if($productdata['promociones'][0]['tipo'] == "importe"){
                        $precioPromocion = $productdata['promociones'][0]['promocion'] * $productdata['tipoCambio'];
                        $producto->setSpecialPrice($precioPromocion);
                        $producto->setSpecialFromDate($productdata['promociones'][0]['vigencia']['inicio']);
                        $producto->setSpecialFromDateIsFormated(true);
                        $producto->setSpecialToDate($productdata['promociones'][0]['vigencia']['fin']);
                        $producto->setSpecialToDateIsFormated(true);
                    }
                    else{
                        if(@$productdata['promociones'][0]['tipo'] == "porcentaje"){
                            $porcentaje = $productdata['promociones'][0]['promocion'] / 100;
                            $valor = $precioReal * $porcentaje;
                            $precioPromocion = $precioReal - $valor;
                            $producto->setSpecialPrice($precioPromocion);
                            $producto->setSpecialFromDate($productdata['promociones'][0]['vigencia']['inicio']);
                            $producto->setSpecialFromDateIsFormated(true);
                            $producto->setSpecialToDate($productdata['promociones'][0]['vigencia']['fin']);
                            $producto->setSpecialToDateIsFormated(true);
                        }                            
                    }   
                }                       
                $this->productRepository->save($producto);
                }               
            }
        }catch (Exception $e) {
            $data = [];
        } 
    }

    // public function updateStock(){
    //     $dataCt  = file_get_contents("C:\Users\luis.olivarria\Desktop\productsjson\dataPrueba.json");
    //     $productsData  = json_decode($dataCt, true);
    //     $resource = \Magento\Framework\App\ObjectManager::getInstance()->get(ResourceConnection::class);
    //     $connection = $resource->getConnection();
    //     $csi = $resource->getTableName('cataloginventory_stock_item');
    //     $cpe = $resource->getTableName('catalog_product_entity');
    //     $sql = "SELECT t1.product_id, t1.stock_id, t1.qty, t1.is_in_stock, t2.sku FROM $csi as t1 LEFT JOIN $cpe as t2 ON t1.product_id = t2.entity_id";
    //     $resultsCSI = $connection->fetchAll($sql);
    //     $jsonSKU = ""; 
    //     foreach($productsData as $key => $CT_){
    //         $jsonSKU .= $CT_["clave"]." ";
    //         $skus = explode(" ",$jsonSKU);
    //     }
    //     foreach($resultsCSI as $CSI_){
    //         if(!in_array($CSI_["sku"],$skus)){
    //             $updateStock = "UPDATE $csi SET qty = :qty, is_in_stock = :is_in_stock WHERE product_id = :product_id";
    //             $params = [
    //                 'product_id'  => $CSI_["product_id"],
    //                 'qty'         => 0,
    //                 'is_in_stock' => 0
    //             ];
    //             $results = $connection->fetchAll($updateStock, $params);
    //         }
    //     }        
    // }

    public function specsCt() {
        $data = file_get_contents("C:\Users\luis.olivarria\Desktop\productsjson\dataPrueba.json");
        try{
            $products  = json_decode($data, true);
            foreach($products as $product){
                $producto = $this->productRepository->get($product['clave']);
                if($producto){
                    $producto->setCtSpecs($productdata['especificaciones']);                  
                    $this->productRepository->save($producto);
                }               
            }
        }catch (Exception $e) {
            $data = [];
        } 
    }
}
