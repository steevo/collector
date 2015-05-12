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

// no direct access

defined('_JEXEC') or die('Restricted access');

$db = JFactory::getDBO();
$user = JFactory::getUser();
$config = JFactory::getConfig();
$now = JFactory::getDate();
$app = JFactory::getApplication();

JHTML::_('behavior.tooltip');
JHTML::_('behavior.modal');

?>

<script language="javascript" type="text/javascript">
	<!--
	function submitbutton(pressbutton)
	{
		var form = document.adminForm;

		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}

		// do field validation
		// if (form.name.value == ""){
			// alert( "<?php echo JText::_( 'COM_COLLECTOR_COLLECTION_MUST_HAVE_A_TITLE', true ); ?>" );
		// } else {
			submitform( pressbutton );
		// }
	}

	function rename(elementId)
	{
		var element = document.getElementById(elementId);

		if ( document.adminForm.renameId.value != '' )
		{
			oldelement = document.getElementById(document.adminForm.renameId.value);
			oldelement.style.display = 'block';
			while ( oldelement.nextSibling ) {
				oldelement.parentNode.removeChild(oldelement.nextSibling);
			}
		}

		document.adminForm.renameId.value = elementId;

		if ( element.style.display != 'none' )
		{
			element.style.display = 'none';

			var file = element;

			while ( file.hasChildNodes() ) {
				file = file.firstChild;
			}

			// Création d'un élément `inputText`
			var inputText = document.createElement('input');
			inputText.setAttribute('class', 'inputbox');
			inputText.setAttribute('type', 'text');
			inputText.setAttribute('id', 'rename');
			inputText.setAttribute('name', 'rename');
			inputText.setAttribute('size', '40');
			inputText.setAttribute('value', file.nextSibling.nodeValue.substr(1));

			document.adminForm.renameElement.value = file.nextSibling.nodeValue.substr(1);

			var space1 = document.createTextNode(' ');
			var space2 = document.createTextNode(' ');

			var inputSubmit = document.createElement('input');
			inputSubmit.setAttribute('type', 'submit');
			inputSubmit.setAttribute('value', '<?php echo JText::_('COM_COLLECTOR_RENAME'); ?>');
			inputSubmit.setAttribute('onclick', 'submitform(\'filemanager.rename\')');

			var inputCancel = document.createElement('input');
			inputCancel.setAttribute('type', 'button');
			inputCancel.setAttribute('value', '<?php echo JText::_('COM_COLLECTOR_CANCEL'); ?>');
			inputCancel.setAttribute('onclick', 'renameCancel()');

			// Ajout de l'élément `inputText` à l'élément `element`
			element.parentNode.appendChild(inputText);
			element.parentNode.appendChild(space1);
			element.parentNode.appendChild(inputSubmit);
			element.parentNode.appendChild(space2);
			element.parentNode.appendChild(inputCancel);

			inputText.focus();
		}
	}

	function renameCancel()
	{
		oldelement = document.getElementById(document.adminForm.renameId.value);
		oldelement.style.display = 'block';
		while ( oldelement.nextSibling ) {
			oldelement.parentNode.removeChild(oldelement.nextSibling);
		}
	}
	//-->
</script>

<div class="row-fluid">
	<?php if (!empty( $this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
	<?php else : ?>
		<div id="j-main-container">
	<?php endif;?>
		<?php if ($user->authorise('core.create', 'com_collector')):?>
		<!-- File Upload Form -->
		<div id="collapseUpload" class="collapse">
			<form action="<?php echo JURI::base(); ?>index.php?option=com_media&amp;task=file.upload&amp;tmpl=component&amp;<?php echo $this->session->getName().'='.$this->session->getId(); ?>&amp;<?php echo JSession::getFormToken();?>=1&amp;format=html" id="uploadForm" class="form-inline" name="uploadForm" method="post" enctype="multipart/form-data">
				<div id="uploadform">
					<fieldset id="upload-noflash" class="actions">
							<label for="upload-file" class="control-label"><?php echo JText::_('COM_MEDIA_UPLOAD_FILE'); ?></label>
								<input type="file" id="upload-file" name="Filedata[]" multiple /> <button class="btn btn-primary" id="upload-submit"><i class="icon-upload icon-white"></i> <?php echo JText::_('COM_MEDIA_START_UPLOAD'); ?></button>
								<p class="help-block"><?php echo $this->config->get('upload_maxsize') == '0' ? JText::_('COM_MEDIA_UPLOAD_FILES_NOLIMIT') : JText::sprintf('COM_MEDIA_UPLOAD_FILES', $this->config->get('upload_maxsize')); ?></p>
					</fieldset>
					<input class="update-folder" type="hidden" name="folder" id="folder" value="<?php echo $this->folder; ?>" />
					<input type="hidden" name="return-url" value="<?php echo base64_encode('index.php?option=com_collector&view=filemanager&folder='.$this->folder); ?>" />
				</div>
			</form>
		</div>
		
			<form action="index.php?option=com_collector&amp;task=filemanager.create" name="folderForm" id="folderForm" class="form-inline" method="post">
					<div class="path">
						<?php echo $this->navigation; ?>
						<input class="inputbox" type="text" id="foldername" name="foldername"  />
						<input class="update-folder" type="hidden" name="folderbase" id="folderbase" value="<?php echo $this->folder; ?>" />
						<input type="hidden" name="view" id="view" value="filemanager" />
						<button type="submit" class="btn"><i class="icon-folder-open"></i> <?php echo JText::_('COM_COLLECTOR_CREATE_FOLDER'); ?></button>
					</div>
					<?php echo JHtml::_('form.token'); ?>
			</form>
		<?php endif;?>
<form action="index.php" method="post" name="adminForm" id="adminForm" >

<div class="manager">
	<table class="table table-striped table-condensed">
		<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this);" />
				</th>
				<th>
					<?php echo JText::_( 'COM_COLLECTOR_NAME' ); ?>
				</th>
				<th width="150">
					<?php echo JText::_( 'COM_COLLECTOR_TYPE' ); ?>
				</th>
				<th width="100">
					<?php echo JText::_( 'COM_COLLECTOR_SIZE' ); ?>
				</th>
				<th width="150">
					<?php echo JText::_( 'COM_COLLECTOR_MODIFIED' ); ?>
				</th>
				<th width="100">
					<?php echo JText::_( 'COM_COLLECTOR_ACTIONS' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php
		$i = 0;
		$k = 0;
		foreach ( $this->lists['folders'] as $folder )
		{
			$checked = JHTML::_( 'grid.id', $i, $folder->path_relative );

			$link = JRoute::_( 'index.php?option=com_collector&amp;view=filemanager&amp;folder=' . $folder->path_relative );

			$linkDelete = JRoute::_( 'index.php?option=com_collector&amp;view=filemanager&amp;task=filemanager.remove&amp;cid[]=' . $folder->path_relative );
			$delete = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_DELETE') . '">';
			$delete .= '<a href="' . $linkDelete . '" onclick="return confirm(\'' . JText::_('COM_COLLECTOR_CONFIRM_DEL') . '\');" ><img src="components/com_collector/assets/images/page_white_delete.png"></a></span>';

			$linkMove = JRoute::_( 'index.php?option=com_collector&amp;view=folderselect&amp;tmpl=component&amp;task=move&amp;cid[]=' . $folder->path_relative );
			$move = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_MOVE') . '">';
			$move .= '<a href="' . $linkMove . '" class="modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" ><img src="components/com_collector/assets/images/folder_page_white.png"></a></span>';

			$linkCopy = JRoute::_( 'index.php?option=com_collector&amp;view=folderselect&amp;tmpl=component&amp;task=copy&amp;cid[]=' . $folder->path_relative );
			$copy = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_COPY') . '">';
			$copy .= '<a href="' . $linkCopy . '" class="modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" ><img src="components/com_collector/assets/images/page_white_copy.png"></a></span>';

			$rename = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_RENAME') . '">';
			$rename .= '<a href="javascript:void(0);" onclick="return rename(\'element'. $i.'\')" ><img src="components/com_collector/assets/images/textfield_rename.png"></a></span>';

			?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<?php
						if ( $folder->name != '' )
						{
							echo $checked;
						}
					?>
				</td>
				<td>
					<div id="element<?php echo $i; ?>" ><a style="text-decoration: none;" href="<?php echo $link; ?>" ><img src="<?php echo $folder->ico; ?>">&nbsp;<?php echo $folder->name; ?></a></div>
				</td>
				<td>
					<?php echo $folder->text; ?>
				</td>
				<td>

				</td>
				<td>

				</td>
				<td align="center">
					<?php
						if ( $folder->text != '' )
						{
							echo $delete . ' ' . $copy . ' ' . $move . ' ' . $rename;
						}
					?>
				</td>
			</tr>
			<?php
			$k = 1 - $k;
			$i++;
		}

		foreach ( $this->lists['files'] as $file )
		{
			JHTML::_('behavior.modal');

			$checked = JHTML::_( 'grid.id', $i, $file->path_relative );

			$linkDelete = JRoute::_( 'index.php?option=com_collector&amp;view=filemanager&amp;task=filemanager.remove&amp;cid[]=' . $file->path_relative );
			$delete = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_DELETE') . '">';
			$delete .= '<a href="' . $linkDelete . '" onclick="return confirm(\'' . JText::_('COM_COLLECTOR_CONFIRM_DEL') . '\');" ><img src="components/com_collector/assets/images/page_white_delete.png"></a></span>';

			$linkMove = JRoute::_( 'index.php?option=com_collector&amp;view=folderselect&amp;tmpl=component&amp;task=move&amp;cid[]=' . $file->path_relative );
			$move = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_MOVE') . '">';
			$move .= '<a href="' . $linkMove . '" class="modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" ><img src="components/com_collector/assets/images/folder_page_white.png"></a></span>';

			$linkCopy = JRoute::_( 'index.php?option=com_collector&amp;view=folderselect&amp;tmpl=component&amp;task=copy&amp;cid[]=' . $file->path_relative );
			$copy = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_COPY') . '">';
			$copy .= '<a href="' . $linkCopy . '" class="modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" ><img src="components/com_collector/assets/images/page_white_copy.png"></a></span>';

			$rename = '<span class="editlinktip hasTip" title="' . JText::_('COM_COLLECTOR_RENAME') . '">';
			$rename .= '<a href="javascript:void(0);" onclick="return rename(\'element'. $i.'\')" ><img src="components/com_collector/assets/images/textfield_rename.png"></a></span>';

			?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<?php echo $checked; ?>
				</td>
				<td>
					<?php
					if ( $file->type == 1 )
					{
						echo '<div id="element'.$i.'" ><a style="text-decoration: none;" class="modal" href="'.JURI::root().'images/collector/'.$file->path_relative.'" ><img src="'. $file->ico. '">&nbsp;' .$file->name. '</a></div>';
					}
					else
					{
						echo '<div id="element'.$i.'" ><img src="'. $file->ico. '" >&nbsp;' .$file->name. '</div>';
					}
					?>
				</td>
				<td>
					<?php echo $file->text; ?>
				</td>
				<td>
					<?php echo $file->size; ?>
				</td>
				<td>
					<?php echo $file->modified; ?>
				</td>
				<td align="center">
					<?php
						echo $delete . ' ' . $copy . ' ' . $move . ' ' . $rename;
					?>
				</td>
			</tr>
			<?php
			$k = 1 - $k;
			$i++;
		}

		?>
		</tbody>
	</table>
</div>

<div class="manager" >
	<table class="table" >
		<tr><td><center>
			<a href='<?php echo $app->input->getURI(); ?>#'><img src="/media/system/images/sort0.png" /><?php echo ' ' . JText::_( 'COM_COLLECTOR_BACK_TO_TOP' ) . ' '; ?><img src="/media/system/images/sort0.png" /></a>
		</center></td></tr>
	</table>
</div>

<input type="hidden" name="option" value="com_collector" />
<input type="hidden" name="view" value="filemanager" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="folder" value="<?php echo $this->folder; ?>" />
<input type="hidden" name="renameId" value="" />
<input type="hidden" name="renameElement" value="" />
<input type="hidden" name="boxchecked" value="0" />
<?php echo JHTML::_( 'form.token' ); ?>
</div>
</div>
</form>