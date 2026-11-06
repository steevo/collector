<?php
namespace Steevo\Component\Collector\Site\View\Itemversions;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $collection;
    protected $item;
    protected $activeVersionId;
    protected $oldestPendingId;
    protected $state;
	protected $pagination = null;

    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        /** @var \Steevo\Component\Collector\Site\Model\ItemversionsModel $model */
        $model = $this->getModel();

        $this->items           = $this->get('Items');
		$this->pagination = $this->get('Pagination');
        $this->state           = $this->get('State');
        $this->activeVersionId = $this->get('ActiveVersionId');
        $this->oldestPendingId = $this->get('OldestPendingVersionId');
        $this->collection = $this->get('Collection');
        $this->item       = $this->get('Item');
		
		$this->pagination->setAdditionalUrlParam('item', $this->item->id ?? 0);
		$this->pagination->setAdditionalUrlParam('collection', $this->collection->id ?? 0);

        if (!$this->collection) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_ERROR_COLLECTION_NOT_FOUND'), 'error');
            return;
        }

        // Contrôle d'accès — doit pouvoir éditer pour voir les versions
        if (!$user->authorise('core.edit', 'com_collector.collection.' . $this->collection->id)
            && !$user->authorise('core.edit', 'com_collector')
            && !$user->authorise('core.edit.own', 'com_collector')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            return;
        }

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->_prepareDocument();
        parent::display($tpl);
    }

    protected function _prepareDocument(): void
    {
        $app     = Factory::getApplication();
		$pathway = $app->getPathway();

		// Ajouter l'objet au fil d'Ariane
		if ($this->item) {
			$pathway->addItem(
				$this->item->fulltitle ?: '#' . $this->item->id,
				Route::_(
					'index.php?option=com_collector&view=item'
					. '&collection=' . ($this->collection->id ?? 0)
					. '&id=' . $this->item->id
				)
			);
			$pathway->addItem(
				Text::_('COM_COLLECTOR_ITEM_VERSIONS'),
				''
			);
		}
		
		$title = $this->item
            ? Text::sprintf('COM_COLLECTOR_ITEM_VERSIONS_TITLE',
                $this->item->fulltitle ?: '#' . $this->item->id)
            : Text::_('COM_COLLECTOR_ITEM_VERSIONS');

        Factory::getApplication()->getDocument()->setTitle($title);
    }
}