<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * HTML Collectors View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewCollectors extends JViewLegacy
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$this->canDo	= CollectorHelper::getActions();
		
		CollectorHelper::addSubmenu('collectors');
		
		$user = JFactory::getUser();
		
		// Get data from the model
		// $collectorVersions = $this->get( 'versions' );
		
		$this->user = $user;
		// $this->collectorVersions = $collectorVersions;
		
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		
		parent::display($tpl);
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_COLLECTOR_COLLECTOR_MANAGER'), 'cpanel');

		if ($this->canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_collector');
		}
		
		JHtmlSidebar::setAction('index.php?option=com_collector&view=collectors');
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
		// $last_version = $this->collectorVersions;
		$filename = JPATH_ADMINISTRATOR . '/components/com_collector/manifest.xml';
		if (file_exists($filename) && is_readable($filename))
		{
			//get the version number
			$xml = JFactory::getXML($filename);
			$your_version = (string)$xml->version;
		} else {
			$your_version = JText::_('COM_COLLECTOR_UNKNOWN');
		}
		
		// if (($last_version != JText::_('COM_COLLECTOR_UNKNOWN')) && ($your_version != JText::_('COM_COLLECTOR_UNKNOWN')))
		// {
			// $compare = version_compare($your_version, $last_version);
			// if ( $compare == -1 )
			// {
				// $version_message = '<img src="components/com_collector/assets/images/cross.png" /> ' . JText::_('COM_COLLECTOR_VERSION_TO_UPDATE') . '<br /><a href="http://joomlacode.org/gf/project/collector/frs/?action=FrsReleaseBrowse&frs_package_id=4710" target="blank" >'.JText::_('COM_COLLECTOR_CHECK_VERSIONS').'</a>';
			// } else if ( $compare == 0 ) {
				// $version_message = '<img src="components/com_collector/assets/images/tick.png" /> ' . JText::_('COM_COLLECTOR_VERSION_UP_TO_DATE');
			// } else {
				// $version_message = '<img src="components/com_collector/assets/images/bullet_error.png" /> ' . JText::_('COM_COLLECTOR_VERSION_TEST');
			// }
		// }
		
		$pane = '<table class="paramlist admintable" cellspacing="1" width="100%"><tbody>';
		$pane .= '<tr>
		<td class="paramlist_key" width="40%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . JText::_('COM_COLLECTOR_YOUR_VERSION') . '</label></span></td>
		<td class="paramlist_value">' . $your_version . '</td>
		</tr>
		<tr>
		</table>';
		// <td class="paramlist_key" width="40%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . JText::_('COM_COLLECTOR_LAST_VERSION') . '</label></span></td>
		// <td class="paramlist_value">' . $last_version . '</td>
		// </tr>
		// <tr>
		// <td colspan=2 class="paramlist_value" align="center">' . $version_message . '</td>
		// </tr>
		// </tbody>
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
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . JText::_('COM_COLLECTOR_SITE') . '</label></span></td>
		<td class="paramlist_value"><a href="http://www.steevo.fr" target="blank" >http://www.steevo.fr</a></td>
		</tr>
		<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . JText::_('COM_COLLECTOR_FORUM') . '</label></span></td>
		<td class="paramlist_value"><a href="http://forum.steevo.fr" target="blank" >http://forum.steevo.fr</a></td>
		</tr>
		<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . JText::_('COM_COLLECTOR_GITHUB') . '</label></span></td>
		<td class="paramlist_value"><a href="https://github.com/steevo/collector" target="blank" >https://github.com/</a></td>
		</tr>
		<tr>
		<td class="paramlist_key" width="50%"><span class="editlinktip"><label id="detailscreated_by-lbl" for="detailscreated_by" class="hasTip">' . JText::_('COM_COLLECTOR_CONTACT') . '</label></span></td>
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
		<strong>' . JText::_('COM_COLLECTOR_THANKS') . '</strong>
		<ul>
		<li><a href="http://www.cyrill-baur.ch/" target="blank" >Cyrill Baur</a> (' . JText::_('COM_COLLECTOR_GERMAN_TRANSLATION') . ')
		<li>Sebastian Pućko (' . JText::_('COM_COLLECTOR_POLISH_TRANSLATION') . ')
		<li>Mora (' . JText::_('COM_COLLECTOR_SPANISH_TRANSLATION') . ')
		</ul>
		<strong>' . JText::_('COM_COLLECTOR_LICENSE') . '</strong>
		<br/>' . JText::_('COM_COLLECTOR_DISTRIBUTED') . ' <a href="http://www.gnu.org/licenses/gpl-2.0.html" >' . JText::_('COM_COLLECTOR_GPLV2') . '<a>
		</td>
		</tr>
		</tbody>
		</table>';
		return $pane;
	}
}