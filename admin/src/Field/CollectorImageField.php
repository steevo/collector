<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Administrator\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\MediaField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorImageField extends MediaField
{
	protected $type = 'CollectorImage';
	
	/**
     * The directory.
     *
     * @var    string
     * @since  3.2
     */
    protected $directory = 'collector';

    /**
     * Layout to render
     *
     * @var    string
     * @since  3.5
     */
    //protected $layout = 'joomla.form.field.collectormedia';

    protected static $initialised = false;
	
	// Saisie : champ Média de Joomla (chemin de l'image seul). L'ancienne saisie de Joomla 3, qui ajoutait
	// titre, alt et tailles séparés par des barres, a été retirée ; ces valeurs sont converties à la mise à jour.
}