<?php
/**
 * Application configuration object. Used to access configuration when application is initialized and installed.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App;

use Magento\Framework\App\Config\ConfigTypeInterface;
use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 */
class Config implements ScopeConfigInterface
{
    /**
     * Config cache tag
     */
    const CACHE_TAG = 'CONFIG';

    /**
     * @var ScopeCodeResolver
     */
    private $scopeCodeResolver;

    /**
     * @var ConfigTypeInterface[]
     */
    private $types;

    /**
     * Config constructor.
     *
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param array $types
     */
    public function __construct(
        ScopeCodeResolver $scopeCodeResolver,
        array $types = []
    ) {
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->types = $types;
    }

    /**
     * Retrieve config value by path and scope
     *
     * @param string $path
     * @param string $scope
     * @param null|int|string $scopeCode
     * @return mixed
     */
    public function getValue(
        $path = null,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        //echo $path . PHP_EOL; //newrelicreporting/general/enable
        //DB2MOD $path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null
        if ($scope === 'store') {
            $scope = 'stores';
        } elseif ($scope === 'website') {
            $scope = 'websites';
        }
        $configPath = $scope;
        if ($scope !== 'default') {
            if (is_numeric($scopeCode) || $scopeCode === null) {
                //var_dump($scope); //stores
                //var_dump($scopeCode); //null
                $scopeCode = $this->scopeCodeResolver->resolve($scope, $scopeCode);
            } elseif ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
                $scopeCode = $scopeCode->getCode();
            }
            if ($scopeCode) {
                $configPath .= '/' . $scopeCode;
            }
        }
        if ($path) {
            $configPath .= '/' . $path;
        }
        //var_dump($configPath); //string(40) "default/newrelicreporting/general/enable"
        return $this->get('system', $configPath);
    }

    /**
     * Retrieve config flag
     *
     * @param string $path
     * @param string $scope
     * @param null|int|string $scopeCode
     * @return bool
     */
    public function isSetFlag($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        //DB2MOD $path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null
        return !!$this->getValue($path, $scope, $scopeCode);
    }

    /**
     * Invalidate cache by type
     *
     * Clean scopeCodeResolver
     *
     * @return void
     */
    public function clean()
    {
        foreach ($this->types as $type) {
            $type->clean();
        }
        $this->scopeCodeResolver->clean();
    }

    /**
     * Retrieve configuration.
     *
     * ('modules') - modules status configuration data
     * ('scopes', 'websites/base') - base website data
     * ('scopes', 'stores/default') - default store data
     *
     * ('system', 'default/web/seo/use_rewrites') - default system configuration data
     * ('system', 'websites/base/web/seo/use_rewrites') - 'base' website system configuration data
     *
     * ('i18n', 'default/en_US') - translations for default store and 'en_US' locale
     *
     * @param string $configType
     * @param string|null $path
     * @param mixed|null $default
     * @return array
     */
    public function get($configType, $path = '', $default = null)
    {
        //DB2MOD DESDE STORE://///////////////////////////////////////////
        //var_dump($configType);
        //string(6) "system"
        //string(6) "system"

        //var_dump($path);
        //string(33) "default/admin/url/use_custom_path"
        //string(40) "default/newrelicreporting/general/enable"

        //DB2MOD DESDE COMPILACION://////////////////////////////////////////
        //var_dump($configType);
        //string(6) "scopes"
        //string(6) "scopes"
        //string(6) "scopes" //FAIL

        //var_dump($path);
        //string(8) "websites" //PASS
        //string(6) "stores" //PASS
        //string(7) "groups/" //FAIL


        /*DB2MOD:
        PRUEBA COMPLETA GROUP REPOSITORY:

        string(6) "scopes"
        string(8) "websites"
        string(6) "scopes"
        string(6) "stores"
        //INVOCACION DESDE GROUP REPOSITORY, OCUPA UN ID
        NULL
        string(6) "scopes"
        string(7) "groups/"*/


        /*if($path == "groups/")
        {
            //Magento\Store\App\Config\Type\Scopes
            //var_dump(get_class($this->types["scopes"]));
        }*/
        
        //ALIMENTACION QUE CAUSA EL ERROR $this->types["scopes"]->get(groups/);


        //13/03/2023

        //var_dump($configType);
        //var_dump(isset($this->types[$configType]));

        //$configType = system

        if($configType == "system")
        {
            /*var_dump($path);

            var_dump(get_class($this->types["system"]));
            var_dump(get_class_methods($this->types["system"]));
            var_dump(get_object_vars($this->types["system"]));*/

            /*
                string(25) "default/web/url/use_store"
                string(37) "Magento\Config\App\Config\Type\System"
                array(3) {
                [0]=>
                string(11) "__construct"
                [1]=>
                string(3) "get"
                [2]=>
                string(5) "clean"
                }
                array(0) {
                }

            */

        }

        //$path = "default/web/url/use_store"

        /*CODIGO INALCANZABLE SYSTEM Y PATH DE ESTE TIPO*/
        //var_dump($this->types["system"]->get($path));


        $result = null;
        if (isset($this->types[$configType])) {
            $result = $this->types[$configType]->get($path);
        }
        //DB2MOD 
        //var_dump($result);
        return $result !== null ? $result : $default;
    }
}
