<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Crm\EventTable;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;

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
                // Получаем историю событий родительского элемента CRM
                $eventRelations = EventRelationsTable::getList([
                    'filter' => [
                        "ENTITY_TYPE" => $CrmEntityType,
                        "ENTITY_ID" => $CrmElementId,
                    ],
                ]);

                foreach ($eventRelations as $eventRelation) {
                    $eventId = $eventRelation['EVENT_ID'];
                    $eventIds[] = $eventId;
                    $eventRelationsList[$eventId] = $eventRelation;
                }

                $res = EventTable::getList([
                    'filter' => [
                        'ID' => $eventIds
                    ],
                ]);

                // Получаем историю событий в копию элемента CRM
                foreach ($res as $key => $arEvent) {
                    $arEvent['ENTITY_ID'] = $CrmCopyElementId;
                    $arEvent['EVENT_ID'] = 'INFO';
                    $eventHistory[$key]['event'] = [
                        'CREATED_BY_ID' => $arEvent['CREATED_BY_ID'],
                        'EVENT_ID' => $arEvent['EVENT_ID'],
                        'EVENT_NAME' => $arEvent['EVENT_NAME'],
                        'EVENT_TEXT_1' => $arEvent['EVENT_TEXT_1'],
                        'EVENT_TEXT_2' => $arEvent['EVENT_TEXT_2'],
                        'EVENT_TYPE' => $CrmEntityType,
                        'FILES' => $arEvent['FILES'],
                        'ENTITY_ID' => $CrmElementId,
                    ];
                    $eventHistory[$key]['eventRelation'] = $eventRelationsList[$arEvent['ID']];
                }

                // Переворачиваем массив событий, так как GetList возвращает от новым к старым,
                // т.к. для для записи нужна хронология от старых к новым
                $eventHistory = array_reverse($eventHistory);

                // Сохраняем все элементы истории в новый элемент CRM
                foreach ($eventHistory as $event) {
                    $eventData = $event['event'];
                    $eventId = EventTable::add($eventData)?->getId();

                    $eventRelationData = [
                        'ASSIGNED_BY_ID' => $eventData['CREATED_BY_ID'],
                        'ENTITY_TYPE' => $CrmEntityType,
                        'ENTITY_ID' => $CrmElementId,
                        'ENTITY_FIELD' => $event['eventRelation']['EVENT_ID'],
                        'EVENT_ID' => $eventId,
                    ];
                    EventRelationsTable::add($eventRelationData);
                }

                switch ($CrmEntityType) {
                    case 'LEAD': $CCrmOwnerType = \CCrmOwnerType::Lead; break;
                    case 'DEAL': $CCrmOwnerType = \CCrmOwnerType::Deal; break;
                    case 'CONTACT': $CCrmOwnerType = \CCrmOwnerType::Contact; break;
                    case 'COMPANY': $CCrmOwnerType = \CCrmOwnerType::Company; break;
                    default: $CCrmOwnerType = \CCrmOwnerType::Undefined;
                }

                TimelineBindingTable::attach(
                    $CCrmOwnerType,
                    $CrmElementId,
                    $CCrmOwnerType,
                    $CrmCopyElementId,
                    [
                        \Bitrix\Crm\Timeline\TimelineType::UNDEFINED,
                        \Bitrix\Crm\Timeline\TimelineType::ACTIVITY,
                        \Bitrix\Crm\Timeline\TimelineType::CREATION,
                        \Bitrix\Crm\Timeline\TimelineType::MODIFICATION,
                        \Bitrix\Crm\Timeline\TimelineType::LINK,
                        \Bitrix\Crm\Timeline\TimelineType::UNLINK,
                        \Bitrix\Crm\Timeline\TimelineType::MARK,
                        \Bitrix\Crm\Timeline\TimelineType::COMMENT,
                        \Bitrix\Crm\Timeline\TimelineType::WAIT,
                        \Bitrix\Crm\Timeline\TimelineType::BIZPROC,
                        \Bitrix\Crm\Timeline\TimelineType::CONVERSION,
                        \Bitrix\Crm\Timeline\TimelineType::SENDER,
                        \Bitrix\Crm\Timeline\TimelineType::DOCUMENT,
                        \Bitrix\Crm\Timeline\TimelineType::RESTORATION,
                        \Bitrix\Crm\Timeline\TimelineType::ORDER,
                        \Bitrix\Crm\Timeline\TimelineType::ORDER_CHECK,
                        \Bitrix\Crm\Timeline\TimelineType::SCORING,
                        \Bitrix\Crm\Timeline\TimelineType::EXTERNAL_NOTICE,
                        \Bitrix\Crm\Timeline\TimelineType::FINAL_SUMMARY,
                        \Bitrix\Crm\Timeline\TimelineType::DELIVERY,
                        \Bitrix\Crm\Timeline\TimelineType::FINAL_SUMMARY_DOCUMENTS,
                        \Bitrix\Crm\Timeline\TimelineType::STORE_DOCUMENT,
                        \Bitrix\Crm\Timeline\TimelineType::PRODUCT_COMPILATION,
                        \Bitrix\Crm\Timeline\TimelineType::SIGN_DOCUMENT,
                        \Bitrix\Crm\Timeline\TimelineType::SIGN_DOCUMENT_LOG,
                        \Bitrix\Crm\Timeline\TimelineType::LOG_MESSAGE,
                        \Bitrix\Crm\Timeline\TimelineType::CALENDAR_SHARING,
                        \Bitrix\Crm\Timeline\TimelineType::TASK,
                        \Bitrix\Crm\Timeline\TimelineType::AI_CALL_PROCESSING,
                        \Bitrix\Crm\Timeline\TimelineType::SIGN_B2E_DOCUMENT,
                        \Bitrix\Crm\Timeline\TimelineType::SIGN_B2E_DOCUMENT_LOG,
                    ],
                );

                \CCrmActivity::AttachBinding($CCrmOwnerType, $CrmElementId, $CCrmOwnerType, $CrmCopyElementId);
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
