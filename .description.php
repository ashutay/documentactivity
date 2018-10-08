<?php
/**
 * Created by PhpStorm.
 * User: Shutay Alexander
 */

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') || die();

$arActivityDescription = [
	// Название действия для конструтора.
	'NAME'              => Loc::getMessage('ACTIVITY_DOCUMENT_NAME'),

	// Описание действия для конструктора.
	'DESCRIPTION'       => Loc::getMessage('ACTIVITY_DOCUMENT_DESCRIPTION'),

	// Тип: “activity” - действие, “condition” - ветка составного действия.
	'TYPE'              => 'activity',

	// Название класса действия без префикса “CBP”.
	'CLASS'             => 'DocumentActivity',

	// Название JS-класса для управления внешним видом и поведением в конструкторе.
	// Если нужно только стандартное поведение, указывайте “BizProcActivity”.
	'JSCLASS'           => 'BizProcActivity',

	// Категория действия в конструкторе.
	// Данный шаг размещен в категории “Уведомления” по историческим причинам.
	'CATEGORY'          => [
		'ID' => 'other',
	],
	// Названия свойств действия, из которых будут взяты возвращаемые значения.
	'ADDITIONAL_RESULT' => ['DocumentResults']
];