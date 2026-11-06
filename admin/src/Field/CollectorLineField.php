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

use Joomla\CMS\Form\Field\TextField;

defined('_JEXEC') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorLineField extends TextField
{
	protected $type = 'CollectorLine';
}