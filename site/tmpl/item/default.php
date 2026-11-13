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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Steevo\Component\Collector\Administrator\Helper\ContactHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

PluginHelper::importPlugin('collector');

HTMLHelper::stylesheet(Uri::base() . 'components/com_collector/assets/css/tabs.css');

HTMLHelper::_('bootstrap.modal', '.modal');

$app = Factory::getApplication();
$Itemid = $app->input->get('Itemid', 0, 'get');

if ($this->params->get('comments') != 0)
{
	if ( ($this->params->get('comments') == 1) && (file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_jcomments'.DIRECTORY_SEPARATOR.'jcomments.php')) )
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_jcomments'.DIRECTORY_SEPARATOR.'jcomments.php');
		$count_comments = JComments::getCommentsCount($this->item->id, 'com_collector');
	} else {
		$count_comments = 0;
	}
}
if ( $this->params->get('navigation',1) ) {
	echo '<div width="100%" align="right">'.$this->navigation.'</div>';
}
?>
<?php if (!empty($this->userlistsStatus)): ?>
<div class="collector-userlists-buttons d-flex flex-wrap gap-2 mb-3" id="userlists-buttons">
    <?php foreach ($this->userlistsStatus as $userslistId => $list): ?>
    <?php
        $inList  = $list['in_list'];
        $type    = (int) $list['type'];
        // Icône selon le type
        $icon = match($type) {
            0 => 'fa fa-heart',           // Je recherche
            1 => 'fa fa-exchange-alt',            // À échanger
            2 => 'fa fa-bookmark',        // Ma collection
            default => 'fa fa-plus'
        };
        $btnClass = $inList ? 'btn-success' : 'btn-outline-secondary';
    ?>
    <button type="button"
            class="btn btn-sm <?php echo $btnClass; ?> btn-userlist"
            data-userslist="<?php echo (int) $userslistId; ?>"
            data-item="<?php echo (int) $this->item->id; ?>"
            data-inlist="<?php echo $inList ? '1' : '0'; ?>"
            data-note="<?php echo htmlspecialchars($list['note'], ENT_QUOTES); ?>"
            onclick="toggleUserlist(this)">
        <span class="<?php echo $icon; ?> me-1"></span>
        <?php echo htmlspecialchars($list['name'], ENT_QUOTES); ?>
    </button>
    <?php endforeach; ?>
</div>

<?php /* Modale note */ ?>
<div class="modal fade" id="modal-userlist-note" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE_TITLE'); ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="userlist-note-input" class="form-control form-control-sm" rows="3"
                          placeholder="<?php echo Text::_('COM_COLLECTOR_USERLIST_NOTE_PLACEHOLDER'); ?>"></textarea>
            </div>
            <div class="modal-footer p-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="saveUserlistNote()">
                    <?php echo Text::_('JSAVE'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var USERLIST_TOKEN    = '<?php echo \Joomla\CMS\Session\Session::getFormToken(); ?>';
var USERLIST_BASE_URL = '<?php echo \Joomla\CMS\Uri\Uri::base(true); ?>/index.php?option=com_collector&';
var _currentNoteBtn  = null;

async function toggleUserlist(btn) {
    const userslistId = btn.dataset.userslist;
    const itemId      = btn.dataset.item;
    const inList      = btn.dataset.inlist === '1';
    const task        = inList ? 'useritem.remove' : 'useritem.add';

    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('userslist', userslistId);
        fd.append('item', itemId);
        fd.append(USERLIST_TOKEN, '1');

        const resp   = await fetch(USERLIST_BASE_URL + 'task=' + task, {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();

        if (result.success) {
            const nowInList = !inList;
            btn.dataset.inlist = nowInList ? '1' : '0';
            btn.classList.toggle('btn-success',          nowInList);
            btn.classList.toggle('btn-outline-secondary', !nowInList);

            // Si ajouté → proposer une note
            if (nowInList) {
                _currentNoteBtn = btn;
                document.getElementById('userlist-note-input').value = btn.dataset.note || '';
                new bootstrap.Modal(document.getElementById('modal-userlist-note')).show();
            }
        }
    } catch(e) {
        console.error(e);
    } finally {
        btn.disabled = false;
    }
}

async function saveUserlistNote() {
    if (!_currentNoteBtn) return;

    const note        = document.getElementById('userlist-note-input').value;
    const userslistId = _currentNoteBtn.dataset.userslist;
    const itemId      = _currentNoteBtn.dataset.item;

    const fd = new FormData();
    fd.append('userslist', userslistId);
    fd.append('item', itemId);
    fd.append('note', note);
    fd.append(USERLIST_TOKEN, '1');

    try {
        const resp   = await fetch(USERLIST_BASE_URL + 'task=useritem.updatenote', {
            method  : 'POST',
            body    : fd,
            headers : {'X-Requested-With': 'XMLHttpRequest'}
        });
        const result = await resp.json();
        if (result.success) {
            _currentNoteBtn.dataset.note = note;
            bootstrap.Modal.getInstance(document.getElementById('modal-userlist-note'))?.hide();
        }
    } catch(e) {
        console.error(e);
    }
}
</script>
<?php endif; ?>
<?php
$showCommentsTab = ($this->params->get('comments') != 0) && ($this->params->get('comments_display_mode') == 1);
$showEditTab     = $this->params->get('allow_front_mod') && $this->item->params->get('access-edit');
$showWantTab     = $this->user->id && !empty($this->userlistWant);
$showExchangeTab = $this->user->id && !empty($this->userlistExchange);

// Fiche seule : pas d'onglet unique inutile, la fiche est affichée directement.
if (!$showCommentsTab && !$showEditTab && !$showWantTab && !$showExchangeTab) :
	echo $this->loadTemplate($this->itemTemplate ? 'template' : 'details');
else :

echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => '#details'));

echo HTMLHelper::_('uitab.addTab', 'myTab', '#details', Text::_('COM_COLLECTOR_ARTICLE'));

echo $this->loadTemplate($this->itemTemplate ? 'template' : 'details');

echo HTMLHelper::_('uitab.endTab');

if ($showCommentsTab)
{
	echo HTMLHelper::_('uitab.addTab', 'myTab', '#comments', Text::sprintf('COM_COLLECTOR_COMMENTS',$count_comments));
	echo $this->loadTemplate('comments');
	echo HTMLHelper::_('uitab.endTab');
}

if ($showEditTab)
{
	echo HTMLHelper::_('uitab.addTab', 'myTab', 'index.php?option=com_collector&view=itemform&collection='.$this->item->collection.'&id='.$this->item->id.'&Itemid='.$Itemid, Text::_('COM_COLLECTOR_EDIT'));

	echo $this->loadTemplate('edit');

	echo HTMLHelper::_('uitab.endTab');
}

if ($showWantTab) {
    echo HTMLHelper::_('uitab.addTab', 'myTab', '#tab-want',
        Text::sprintf('COM_COLLECTOR_USERLIST_TAB_WANT', count($this->userlistWant)));
    // contenu inline
    ob_start();
    foreach ($this->userlistWant as $match):
		$subject = Text::sprintf('COM_COLLECTOR_CONTACT_SUBJECT', $this->item->fulltitle ?? '');
        $contacts = ContactHelper::getAllContactInfo((int) $match->user_id, $subject);
        ?>
        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
            <div class="flex-grow-1">
                <strong><?php echo htmlspecialchars($match->user_name, ENT_QUOTES); ?></strong>
                <?php if (!empty($match->userlist_note)): ?>
                <div class="small text-muted fst-italic"><?php echo htmlspecialchars($match->userlist_note, ENT_QUOTES); ?></div>
                <?php endif; ?>
            </div>
			<?php foreach ($contacts as $contact): ?>
            <?php if ($contact): ?>
            <a href="<?php echo htmlspecialchars($contact['url'], ENT_QUOTES); ?>"
               class="btn btn-sm btn-outline-primary" target="_blank">
                <span class="<?php echo $contact['icon']; ?> me-1"></span>
                <?php echo htmlspecialchars($contact['label'], ENT_QUOTES); ?>
            </a>
            <?php endif; ?>
			<?php endforeach; ?>
        </div>
        <?php
    endforeach;
    echo ob_get_clean();
    echo HTMLHelper::_('uitab.endTab');
}

if ($showExchangeTab) {
    echo HTMLHelper::_('uitab.addTab', 'myTab', '#tab-exchange',
        Text::sprintf('COM_COLLECTOR_USERLIST_TAB_EXCHANGE', count($this->userlistExchange)));
    ob_start();
    foreach ($this->userlistExchange as $match):
		$subject = Text::sprintf('COM_COLLECTOR_CONTACT_SUBJECT', $this->item->fulltitle ?? '');
        $contacts = ContactHelper::getAllContactInfo((int) $match->user_id, $subject);
        ?>
        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
            <div class="flex-grow-1">
                <strong><?php echo htmlspecialchars($match->user_name, ENT_QUOTES); ?></strong>
                <?php if (!empty($match->userlist_note)): ?>
                <div class="small text-muted fst-italic"><?php echo htmlspecialchars($match->userlist_note, ENT_QUOTES); ?></div>
                <?php endif; ?>
            </div>
			<?php foreach ($contacts as $contact): ?>
            <?php if ($contact): ?>
            <a href="<?php echo htmlspecialchars($contact['url'], ENT_QUOTES); ?>"
               class="btn btn-sm btn-outline-primary" target="_blank">
                <span class="<?php echo $contact['icon']; ?> me-1"></span>
                <?php echo htmlspecialchars($contact['label'], ENT_QUOTES); ?>
            </a>
            <?php endif; ?>
			<?php endforeach; ?>
        </div>
        <?php
    endforeach;
    echo ob_get_clean();
    echo HTMLHelper::_('uitab.endTab');
}

echo HTMLHelper::_('uitab.endTabSet');

endif;
?>

<br />
<div align="center">
	<?php echo Text::_('COM_COLLECTOR_POWERED_BY'); ?>
	<a href="http://www.steevo.fr/" target="blank">
		<img src="components/com_collector/assets/images/collector_logo_mini.png" border="0" alt="Collector Logo" align="top" />
	</a>
</div>