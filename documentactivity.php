<?php
/**
 * Created by PhpStorm.
 * User: Shutay Alexander
 */

use Bitrix\Bizproc\FieldType;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Iblock\ElementPropertyTable;

defined('B_PROLOG_INCLUDED') || die();

/**
 * Class CBPDocumentActivity
 */
class CBPDocumentActivity extends \CBPActivity
{
	/**
	 * @var array
	 */
	protected $fields;

	/**
	 * @var int
	 */
	protected $id;
	protected $iblock;

	/**
	 * @TODO не реализована поддержка свойств БП
	 * @var bool
	 */
	protected $setVariable = false;

	/**
	 * CBPDocumentActivity constructor.
	 *
	 * @param $name
	 */
	public function __construct($name)
	{

		parent::__construct($name);

		$this->arProperties = [
			'INPUT'    => 0,
			'OUTPUT'   => 0,
			'FILE_ID'  => '',
			'FILE_URL' => ''
		];

		$this->SetPropertiesTypes(
			[
				'INPUT'    => ['TYPE' => FieldType::INT],
				'OUTPUT'   => ['TYPE' => FieldType::INT],
				'FILE_ID'  => ['TYPE' => FieldType::STRING],
				'FILE_URL' => ['TYPE' => FieldType::STRING]
			]
		);
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	public function Execute()
	{

		$this->loadFields();
		$this->prepareFields();
		$this->generateDoc();

		return CBPActivityExecutionStatus::Closed;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function loadFields()
	{

		$this->getIblock();
		if ($this->iblock) {
			$this->getProperty();
		}

		if ($this->setVariable) {
			$variables = $this->getVariables();

			if (!empty($variables)) {
				$this->getVariableValues($variables);
			}
		}
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function prepareFields()
	{

		$date = [
			'TIMESTAMP_X',
			'DATE_CREATE',
			'ACTIVE_FROM',
			'ACTIVE_TO',
		];

		$user = [
			'MODIFIED_BY',
			'CREATED_BY'
		];

		foreach ($date as $code) {
			if (!empty($this->fields['DOCUMENT']['SIMPLE'][$code])) {
				$this->fields['DOCUMENT']['SIMPLE'][$code] = $this->getDate($this->fields['DOCUMENT']['SIMPLE'][$code]);
			}
		}

		foreach ($user as $code) {
			if (!empty($this->fields['DOCUMENT']['SIMPLE'][$code])) {
				$this->fields['DOCUMENT']['SIMPLE'][$code] = $this->getUser($this->fields['DOCUMENT']['SIMPLE'][$code]);
			}
		}

		foreach ($this->fields['DOCUMENT']['PROPERTY'] as $key => &$prop) {

			if ($prop['VALUE'] === null) {
				unset($this->fields['DOCUMENT']['PROPERTY'][$key]);
				continue;
			}

			if ($prop['LIST'] === 'L') {
				$prop['VALUE'] = $this->getEnum($prop['VALUE']);
			}

			if ($prop['TYPE'] === null) {
				continue;
			}

			switch ($prop['TYPE']) {
				case 'ECrm':
					{
						$prop['VALUE'] = $this->getCrm($prop['VALUE']);
					}
					break;
				case 'employee':
					{
						$prop['VALUE'] = $this->getUser($prop['VALUE']);
					}
					break;
				case 'HTML':
					{
						$uns = \unserialize($prop['VALUE'], ['allowed_classes' => false]);
						if (!empty($uns['TEXT'])) {
							$prop['VALUE'] = $uns['TEXT'];
						} else {
							$prop['VALUE'] = null;
						}
					}
					break;
				case 'EList':
					{
						$prop['VALUE'] = $this->getElement($prop['VALUE']);
					}
					break;
				case 'Date':
					{
						$prop['VALUE'] = (new Date($prop['VALUE'], 'Y-m-d'))->format('d.m.Y');
					}
					break;
				case 'DateTime':
					{
						$prop['VALUE'] = (new DateTime($prop['VALUE'], 'Y-m-d H:i:s'))->format('d.m.Y H:i:s');
					}
					break;
			}
		}

	}

	/**
	 * @param $id
	 *
	 * @return null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getElement($id)
	{

		$element = ElementTable::getList(
			[
				'select' => ['NAME'],
				'filter' => Query::filter()->where('ID', $id),
				'limit'  => 1
			]
		)->fetch();

		if ($element) {
			return $element['NAME'];
		}

		return null;
	}

	/**
	 * @param $userId
	 *
	 * @return null|string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getUser($userId)
	{

		$user = UserTable::getList(
			[
				'select' => [
					'SECOND_NAME',
					'NAME',
					'LAST_NAME'
				],
				'filter' => Query::filter()->where('ID', $userId),
				'limit'  => 1
			]
		)->fetch();
		if ($user) {
			return "{$user['LAST_NAME']} {$user['NAME']} {$user['SECOND_NAME']}";
		}

		return null;
	}

	/**
	 * @param $data
	 *
	 * @return null|string
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function getDate($data)
	{

		if ($data instanceof Date) {
			return (new Date($data))->format('d.m.Y');
		}

		if ($data instanceof DateTime) {
			return (new DateTime($data))->format('d.m.Y H:i:s');
		}

		return null;

	}

	/**
	 * @param $entity
	 *
	 * @return null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getCrm($entity)
	{

		$entity = explode('_', $entity);

		$query = null;
		switch ($entity[0]) {
			case 'L':
				{
					$query = LeadTable::query()->setSelect(['TITLE']);
				}
				break;
			case 'C':
				{
					$query = ContactTable::query()->setSelect(['TITLE' => 'FULL_NAME']);
				}
				break;
			case 'CO':
				{
					$query = CompanyTable::query()->setSelect(['TITLE']);
				}
				break;
			case 'D':
				{
					$query = DealTable::query()->setSelect(['TITLE']);
				}
				break;
		}

		if ($query === null) {
			return null;
		}

		$crmEl = $query->where('ID', $entity[1])->setLimit(1)->exec()->fetch();

		if ($crmEl) {
			return $crmEl['TITLE'];
		}

		return null;
	}

	/**
	 * @param $id
	 *
	 * @return null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getEnum($id)
	{

		$enum = PropertyEnumerationTable::getList(
			[
				'select' => ['VALUE'],
				'filter' => Query::filter()->where('ID', $id),
				'limit'  => 1
			]
		)->fetch();

		if ($enum) {
			return $enum['VALUE'];
		}

		return null;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getIblock()
	{

		Loader::includeModule('iblock');

		$id = $this->GetDocumentId();
		if (is_numeric($id[2])) {
			$this->id = (int)$id[2];
		}

		$type = $this->GetDocumentType();

		if (\strpos($type[2], 'iblock') === false) {
			return;
		}

		$this->iblock = (int)\preg_replace('/\D/', '', $type[2]);

		if (!$this->iblock) {
			return;
		}

		$this->getIblockFields();

	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getIblockFields()
	{

		$this->fields['DOCUMENT']['SIMPLE'] = ElementTable::getList(
			[
				'select' => ['*'],
				'filter' => Query::filter()->where('ID', $this->id)->where('IBLOCK_ID', $this->iblock)
			]
		)->fetch();
	}

	/**
	 * @TODO можно сделать поддержку множественных свойств
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getProperty()
	{

		$this->fields['DOCUMENT']['PROPERTY'] = ElementPropertyTable::getList(
			[
				'select' => [
					'CODE' => 'IBLOCK_PROPERTY.CODE',
					'VALUE',
					'TYPE' => 'IBLOCK_PROPERTY.USER_TYPE',
					'LIST' => 'IBLOCK_PROPERTY.PROPERTY_TYPE'
				],
				'filter' => Query::filter()->where('IBLOCK_ELEMENT_ID', $this->id)
			]
		)->fetchAll();
	}

	protected function getVariables()
	{

		$wId = Application::getInstance()->getContext()->getRequest()->get('workflow_template_id');
		if ($wId === null) {
			return;
		}

		$variablesOb = \Bitrix\Bizproc\WorkflowTemplateTable::getList(
			[
				'select' => ['VARIABLES'],
				'filter' => Query::filter()->where('ID', $wId),
				'limit'  => 1
			]
		);
		$variables = [];
		while ($variable = $variablesOb->fetch()) {
			foreach ($variable['VARIABLES'] as $code => $desc) {
				$variables[] = $code;
			}
		}

		return $variables;

	}

	protected function getVariableValues($variables)
	{

		foreach ($variables as $variable) {
			$value = $this->GetVariable($variable);
			if ($value !== null) {
				$this->fields['VARIBLE'][$variable] = $value;
			}
		}

	}

	/**
	 * @param Exception $exception
	 *
	 * @return int|void
	 * @throws Exception
	 */
	public function HandleFault(Exception $exception)
	{

		if ($exception === null) {
			throw new Exception('exception');
		}

		$status = $this->Cancel();
		if ($status === CBPActivityExecutionStatus::Canceling) {
			return CBPActivityExecutionStatus::Faulting;
		}

		return $status;
	}

	/**
	 * @return int|void
	 */
	public function Cancel()
	{

		return \CBPActivityExecutionStatus::Closed;
	}

	/**
	 * @param        $documentType
	 * @param        $activityName
	 * @param        $template
	 * @param        $parameters
	 * @param        $variables
	 * @param null   $current
	 * @param string $formName
	 *
	 * @return false|null|string
	 */
	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$template,
		$parameters,
		$variables,
		$current = null,
		$formName = ''
	) {

		if (!is_array($current)) {
			$current = [
				'INPUT'    => 0,
				'OUTPUT'   => 0,
				'FILE_ID'  => '',
				'FILE_URL' => ''
			];

			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
				$template,
				$activityName
			);
			if (is_array($arCurrentActivity['Properties'])) {
				$current = array_merge(
					$current,
					$arCurrentActivity['Properties']
				);
			}

		}

		$fileIdVar = [];
		$fileUrlVar = [];
		foreach ($variables as $code => $variable) {
			if ($variable['Type'] === 'file') {
				$fileIdVar[$code] = $variable['Name'];
			}

			if ($variable['Type'] === 'string') {
				$fileUrlVar[$code] = $variable['Name'];
			}

		}

		$runtime = CBPRuntime::GetRuntime();

		return $runtime->ExecuteResourceFile(
			__FILE__,
			'properties_dialog.php',
			[
				'current'    => $current,
				'formName'   => $formName,
				'fileIdVar'  => $fileIdVar,
				'fileUrlVar' => $fileUrlVar,
			]
		);
	}

	/**
	 * @param $documentType
	 * @param $activityName
	 * @param $template
	 * @param $parameters
	 * @param $variables
	 * @param $current
	 * @param $arErrors
	 *
	 * @return bool
	 */
	public static function GetPropertiesDialogValues(
		$documentType,
		$activityName,
		&$template,
		&$parameters,
		&$variables,
		$current,
		&$arErrors
	) {

		$arErrors = [];

		$runtime = CBPRuntime::GetRuntime();

		if (empty($current['INPUT'])) {
			$arErrors[] = array(
				'code'    => 'Empty',
				'message' => Loc::getMessage('ACTIVITY_DOCUMENT_ERROR_NO_INPUT')
			);
		}

		if (empty($current['OUTPUT'])) {
			$arErrors[] = array(
				'code'    => 'Empty',
				'message' => Loc::getMessage('ACTIVITY_DOCUMENT_ERROR_NO_OUTPUT')
			);
		}

		if (!empty($arErrors)) {
			return false;
		}

		$arProperties = array(
			'INPUT'    => $current['INPUT'],
			'OUTPUT'   => $current['OUTPUT'],
			'FILE_ID'  => $current['FILE_ID'],
			'FILE_URL' => $current['FILE_URL']
		);

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
			$template,
			$activityName
		);
		$arCurrentActivity['Properties'] = $arProperties;

		return true;

	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 */
	protected function generateDoc()
	{

		$file = \Bitrix\Disk\File::loadById($this->arProperties['INPUT']);
		if (empty($file)) {
			return;
		}

		$folder = \Bitrix\Disk\Folder::loadById($this->arProperties['OUTPUT']);
		if (empty($folder)) {
			return;
		}

		$filePath = $_SERVER['DOCUMENT_ROOT'] . \CFile::GetPath($file->getFile()['ID']);

		$doc = new \PhpOffice\PhpWord\TemplateProcessor($filePath);

		foreach ($this->fields['DOCUMENT']['SIMPLE'] as $code => $val) {
			$doc->setValue('Document:' . $code, $val);
		}

		foreach ($this->fields['DOCUMENT']['PROPERTY'] as $prop) {

			$doc->setValue('Document:PROPERTY_' . $prop['CODE'], $prop['VALUE']);
		}

		$fileName = 'BP-' . $this->GetWorkflowInstanceId() . '.docx';

		$dirName = $_SERVER['DOCUMENT_ROOT'] . '/tmp/';

		/**
		 * https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
		 */
		if (!\is_dir($dirName) && !\mkdir($dirName) && !\is_dir($dirName)) {
			return;
		}

		$doc->saveAs($dirName . $fileName);

		$newFile = CFile::MakeFileArray('/tmp/' . $fileName);

		if (empty($newFile)) {
			return;
		}

		$createdBy = CBPHelper::ExtractUsers($this->CreatedBy, $this->GetDocumentId(), true);
		if (!$createdBy) {
			$createdBy = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;
		}

		$fileModel = $folder->uploadFile(
			$newFile,
			[
				'NAME'       => $newFile['name'],
				'CREATED_BY' => $createdBy,
			],
			[],
			true
		);

		\unlink($dirName . $fileName);

		if (!empty($this->arProperties['FILE_ID'])) {
			$this->SetVariable($this->arProperties['FILE_ID'], $fileModel->getFileId());
		}

		if (!empty($this->arProperties['FILE_URL'])) {
			$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
			$url = $urlManager->encodeUrn($urlManager->getHostUrl() . $urlManager->getPathFileDetail($fileModel));
			$this->SetVariable($this->arProperties['FILE_URL'], $url);
		}
	}
}