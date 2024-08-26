<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => 'Копирование истории событий элемента CRM',
	'DESCRIPTION' => 'Копирование истории событий элемента CRM',
	'TYPE' => ['activity'],
	'CLASS' => 'SyncCrmEventHistoryActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
	],
	'RETURN' => [],
	'FILTER' => [
		'EXCLUDE' => [
			['tasks'],
		],
	],
];
