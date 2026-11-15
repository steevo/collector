<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Objets à valider d'une collection : même présentation que les autres tableaux de Collector
 * (couleurs du template, mode sombre, fiches sur mobile).
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::stylesheet(Uri::base() . 'components/com_collector/assets/css/collection.css', ['version' => 'auto']);

$itemLabel    = Text::_('COM_COLLECTOR_ITEM');
$typeLabel    = Text::_('COM_COLLECTOR_TO_VALIDATE_TYPE');
$pendingLabel = Text::_('COM_COLLECTOR_VERSIONS_TO_VALIDATE');
?>

<div class="collector-tovalidate category-list<?php echo $this->pageclass_sfx ?? ''; ?>">

  <h2>
    <?php echo Text::sprintf('COM_COLLECTOR_ITEMS_TO_VALIDATE_TITLE', $this->escape($this->collection->name)); ?>
  </h2>

  <p class="mb-3">
    <a href="<?php echo Route::_('index.php?option=com_collector&view=collection&id=' . $this->collection->id); ?>"
       class="btn btn-sm btn-outline-secondary">
      <span class="icon-arrow-left me-1" aria-hidden="true"></span>
      <?php echo Text::_('COM_COLLECTOR_RETURN_TO_LIST'); ?>
    </a>
  </p>

  <?php if (empty($this->items)): ?>
    <div class="alert alert-info">
      <?php echo Text::_('COM_COLLECTOR_NO_ITEMS_TO_VALIDATE'); ?>
    </div>
  <?php else: ?>
    <p class="counter mb-2">
      <?php echo Text::plural('COM_COLLECTOR_ITEMS_TO_VALIDATE_COUNT', count($this->items)); ?>
    </p>

    <table class="category table table-striped table-bordered table-hover collector-table-stack collector-tovalidate-table">
      <thead>
        <tr>
          <th><?php echo $itemLabel; ?></th>
          <th><?php echo $typeLabel; ?></th>
          <th class="text-center"><?php echo $pendingLabel; ?></th>
          <th><span class="visually-hidden"><?php echo Text::_('COM_COLLECTOR_SEE_VERSIONS'); ?></span></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($this->items as $item):
        // Titre à partir des champs de titre de la collection (version validée), sinon le titre enregistré
        $title = '';
        foreach ($this->fields as $field) {
          $col = $field->_field->tablecolumn;
          $part = trim((string) $field->displayInTitle($item->$col ?? ''));
          if ($part !== '') {
            $title .= ($title !== '' ? ' ' : '') . $part;
          }
        }
        if ($title === '') {
          $title = $item->fulltitle ?: '#' . $item->id;
        }

        // Proposition (objet jamais validé, une seule version) : sa fiche s'ouvre sur le formulaire de
        // modification, pour la relire, la corriger et la valider ; modification : le tableau des versions
        $isProposal = empty($item->active_version);
        $targetUrl  = $isProposal
          ? Route::_('index.php?option=com_collector&view=item&collection=' . (int) $item->collection . '&id=' . (int) $item->id . ':' . $item->alias)
          : Route::_('index.php?option=com_collector&view=itemversions&collection=' . (int) $item->collection . '&item=' . (int) $item->id);
      ?>
        <tr>
          <td data-label="<?php echo $this->escape($itemLabel); ?>" style="vertical-align:middle">
            <a href="<?php echo $targetUrl; ?>"><?php echo $this->escape($title); ?></a>
          </td>
          <td data-label="<?php echo $this->escape($typeLabel); ?>" style="vertical-align:middle">
            <?php if ($isProposal) : ?>
            <span class="badge collector-status collector-status-pending">
              <span class="icon-plus me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_TO_VALIDATE_PROPOSAL'); ?>
            </span>
            <?php else : ?>
            <span class="badge collector-status collector-status-archived">
              <span class="icon-edit me-1" aria-hidden="true"></span><?php echo Text::_('COM_COLLECTOR_TO_VALIDATE_MODIFICATION'); ?>
            </span>
            <?php endif; ?>
          </td>
          <td data-label="<?php echo $this->escape($pendingLabel); ?>" class="text-center" style="vertical-align:middle">
            <span class="badge collector-count"><?php echo (int) $item->to_validate; ?></span>
          </td>
          <td data-label="" class="text-end" style="vertical-align:middle">
            <a href="<?php echo $targetUrl; ?>" class="btn btn-sm btn-primary">
              <?php echo Text::_($isProposal ? 'COM_COLLECTOR_PROPOSAL_OPEN' : 'COM_COLLECTOR_SEE_VERSIONS'); ?>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <?php echo $this->pagination->getListFooter(); ?>
  <?php endif; ?>

</div>
