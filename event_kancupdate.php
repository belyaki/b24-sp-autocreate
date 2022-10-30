<?php
@set_time_limit(0);
@ignore_user_abort(true);
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']);
chdir($path_parts['dirname']);
require_once (__DIR__.'/include/crest.php');
$_REQUEST['DATE TIME'] = date('d.m.Y H:i:s');

/*
file_put_contents(
	$path_parts['dirname']."/onCrmDynamicItemUpdate_all.log", 
	print_r($_REQUEST, true), 
	FILE_APPEND
);
*/
if($_REQUEST['event'] == 'ONCRMDYNAMICITEMUPDATE'){
	$kanc_id = $_REQUEST['data']['FIELDS']['ID'];

	$arTests = array();
	$arTests['DATE TIME'] = date('d.m.Y H:i:s');
	$arTests['kanc_id'] = $kanc_id;
	$arTests['entityTypeId'] = $_REQUEST['data']['FIELDS']['ENTITY_TYPE_ID'];
	if($_REQUEST['data']['FIELDS']['ENTITY_TYPE_ID'] == '143'){
		$arNewKanc_data = CRest::call("crm.item.get", array(
			"entityTypeId" => 143,
			"id" => $kanc_id
		));
		$arTests['arNewKanc_data'] = $arNewKanc_data;
		// если хотя бы одно дело перешло в подтверждение, то сделку переводим в оплату
		if($arNewKanc_data['result']['item']['id'] && $arNewKanc_data['result']['item']['stageId'] == "DT143_4:UC_QZJEFV"){
			$arNewDeal_data = CRest::call("crm.deal.get", array(
				"id" => $arNewKanc_data['result']['item']['parentId2']
			));
			$arTests['crm.deal.get'] = $arNewDeal_data;
			if($arNewDeal_data['result']['STAGE_ID'] == 'C6:EXECUTING'){
				sleep(2);
				$dealUpdateFields['STAGE_ID'] = 'C6:FINAL_INVOICE';
				$arDealUpdate_data = CRest::call("crm.deal.update", array(
					"id" => $arNewKanc_data['result']['item']['parentId2'],
					"fields" => $dealUpdateFields
				));
				$arTests['arDealUpdate_data'] = $arDealUpdate_data;
			}
		}
		

		if($arNewKanc_data['result']['item']['id'] && $arNewKanc_data['result']['item']['stageId'] == "DT143_6:UC_IKFJ90"){
			// Если дело на этапе Wezwanie 1, то двигаем сделку на этап C6:FINAL_INVOICE
			$arNewDeal_data = CRest::call("crm.deal.get", array(
				"id" => $arNewKanc_data['result']['item']['parentId2']
			));
			$arTests['crm.deal.get'] = $arNewDeal_data;
			if($arNewDeal_data['result']['STAGE_ID'] !== 'C6:FINAL_INVOICE'){
				$dealUpdateFields['STAGE_ID'] = 'C6:FINAL_INVOICE';
				$arDealUpdate_data = CRest::call("crm.deal.update", array(
					"id" => $arNewKanc_data['result']['item']['parentId2'],
					"fields" => $dealUpdateFields
				));
				$arTests['arDealUpdate_data'] = $arDealUpdate_data;
			}
		}

		if($arNewKanc_data['result']['item']['id'] && $arNewKanc_data['result']['item']['stageId'] == "DT143_6:UC_PFXFK4"){
			// Если дело на этапе Produkcja karty, то двигаем сделку на этап Payment 3 C6:UC_9F6TD7
			$arNewDeal_data = CRest::call("crm.deal.get", array(
				"id" => $arNewKanc_data['result']['item']['parentId2']
			));
			$arTests['crm.deal.get'] = $arNewDeal_data;
			if($arNewDeal_data['result']['STAGE_ID'] !== 'C6:UC_9F6TD7'){
				$dealUpdateFields['STAGE_ID'] = 'C6:UC_9F6TD7';
				$arDealUpdate_data = CRest::call("crm.deal.update", array(
					"id" => $arNewKanc_data['result']['item']['parentId2'],
					"fields" => $dealUpdateFields
				));
				$arTests['arDealUpdate_data'] = $arDealUpdate_data;
			}
		}
		file_put_contents(
			$path_parts['dirname']."/onCrmDynamicItemUpdate.log", 
			print_r($arTests, true), 
			FILE_APPEND
		);
	}
}
?>