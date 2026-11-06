<?php
namespace Steevo\Component\Collector\Administrator\View\Files;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public string $context     = 'collection';
    public int    $contextId   = 0;
    public array  $collections = [];
    public array  $defines     = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();

        $this->context   = $app->input->getString('context', 'collection');
        $this->contextId = $app->input->getInt('id', 0);

        $model = $this->getModel('Files');

        // Collections
        $db = Factory::getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id, name')
               ->from($db->quoteName('#__collector'))
               ->where($db->quoteName('state') . ' = 1')
               ->order('name')
        );
        $this->collections = $db->loadObjectList() ?: [];

        // Groupes define
        $this->defines = $model->getDefineGroups();

        // Créer le dossier racine si nécessaire
        if ($this->contextId) {
            $model->ensureRootExists($this->context, $this->contextId);
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_COLLECTOR_FILES_TITLE'), 'images');

        ToolbarHelper::link(
            'index.php?option=com_collector&view=tools',
            Text::_('COM_COLLECTOR_USERLISTSTATS_BACK'),
            'icon-arrow-left'
        );
    }
}