<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\View\Versionscompare;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;

/**
 * Vue de comparaison côte à côte de deux versions d'un item.
 * Ouverte dans une fenêtre popup (tmpl=component).
 */
class HtmlView extends BaseHtmlView
{
    protected $comparison;
    protected $meta1;
    protected $meta2;
    protected $collection;
    protected $item;
    protected $v1;
    protected $v2;

    public function display($tpl = null)
    {
        $input = Factory::getApplication()->input;

        $this->collection = $input->getInt('collection');
        $this->item       = $input->getInt('item');
        $this->v1         = $input->getInt('v1');
        $this->v2         = $input->getInt('v2');

        /** @var \Steevo\Component\Collector\Administrator\Model\VersionscompareModel $model */
        $model = $this->getModel();

        $this->comparison = $model->getComparison($this->collection, $this->v1, $this->v2);
        $this->meta1      = $model->getVersionMeta($this->v1);
        $this->meta2      = $model->getVersionMeta($this->v2);

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        parent::display($tpl);
    }
 
    /**
     * Retourne le HTML d'une cellule de comparaison.
     *
     * Délègue entièrement à CollectorField::displayComparison() — méthode
     * polymorphique surchargeable dans chaque sous-classe de champ.
     * Aucun switch par type ici : la vue ignore le type du champ.
     *
     * @param  array   $row   Ligne issue de VersionscompareModel::getComparison()
     * @param  string  $side  'v1' (version A) ou 'v2' (version B)
     * @return string  HTML prêt à l'affichage
     */
    public function renderFieldValue(array $row, string $side): string
    {
        /** @var CollectorField $field */
        $field      = $row['field'];
        $value      = $row[$side];
        $otherValue = $row[($side === 'v1') ? 'v2' : 'v1'];
 
        $display = $field->displayComparison($value, $otherValue, $side);
 
        if ($display === false || $display === null || $display === '') {
            return '<span class="value-empty">' . Text::_('JNONE') . '</span>';
        }
 
        return $display;
    }
}