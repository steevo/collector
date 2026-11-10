<?php
/**
 * Joomla! 4.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

namespace Steevo\Component\Collector\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Routing class of com_content
 *
 * @since  3.3
 */
class Router extends RouterView
{
    /**
     * Flag to remove IDs
     *
     * @var    boolean
     */
    protected $noIDs = false;

    /**
     * The category factory
     *
     * @var CategoryFactoryInterface
     *
     * @since  4.0.0
     */
    private $categoryFactory;

    /**
     * The category cache
     *
     * @var  array
     *
     * @since  4.0.0
     */
    private $categoryCache = [];

    /**
     * The db
     *
     * @var DatabaseInterface
     *
     * @since  4.0.0
     */
    private $db;

    /**
     * Content Component router constructor
     *
     * @param   SiteApplication           $app              The application object
     * @param   AbstractMenu              $menu             The menu object to work with
     * @param   CategoryFactoryInterface  $categoryFactory  The category object
     * @param   DatabaseInterface         $db               The database object
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
    {
        $this->categoryFactory = $categoryFactory;
        $this->db              = $db;

        $params      = ComponentHelper::getParams('com_collector');
        $this->noIDs = (bool) $params->get('sef_ids');
        // Liste de toutes les collections : pas d'identifiant. Avec une clé, un lien de menu
        // vers cette vue donnait un Itemid sous forme de tableau et une erreur 500.
        $collections  = new RouterViewConfiguration('collections');
        $this->registerView($collections);
        $collection = new RouterViewConfiguration('collection');
        $collection->setKey('id');
        $this->registerView($collection);
        $item = new RouterViewConfiguration('item');
        $item->setKey('id')->setParent($collection, 'collection');
        $this->registerView($item);
        $form = new RouterViewConfiguration('form');
        $form->setKey('id');
        $this->registerView($form);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Method to get the segment(s) for a category
     *
     * @param   string  $id     ID of the category to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getCategorySegment($id, $query)
    {
        $category = $this->getCategories(['access' => true])->get($id);

        if ($category) {
            $path    = array_reverse($category->getPath(), true);
            $path[0] = '1:root';

            if ($this->noIDs) {
                foreach ($path as &$segment) {
                    list($id, $segment) = explode(':', $segment, 2);
                }
            }

            return $path;
        }

        return [];
    }

    /**
     * Method to get the segment(s) for a category
     *
     * @param   string  $id     ID of the category to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getCategoriesSegment($id, $query)
    {
        return $this->getCategorySegment($id, $query);
    }

    /**
     * Method to get the segment(s) for a collection
     *
     * @param   string  $id     ID of the collection to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this collection
     */
    public function getCollectionSegment($id, $query)
    {
        if (!strpos($id, ':')) {
            $id      = (int) $id;
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__collector'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        if ($this->noIDs) {
            list($void, $segment) = explode(':', $id, 2);

            return [$void => $segment];
        }

        return [(int) $id => $id];
    }

    /**
     * Method to get the id for a collection
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getCollectionId($segment, $query)
    {
        if ($this->noIDs) {
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__collector'))
                ->where($this->db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $segment);
            $this->db->setQuery($dbquery);

            return (int) $this->db->loadResult();
        }

        return (int) $segment;
    }

    /**
     * Method to get the segment(s) for an item
     *
     * @param   string  $id     ID of the item to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getItemSegment($id, $query)
    {
        if (!strpos($id, ':')) {
            $id      = (int) $id;
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__collector_items'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        if ($this->noIDs) {
            list($void, $segment) = explode(':', $id, 2);

            return [$void => $segment];
        }

        return [(int) $id => $id];
    }

    /**
     * Method to get the id for an item
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getItemId($segment, $query)
    {
        if ($this->noIDs) {
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__collector_items'))
                ->where(
                    [
                        $this->db->quoteName('alias') . ' = :alias',
                        $this->db->quoteName('collection') . ' = :collection',
                    ]
                )
                ->bind(':alias', $segment)
                ->bind(':collection', $query['id'], ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            return (int) $this->db->loadResult();
        }

        return (int) $segment;
    }

    /**
     * Method to get the segment(s) for a form
     *
     * @param   string  $id     ID of the article form to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     *
     * @since   3.7.3
     */
    public function getFormSegment($id, $query)
    {
        return $this->getItemSegment($id, $query);
    }

    /**
     * Method to get the id for a category
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getCategoryId($segment, $query)
    {
        if (isset($query['id'])) {
            $category = $this->getCategories(['access' => false])->get($query['id']);

            if ($category) {
                foreach ($category->getChildren() as $child) {
                    if ($this->noIDs) {
                        if ($child->alias == $segment) {
                            return $child->id;
                        }
                    } else {
                        if ($child->id == (int) $segment) {
                            return $child->id;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Method to get the segment(s) for a category
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getCategoriesId($segment, $query)
    {
        return $this->getCategoryId($segment, $query);
    }

    /**
     * Method to get the segment(s) for an article
     *
     * @param   string  $segment  Segment of the article to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getArticleId($segment, $query)
    {
        if ($this->noIDs) {
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__content'))
                ->where(
                    [
                        $this->db->quoteName('alias') . ' = :alias',
                        $this->db->quoteName('catid') . ' = :catid',
                    ]
                )
                ->bind(':alias', $segment)
                ->bind(':catid', $query['id'], ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            return (int) $this->db->loadResult();
        }

        return (int) $segment;
    }

    /**
     * Method to get categories from cache
     *
     * @param   array  $options   The options for retrieving categories
     *
     * @return  CategoryInterface  The object containing categories
     *
     * @since   4.0.0
     */
    private function getCategories(array $options = []): CategoryInterface
    {
        $key = serialize($options);

        if (!isset($this->categoryCache[$key])) {
            $this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
        }

        return $this->categoryCache[$key];
    }
}

defined('_JEXEC') or die;

function CollectorBuildRoute(&$query)
{
	$segments = array();

	// get a menu item based on Itemid or currently active
	$app		= Factory::getApplication();
	$menu		= $app->getMenu();
	// we need a menu item.  Either the one specified in the query, or the current active one if none specified
	if (empty($query['Itemid'])) {
		$menuItem = $menu->getActive();
		$menuItemGiven = false;
	} else {
		$menuItem = $menu->getItem($query['Itemid']);
		$menuItemGiven = true;
		// $query = $menuItem->query;
	}
	
	// if($menuItemGiven)
	// {
		// $queryMenu = $menuItem->query;
		// if(isset($queryMenu['reset']))
		// {
			// $reset = $queryMenu['reset'];
			// unset($queryMenu['reset']);
			// if ($reset == 0)
			// {
				// $segments[] = 'search';
			// } else {
				// $segments[] = 'nosearch';
			// }
		// }
	// }
	
	if (isset($query['view'])) {
		$view = $query['view'];
		$segments[] = $view;
		unset($query['view']);
	}
	else {
		// we need to have a view in the query or it is an invalid URL
		return $segments;
	}
	
	if ($view == 'collection')
	{
		if (isset($query['id'])){
			// Make sure we have the id and the alias
			if (strpos($query['id'], ':') === false) {
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector')
					->where('id='.(int)$query['id'])
				);
				$alias = $db->loadResult();
				$query['id'] = $query['id'].':'.$alias;
			}
		} else {
			// we should have these two set for this view.  If we don't, it is an error
			return $segments;
		}
		
		$segments[] = $query['id'];
		
		unset($query['id']);
	}
	if ($view == 'item')
	{
		if (isset($query['collection']) && isset($query['id'])){
			// Make sure we have the id and the alias
			if (strpos($query['collection'], ':') === false) {
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector')
					->where('id='.(int)$query['collection'])
				);
				$alias = $db->loadResult();
				$query['collection'] = $query['collection'].':'.$alias;
			}
			if (strpos($query['id'], ':') === false) {
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector_items')
					->where('id='.(int)$query['id'])
				);
				$alias = $db->loadResult();
				$query['id'] = $query['id'].':'.$alias;
			}
		} else {
			// we should have these two set for this view.  If we don't, it is an error
			return $segments;
		}
		
		$segments[] = $query['collection'];
		$segments[] = $query['id'];
		
		unset($query['collection']);
		unset($query['id']);
	}
	if ($view == 'itemform')
	{
		if (isset($query['collection'])){
			// Make sure we have the id and the alias
			if (strpos($query['collection'], ':') === false) {
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				$aquery = $db->setQuery($db->getQuery(true)
					->select('alias')
					->from('#__collector')
					->where('id='.(int)$query['collection'])
				);
				$alias = $db->loadResult();
				$query['collection'] = $query['collection'].':'.$alias;
			}
		} else {
			// we should have these two set for this view.  If we don't, it is an error
			return $segments;
		}
		
		$segments[] = $query['collection'];
		
		unset($query['collection']);
	}

	if(isset($query['reset']))
	{
		$reset = $query['reset'];
		unset($query['reset']);
		if ($reset == 0)
		{
			$segments[] = 'search';
		} else {
			$segments[] = 'nosearch';
		}
	}
	
	return $segments;
}

function CollectorParseRoute($segments)
{
	$vars = array();

	$vars['option'] = 'com_collector';

	//Get the active menu item.
	$app	= Factory::getApplication();
	$menu	= $app->getMenu();
	$item	= $menu->getActive();

	// Count route segments
	$count = count($segments);

	// if( $count == 1 )
	// {
		// if( $segments[0]=='nosearch' ){
			// $vars = $item->query;
			// $vars['reset'] = 1;
			// return $vars;
		// }
		// if( $segments[0]=='search' ){
			// $vars = $item->query;
			// $vars['reset'] = 0;
			// return $vars;
		// }
	// }
	
	//Standard routing for collection
	$vars['view']  = $segments[0];
	if ( $vars['view'] == 'collection' )
	{
		$vars['id'] = $segments[1];
	}
	else if ( $vars['view'] == 'item' )
	{
		$vars['collection'] = $segments[1];
		$vars['id'] = $segments[2];
		if ( isset($segments[3]) ){
			$vars['reset'] = $segments[3];
		}
	}
	else if ( $vars['view'] == 'itemform' )
	{
		$vars['layout'] = 'edit';
		$vars['collection'] = $segments[1];
	}

	return $vars;
}