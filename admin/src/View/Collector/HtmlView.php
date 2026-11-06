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

namespace Steevo\Component\Collector\Administrator\View\Collector;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\Sidebar;

/**
 * HTML Collectors View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		// $this->canDo	= CollectorHelper::getActions();
		$this->canDo	= ContentHelper::getActions('com_collector');
		
		// CollectorHelper::addSubmenu('collectors');
		
		$user = Factory::getApplication()->getIdentity();
		
		// Get data from the model
		$collectorLastVersion = $this->get( 'lastVersion' );
		
		$this->user = $user;
		$this->collectorLastVersion = $collectorLastVersion;
		
		$this->addToolbar();
		
		parent::display($tpl);
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		ToolBarHelper::title(Text::_('COM_COLLECTOR_COLLECTOR_MANAGER'), 'cpanel');

		if ($this->canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_collector');
		}
		
		Sidebar::setAction('index.php?option=com_collector&view=collectors');
	}
	
	/**
	 * Method to display version slide in dashboard
	 *
	 * @access	public
	 * @return	string	HTML slide
	 */
	function compare_version_panel()
	{
		$version_message = '';
		$last_version = $this->collectorLastVersion;
		$filename = JPATH_ADMINISTRATOR . '/components/com_collector/collector.xml';
		if (file_exists($filename) && is_readable($filename))
		{
			//get the version number
			$xml = simplexml_load_file($filename);
			$your_version = (string)$xml->version;
		} else {
			$your_version = Text::_('COM_COLLECTOR_UNKNOWN');
		}
		
		if (($last_version != Text::_('COM_COLLECTOR_UNKNOWN')) && ($your_version != Text::_('COM_COLLECTOR_UNKNOWN')))
		{
			$compare = version_compare($your_version, $last_version);
			if ( $compare == -1 )
			{
				$version_message = '<img src="components/com_collector/assets/images/cross.png" /> ' . Text::_('COM_COLLECTOR_VERSION_TO_UPDATE') . '<br /><a href="index.php?option=com_installer&view=update" >'.Text::_('COM_COLLECTOR_CHECK_VERSIONS').'</a>';
			} else if ( $compare == 0 ) {
				$version_message = '<img src="components/com_collector/assets/images/tick.png" /> ' . Text::_('COM_COLLECTOR_VERSION_UP_TO_DATE');
			} else {
				$version_message = '<img src="components/com_collector/assets/images/bullet_error.png" /> ' . Text::_('COM_COLLECTOR_VERSION_TEST');
			}
		}
		
		$pane = '<table class="paramlist admintable" cellspacing="1" width="100%"><tbody>';
		$pane .= '<tr>
		<td class="paramlist_key" width="40%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . Text::_('COM_COLLECTOR_YOUR_VERSION') . '</label></span></td>
		<td class="paramlist_value">' . $your_version . '</td>
		</tr>
		<tr>
		<td class="paramlist_key" width="40%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . Text::_('COM_COLLECTOR_LAST_VERSION') . '</label></span></td>
		<td class="paramlist_value">' . $last_version . '</td>
		</tr>
		<tr>
		<td colspan=2 class="paramlist_value" align="center">' . $version_message . '</td>
		</tr>
		</tbody>
		</table>';
		return $pane;
	}
	
	/**
	 * Method to display support slide in dashboard
	 *
	 * @access	public
	 * @return	string	HTML slide
	 */
	function support_panel()
	{
		$pane = '<table class="paramlist admintable" cellspacing="1" width="100%"><tbody>';
		$pane .= '<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . Text::_('COM_COLLECTOR_SITE') . '</label></span></td>
		<td class="paramlist_value"><a href="https://www.steevo.fr" target="blank" >https://www.steevo.fr</a></td>
		</tr>
		<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . Text::_('COM_COLLECTOR_FORUM') . '</label></span></td>
		<td class="paramlist_value"><a href="https://forum.steevo.fr" target="blank" >https://forum.steevo.fr</a></td>
		</tr>
		<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . Text::_('COM_COLLECTOR_GITHUB') . '</label></span></td>
		<td class="paramlist_value"><a href="https://github.com/steevo/collector" target="blank" >https://github.com/</a></td>
		</tr>
		<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . Text::_('COM_COLLECTOR_CONTACT') . '</label></span></td>
		<td class="paramlist_value"><a href="mailto:steevo@steevo.fr">steevo@steevo.fr</a></td>
		</tr>
		</tbody>
		</table>';
		return $pane;
	}
	
	/**
	 * Method to display about slide in dashboard
	 *
	 * @access	public
	 * @return	string	HTML slide
	 */
	function about_panel()
	{
		$pane = '<table class="paramlist admintable" cellspacing="1" width="100%"><tbody>';
		$pane .= '<tr>
		<td>
		<strong>' . Text::_('COM_COLLECTOR_THANKS') . '</strong>
		<ul>
		<li><a href="http://www.cyrill-baur.ch/" target="blank" >Cyrill Baur</a> (' . Text::_('COM_COLLECTOR_GERMAN_TRANSLATION') . ')</li>
		<li>Sebastian Pućko (' . Text::_('COM_COLLECTOR_POLISH_TRANSLATION') . ')</li>
		<li>Mora (' . Text::_('COM_COLLECTOR_SPANISH_TRANSLATION') . ')</li>
		</ul>
		<strong>' . Text::_('COM_COLLECTOR_LIBRARY') . '</strong>
		<ul>
		<li><a href="https://github.com/PHPOffice/PHPExcel" target="blank" >PHPExcel</a> (' . Text::_('COM_COLLECTOR_LIBRARY_EXCEL') . ')</li>
		</ul>
		<strong>' . Text::_('COM_COLLECTOR_LICENSE') . '</strong>
		<br/>' . Text::_('COM_COLLECTOR_DISTRIBUTED') . ' <a href="http://www.gnu.org/licenses/gpl-2.0.html" >' . Text::_('COM_COLLECTOR_GPLV2') . '<a>
		</td>
		</tr>
		</tbody>
		</table>';
		return $pane;
	}
}