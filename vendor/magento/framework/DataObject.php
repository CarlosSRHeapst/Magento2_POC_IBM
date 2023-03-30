<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework;

/**
 * Universal data container with array access implementation
 *
 * @api
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @since 100.0.2
 */
class DataObject implements \ArrayAccess
{
    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Setter/Getter underscore transformation cache
     *
     * @var array
     */
    protected static $_underscoreCache = [];

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assigns it as object attributes
     * This behavior may change in child classes
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    /**
     * Add data to the object.
     *
     * Retains previous data in the object.
     *
     * @param array $arr
     * @return $this
     */
    public function addData(array $arr)
    {

        //DB2MOD BUG O COMPORTAMENTIO EXTRAÑO DE MAGENTO
        //La clase de redirige a framework/Model/AbstractModel.php
        if ($this->_data === []) {
            $this->setData($arr);
            return $this;
        }
        foreach ($arr as $index => $value) {
            $this->setData($index, $value);
        }
        //echo "Aca estoyNOIF" . PHP_EOL;
        return $this;
    }

    /**
     * Overwrite data in the object.
     *
     * The $key parameter can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        if ($key === (array)$key) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $value;
        }
        return $this;
    }

    /**
     * Unset data from the object.
     *
     * @param null|string|array $key
     * @return $this
     */
    public function unsetData($key = null)
    {
        if ($key === null) {
            $this->setData([]);
        } elseif (is_string($key)) {
            if (isset($this->_data[$key]) || array_key_exists($key, $this->_data)) {
                unset($this->_data[$key]);
            }
        } elseif ($key === (array)$key) {
            foreach ($key as $element) {
                $this->unsetData($element);
            }
        }
        return $this;
    }

    /**
     * Object data getter
     *
     * If $key is not defined will return all the data as an array.
     * Otherwise it will return value of the element specified by $key.
     * It is possible to use keys like a/b/c for access nested array data
     *
     * If $index is specified it will assume that attribute data is an array
     * and retrieve corresponding member. If data is the string - it will be explode
     * by new line character and converted to array.
     *
     * @param string $key
     * @param string|int $index
     * @return mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getData($key = '', $index = null)
    {
        //23/03/2023
        //echo " Estoy aca y la key es: " . $key . PHP_EOL;
        //DB2MOD 21/02/2023
        /*if("groups/" == $key) {
            echo "PorAqui " . PHP_EOL;
        }*/
        /* process a/b/c key as ['a']['b']['c'] */
        if ($key !== null && strpos($key, '/') !== false) {
            //DB2MOD 22/02/2023
            //echo "PorAqui1 " . PHP_EOL;
            $data = $this->getDataByPath($key);
            //DEVUELVE NULL BAJO ESTAS CONDICIONES
        } else {
            $data = $this->_getData($key);
        }

        ///////////////24/03/2023 FIX EMPTY PATH//////////////////
        //27/03/2023 se comenta momentaneamente para ver que pasa con cambio en driver
        //if($data == null && $key == "ALL_OBJ"){
            //24/03/2023 Se prueba con ambas, no hay cambio siguiente error esta en store.
            /*
            Warning: Undefined array key "code" in /var/www/html/magento2/vendor/magent  
            o/module-store/Model/ResourceModel/Website.php on line 56 
            */
            //$data = $this->_data;
            //$data  = [];
        //}

        if ($index !== null) {
            if ($data === (array)$data) {
                $data = isset($data[$index]) ? $data[$index] : null;
            } elseif (is_string($data)) {
                $data = explode(PHP_EOL, $data);
                $data = isset($data[$index]) ? $data[$index] : null;
            } elseif ($data instanceof \Magento\Framework\DataObject) {
                $data = $data->getData($index);
            } else {
                $data = null;
            }
        }
        return $data;
    }

    /**
     * Get object data by path
     *
     * Method consider the path as chain of keys: a/b/c => ['a']['b']['c']
     *
     * @param string $path
     * @return mixed
     */
    public function getDataByPath($path)
    {
        //DB2MOD magento mayor bug
        $keys = explode('/', (string)$path);

        //Revisitado 24/03/2023

        $data = $this->_data;

        //var_dump($data);
        //El codigo de IFS se hizo para el caso de que no se encuentre un grupo
        /*Por ejemplo "groups/" se vaya al default o el primero */
        /*if("groups/" == $path) {
            echo "PorAqui AAA" . PHP_EOL;
            var_dump($keys);
            var_dump(gettype($this->_data));
            print_r(array_keys($this->_data));
            var_dump(gettype($this->_data["groups"]));
            var_dump($this->_data["groups"]);
            var_dump((array)$data === $data);
            var_dump(isset($data["groups"]));
            var_dump($this->_data["groups"]);
        }*/

        //var_dump($keys); Se confirma el 1

        foreach ($keys as $key) {
            //echo $key . PHP_EOL; //groups

            //22/02/2023 MAYOR BUG FIX DE MAGENTO. 
            //Prueba1 devolver arreglo entero de GROUPS (sin numero sin filtrado)

            if($key == ""){
                $key = 0;
            }

            if($key != ""){  
                if ((array)$data === $data && isset($data[$key])) {
                    //echo "PorAqui1 " . PHP_EOL;
                    $data = $data[$key];
                } elseif ($data instanceof \Magento\Framework\DataObject) {
                    //echo "PorAqui2 " . PHP_EOL;
                    $data = $data->getDataByKey($key);
                } else {
                    //echo "PorAqui3 " . PHP_EOL; //ESTE ES EL PROBEMA
                    return null;
                }
            }
            
        }
        return $data;
    }

    /**
     * Get object data by particular key
     *
     * @param string $key
     * @return mixed
     */
    public function getDataByKey($key)
    {
        return $this->_getData($key);
    }

    /**
     * Get value from _data array without parse key
     *
     * @param   string $key
     * @return  mixed
     */
    protected function _getData($key)
    {
        //echo " Estoy entrando aqui " . $key . " " . PHP_EOL;
        //20/02/2023
        //DB2MOD INTERCEPTOR GROUPREP
        //PRUEBA DE QUE CIERTA CLAVE EXISTE

        //EL CAMBIO DE MAYUSCULAS DEBE SER AQUI-
        //CORRECCION EL CAMBIO DE MAYUSCULAS NO PUEDE SER AQUI YA QUE ALGUNAS CLAVES
        //ESTAN EN MINUSCULAS Y OTRAS EN MAYUSUCLAS 21/02/2023 , se regresa a abstractmodel
        /*
        string(8) "websites"
        string(4) "code"
        string(10) "WEBSITE_ID"
        string(6) "stores"
        string(4) "code"
        string(8) "store_id"
        string(4) "code"
        string(8) "store_id"
        string(9) "is_active"
        string(16) "default_group_id"
        string(8) "GROUP_ID"*/

        /*try {

            if($key == "GROUP_ID")
            {
                //21/02/2023 AL MOMENTO DE QUE WEBSITEREPOSITORY ENVIA A CONSULTAR GROUP_ID 
                //este no tiene informacion
                //echo "Trigger GROUPID " . PHP_EOL;
                //var_dump($this->_data);


                //echo "Trigger GROUPID " . PHP_EOL;
                //if(array_key_exists("GROUP_ID", $this->_data))
                //{
                    //var_dump($this->_data["GROUP_ID"]);
                //}
            }
            
        } catch (Exception $ex) {

        }*/
        //var_dump($this->_data);
        /*
        GROUP ID MUESTRA
        string(1) "0"
        string(1) "0"
        string(1) "1"
        string(1) "1"
        string(1) "1"
        */

        /*string(8) "websites"
        string(4) "CODE"
        string(10) "WEBSITE_ID"
        string(10) "WEBSITE_ID"
        string(6) "stores"
        string(4) "code"
        string(8) "store_id"
        string(4) "code"
        string(8) "store_id"
        string(9) "is_active"
        string(16) "DEFAULT_GROUP_ID"*/

        //var_dump($key);

        //Sigue existiendo una falla con "code" debe ser "CODE" existen muchos conflictos de este tipo
///////////////////////////////////////////

            /*if($key == "store_id")
            {
                //var_dump(ctype_upper($key));
                //var_dump(ctype_lower($key)); //CTYPE NO FUNCIONA SI TIENE UN GUION BAJO O MEDIO
                if(array_key_exists("store_id", $this->_data))
                {
                    echo "Chiquito " . PHP_EOL;
                    var_dump($this->_data["store_id"]);
                    echo "Cambiado " . strtolower($key) . PHP_EOL;
                    echo "Cambiado " . strtoupper($key) . PHP_EOL;
                }
                if(array_key_exists("STORE_ID", $this->_data))
                {
                    echo "Grandote " . PHP_EOL;
                    var_dump($this->_data["STORE_ID"]);
                    echo "Cambiado " . strtolower($key) . PHP_EOL;
                    echo "Cambiado " . strtoupper($key) . PHP_EOL;
                }
            }*/

/////////////////////////////////////////////////////////////////////////////////////////////////////
        //var_dump($key);

        //DB2MOD Intento de fix para problema de mayusculas y minusculas
        $keyField = "";

        //echo "Sigue en encontrarse: " . $key . PHP_EOL;
        //Si lo obtiene cambia su valor de NULL

        if (isset($this->_data[$key])) {
            //echo "Lo encontre a la primera " . $key . PHP_EOL;
            return $this->_data[$key];
        }

        //Si no lo obtiene lo intenta obtener con uppercase o lowercase segun sea el caso
        if(gettype($key) == "string"){

            //Intentar obtener datos dependiendo de si es MAYUSCULAS O MINUSCULAS
            if (preg_match('/[A-Z]/', $key)) {
                //echo "Upper a Lower " . $key . PHP_EOL;

                $keyField = strtolower($key);

                if (isset($this->_data[$keyField])) {
                    return $this->_data[$keyField];
                }

            } else if (preg_match('/[a-z]/', $key)) {
                //echo "Lower a Upper " . $key . PHP_EOL;

                $keyField = strtoupper($key);

                if (isset($this->_data[$keyField])) {
                    return $this->_data[$keyField];
                }
            }

        }

        return null;
//////////////////////////////////////////////////////////////////////////////////////////////////////////
        /* //CODIGO ORIGINAL
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return null;*/
    }

    /**
     * Set object data with calling setter method
     *
     * @param string $key
     * @param mixed $args
     * @return $this
     */
    public function setDataUsingMethod($key, $args = [])
    {
        $method = 'set' . ($key !== null ? str_replace('_', '', ucwords($key, '_')) : '');
        $this->{$method}($args);
        return $this;
    }

    /**
     * Get object data by key with calling getter method
     *
     * @param string $key
     * @param mixed $args
     * @return mixed
     */
    public function getDataUsingMethod($key, $args = null)
    {
        $method = 'get' . ($key !== null ? str_replace('_', '', ucwords($key, '_')) : '');
        return $this->{$method}($args);
    }

    /**
     * If $key is empty, checks whether there's any data in the object
     *
     * Otherwise checks if the specified attribute is set.
     *
     * @param string $key
     * @return bool
     */
    public function hasData($key = '')
    {
        if (empty($key) || !is_string($key)) {
            return !empty($this->_data);
        }
        return array_key_exists($key, $this->_data);
    }

    /**
     * Convert array of object data with to array with keys requested in $keys array
     *
     * @param array $keys array of required keys
     * @return array
     */
    public function toArray(array $keys = [])
    {
        if (empty($keys)) {
            return $this->_data;
        }

        $result = [];
        foreach ($keys as $key) {
            if (isset($this->_data[$key])) {
                $result[$key] = $this->_data[$key];
            } else {
                $result[$key] = null;
            }
        }
        return $result;
    }

    /**
     * The "__" style wrapper for toArray method
     *
     * @param  array $keys
     * @return array
     */
    public function convertToArray(array $keys = [])
    {
        return $this->toArray($keys);
    }

    /**
     * Convert object data into XML string
     *
     * @param array $keys array of keys that must be represented
     * @param string $rootName root node name
     * @param bool $addOpenTag flag that allow to add initial xml node
     * @param bool $addCdata flag that require wrap all values in CDATA
     * @return string
     */
    public function toXml(array $keys = [], $rootName = 'item', $addOpenTag = false, $addCdata = true)
    {
        $xml = '';
        $data = $this->toArray($keys);
        foreach ($data as $fieldName => $fieldValue) {
            if ($addCdata === true) {
                $fieldValue = "<![CDATA[{$fieldValue}]]>";
            } else {
                $fieldValue = $fieldValue !== null ? str_replace(
                    ['&', '"', "'", '<', '>'],
                    ['&amp;', '&quot;', '&apos;', '&lt;', '&gt;'],
                    $fieldValue
                ) : '';
            }
            $xml .= "<{$fieldName}>{$fieldValue}</{$fieldName}>\n";
        }
        if ($rootName) {
            $xml = "<{$rootName}>\n{$xml}</{$rootName}>\n";
        }
        if ($addOpenTag) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml;
        }
        return $xml;
    }

    /**
     * The "__" style wrapper for toXml method
     *
     * @param array $arrAttributes array of keys that must be represented
     * @param string $rootName root node name
     * @param bool $addOpenTag flag that allow to add initial xml node
     * @param bool $addCdata flag that require wrap all values in CDATA
     * @return string
     */
    public function convertToXml(
        array $arrAttributes = [],
        $rootName = 'item',
        $addOpenTag = false,
        $addCdata = true
    ) {
        return $this->toXml($arrAttributes, $rootName, $addOpenTag, $addCdata);
    }

    /**
     * Convert object data to JSON
     *
     * @param array $keys array of required keys
     * @return bool|string
     * @throws \InvalidArgumentException
     */
    public function toJson(array $keys = [])
    {
        $data = $this->toArray($keys);
        return \Magento\Framework\Serialize\JsonConverter::convert($data);
    }

    /**
     * The "__" style wrapper for toJson
     *
     * @param array $keys
     * @return bool|string
     * @throws \InvalidArgumentException
     */
    public function convertToJson(array $keys = [])
    {
        return $this->toJson($keys);
    }

    /**
     * Convert object data into string with predefined format
     *
     * Will use $format as an template and substitute {{key}} for attributes
     *
     * @param string $format
     * @return string
     */
    public function toString($format = '')
    {
        if (empty($format)) {
            $result = implode(', ', $this->getData());
        } else {
            preg_match_all('/\{\{([a-z0-9_]+)\}\}/is', $format, $matches);
            foreach ($matches[1] as $var) {
                $data = $this->getData($var) ?? '';
                $format = str_replace('{{' . $var . '}}', $data, $format);
            }
            $result = $format;
        }
        return $result;
    }

    /**
     * Set/Get attribute wrapper
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __call($method, $args)
    {
        switch (substr((string)$method, 0, 3)) {
            case 'get':
                $key = $this->_underscore(substr($method, 3));
                $index = isset($args[0]) ? $args[0] : null;
                return $this->getData($key, $index);
            case 'set':
                $key = $this->_underscore(substr($method, 3));
                $value = isset($args[0]) ? $args[0] : null;
                return $this->setData($key, $value);
            case 'uns':
                $key = $this->_underscore(substr($method, 3));
                return $this->unsetData($key);
            case 'has':
                $key = $this->_underscore(substr($method, 3));
                return isset($this->_data[$key]);
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Invalid method %1::%2', [get_class($this), $method])
        );
    }

    /**
     * Checks whether the object is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (empty($this->_data)) {
            return true;
        }
        return false;
    }

    /**
     * Converts field names for setters and getters
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name
     * @return string
     */
    protected function _underscore($name)
    {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }
        $result = strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "_$1", $name), '_'));
        self::$_underscoreCache[$name] = $result;
        return $result;
    }

    /**
     * Convert object data into string with defined keys and values.
     *
     * Example: key1="value1" key2="value2" ...
     *
     * @param   array $keys array of accepted keys
     * @param   string $valueSeparator separator between key and value
     * @param   string $fieldSeparator separator between key/value pairs
     * @param   string $quote quoting sign
     * @return  string
     */
    public function serialize($keys = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"')
    {
        $data = [];
        if (empty($keys)) {
            $keys = array_keys($this->_data);
        }

        foreach ($this->_data as $key => $value) {
            if (in_array($key, $keys)) {
                $data[] = $key . $valueSeparator . $quote . $value . $quote;
            }
        }
        $res = implode($fieldSeparator, $data);
        return $res;
    }

    /**
     * Present object data as string in debug mode
     *
     * @param mixed $data
     * @param array $objects
     * @return array
     */
    public function debug($data = null, &$objects = [])
    {
        if ($data === null) {
            $hash = spl_object_hash($this);
            if (!empty($objects[$hash])) {
                return '*** RECURSION ***';
            }
            $objects[$hash] = true;
            $data = $this->getData();
        }
        $debug = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $debug[$key] = $value;
            } elseif (is_array($value)) {
                $debug[$key] = $this->debug($value, $objects);
            } elseif ($value instanceof \Magento\Framework\DataObject) {
                $debug[$key . ' (' . get_class($value) . ')'] = $value->debug(null, $objects);
            }
        }
        return $debug;
    }

    /**
     * Implementation of \ArrayAccess::offsetSet()
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     * @link http://www.php.net/manual/en/arrayaccess.offsetset.php
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of \ArrayAccess::offsetExists()
     *
     * @param string $offset
     * @return bool
     * @link http://www.php.net/manual/en/arrayaccess.offsetexists.php
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]) || array_key_exists($offset, $this->_data);
    }

    /**
     * Implementation of \ArrayAccess::offsetUnset()
     *
     * @param string $offset
     * @return void
     * @link http://www.php.net/manual/en/arrayaccess.offsetunset.php
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Implementation of \ArrayAccess::offsetGet()
     *
     * @param string $offset
     * @return mixed
     * @link http://www.php.net/manual/en/arrayaccess.offsetget.php
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (isset($this->_data[$offset])) {
            return $this->_data[$offset];
        }
        return null;
    }
}
