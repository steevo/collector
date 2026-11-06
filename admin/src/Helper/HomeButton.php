<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Helper;

use Joomla\CMS\Button\ActionButton;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The FeaturedButton class.
 *
 * @since  4.0.0
 */
class HomeButton extends ActionButton
{
    /**
     * Configure this object.
     *
     * @return  void
     *
     * @since  4.0.0
     */
    protected function preprocess()
    {
        $this->addState(
            0,
            'home',
            'icon-unfeatured',
            Text::_('COM_COLLECTOR_TOGGLE_TO_HOME')
        );
        $this->addState(
            1,
            'unhome',
            'icon-color-featured icon-star',
            Text::_('COM_COLLECTOR_COLLECTION_HOME')
        );
    }

    /**
     * Render action button by item value.
     *
     * @param   integer|null  $value         Current value of this item.
     * @param   integer|null  $row           The row number of this item.
     * @param   array         $options       The options to override group options.
     * @param   string|Date   $featuredUp    The date which item featured up.
     * @param   string|Date   $featuredDown  The date which item featured down.
     *
     * @return  string  Rendered HTML.
     *
     * @since  4.0.0
     */
    public function render(?int $value = null, ?int $row = null, array $options = []): string
    {
        return parent::render($value, $row, $options);
    }
}
