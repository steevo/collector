<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\View\Versionpreview;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Steevo\Component\Collector\Administrator\Model\VersionscompareModel;

/**
 * Aperçu d'une version d'un objet (page des versions de l'administration), dans une fenêtre
 * (tmpl=component). Les valeurs sont celles de la comparaison des versions, pour une seule version.
 */
class HtmlView extends BaseHtmlView
{
    protected $rows = [];
    protected $meta;
    protected $collection;
    protected $item;
    protected $version;

    public function display($tpl = null)
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $user  = $app->getIdentity();

        $this->collection = $input->getInt('collection');
        $this->item       = $input->getInt('item');
        $this->version    = $input->getInt('v_id');

        if (!$user->authorise('core.manage', 'com_collector')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = new VersionscompareModel();

        // Même version des deux côtés : on n'en garde qu'un
        $this->rows = $model->getComparison($this->collection, $this->version, $this->version) ?: [];
        $this->meta = $model->getVersionMeta($this->version);

        if (!$this->meta || (int) ($this->meta->item ?? $this->item) !== $this->item) {
            throw new \Exception(Text::_('COM_COLLECTOR_ITEM_HISTORY_VERSION_NOT_FOUND'), 404);
        }

        parent::display($tpl);
    }

    /**
     * Valeur affichée d'un champ (comme dans la comparaison, sans différence à souligner)
     */
    public function renderFieldValue(array $row): string
    {
        $display = $row['field']->displayComparison($row['v1'], $row['v1'], 'v1');

        if ($display === false || $display === null || $display === '') {
            return '<span class="value-empty">' . Text::_('JNONE') . '</span>';
        }

        return $display;
    }
}
