<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\BepadoExport;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_BepadoConfig extends Shopware_Controllers_Backend_ExtJs
{

    /** @var  \Shopware\Bepado\Components\Config */
    private $configComponent;

    /**
     * @var \Shopware\Bepado\Components\BepadoFactory
     */
    private $factory;

    /**
     * The getGeneralAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
     * required in the general config form.
     * @return string
     */
    public function getGeneralAction()
    {
        $generalConfig = $this->getConfigComponent()->getGeneralConfigArrays();

        $this->View()->assign(array(
            'success' => true,
            'data' => $generalConfig
        ));
    }

    /**
     * The saveGeneralAction function is an ExtJs event listener method of the
     * bepado module. The function is used to save store data.
     * @return string
     */
    public function saveGeneralAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $this->getConfigComponent()->setGeneralConfigsArrays($data);

        $this->View()->assign(array(
                'success' => true
        ));
    }

    /**
     * The getImportAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
     * required in the import config form.
     * @return string
     */
    public function getImportAction()
    {
        $importConfigArray = $this->getConfigComponent()->getImportConfig();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $importConfigArray
            )
        );
    }

    /**
     * The saveImportAction function is an ExtJs event listener method of the
     * bepado module. The function is used to save store data.
     * @return string
     */
    public function saveImportAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $this->getConfigComponent()->setImportConfigs($data);

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    /**
     * The getExportAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
     * required in the export config form.
     * @return string
     */
    public function getExportAction()
    {
        $exportConfigArray = $this->getConfigComponent()->getExportConfig();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $exportConfigArray
            )
        );
    }

    /**
     * The saveExportAction function is an ExtJs event listener method of the
     * bepado module. The function is used to save store data.
     * @return string
     */
    public function saveExportAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $isModified = $this->getConfigComponent()->compareExportConfiguration($data);
        $this->getConfigComponent()->setExportConfigs($data);

        if ($isModified === true) {
            $bepadoExport = $this->getBepadoExport();
            try {
                $ids = $bepadoExport->getExportArticlesIds();
                $errors = $bepadoExport->export($ids);
            }catch (\RuntimeException $e) {
                $this->View()->assign(array(
                        'success' => false,
                        'message' => $e->getMessage()
                    ));
                return;
            }

            if (!empty($errors)) {
                $this->View()->assign(array(
                        'success' => false,
                        'message' => implode("<br>\n", $errors)
                    ));
                return;
            }
        }

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    /**
     * @return BepadoExport
     */
    public function getBepadoExport()
    {
        return new BepadoExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->getModelManager()
        );
    }

    /**
     * @return \Shopware\Bepado\Components\Helper
     */
    public function getHelper()
    {
        if ($this->factory === null) {
            $this->factory = new \Shopware\Bepado\Components\BepadoFactory();
        }

        return $this->factory->getHelper();
    }

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    /**
     * @return Shopware\Components\Model\ModelManager
     */
    public function getModelManager()
    {
        return Shopware()->Models();
    }

    /**
     * Helper function to get access on the Config component
     *
     * @return \Shopware\Bepado\Components\Config
     */
    private function getConfigComponent()
    {
        if ($this->configComponent === null) {
            $modelsManager = Shopware()->Models();
            $this->configComponent = new Config($modelsManager);
        }

        return $this->configComponent;
    }

} 