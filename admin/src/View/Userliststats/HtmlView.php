<?php
namespace Steevo\Component\Collector\Administrator\View\Userliststats;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Steevo\Component\Collector\Administrator\Model\UserliststatsModel;
use Joomla\Database\DatabaseInterface;

class HtmlView extends BaseHtmlView
{
    public ?object $collection = null;
    public object  $stats;
    public array   $topUsersByType   = [];
    public array   $topItemsByType   = [];
    public array   $matchingItems    = [];
    public array   $activity         = [];

    public function display($tpl = null): void
    {
        $app          = Factory::getApplication();
        $collectionId = $app->input->getInt('collection', 0);

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery(
            $db->getQuery(true)
               ->select('*')
               ->from($db->quoteName('#__collector'))
               ->where($db->quoteName('id') . ' = ' . $collectionId)
        );
        $this->collection = $db->loadObject();

        if (!$this->collection) {
            $app->enqueueMessage(
                Text::_('COM_COLLECTOR_CONF_COL_FIRST')
                . ' <a href="index.php?option=com_collector&view=collections">'
                . Text::_('COM_COLLECTOR_CONF_COL_MENU') . '</a>',
                'error'
            );
            return;
        }

        /** @var UserliststatsModel $model */
        $model = $this->getModel();
        $model->setCollection($collectionId);

        $this->stats = $model->getGlobalStats();

        foreach ([0, 1, 2] as $type) {
            $this->topUsersByType[$type] = $model->getTopUsers($type, 10);
            $this->topItemsByType[$type] = $model->getTopItems($type, 10);
        }

        $this->matchingItems = $model->getMatchingItems(20);
        $this->activity      = $model->getActivityByMonth(12);
error_log('topItems type=0 : ' . json_encode($this->topItemsByType[0]));
error_log('matchingItems : ' . json_encode($this->matchingItems));
        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(
            Text::sprintf('COM_COLLECTOR_USERLISTSTATS_TITLE', $this->collection->name),
            'chart'
        );

        ToolbarHelper::link(
            'index.php?option=com_collector&view=userslists&collection=' . (int) $this->collection->id,
            Text::_('COM_COLLECTOR_USERLISTSTATS_BACK'),
            'icon-arrow-left'
        );
    }
}