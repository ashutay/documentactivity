<?php
/**
 * Created by PhpStorm.
 * User: Shutay Alexander
 */

use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') || die;

Loc::loadMessages(__FILE__);
?>

<tr>
	<td align="right" width="40%">
		<span class="adm-required-field"><?= Loc::getMessage('ACTIVITY_DOCUMENT_INPUT') ?>:</span>
	</td>
	<td width="60%">
		<?php
		$objectId = 0;
		$objectName = Loc::getMessage('ACTIVITY_DOCUMENT_DISK_EMPTY');
		if ($current['INPUT'] && !CBPDocument::IsExpression($current['INPUT'])) {
			$object = \Bitrix\Disk\BaseObject::loadById($current['INPUT']);
			if ($object) {
				$objectId = $object->getId();
				$objectName = $object->getName();
			}
		}
		?>
		<div style="padding: 3px;">
			<input type="hidden" name="INPUT" id="INPUT_value" value="<?= (int)$objectId ?>"/>
			<span id="INPUT_name" style="color: grey">
				<?= htmlspecialcharsbx($objectName) ?>
			</span>
			<a href="#" id="INPUT_clear" onclick="return remoteFile();"
			   style="<?= $objectId ? '' : 'display: none;' ?>color: red; text-decoration: none; border-bottom: 1px dotted">x
			</a>
			<br/>
			<a href="#" onclick="return chooseFile();"
			   style="color: black; text-decoration: none; border-bottom: 1px dotted"><?= Loc::getMessage(
					'ACTIVITY_DOCUMENT_CHOOSE_FILE'
				) ?></a>
		</div>
		<?= \CBPDocument::ShowParameterField(
			'int',
			'INPUT_hand',
			CBPDocument::IsExpression($current['INPUT']) ? $current['INPUT'] : '',
			['size' => 30]
		) ?>
	</td>
</tr>

<tr>
	<td align="right" width="40%">
		<span class="adm-required-field"><?= Loc::getMessage('ACTIVITY_DOCUMENT_OUTPUT') ?>:</span>
	</td>
	<td width="60%">
		<?php

		$folderId = 0;
		$folderName = Loc::getMessage('ACTIVITY_DOCUMENT_DISK_EMPTY');
		if ($current['OUTPUT'] && !CBPDocument::IsExpression($current['OUTPUT'])) {
			$folder = \Bitrix\Disk\Folder::loadById($current['OUTPUT']);
			if ($folder) {
				$folderId = $folder->getId();
				$folderName = $folder->getName();
			}
		}
		?>
		<div style="padding: 3px;">
			<input type="hidden" name="OUTPUT" id="OUTPUT_value" value="<?= (int)$folderId ?>"/>
			<span id="OUTPUT_name" style="color: grey">
				<?= htmlspecialcharsbx($folderName) ?>
			</span>
			<a href="#" id="OUTPUT_clear" onclick="return remoteFolder();"
			   style="<?= $folderId ? '' : 'display: none;' ?>color: red; text-decoration: none; border-bottom: 1px dotted">x
			</a>
			<br/>
			<a href="#" onclick="return chooseFolder();"
			   style="color: black; text-decoration: none; border-bottom: 1px dotted"><?= Loc::getMessage(
					'ACTIVITY_DOCUMENT_CHOOSE_FOLDER'
				) ?></a>
		</div>
		<?= \CBPDocument::ShowParameterField(
			'int',
			'OUTPUT_hand',
			CBPDocument::IsExpression($current['OUTPUT']) ? $current['OUTPUT'] : '',
			['size' => 30]
		) ?>
	</td>
</tr>

<?php if (!empty($fileIdVar)): ?>
	<tr>
		<td align="right" width="40%">
			<span><?= Loc::getMessage('ACTIVITY_DOCUMENT_FILE_ID') ?>:</span>
		</td>
		<td width="60%">
			<select name="FILE_ID">
				<option><?= Loc::getMessage('ACTIVITY_DOCUMENT_FILE_ID_NOT') ?></option>
				<?php foreach ($fileIdVar as $code => $name): ?>
					<option value="<?= $code ?>" <?= $current['FILE_ID']
					                                 === $code ? 'selected' : ''; ?>><?= $name ?></option>
				<? endforeach; ?>
			</select>
		</td>
	</tr>
<? endif; ?>

<?php if (!empty($fileUrlVar)): ?>
	<tr>
		<td align="right" width="40%">
			<span><?= Loc::getMessage('ACTIVITY_DOCUMENT_FILE_URL') ?>:</span>
		</td>
		<td width="60%">
			<select name="FILE_URL">
				<option><?= Loc::getMessage('ACTIVITY_DOCUMENT_FILE_URL_NOT') ?></option>
				<?php foreach ($fileUrlVar as $code => $name): ?>
					<option value="<?= $code ?>" <?= $current['FILE_URL']
					                                 === $code ? 'selected' : ''; ?>><?= $name ?></option>
				<? endforeach; ?>
			</select>
		</td>
	</tr>
<? endif; ?>

<script>
    var chooseFile = function () {
        var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
        var dialogName = 'BPDUV';

        BX.ajax.get(urlSelect, 'multiselect=N&dialogName=' + dialogName,
            BX.delegate(function () {
                setTimeout(BX.delegate(function () {
                    BX.DiskFileDialog.obCallback[dialogName] = {
                        'saveButton': function (tab, path, selected) {
                            var i;
                            for (i in selected) {
                                if (selected.hasOwnProperty(i)) {
                                    if (selected[i].type == 'file') {
                                        BX('INPUT_value').value = (selected[i].id).toString().substr(1);
                                        BX('INPUT_name').innerHTML = selected[i].name;
                                        BX('INPUT_clear').style.display = '';
                                        break;
                                    }
                                }
                            }
                        }
                    };
                    BX.DiskFileDialog.openDialog(dialogName);
                }, this), 10);
            }, this)
        );
        return false;
    };

    var remoteFile = function () {
        BX('INPUT_value').value = 0;
        BX('INPUT_name').innerHTML = '<?=Loc::getMessage('ACTIVITY_DOCUMENT_DISK_EMPTY');?>';
        BX('INPUT_clear').style.display = 'none';
        return false;
    };

    var chooseFolder = function () {
        var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
        var dialogName = 'BPDUA';

        BX.ajax.get(urlSelect, 'wish=fakemove&dialogName=' + dialogName,
            BX.delegate(function () {
                setTimeout(BX.delegate(function () {
                    BX.DiskFileDialog.obCallback[dialogName] = {
                        'saveButton': function (tab, path, selected) {
                            var i;
                            for (i in selected) {
                                if (selected.hasOwnProperty(i)) {
                                    if (selected[i].type == 'folder') {
                                        BX('OUTPUT_value').value = selected[i].id;
                                        BX('OUTPUT_name').innerHTML = selected[i].name;
                                        BX('OUTPUT_clear').style.display = '';
                                        break;
                                    }
                                }
                            }
                        }
                    };
                    BX.DiskFileDialog.openDialog(dialogName);
                }, this), 10);
            }, this)
        );
        return false;
    };

    var remoteFolder = function () {
        BX('OUTPUT_value').value = 0;
        BX('OUTPUT_name').innerHTML = '<?=GetMessageJs('ACTIVITY_DOCUMENT_DISK_EMPTY')?>';
        BX('OUTPUT_clear').style.display = 'none';
        return false;
    }

</script>
