<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @property-write string|null ErrorMessage */
class CBPSyncCrmEventHistoryActivity extends CBPActivity
{
    protected static $listDefaultEntityType = ['LEAD', 'CONTACT', 'COMPANY', 'DEAL'];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
            'CrmEntityType' => null,  // Тип сущности элемента CRM
            'CrmElementId' => null,  // ID элемента CRM
            'CrmCopyElementId' => null,  // ID копии элемента CRM
			'ErrorMessage' => null,
		];

		$this->setPropertiesTypes([
            'CrmEntityType' => array(
                'Type' => 'string',
            ),
            'CrmElementId' => array(
                'Type' => 'string',
            ),
            'CrmCopyElementId' => array(
                'Type' => 'string',
            ),
			'ErrorMessage' => [
                'Type' => 'string'
            ],
		]);
	}

	public function Execute()
	{
        $CrmEntityType = $this->__get('CrmEntityType');
        preg_match('/\d+/', $this->__get('CrmElementId'), $matches);
        $CrmElementId = (string)$matches[0];
        $CrmCopyElementId = (string)$this->__get('CrmCopyElementId');

        if (!empty($CrmElementId) && !empty($CrmCopyElementId)) {
            if (\Bitrix\Main\Loader::includeModule('crm')) {
                $CCrmEvent = new \CCrmEvent();

                // Получаем историю событий родительского элемента CRM
                $res = CCrmEvent::GetList(array(), array(
                    "ENTITY_TYPE" => $CrmEntityType,
                    "ENTITY_ID" => $CrmElementId,
                ), false);

                // Получаем историю событий в копию элемента CRM
                while($arEvent = $res->Fetch()) {
                    $arEvent['ENTITY_ID'] = $CrmCopyElementId;
                    $arEvent['EVENT_ID'] = 'INFO';
                    $eventHistory[] = $arEvent;
                }

                // Переворачиваем массив событий, так как GetList возвращает от новым к старым,
                // т.к. для для записи нужна хронология от старых к новым
                $eventHistory = array_reverse($eventHistory);

                // Сохраняем все элементы истории в новый элемент CRM
                foreach ($eventHistory as $event) {
                    $CCrmEvent->Add($event, false);
                }
            }
        }

        return CBPActivityExecutionStatus::Closed;
	}

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array(
                'CrmEntityType' => null,
                'CrmElementId' => null,
                'CrmCopyElementId' => null,
            );

            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
                $arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity['Properties'])) {
                $arCurrentValues = array_merge($arCurrentValues,
                    $arCurrentActivity['Properties']);
            }
        }

        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(__FILE__, "properties_dialog.php",
            array(
                "arCurrentValues" => $arCurrentValues,
                "formName" => $formName
            ));
    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
    {
        $arProperties = array(
            'CrmEntityType' => $arCurrentValues['CrmEntityType'],
            'CrmElementId' => $arCurrentValues['CrmElementId'],
            'CrmCopyElementId' => $arCurrentValues['CrmCopyElementId'],
        );

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
            $arWorkflowTemplate,
            $activityName
        );

        if (empty($arCurrentValues['CrmElementId']) || !self::checkEntityType($arCurrentValues['CrmEntityType'])
            || empty($arCurrentValues['CrmCopyElementId']))
        {
            $arErrors[] = [
                'code'    => 'emptyRequiredField',
                'message' => 'Заполнены не все поля',
            ];
        }

        if (!empty($arErrors))
            return false;

        $arCurrentActivity['Properties'] = $arProperties;

        return true;
    }

    protected static function checkEntityType($entityType)
    {
        return in_array($entityType, self::$listDefaultEntityType);
    }
}
