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

use Joomla\CMS\Form\Field\RadioField;

defined('JPATH_BASE') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class YesnoimpossibleField extends RadioField
{
	/**
     * The form field type.
     *
     * @var    string
     * @since  1.7.0
     */
    protected $type = 'Yesnoimpossible';

    /**
     * Name of the layout being used to render the field
     *
     * @var    string
     * @since  3.5
     */
    protected $layout = 'joomla.form.field.radio.yesnoimpossible';
	
}
?>