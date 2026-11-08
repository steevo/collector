<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

/**
 * Template d'affichage d'une collection.
 */
class TemplateModel extends AdminModel
{
    public $typeAlias = 'com_collector.template';

    public function getTable($name = 'CollectorTemplates', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        // nouveau template : collection reprise de la liste, vue liste par défaut
        if ($item && empty($item->id)) {
            $app              = Factory::getApplication();
            $item->collection = $app->input->getInt('collection')
                ?: (int) $app->getUserState('com_collector.current_collection', 0);
            $item->client     = CollectorTemplateHelper::CLIENT_LIST;
        }

        return $item;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_collector.template', 'template', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_collector.edit.template.data', []);

        return empty($data) ? $this->getItem() : $data;
    }

    /**
     * Balises proposées dans l'éditeur, pour chaque vue.
     */
    public function getTags(): array
    {
        $collection = (int) $this->getItem()->collection;

        return [
            CollectorTemplateHelper::CLIENT_LIST => CollectorTemplateHelper::getTags($collection, CollectorTemplateHelper::CLIENT_LIST),
            CollectorTemplateHelper::CLIENT_ITEM => CollectorTemplateHelper::getTags($collection, CollectorTemplateHelper::CLIENT_ITEM),
        ];
    }
}
