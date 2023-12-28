<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Unilane\SyncProducts\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Bootstrap;
/**
 * @api
 */
class Import extends \Magento\Config\Block\System\Config\Form\Field
{
    private $productFactory;
    private $productRepository;
    private File $file;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        ProductRepositoryInterface            $productRepository,
        File                                  $file     
    )
    {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->file = $file;
    }
    /**
     * @inheritdoc
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {       
        $connect = $this->connectCT();
        if($connect){
            $this->importProducts();
        }                
    }    
    public function importProducts(){  
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/importProductData.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("Inicio de la importacion de productos");
        $logger->info("");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $mediaDir = $objectManager->get('Magento\Framework\App\Filesystem\DirectoryList')->getPath('media');
        //CT
        $dataCt  = file_get_contents("/home/master/json/product.json");
        $productsData  = json_decode($dataCt, true);
        $data = [];
        foreach($productsData as $key => $productdata){
            $producto = $this->productRepository->get($productdata['clave']);
            if($producto == null){                
                array_push($data, $productdata);
            }        
        }        
        if($data){
            try {
                //$products  = json_decode($data, true);
                foreach($data as $product){
                    $items = $this->productFactory->create();
                    $sumaExistencia = 0;
                    $pro = $product['existencia'];
                    foreach($pro as $existencia){
                        $sumaExistencia += $existencia;
                    }
                    //$precio5porciento = $product['precio'] * 0.05;
                    //$precioReal = $product['precio'] * $product['tipoCambio'];
                    $precioIva = $product['precio'] * 1.16;                
                    $precioivaxtipocambio = $precioIva * $product['tipoCambio'];
                    $precioReal = $precioivaxtipocambio * 1.05;
                    $nombreCategoria = $product['subcategoria'];
                    if($nombreCategoria == "Cables USB" ){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,245
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }                  

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                           
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores de Energía" || $nombreCategoria == "Inversores de Energia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,243
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );   
                        if(count($product['promociones']) > 0){
                            if(@$product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                $porsentaje = $product['promociones'][0]['promocion'] / 100;
                                $valor = $precioReal * $porsentaje;
                                $precioPromo = $precioReal - $valor;
                                $precioPromocion = $precioPromo * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                        }                            

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Reemplazos"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,242
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }                   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Bancos de Batería"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,241
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );    
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }               

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Convertidores AV" || $nombreCategoria == "Transformadores" ){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,240,36,103
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );      
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }             

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Supresores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,261,239
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );       
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }            

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Regletas y Multicontactos"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,261,238
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Estaciones de Carga"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,237
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );      
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }             

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }  
                    if($nombreCategoria == "Reguladores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,236
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "No Breaks y UPS"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,235
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );       
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }            

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    } 
                    if($nombreCategoria == "Baterías"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,234
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Barra de Contactos"){                    
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,67,233
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Tarjetas de Acceso"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,223
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Camaras Deteccion"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,218
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para seguridad"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,217
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Soportes para Video Vigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,260,215
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );       
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }            

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Sirenas para Video Vigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,260,214
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Monitores para Video Vigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,260,213
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Kits de Video Vigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,212
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );      
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }             

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Grabadoras Digitales" || $nombreCategoria == "Grabadores analógicos" || $nombreCategoria == "Kit Analógicos HD" || $nombreCategoria == "Videovigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,211
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Fuentes de Poder para Video Vigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,262,210
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cámara bala análogica" || $nombreCategoria == "Cámaras" || $nombreCategoria == "Cámaras de Video Vigilancia" || $nombreCategoria == "Cámaras domo analógicas" || $nombreCategoria == "Cámaras PTZ analógicas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,209,56,166,40,125
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );      
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }             

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cables y conectores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,63,260,208
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Inyectores PoE"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,206
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Antenas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,205
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para Racks" || $nombreCategoria == "Racks Modulo"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,204
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Networking" || $nombreCategoria == "PDU" || $nombreCategoria == "Switches"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,203
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Amplificadores Wifi" || $nombreCategoria == "Extensores de Red" || $nombreCategoria == "Hub y Concentadores Wifi" || $nombreCategoria == "Seguridad Inteligente"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,202
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Access Points" || $nombreCategoria == "Routers"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,201
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para Cables" || $nombreCategoria == "Bobinas" || $nombreCategoria == "Fibras Ópticas" || $nombreCategoria == "Herramientas" || $nombreCategoria == "Herramientas para red" || $nombreCategoria == "Jacks"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,199
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Accesorios de Redes" || $nombreCategoria == "Convertidor de medios" || $nombreCategoria == "Transceptores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,62,198
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Consumibles POS" || $nombreCategoria == "Etiquetas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,196
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Bases" || $nombreCategoria == "Baterías POS" || $nombreCategoria == "Cables POS"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,195
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Digitalizadores de Firmas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,260,194
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Terminales POS"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,260,193
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Monitores POS"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,260,192
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Lectores de Códigos de Barras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,191
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Impresoras POS"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,190
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cajones de Dinero"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,189
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Kit Punto de Venta"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,61,260,188
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Pcs de Escritorio Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,187
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Monitores Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,186
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Laptops Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,185
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Tarjetas de Video Gaming" || $nombreCategoria == "Tarjetas de Video"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,184
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Controles" || $nombreCategoria == "Consolas y Video Juegos" || $nombreCategoria == "Controles Gaming" || $nombreCategoria == "Pilas" || $nombreCategoria == "Soporte para Control"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,183
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Escritorio Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,182
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Sillas Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,181
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Motherboards Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,180
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Gabinetes Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,179
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Fuentes de Poder Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,60,178
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Kits de Teclado y Mouse Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,177,32,77
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Teclados Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,176
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Mouse Gaming" || $nombreCategoria == "Mouse Pads Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,175
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Diademas Gaming"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,59,174
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Hidrolavadoras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,58,172
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                  
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Sensores" || $nombreCategoria == "Sensores para Vídeo Vigilancia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,169
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Paneles para Alarma"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,168
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores USB"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,106
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para PCs" || $nombreCategoria == "Kits para Teclado y Mouse"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,32,78,70
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");
                        
                    }
                    if($nombreCategoria == "Acceso" || $nombreCategoria == "Seguridad Inteligente"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,167
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cámara Inteligentes" || $nombreCategoria == "Cámaras Inteligentes"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,56,165
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cerraduras" || $nombreCategoria == "Timbres"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,57,225
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Sensores Wifi"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,56,163
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Contactos Inteligentes Wifi" || $nombreCategoria == "Control Inteligente" || $nombreCategoria == "Iluminación" || $nombreCategoria == "Interruptores Wifi"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,56,161,56,160
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Control de Acceso"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,64,220,55,159
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Checadores" || $nombreCategoria == "Lector de Huella" || $nombreCategoria == "Reconocimiento Facial" || $nombreCategoria == "Tiempo y Asistencia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,64,224,55,158
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Equipo" || $nombreCategoria == "Salud" || $nombreCategoria == "Termómetros"){
                        $items->setAttributeSetId(4);
                            $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                            $items->setSku($product['clave']);
                            $items->setPrice($precioReal);
                            $items->setVisibility(4);
                            $items->setStatus(1);
                            $items->setTypeId('simple');
                            $items->setTaxClassId(2);
                            $items->setWebsiteIds([1]);
                            //ICECAT
                            $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                            $items->setBrandName($product['marca']);
                            $items->setProductCode($product['numParte']);                    
                            $items->setCategoryIds([
                                2,54,156
                            ]);
                            $items->setStockData(
                                array( 
                                'use_config_manage_stock' => 1,                       
                                'manage_stock' => 1,
                                'is_in_stock' => 1,   
                                'qty' => $sumaExistencia
                                )
                            );  
                            if(count($product['promociones']) > 0){
                                if(@$product['promociones'][0]['tipo'] == "importe"){
                                    $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }
                                else{
                                    $porsentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porsentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }         
                            }

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Desinfectantes"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,54,155
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Caretas" || $nombreCategoria == "Cubrebocas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,54,154
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );               
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }    

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Aspiradoras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,58,171,53,153
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );               
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }    

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Microondas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,58,173,53,152
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Aires Acondicionados"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,58,170,53,151
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Pantallas Profesionales"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,53,150
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Video Conferencia"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,53,149
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Análogos" || $nombreCategoria == "Central Telefónica" || $nombreCategoria == "Sistemas Análogos" || $nombreCategoria == "Telefonía para empresas" || $nombreCategoria == "Teléfonos Analógicos" || $nombreCategoria == "Teléfonos Digitales" || $nombreCategoria == "Teléfonos IP" || $nombreCategoria == "Teléfonos para Hogar" || $nombreCategoria == "Teléfonos SIP"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,53,148
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Escritorio de Oficina"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,53,147
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Ergonomia" || $nombreCategoria == "Sillas de Oficina"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,53,146
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Almacenamiento Óptico" || $nombreCategoria == "Contabilidad" || $nombreCategoria == "Quemadores DVD y BluRay"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,52,145
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );               
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }    

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios de Papeleria" || $nombreCategoria == "Articulos de Escritura" || $nombreCategoria == "Basico de Papeleria" || $nombreCategoria == "Cuadernos" || $nombreCategoria == "Papelería" || $nombreCategoria == "Plumas Interactivas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,52,142
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Mantenimiento"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,44,142
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Refacciones"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,44,141
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cabezales"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,44,140
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para impresoras" || $nombreCategoria == "Gabinetes para Impresoras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,44,139
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cintas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,43,144
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Papel"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,52,144,43,137
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Tóners"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,43,136
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cartuchos"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,43,135
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Plotters"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,42,134
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Rotuladores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,42,133
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Escaner"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,42,132
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Multifuncionales"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,42,131
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Impresoras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,42,130
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Soporte para TV" || $nombreCategoria == "Soporte Videowall" || $nombreCategoria == "Soportes"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,41,129
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Soporte para Proyector"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,41,128
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Limpieza"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,54,157,41,127
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );       
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }            

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Controles"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,41,126
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para Camaras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,40,125
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );          
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }         

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Lentes"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,40,124
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Micrófonos"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,39,122
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Home Theaters"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,39,121
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                 
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }  

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Bocina Portatil" || $nombreCategoria == "Bocinas" || $nombreCategoria == "Bocinas Gaming" || $nombreCategoria == "Bocinas y Bocinas Portátiles" || $nombreCategoria == "Home Theaters"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,56,162,39,120
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                 
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }  

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Base Diademas" || $nombreCategoria == "Diademas" || $nombreCategoria == "Diademas y Audífonos"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,39,255,119
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );               
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }    

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria =="Audífonos" || $nombreCategoria == "Audífonos para Apple" || $nombreCategoria == "Auriculares" || $nombreCategoria == "Earbuds" || $nombreCategoria == "In Ears" || $nombreCategoria == "On Ear" || $nombreCategoria == "on-ear" || $nombreCategoria == "Perifericos Apple" || $nombreCategoria == "Reproductores MP3"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,39,255,118
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                  
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        } 

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Patinetas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,117
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Streaming" || $nombreCategoria == "Televisiones"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,116
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Pantallas de Proyección" || $nombreCategoria == "Proyectores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,115
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Power banks"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,114
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Smartwatch"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,113
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cables Lightning" || $nombreCategoria == "Cargadores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,112
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios de Telefonía" || $nombreCategoria == "Accesorios para Celulares" || $nombreCategoria == "Bases" || $nombreCategoria == "Celulares" || $nombreCategoria == "Equipo para Celulares" || $nombreCategoria == "Transmisores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,38,111
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Gabinetes para Discos Duros"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,37,110
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );               
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }    

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Memorias Flash" || $nombreCategoria == "Memorias USB"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,37,109
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores para Disco Duro" || $nombreCategoria == "Almacenamiento Externo" || $nombreCategoria == "Discos Duros" || $nombreCategoria == "Discos Duros Externos" || $nombreCategoria == "SSD"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,37,108
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    //CABLES

                    if($nombreCategoria == "Cables de Alimentación"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,99
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables de Audio"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,100
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables de Video"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,101
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables Serial"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,102
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cables de Red"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,199
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Cables Coaxial"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,271
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables Displayport"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,272
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables DVI"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,273
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables HDMI"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,274
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables KVM"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,275
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "Cables VGA"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,276
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                  
                    if($nombreCategoria == "Cables de Energía"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,252,263
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    //FIN CABLES

                    //ADAPTADORES
                    if($nombreCategoria == "Adaptadores para Video"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,104
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores USB"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,106
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores para Disco Duro"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,107
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores HDMI"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,264
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores de Ethernet"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,265
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores Displayport"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,266
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores para Apple"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,267
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores para Red"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,198
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores para Audio"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,268
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores USB para Video"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,269
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Adaptadores USB Red"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,36,254,270
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    //FIN ADAPTADORES

                    if($nombreCategoria == "Fundas y Maletines" || $nombreCategoria == "Mochila Gaming" || $nombreCategoria == "Mochilas y Maletines"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,249,251,40,123
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Fundas y Maletines" || $nombreCategoria == "Mochila Gaming" || $nombreCategoria == "Mochilas y Maletines"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,40,123,35,96
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Fundas Laptops" || $nombreCategoria == "Fundas para Tablets" || $nombreCategoria == "Protectores para Tablets"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,249,250
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Filtro de Privacidad"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,94
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Concentradores Hub" || $nombreCategoria == "Docking Station"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,93
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Candados Laptops"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,92
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );              
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }     

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Bases Enfriadoras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,91
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );   
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }                

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Accesorios para Laptops" || $nombreCategoria == "Adaptadores para Laptops" || $nombreCategoria == "Bases" || $nombreCategoria == "Baterias Laptops" || $nombreCategoria == "Pantallas Laptops" || $nombreCategoria == "Teclados Laptops"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,90
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Tarjetas de Sonido" || $nombreCategoria == "Tarjetas Paralelas" || $nombreCategoria == "Tarjetas Seriales"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,89
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );   
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }                

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Motherboards"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,88
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );     
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }              

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Microprocesadores"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,87
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Memorias RAM"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,86
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );        
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }           

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Lectores de Memorias"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,85
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Gabinetes para Computadoras"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,84
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Fuentes de Poder"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,83
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );               
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }    

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Enfriamiento y Ventilación"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,34,82
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );           
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }        

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Webcams"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,33,81
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );         
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }          

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Bases" || $nombreCategoria == "Soporte de Monitor" || $nombreCategoria == "Soporte Laptops" || $nombreCategoria == "Soportes para PCs"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,35,97,33,80
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Monitores" || $nombreCategoria == "Monitores Curvos"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,33,79
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Teclados"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,32,77
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );             
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }      

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Mouse" || $nombreCategoria == "Mouse Pads"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,32,76
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        ); 
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "iPad" ||$nombreCategoria == "Soporte para Tablets" || $nombreCategoria == "Tabletas"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,31,75
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );            
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }       

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Workstations de Escritorio" || $nombreCategoria == "Workstations Gaming" || $nombreCategoria == "Workstations Móviles"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,31,74
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                   
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Mini PC"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,31,73
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        }   

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }

                    if($nombreCategoria == "PCs de Escritorio"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,31,70
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );                  
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        } 

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "Laptops" || $nombreCategoria == "MacBook"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }                        
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,31,69
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );
                        if(count($product['promociones']) > 0){
                            if($product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }   
                        } 
                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }
                    if($nombreCategoria == "All In One" || $nombreCategoria == "iMac"){
                        $items->setAttributeSetId(4);
                        $items->setName(ucfirst(strtolower($product['nombre']."-".$product['clave'])));
                        $items->setSku($product['clave']);
                        $items->setPrice($precioReal);
                        $items->setVisibility(4);
                        $items->setStatus(1);
                        $items->setTypeId('simple');
                        $items->setTaxClassId(2);
                        $items->setWebsiteIds([1]);

                        if($items->getShortDescription() == null){
                            $items->setShortDescription($product["descripcion_corta"]);
                        }
                        //ICECAT
                        $items->setGtinEan($product['upc'] == "" || null ? $product['ean'] : $product['upc']);
                        $items->setBrandName($product['marca']);
                        $items->setProductCode($product['numParte']);                    
                        $items->setCategoryIds([
                            2,31,68
                        ]);
                        $items->setStockData(
                            array( 
                            'use_config_manage_stock' => 1,                       
                            'manage_stock' => 1,
                            'is_in_stock' => 1,   
                            'qty' => $sumaExistencia
                            )
                        );
                        if(count($product['promociones']) > 0){
                            if(@$product['promociones'][0]['tipo'] == "importe"){
                                $precioPromocion = $product['promociones'][0]['promocion'] * $product['tipoCambio'];
                                $items->setSpecialPrice($precioPromocion);
                                $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                $items->setSpecialFromDateIsFormated(true);
                                $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                $items->setSpecialToDateIsFormated(true);
                            }
                            else{
                                if(@$product['promociones'][0]['tipo'] == "porcentaje"){
                                    $porcentaje = $product['promociones'][0]['promocion'] / 100;
                                    $valor = $precioReal * $porcentaje;
                                    $precioPromocion = $precioReal - $valor;
                                    $items->setSpecialPrice($precioPromocion);
                                    $items->setSpecialFromDate($product['promociones'][0]['vigencia']['inicio']);
                                    $items->setSpecialFromDateIsFormated(true);
                                    $items->setSpecialToDate($product['promociones'][0]['vigencia']['fin']);
                                    $items->setSpecialToDateIsFormated(true);
                                }                            
                            }
                        }

                        $filename = md5($product['imagen']); // LE DAMOS UN NUEVO NOMBRE
                        //if (!file_exists($mediaDir)) mkdir($mediaDir, 0777, true);
                        //else chmod($mediaDir, 0777);
                        $filepath = $mediaDir . '/catalog/product/imgct/' . $filename.'.jpg'; // SELECCIONAMOS UN PATH TEMPORAL
                        file_put_contents($filepath, file_get_contents(trim($product['imagen']))); // OBTENEMOS LA IMAGEN DE UNA URL EXTENA
                        $imgUrl = $filepath;
                        $items->addImageToMediaGallery($imgUrl, ['image', 'small_image', 'thumbnail'], false, false);                          
                        
                        $items->save();
                        $logger->info($product['clave']." sku agregado");

                    }                                       
                }
            } catch (Exception $e) {
                $data = [];
            }           
        }
        else{
        }
        //INGRAM

    }

    public function connectCT(){
        //FTP
        // Configuración de la conexión FTP
        $ftp_server = '216.70.82.104'; // Reemplaza con la dirección del servidor FTP
        $ftp_user = 'HMO0410'; // Reemplaza con tu nombre de usuario FTP
        $ftp_pass = 'Z6v3Bh7*k@lLcXTGR0!P'; // Reemplaza con tu contraseña FTP

        // Ruta al archivo JSON en el servidor FTP
        $remote_file = 'catalogo_xml/productos.json'; // Reemplaza con la ruta de tu archivo JSON

        // Ruta local donde guardarás el archivo JSON descargado
        $local_file = '/home/master/json/product.json'; // Reemplaza con la ruta de tu elección en tu servidor local

        // Establece la conexión FTP
        $ftp_conn = ftp_connect($ftp_server);
        if (!$ftp_conn) {
            die('No se pudo conectar al servidor FTP.');
        }

        // Inicia sesión en el servidor FTP
        if (ftp_login($ftp_conn, $ftp_user, $ftp_pass)) {
            // Descarga el archivo JSON
            if (ftp_get($ftp_conn, $local_file, $remote_file, FTP_ASCII, 0)) {
                return true;
            } else {
                return false;
            }

            // Cierra la conexión FTP
            ftp_close($ftp_conn);
        } else {
            echo "Error al iniciar sesión en el servidor FTP.";
        }
        //FIN FTP
    } 

    public function updateStock(){
        $dataCt  = file_get_contents("C:\Users\luis.olivarria\Desktop\productsjson\dataPrueba.json");
        $productsData  = json_decode($dataCt, true);
        $resource = \Magento\Framework\App\ObjectManager::getInstance()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $csi = $resource->getTableName('cataloginventory_stock_item');
        $cpe = $resource->getTableName('catalog_product_entity');
        $sql = "SELECT t1.product_id, t1.stock_id, t1.qty, t1.is_in_stock, t2.sku FROM $csi as t1 LEFT JOIN $cpe as t2 ON t1.product_id = t2.entity_id";
        $resultsCSI = $connection->fetchAll($sql);
        $jsonSKU = ""; 
        foreach($productsData as $key => $CT_){
            $jsonSKU .= $CT_["clave"]." ";
            $skus = explode(" ",$jsonSKU);
        }
        foreach($resultsCSI as $CSI_){
            if(!in_array($CSI_["sku"],$skus)){
                $updateStock = "UPDATE $csi SET qty = :qty, is_in_stock = :is_in_stock WHERE product_id = :product_id";
                $params = [
                    'product_id'  => $CSI_["product_id"],
                    'qty'         => 0,
                    'is_in_stock' => 0
                ];
                $results = $connection->fetchAll($updateStock, $params);
            }
        }        
    }
}
