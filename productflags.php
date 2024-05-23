<?php
/**
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Productflags extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'productflags';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Andrés Abarzúa';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Agregar Flags a Productos más Vendidos');
        $this->description = $this->l('Con este modulo se creará una nueva flag para los productos más vendidos de la web.');

        $this->confirmUninstall = $this->l('¿Estas seguro de Desinstalar este Modulo?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '8.0');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('PRODUCTFLAGS_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') && 
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionProductFlagsModifier');;
    }

    public function uninstall()
    {
        Configuration::deleteByName('PRODUCTFLAGS_LIVE_MODE');

        return parent::uninstall();
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
    
    public function getBestSellingProductsId() {
        $langID = Context::getContext()->language->id;
    
        // Preparar la consulta SQL
        $sql = new DbQuery();
        $sql->select('ps.id_product');
        $sql->from('product_sale', 'ps');
        $sql->leftJoin('product_lang', 'pl', 'ps.id_product = pl.id_product AND pl.id_lang = ' . (int)$langID);
        $sql->orderBy('ps.sale_nbr DESC');
        $sql->groupBy('ps.id_product');
        $sql->limit(1000);
        // Puedes agregar un límite si es necesario, por ejemplo: $sql->limit(1000);
    
        $results = Db::getInstance()->executeS($sql);
    
        $productsIdArray = array();
        foreach ($results as $result) {
            $productsIdArray[] = $result['id_product'];
        }
    
        return $productsIdArray;
    }

    
    public function isBestSelling($arrayOfBestSellingIds = array(), $productId) {
        if (!empty($arrayOfBestSellingIds)) {
            if (in_array($productId, $arrayOfBestSellingIds)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function hookActionProductFlagsModifier($params)
    {
        $bestSellings = $this->getBestSellingProductsId();
        $allProductsId = $params['product']['id_product'];
        if (!empty($allProductsId)) {
            if (in_array($allProductsId, $bestSellings)) {
                array_push($params['product'], $params['product']['is_best_seller'] = (int) in_array($allProductsId, $bestSellings));
            }
        }
        if (in_array('is_best_seller', $params['product'])
            && $this->isBestSelling($bestSellings, $allProductsId)) {
            $params['flags']['bestseller'] = array(
                'type' => 'bestseller',
                'label' => '¡Top Venta!'
            );
        }
    }
    
}
