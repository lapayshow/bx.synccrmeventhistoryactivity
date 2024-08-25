<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$listDefaultEntity = array(
    'LEAD' => 'Лид',
    'DEAL' => 'Сделка',
    'CONTACT' => 'Контакт',
    'COMPANY' => 'Компания'
);

$currentEntityType = !empty($arCurrentValues['CrmEntityType']) ? $arCurrentValues['CrmEntityType'] : '';
?>

<tr>
    <td align="right" width="40%">
        <span style="font-weight: bold">Тип сущности</span>
    </td>
    <td width="60%">
        <select name="CrmEntityType">
            <option value="">Выберите тип сущности элемента CRM</option>
            <?php foreach($listDefaultEntity as $entityType => $entityName):?>
                <option value="<?=htmlspecialcharsbx($entityType)?>"
                    <?=($currentEntityType == $entityType) ? 'selected' : ''?>>
                    <?=htmlspecialcharsbx($entityName)?>
                </option>
            <?php endforeach;?>
        </select>
    </td>
</tr>

<!-- Поле для указания родительского документа элемента CRM -->
<tr>
  <td align="right"><span class="adm-required-field"><?= 'ID Элемента CRM' ?>:</span></td>
  <td>
    <?= CBPDocument::ShowParameterField("string", 'CrmElementId', $arCurrentValues['CrmElementId'], array("size"=>"5"))?>
  </td>
</tr>

<!-- Поле для указания копии документа элемента CRM -->
<tr>
    <td align="right"><span class="adm-required-field"><?= 'ID Копии Элемента CRM' ?>:</span></td>
    <td>
        <?= CBPDocument::ShowParameterField("string", 'CrmCopyElementId', $arCurrentValues['CrmCopyElementId'], array("size"=>"5"))?>
    </td>
</tr>
