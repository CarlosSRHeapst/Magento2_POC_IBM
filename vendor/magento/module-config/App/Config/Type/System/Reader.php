<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Config\App\Config\Type\System;

/**
 * System configuration reader. Created this class to encapsulate the complexity of configuration data retrieval.
 *
 * All clients of this class can use its proxy to avoid instantiation when configuration is cached.
 */
class Reader
{
    /**
     * @var \Magento\Framework\App\Config\ConfigSourceInterface
     */
    private $source;

    /**
     * @var \Magento\Store\Model\Config\Processor\Fallback
     */
    private $fallback;

    /**
     * @var \Magento\Framework\App\Config\Spi\PreProcessorInterface
     */
    private $preProcessor;

    /**
     * Reader constructor.
     * @param \Magento\Framework\App\Config\ConfigSourceInterface $source
     * @param \Magento\Store\Model\Config\Processor\Fallback $fallback
     * @param \Magento\Framework\App\Config\Spi\PreProcessorInterface $preProcessor
     * @param \Magento\Framework\App\Config\Spi\PostProcessorInterface $postProcessor
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Framework\App\Config\ConfigSourceInterface $source,
        \Magento\Store\Model\Config\Processor\Fallback $fallback,
        \Magento\Framework\App\Config\Spi\PreProcessorInterface $preProcessor,
        \Magento\Framework\App\Config\Spi\PostProcessorInterface $postProcessor
    ) {
        $this->source = $source;
        $this->fallback = $fallback;
        $this->preProcessor = $preProcessor;
    }

    /**
     * Retrieve and process system configuration data
     *
     * Processing includes configuration fallback (default, website, store) and placeholder replacement
     *
     * @return array
     */
    public function read()
    {

        /*  string(51) "Magento\Framework\App\Config\ConfigSourceAggregated"
            array(2) {
            [0]=>
            string(11) "__construct"
            [1]=>
            string(3) "get"
            }
            array(0) {
            }
        */
//////////////////////////////////////////////////////////////////////////       
        /*var_dump(get_class($this->source));
        var_dump(get_class_methods($this->source));
        var_dump(get_object_vars($this->source));*/

        //$PruebaDB2MOD = $this->source->get();

        //var_dump($PruebaDB2MOD);
///////////////////////////////////////////////////////////////////////////
        //Trazo cruzado en ConfigSourceAggregated

        //SOURCE DE ERROR 08/02/2023

        /*
        Undefined constant "Magento\Framework\App\Config\sa"#0 
        /var/www/html/magento2/vendor/magento/module-config/App/Config/Type/System/Reader.php(76): 
        Magento\Framework\App\Config\PreProcessorComposite->process()

        #1 /var/www/html/magento2/generated/code/Magento/Config/App
        /Config/Type/System/Reader/Proxy.php(95): 
        Magento\Config\App\Config\Type\System\Reader->read()

        */
        //21/02/2023 NOTA READER NO OFRECE UN PATH POR LO TANTO ES CORRECTO QUE SE REGRESE NULL
        return $this->fallback->process(
            $this->preProcessor->process(
                $this->source->get()
            )
        );
    }
}
