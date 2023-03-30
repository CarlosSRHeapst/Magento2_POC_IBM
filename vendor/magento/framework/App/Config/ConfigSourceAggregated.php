<?php
/**
 * Application configuration object. Used to access configuration when application is initialized and installed.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Config;

class ConfigSourceAggregated implements ConfigSourceInterface
{
    /**
     * @var ConfigSourceInterface[]
     */
    private $sources;

    /**
     * ConfigSourceAggregated constructor.
     *
     * @param array $sources
     */
    public function __construct(array $sources = [])
    {
        $this->sources = $sources;

        //22/02/2023 se investigan sources ya que es comportamiento esperado al alimentar
        /*if(gettype($this->sources) == "array")
        {
            //echo " Entre al error " .PHP_EOL;
            //var_dump(array_keys($this->sources));
            //Primera corrida tiene dos! (Se guardaron las ejecucciones en archivo)
            //Para esta prueba asumiremos seguramente que todos tienen source

            foreach ($this->sources as $sourceConfig) {
                $source = $sourceConfig['source'];

                var_dump(get_class($source));
                var_dump(get_class_methods($source));
                var_dump(get_object_vars($source));

                if(get_class($source) == "Magento\Config\App\Config\Source\ModularConfigSource")
                {
                    sa;
                }
            }
            echo " ////////////////////////////////////Mas Uno " . PHP_EOL;
        }*/

        uasort($this->sources, function ($firstItem, $secondItem) {
            return $firstItem['sortOrder'] <=> $secondItem['sortOrder'];
        });
    }

    /**
     * Retrieve aggregated configuration from all available sources.
     *
     * @param string $path
     * @return string|array
     */
    public function get($path = '')
    {
        //DB2MOD
        $data = [];
        //echo " Accedi a este metodo aqui " . PHP_EOL;
        //var_dump(gettype($this->sources)); //array
        //var_dump($this->sources); //Muy grande para debuggear

        //17/03/2023 regreso a revision despues de revisar sources anteriores en stacktrace
        //var_dump(count($this->sources));
        //var_dump(array_keys($this->sources));
        /*
            int(3)

            array(3) {
            [0]=>
            string(7) "modular"
            [1]=>
            string(7) "dynamic"
            [2]=>
            string(7) "initial"
            }
        */

        //var_dump(count($this->sources["modular"]));
        //var_dump(gettype($this->sources["modular"]));
        //var_dump(array_keys($this->sources["modular"]));
        /*
            int(2)
            string(5) "array"

            array(2) {
            [0]=>
            string(6) "source"
            [1]=>
            string(9) "sortOrder"
            }
        */

        //var_dump(count($this->sources["dynamic"]));
        //var_dump(gettype($this->sources["dynamic"]));
        //var_dump(array_keys($this->sources["dynamic"]));
        /*
            int(2)
            string(5) "array"

            array(2) {
            [0]=>
            string(6) "source"
            [1]=>
            string(9) "sortOrder"
            }
        */

        //var_dump(count($this->sources["initial"]));
        //var_dump(gettype($this->sources["initial"]));
        //var_dump(array_keys($this->sources["initial"]));
        /*
            int(2)
            string(5) "array"

            array(2) {
            [0]=>
            string(6) "source"
            [1]=>
            string(9) "sortOrder"
            }

        */

        foreach ($this->sources as $sourceConfig) {
            /** @var ConfigSourceInterface $source */
            $source = $sourceConfig['source'];


            //var_dump($source);//Muy grande para debuggear
            //var_dump($path); //PATH nunca se alimenta por parte de a llamada
            // path == "";
            // path == "";

///////////////////////////////////////////////////////////////////////////////////////////////////             
            //17/03/2023 Nuevo analisis de este objeto y su creacion, objeto muy grande

            //var_dump(gettype($source));
            /*
                string(6) "object"
                string(6) "object"
                string(6) "object"
            */

            /*var_dump(get_class($source));
            var_dump(get_class_methods($source));
            var_dump(get_object_vars($source));*/

            /*
                string(6) "object"
                string(52) "Magento\Config\App\Config\Source\ModularConfigSource"
                array(2) {
                [0]=>
                string(11) "__construct"
                [1]=>
                string(3) "get"
                }
                array(0) {
                }
                string(6) "object"
                string(52) "Magento\Config\App\Config\Source\RuntimeConfigSource"
                array(2) {
                [0]=>
                string(11) "__construct"
                [1]=>
                string(3) "get"
                }
                array(0) {
                }
                string(6) "object"
                string(48) "Magento\Framework\App\Config\InitialConfigSource"
                array(2) {
                [0]=>
                string(11) "__construct"
                [1]=>
                string(3) "get"
                }
                array(0) {
                }
            */

    //21/02/2023////////////////////////////////////
            /*
                string(5) "array"
                bool(true)
                string(4) "NULL" //No se entrega PATH por lo tanto se recibe NULL
                bool(false)
                string(5) "array"
                bool(true)
            */

//////////////////////////////////////////////////////////////////////////////////////////////////
            $configData = $source->get($path);

    //24/03/2023/////////////////////////////////////////
            //REVISION DE CADA CONFIGDATA Y EL RESULTADO FINAL

            //var_dump($configData);
            //echo "DB2MOD Revision de datos " . PHP_EOL;
            //////////////////////////////////////////////////////////////////////////////////
            /*var_dump(gettype($configData));
            var_dump(is_array($configData));*/

            /*
            DB2MOD Revision de datos 
            bool(true)  //En la condicion seria false !is_array($configData)
            DB2MOD Revision de datos 
            bool(false) //En la condicion seria true  !is_array($configData)
            DB2MOD Revision de datos 
            bool(true) //En la condicion seria false !is_array($configData)
            */

            /*
                bool(true) //var_dump(is_array($configData));
                string(5) "array" //$data
                bool(false) // var_dump(is_array($configData));
                string(4) "NULL" //$data
                bool(true) //var_dump(is_array($configData));
                string(4) "NULL" //$data (Es un string que dice NULL....)
                NULL //$data

             */

             /*string(5) "array"
             string(4) "NULL"
             string(4) "NULL"*/

    //24/03/2023/////////////////////////////////////////
    //REVISION DE CADA CONFIGDATA Y EL RESULTADO FINAL
            /*if(get_class($source) == "Magento\Config\App\Config\Source\ModularConfigSource")
            {
                var_dump($configData);
            }*/

            /*if(get_class($source) == "Magento\Config\App\Config\Source\RuntimeConfigSource")
            {
                var_dump($configData);
            }*/

            /*if(get_class($source) == "Magento\Framework\App\Config\InitialConfigSource")
            {
                var_dump($configData);
            }*/
             

            if (!is_array($configData)) {
                //echo "Entrada UP " . PHP_EOL; //2
                $data = $configData;
            } elseif (!empty($configData)) {
                //echo "Entrada DOWN " . PHP_EOL; //1
                $data = array_replace_recursive(is_array($data) ? $data : [], $configData);
            }
            /********/

            //21/02/2023
            //var_dump(gettype($data));
            
            /*El procesado de esta estructura (los if) es:

             string(5) "array"
             string(4) "NULL"
             string(4) "NULL"*/

            

        }
        /********/
        //var_dump($data);
        return $data;
    }
}
