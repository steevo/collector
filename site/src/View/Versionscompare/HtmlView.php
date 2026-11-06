<?php
namespace Steevo\Component\Collector\Site\View\Versionscompare;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Uri\Uri;
use Steevo\Component\Collector\Administrator\Model\VersionscompareModel;

class HtmlView extends BaseHtmlView
{
    protected $comparison;
    protected $meta1;
    protected $meta2;
    protected $collection;
    protected $item;
    protected $v1;
    protected $v2;
    protected $canValidate = false;

    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        $input = $app->input;

		$this->document->addStyleSheet(
			Uri::root(true) . '/media/com_collector/css/versionscompare.css'
		);

        $this->collection = $input->getInt('collection');
        $this->item       = $input->getInt('item');
        $this->v1         = $input->getInt('v1');
        $this->v2         = $input->getInt('v2');

        // Vérifier les droits d'édition
        $this->canValidate = $user->authorise('core.edit', 'com_collector.collection.' . $this->collection)
                          || $user->authorise('core.edit', 'com_collector');

        // Utiliser le modèle backend (logique partagée)
        $model = new VersionscompareModel();

        $this->comparison = $model->getComparison($this->collection, $this->v1, $this->v2);
		
		if ($this->comparison === [] && $model->getError()) {
			throw new GenericDataException($model->getError(), 500);
		}

        $this->meta1      = $model->getVersionMeta($this->v1);
        $this->meta2      = $model->getVersionMeta($this->v2);

        parent::display($tpl);
    }

    public function renderFieldValue(array $row, string $side): string
    {
        $field      = $row['field'];
        $value      = $row[$side];
        $otherValue = $row[($side === 'v1') ? 'v2' : 'v1'];

        $display = $field->displayComparison($value, $otherValue, $side);

        if ($display === false || $display === null || $display === '') {
            return '<span class="text-muted fst-italic">' . Text::_('JNONE') . '</span>';
        }

        return $display;
    }
}