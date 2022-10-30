<?php
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']);
chdir($path_parts['dirname']);
require_once (__DIR__.'/include/crest.php');
//$_REQUEST['DATE TIME'] = date('d.m.Y H:i:s');
/*
file_put_contents(
	$path_parts['dirname']."/onCrmDynamicItemAdd_all.log", 
	print_r($_REQUEST, true), 
	FILE_APPEND
);
*/
function readHeader($ch, $header)
{
	global $responseHeaders;
	$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	if(strpos($header, 'Content-Disposition') !== false){
		$responseHeaders[] = $header;
	}
	return strlen($header);
}

if($_REQUEST['event'] == 'ONCRMDYNAMICITEMADD' && $_REQUEST['data']['FIELDS']['ENTITY_TYPE_ID'] == '143'){
	$kanc_id = $_REQUEST['data']['FIELDS']['ID'];

	$arTests = array();
	$arTests['DATE TIME'] = date('d.m.Y H:i:s');
	$arTests['kanc_id'] = $kanc_id;
	$arTests['entityTypeId'] = $_REQUEST['data']['FIELDS']['ENTITY_TYPE_ID'];
	$arNewKanc_data = CRest::call("crm.item.get", array(
		"entityTypeId" => 143,
		"id" => $kanc_id
	));
	$arTests['arNewKanc_data'] = $arNewKanc_data;
	// если хотя бы одно дело перешло в подтверждение, то сделку переводим в оплату
	
	if($arNewKanc_data['result']['item']['id'] && $arNewKanc_data['result']['item']['ufCrm4_1651738781'] !== "" && ($arNewKanc_data['result']['item']['webformId'] == '54' || $arNewKanc_data['result']['item']['webformId'] == '60' || $arNewKanc_data['result']['item']['webformId'] == '62' || $arNewKanc_data['result']['item']['webformId'] == '64')){
		$arTests['has_id_kanc'] = $arNewKanc_data['result']['item']['ufCrm4_1651738781'];
		$linked_item_id = trim($arNewKanc_data['result']['item']['ufCrm4_1651738781']);
		$linked_item_id = str_replace(' ', '', $linked_item_id);
		$linked_item_id = str_replace(' ', '', $linked_item_id);
		$linked_item_id = (int) $linked_item_id;
		$arLinkedKanc_data = CRest::call("crm.item.get", array(
			"entityTypeId" => 143,
			"id" => $linked_item_id
		));
		$arTests['arLinkedKanc_data'] = $arLinkedKanc_data;

		sleep(2);
		if(is_array($arLinkedKanc_data['result']['item']) && count($arLinkedKanc_data['result']['item']) > 0){
			$arUpdateKancFields = [];
			foreach($arNewKanc_data['result']['item'] as $k => $arNewKancField){
				if(!empty($arNewKancField) && !in_array($k, ['id','title', 'ufCrm4_1651738781', 'ufCrm4_1651737369', 'ufCrm4_1651233672', 'ufCrm4_1650988733', 'parentId2', 'opportunity', 'taxValue', 'mycompanyId', 'isManualOpportunity', 'currencyId', 'sourceDescription', 'sourceId', 'stageId', 'previousStageId', 'movedBy', 'movedTime', 'observers', 'categoryId', 'contacts', 'contactIds', 'companyId', 'contactId', 'webformId', 'begindate', 'closedate', 'opened', 'updatedTime', 'createdBy', 'updatedBy', 'assignedById', 'id', 'title', 'xmlId', 'createdTime', 'entityTypeId'])){
					// [ "myfile.pdf", "...base64_encoded_file_content..." ], [...]

					// if field type is File, then change values
					if(in_array($k, ['ufCrm4_1650985012', 'ufCrm4_1651051589', 'ufCrm4_1650985069', 'ufCrm4_1650984910', 'ufCrm4_1652024671'])){
						
						$curl_url = $arNewKancField['urlMachine'];
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $curl_url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
						curl_setopt($ch, CURLOPT_HEADER, 1);
						curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'readHeader');
						$output = curl_exec($ch);
						curl_close($ch);

						$a = explode('filename="', $responseHeaders[0]);
						$b = explode('"; filename*=utf-8', $a[1]);
						$arUpdateKancFields[$k] = [
							$b[0],
							base64_encode(file_get_contents($arNewKancField['urlMachine']))
						];
						$curl_url = '';
						$a = $b = $responseHeaders = [];
					}elseif ($k == 'ufCrm626bc33fb3246' || $k == 'ufCrm626bc33fb3246') {
						foreach($arNewKancField as $arPhoto){
							$curl_url = $arPhoto['urlMachine'];
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $curl_url);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
							curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
							curl_setopt($ch, CURLOPT_HEADER, 1);
							curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'readHeader');
							$output = curl_exec($ch);
							curl_close($ch);

							$a = explode('filename="', $responseHeaders[0]);
							$b = explode('"; filename*=utf-8', $a[1]);
							
							$arUpdateKancFields[$k][] = [
								$b[0],
								base64_encode(file_get_contents($arPhoto['urlMachine']))
							];
							$curl_url = '';
							$a = $b = $responseHeaders = [];
						}
					}else{
						$arUpdateKancFields[$k] = $arNewKancField;
					}
				}
			}
			$arTests['arUpdateKancFields'] = $arUpdateKancFields;
			if(count($arUpdateKancFields)>0){
				sleep(2);
				$arKancItemUpdate_data = CRest::call("crm.item.update", array(
					"entityTypeId" => 143,
					"id" => $linked_item_id,
					"fields" => $arUpdateKancFields
				));
				$arDeleteKanc_data = CRest::call("crm.item.delete", array(
					"entityTypeId" => 143,
					"id" => $kanc_id
				));
				$arTests['arDeleteKanc_data'] = $arDeleteKanc_data;
			}
		}
		/*
		$arNewDeal_data = CRest::call("crm.deal.get", array(
			"id" => $arNewKanc_data['result']['item']['parentId2']
		));
		//$arTests['crm.deal.get'] = $arNewDeal_data;
		//if(strpos($arNewDeal_data['result']['TITLE'], "test2") !== false){
			if($arNewDeal_data['result']['STAGE_ID'] == 'C6:EXECUTING'){
				sleep(2);
				$dealUpdateFields['STAGE_ID'] = 'C6:FINAL_INVOICE';
				$arDealUpdate_data = CRest::call("crm.deal.update", array(
					"id" => $arNewKanc_data['result']['item']['parentId2'],
					"fields" => $dealUpdateFields
				));
				//$arTests['arDealUpdate_data'] = $arDealUpdate_data;
			}
		//}
		*/
	}
	
	file_put_contents(
		$path_parts['dirname']."/onCrmDynamicItemAdd.log", 
		print_r($arTests, true), 
		FILE_APPEND
	);
}
?>