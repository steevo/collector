<?php
namespace Steevo\Component\Collector\Administrator\View\Importhistory;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

class HtmlView extends BaseHtmlView
{
    /** @var array */
    protected $imports = [];

    /** @var object|null */
    protected $collection;

    /** @var int */
    protected $collectionId = 0;

    public function display($tpl = null): void
	{
		$app                = Factory::getApplication();
		$this->collectionId = $app->input->getInt('collection', 0);
		$model              = $this->getModel();
		$layout             = $this->getLayout();

		// Charger la collection
		$db = Factory::getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->select(['id', 'name'])
			   ->from($db->quoteName('#__collector'))
			   ->where($db->quoteName('id') . ' = ' . $this->collectionId)
		);
		$this->collection = $db->loadObject();

		if (!$this->collection) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_CONF_COL_FIRST'), 'error');
			return;
		}

		if ($layout === 'detail') {
			$this->importId = $app->input->getInt('import_id', 0);
			$this->import   = $model->getImport($this->importId);
			$this->items    = $model->getImportItems($this->importId, $this->collectionId);

			if (!$this->import) {
				$app->enqueueMessage(Text::_('COM_COLLECTOR_IMPORTHISTORY_NOT_FOUND'), 'error');
				$this->setLayout('default');
			}
		} else {
			$this->imports = $model->getImports($this->collectionId);
		}

		if (count($errors = $this->get('Errors'))) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		
		parent::display($tpl);
	}

	protected function addToolbar(): void
	{
		$layout = $this->getLayout();

		if ($layout === 'detail') {
			ToolbarHelper::title(
				Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_DETAIL_TITLE',
					$this->collection->name,
					HTMLHelper::_('date', $this->import->created ?? '', Text::_('DATE_FORMAT_LC2'))
				),
				'clock'
			);
			ToolbarHelper::link(
				Route::_('index.php?option=com_collector&view=importhistory&collection=' . $this->collectionId, false),
				'COM_COLLECTOR_IMPORTHISTORY_BACK_BTN',
				'arrow-left'
			);
		} else {
			ToolbarHelper::title(
				Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_TITLE', $this->collection->name),
				'clock'
			);
			ToolbarHelper::link(
				Route::_('index.php?option=com_collector&view=import&collection=' . $this->collectionId, false),
				'COM_COLLECTOR_IMPORT_BTN',
				'upload'
			);
			ToolbarHelper::link(
				Route::_('index.php?option=com_collector&view=items&collection=' . $this->collectionId, false),
				'COM_COLLECTOR_ITEMS_BTN',
				'list'
			);
		}
	}
}