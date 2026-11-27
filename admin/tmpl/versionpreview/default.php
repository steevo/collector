<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Aperçu d'une version d'un objet, ouvert dans la fenêtre intégrée de la page des versions.
 * Même présentation que la comparaison des versions.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$meta  = $this->meta;
$date  = HTMLHelper::_('date', $meta->modified, Text::_('DATE_FORMAT_LC6'));
$state = match ((int) $meta->state) {
    1       => ['text-success', 'icon-publish', 'COM_COLLECTOR_ITEM_HISTORY_PUBLISHED'],
    2       => ['text-muted', 'icon-archive', 'COM_COLLECTOR_ITEM_HISTORY_ARCHIVED'],
    3       => ['text-danger', 'icon-cancel', 'COM_COLLECTOR_ITEM_HISTORY_REFUSED_STATE'],
    default => ['text-warning', 'icon-clock', 'COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED'],
};
?>
<!DOCTYPE html>
<html lang="<?php echo $this->document->getLanguage(); ?>" dir="<?php echo $this->document->getDirection(); ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php // Liens relatifs du contenu (sommaire vers la liseuse…) : ceux du site, ouverts dans un nouvel onglet ?>
<base href="<?php echo Uri::root(); ?>" target="_blank">
<title><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PREVIEW'); ?></title>
<link rel="stylesheet" href="<?php echo Uri::root(true); ?>/media/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo Uri::root(true); ?>/media/system/css/system.css">
<style>
body{font-size:.875rem}
.preview-header{position:sticky;top:0;z-index:10;background:var(--bs-body-bg,#fff);border-bottom:2px solid #dee2e6;padding:.6rem 1rem}
.preview-table{table-layout:fixed;width:100%}
.col-label{width:22%}
.value-number{font-family:var(--bs-font-monospace,monospace);font-size:1rem}
.value-define{display:inline-block;background:#e9ecef;border-radius:4px;padding:2px 8px;font-size:.82rem}
.value-empty{color:#adb5bd;font-style:italic;font-size:.8rem}
.preview-table img{max-width:100%;height:auto}
</style>
<script src="<?php echo Uri::root(true); ?>/media/com_collector/js/version-dialog.js"></script>
</head>
<body>

  <div class="preview-header d-flex flex-wrap align-items-center gap-2">
    <span class="fw-semibold text-muted"><?php echo Text::_('COM_COLLECTOR_ITEM_HISTORY_PREVIEW'); ?></span>
    <span class="<?php echo $state[0]; ?>" title="<?php echo Text::_($state[2]); ?>">
      <span class="<?php echo $state[1]; ?>" aria-hidden="true"></span>
      <span class="visually-hidden"><?php echo Text::_($state[2]); ?></span>
    </span>
    <span class="text-body-secondary"><?php echo $this->escape($date); ?></span>
    <?php if (!empty($meta->author_name)) : ?>
      <span class="text-muted small">— <?php echo $this->escape($meta->author_name); ?></span>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="Joomla.collectorVersionDialog.closeSelf()">
      <?php echo Text::_('JTOOLBAR_CLOSE'); ?>
    </button>
  </div>

  <div class="p-3">
    <?php if (!empty($meta->modification)) : ?>
      <p class="text-muted fst-italic mb-3"><?php echo $this->escape($meta->modification); ?></p>
    <?php endif; ?>

    <table class="table table-sm table-bordered table-striped preview-table">
      <tbody>
        <?php foreach ($this->rows as $row) : ?>
        <tr>
          <th class="col-label" scope="row"><?php echo $this->escape($row['label']); ?></th>
          <td><?php echo $this->renderFieldValue($row); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
