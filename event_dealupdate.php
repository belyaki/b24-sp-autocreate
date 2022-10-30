<?php
@set_time_limit(0);
@ignore_user_abort(true);
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']);
chdir($path_parts['dirname']);
require_once (__DIR__.'/include/crest.php');
$_REQUEST['DATE TIME'] = date('d.m.Y H:i:s');
/*
file_put_contents(
	$path_parts['dirname']."/event_deal_update_all.log", 
	print_r($_REQUEST, true), 
	FILE_APPEND
);
*/
if($_REQUEST['event'] == 'ONCRMDEALUPDATE'){
	$deal_id = $_REQUEST['data']['FIELDS']['ID'];

	$arTests = array();
	$arTests['DATE TIME'] = date('d.m.Y H:i:s');
	$arTests['deal_id'] = $deal_id;
	$arNewDeal_data = CRest::call("crm.deal.get", array(
		"id" => $deal_id
	));
	$arTests['crm.deal.get'] = $arNewDeal_data;
	// Контракт воронка
	if($arNewDeal_data['result']['CATEGORY_ID'] == '6' && $arNewDeal_data['result']['STAGE_ID'] == 'C6:EXECUTING'){
		// Если сделка в стадии "передана в канцелярию", то создаём дела канцелярии из товаров, если дел ещё нет
		$arKancItems_data = CRest::call("crm.item.list", array(
			"entityTypeId" => 143,
			"filter" => ["parentId2" => $deal_id]
		));
		//$arTests['arKancItems_data'] = $arKancItems_data;
		sleep(2);
		$arDealProducts_data = CRest::call("crm.deal.productrows.get", array(
			"id" => $deal_id
		));
		$arTests['arDealProducts_data'] = $arDealProducts_data;
		
		//если в сделке есть товары и дел в канцелярии ещё нет, то нужно их создать
		if(count($arKancItems_data['result']['items']) == 0 && count($arDealProducts_data['result']) > 0){
			$arKancItems = [];
			$arKancLinks = [];
			$arKancLinksEN = [];
			$crm_item_lang_list = '';
			$contact_id = $arNewDeal_data['result']['CONTACT_ID'];
			sleep(2);
			$arContact_data = CRest::call("crm.contact.get", array(
				"id" => $contact_id
			));
			
			$arContactFields_data = CRest::call("crm.contact.userfield.list", array(
				"order" => ["SORT" => "ASC"],
				"filter" => [
					"LANG" => "en",
					"FIELD_NAME" => "UF_CRM_613F281A02FB4"
				]
			));
			$arContactFields = $arContactFields_data['result'];
			sleep(2);
			if($arContact_data['result']['UF_CRM_613F281A02FB4'] !== ""){
				$arItemFields_data = CRest::call("crm.item.fields",
					array("entityTypeId" => 143)
				);
	
				foreach($arItemFields_data['result']['fields'] as $k => $item_field){
					if($k == "ufCrm4_1652708951"){
						$arItemFields = $item_field;
					}
				}
	
				foreach($arContactFields[0]['LIST'] as $contactListValues){
					if($arContact_data['result']['UF_CRM_613F281A02FB4'] == $contactListValues['ID']){
						foreach($arItemFields['items'] as $itemListValues){
							if($itemListValues['VALUE'] == $contactListValues['VALUE']){
								$crm_item_lang_list = $itemListValues['ID'];
							}
						}
					}
				}
			}

			sleep(1);
			foreach($arDealProducts_data['result'] as $k => $arItem){
				
				$resCurProductsList = CRest::call(
					'crm.product.get',
					[
						"id" => $arItem['PRODUCT_ID']
					]
				);
				$arTests['DEAL ITEMS'][$k]['resCurProductsList'] = $resCurProductsList;
				if(!$resCurProductsList['result']){
					sleep(1);
					$resCatalogProductGet = CRest::call(
						'catalog.product.get',
						[
							"id" => $arItem['PRODUCT_ID']
						]
					);
					/*
					if($resCatalogProductGet['result']['product']['property94']['value']){
						$arItem['PRODUCT_ID'] = $resCatalogProductGet['result']['product']['property94']['value'];
					
						$resCurProductsList = CRest::call(
							'crm.product.get',
							[
								"id" => $arItem['PRODUCT_ID']
							]
						);
						sleep(1);
					}
					*/
					if($resCatalogProductGet['result']['product']['parentId']['value']){
						$arItem['PRODUCT_ID'] = $resCatalogProductGet['result']['product']['parentId']['value'];
					
						$resCurProductsList = CRest::call(
							'crm.product.get',
							[
								"id" => $arItem['PRODUCT_ID']
							]
						);
						sleep(1);
					}
					
					$arTests['DEAL ITEMS'][$k]['resCurProductsList']['if'] = $resCurProductsList;
				}
				if($resCurProductsList['result']['PROPERTY_224']['value'] == '13690'){
					// VIZA
					$kancItemFields_categoryId = '4';
					$kancItemFields_stageId = 'DT143_4:NEW';
				}elseif($resCurProductsList['result']['PROPERTY_224']['value'] == '13692'){
					// KP
					$kancItemFields_categoryId = '6';
					$kancItemFields_stageId = 'DT143_6:NEW';
				}else{
					// default 
					$kancItemFields_categoryId = '4';
					$kancItemFields_stageId = 'DT143_4:NEW';
				}
				sleep(1);
				
				if($arItem['QUANTITY'] > 1){
					//$arTests['quantity more than one'][] = $arItem;
					$dp = 1;
					while ($dp <= $arItem['QUANTITY']) {
						sleep(2);
						//$arTests['quantity more than one']['dp'][] = $dp;
						$kancItemFields = [];
						
						$kancItemFields['title'] = "#".$deal_id." - ".$arItem['PRODUCT_NAME']." - #".$dp;
						//$kancItemFields['opened'] = '';
						$kancItemFields['stageId'] = $kancItemFields_stageId;
						$kancItemFields['categoryId'] = $kancItemFields_categoryId;
						$kancItemFields['companyId'] = $arNewDeal_data['result']['COMPANY_ID'];
						$kancItemFields['contactId'] = $arNewDeal_data['result']['CONTACT_ID'];
						$kancItemFields['createdBy'] = $arNewDeal_data['result']['ASSIGNED_BY_ID'];
						$kancItemFields['assignedById'] = $arNewDeal_data['result']['ASSIGNED_BY_ID'];
						$kancItemFields['opportunity'] = $arItem['PRICE'];
						$kancItemFields['currencyId'] = $arNewDeal_data['result']['CURRENCY_ID'];
						$kancItemFields['parentId2'] = $deal_id;
						$kancItemFields['ufCrm4_1650988733'] = $deal_id;
						$kancItemFields['ufCrm4_1652708951'] = $crm_item_lang_list;
						$arKancItemAdd_data = CRest::call("crm.item.add", array(
							"entityTypeId" => 143,
							"fields" => $kancItemFields
						));

						$arTests['arKancItemAdd_data'][] = $arKancItemAdd_data;
						if($arKancItemAdd_data['result']['item']['id']) {
							if($kancItemFields_categoryId == '4'){
								// VIZA
								$kancItemUpdateFields['ufCrm4_1651737369'] = $arKancLinks[] = 'https://b24-m72enc.bitrix24.site/crm_form_uomm0/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];
								$kancItemUpdateFields['ufCrm4_1654524437'] = $arKancLinksEN[] = 'https://b24-m72enc.bitrix24.site/crm_form_7raa1/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];

								$arKancItemUpdate_data = CRest::call("crm.item.update", array(
									"entityTypeId" => 143,
									"id" => $arKancItemAdd_data['result']['item']['id'],
									"fields" => $kancItemUpdateFields
								));
							}elseif($kancItemFields_categoryId == '6'){
								// KP
								$kancItemUpdateFields['ufCrm4_1654524747'] = $arKancLinks[] = 'https://b24-m72enc.bitrix24.site/crm_form_gibwb/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];
								$kancItemUpdateFields['ufCrm4_1654524802'] = $arKancLinksEN[] = 'https://b24-m72enc.bitrix24.site/crm_form_omd8y/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];

								$arKancItemUpdate_data = CRest::call("crm.item.update", array(
									"entityTypeId" => 143,
									"id" => $arKancItemAdd_data['result']['item']['id'],
									"fields" => $kancItemUpdateFields
								));
							}
						
							sleep(2);
							
							$kancProductItemFields = [];
							$kancProductItemFields['productId'] = $arItem['PRODUCT_ID'];
							$kancProductItemFields['ownerId'] = $arKancItemAdd_data['result']['item']['id'];
							$kancProductItemFields['ownerType'] = 'T8f';
							$kancProductItemFields['price'] = $arItem['PRICE'];
							$kancProductItemFields['quantity'] = 1;
							$arKancProductItemAdd_data = CRest::call("crm.item.productrow.add", array(
								"fields" => $kancProductItemFields
							));
							$arTests['arKancProductItemAdd_data fields'][] = $kancProductItemFields;
							$arTests['arKancProductItemAdd_data'][] = $arKancProductItemAdd_data;
						}
						++$dp;
					}
				}else{
					sleep(2);

					$kancItemFields = [];
					
					$kancItemFields['title'] = "#".$deal_id." - ".$arItem['PRODUCT_NAME'];
					//$kancItemFields['opened'] = '';
					$kancItemFields['stageId'] = $kancItemFields_stageId;
					$kancItemFields['categoryId'] = $kancItemFields_categoryId;
					$kancItemFields['companyId'] = $arNewDeal_data['result']['COMPANY_ID'];
					$kancItemFields['contactId'] = $arNewDeal_data['result']['CONTACT_ID'];
					$kancItemFields['createdBy'] = $arNewDeal_data['result']['ASSIGNED_BY_ID'];
					$kancItemFields['assignedById'] = $arNewDeal_data['result']['ASSIGNED_BY_ID'];
					$kancItemFields['opportunity'] = $arItem['PRICE'];
					$kancItemFields['currencyId'] = $arNewDeal_data['result']['CURRENCY_ID'];
					$kancItemFields['parentId2'] = $deal_id;
					$kancItemFields['ufCrm4_1650988733'] = $deal_id;
					$kancItemFields['ufCrm4_1652708951'] = $crm_item_lang_list;

					$arKancItemAdd_data = CRest::call("crm.item.add", array(
						"entityTypeId" => 143,
						"fields" => $kancItemFields
					));
					$arTests['arKancItemAdd_data'][] = $arKancItemAdd_data;
					if($arKancItemAdd_data['result']['item']['id']) {
						if($kancItemFields_categoryId == '4'){
							// VIZA
							$kancItemUpdateFields['ufCrm4_1651737369'] = $arKancLinks[] = 'https://b24-m72enc.bitrix24.site/crm_form_uomm0/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];
							$kancItemUpdateFields['ufCrm4_1654524437'] = $arKancLinksEN[] = 'https://b24-m72enc.bitrix24.site/crm_form_7raa1/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];

							$arKancItemUpdate_data = CRest::call("crm.item.update", array(
								"entityTypeId" => 143,
								"id" => $arKancItemAdd_data['result']['item']['id'],
								"fields" => $kancItemUpdateFields
							));
						}elseif($kancItemFields_categoryId == '6'){
							// KP
							$kancItemUpdateFields['ufCrm4_1654524747'] = $arKancLinks[] = 'https://b24-m72enc.bitrix24.site/crm_form_gibwb/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];
							$kancItemUpdateFields['ufCrm4_1654524802'] = $arKancLinksEN[] = 'https://b24-m72enc.bitrix24.site/crm_form_omd8y/?smartproc_id='.$arKancItemAdd_data['result']['item']['id'];

							$arKancItemUpdate_data = CRest::call("crm.item.update", array(
								"entityTypeId" => 143,
								"id" => $arKancItemAdd_data['result']['item']['id'],
								"fields" => $kancItemUpdateFields
							));
						}

						sleep(2);

						$kancProductItemFields = [];
						$kancProductItemFields['productId'] = $arItem['PRODUCT_ID'];
						$kancProductItemFields['ownerId'] = $arKancItemAdd_data['result']['item']['id'];
						$kancProductItemFields['ownerType'] = 'T8f';
						$kancProductItemFields['price'] = $arItem['PRICE'];
						$kancProductItemFields['quantity'] = $arItem['QUANTITY'];
						$arKancProductItemAdd_data = CRest::call("crm.item.productrow.add", array(
							"fields" => $kancProductItemFields
						));
						$arTests['arKancProductItemAdd_data fields'][] = $kancProductItemFields;
						$arTests['arKancProductItemAdd_data'][] = $arKancProductItemAdd_data;
					}
				}
				$arTests['DEAL ITEMS'][$k]['core'] = $arItem;
			}
			if(count($arKancLinks)>0 || count($arKancLinksEN)>0){
				/*
				Добрый день, (подтянуть ИФ контакта) 

				Спасибо что обратились за юридическими услугами в нашу компанию!

				Для реализации заказа ID (подтянуть номер id сделки отдела продаж) от (подтянуть дату заказа) просим заполнить форму передачи дела специалисту.

				С уважением, L&S Business Consortium
				(Тут будет ещё телефон)

				Я думаю что это письмо будет всегда одинаковым, на любую услугу

				На английский я могу только через переводчик перевести. Попрошу вас перевести самостоятельно, если владеете языком)
				*/
				// Если есть ссылки, то надо создать письмо в сделке
				// Текст письма зависит от языка (если русский - то русский, остальные - англ текст)
				// UF_CRM_613F281A02FB4 свойство, 8666 значение "русский"
				
				sleep(2);
				/*
				$arContact_data = CRest::call("crm.contact.get", array(
					"id" => $contact_id
				));
				*/
				//$arTests['arContact_data'] = $arContact_data;
				//$arTests['arKancLinks'] = $arKancLinks;
				//$arTests['arKancLinksEN'] = $arKancLinksEN;
				$mailTitle = '';
				$mailText = '';
				$contactMail = $arContact_data['result']['EMAIL'][0]['VALUE'];
				$dtime = DateTime::createFromFormat("d/m/Y H:i:s", date("d/m/Y H:i:s"));
				$current_call_timestamp = $dtime->getTimestamp();
				$cur_time = date('c', $current_call_timestamp);
				

				$deal_start_date = date('d.m.Y', strtotime($arNewDeal_data['result']['BEGINDATE']));
				

				$arStaff_data = CRest::call("user.get", array(
					"ID" => $arNewDeal_data['result']['ASSIGNED_BY_ID']
				));
				//$arTests['arStaff_data'] = $arStaff_data;

				$linkStr = '';
				foreach($arKancLinks as $link){
					$linkStr .= "<a href='".$link."'>".$link."</a><br />";
				}

				$linkStrEN = '';
				foreach($arKancLinksEN as $linkEN){
					$linkStrEN .= "<a href='".$linkEN."'>".$linkEN."</a><br />";
				}

				if($arContact_data['result']['UF_CRM_613F281A02FB4'] == '8666'){
					// Если язык русский
					$mailTitle = "Форма передачи дела";
					$mailText = "<p>Добрый день, ".$arContact_data['result']['NAME']." ".$arContact_data['result']['LAST_NAME']."</p>
					<br />
					<p>Спасибо, что обратились за юридическими услугами в нашу компанию!</p>
					<p>Для реализации заказа ".$deal_id." от ".$deal_start_date." просим заполнить форму передачи дела специалисту.</p>
					<br />
					".$linkStr."
					<br />
					<br />
					<p>
						С уважением, L&S Business Consortium <br />
					</p>";
					//<a href='tel:+48 514 333 303'>+48 514 333 303</a><br />
					
				}else{
					$mailTitle = "Form of case transfer";
					$mailText = "<p>Good day, ".$arContact_data['result']['NAME']." ".$arContact_data['result']['LAST_NAME']."</p>
					<br />
					<p>Thank you for contacting our firm for legal services!</p>
					<p>To implement the order ".$deal_id." from ".$deal_start_date." please fill out the form for transferring the case to a specialist.</p>
					<br />
					".$linkStrEN."
					<br />
					<br />
					<p>
						Best regards, L&S Business Consortium <br />
					</p>";
					//<a href='tel:+48 514 333 303'>+48 514 333 303</a><br />
				}
				
				$staff['ID'] = $arStaff_data['result'][0]['ID'];
				$staff['NAME'] = $arStaff_data['result'][0]['NAME'];
				$staff['LAST_NAME'] = $arStaff_data['result'][0]['LAST_NAME'];
				$staff['EMAIL'] = $arStaff_data['result'][0]['EMAIL'];
				$fields = [
					"OWNER_TYPE_ID" => 2,
					"OWNER_ID" => $deal_id,
					"TYPE_ID" => 4,
					"COMMUNICATIONS" => [
						[
							"VALUE" => $contactMail,
							"ENTITY_ID" => $contact_id,
							"ENTITY_TYPE_ID" => 3
						]
					], //где 3 - тип "контакт"
					"SUBJECT" => $mailTitle,
					"START_TIME" => $cur_time,
					"END_TIME" => $cur_time,
					"COMPLETED" => "Y",  //////////////////////////////////////////////////////// ТУТ ПОКА N
					"RESPONSIBLE_ID" => $staff['ID'],
					"AUTHOR_ID" => $staff['ID'],
					"EDITOR_ID" => $staff['ID'],
					"DESCRIPTION" => $mailText,
					"DESCRIPTION_TYPE" => 3, //из метода crm.enum.contenttype - HTML
					"DIRECTION" => 2,
					'SETTINGS' => [
						'MESSAGE_FROM' => implode(
							' ',
							[$staff['NAME'], $staff['LAST_NAME'], '<' . $staff['EMAIL'] . '>']
						),
						'DISABLE_SENDING_MESSAGE_COPY' => 'Y'
					],
				];

				$arActivityAdd_data = CRest::call("crm.activity.add", array(
					"fields" => $fields
				));
				$arTests["arActivityAdd_data fields"] = $fields;
				$arTests["arActivityAdd_data"] = $arActivityAdd_data;
			}
		}
	}elseif($arNewDeal_data['result']['CATEGORY_ID'] == '6' && $arNewDeal_data['result']['STAGE_ID'] == 'C6:WON'){
		// Если сделка оплачена целиком (выиграна), то надо перевести все дела канцелярии в статус Комплектация
		$arKancItems_data = CRest::call("crm.item.list", array(
			"entityTypeId" => 143,
			"filter" => ["parentId2" => $deal_id]
		));
		//$arTests['arKancItems_data'] = $arKancItems_data;
		if(count($arKancItems_data['result']['items']) > 0){
			sleep(2);
			foreach($arKancItems_data['result']['items'] as $arKancItem){
				if($arKancItem['entityTypeId'] == '143'){

					$arKancItemsUpdate_data = CRest::call("crm.item.update", array(
						"entityTypeId" => 143,
						"id" => $arKancItem['id'],
						"fields" => ["stageId" => 'DT143_4:UC_QZJEFV']
					));
					//$arTests['arKancItemsUpdate_data'][] = $arKancItemsUpdate_data;
					sleep(1);
				}
			}
		}
	}elseif($arNewDeal_data['result']['CATEGORY_ID'] == '6' && $arNewDeal_data['result']['STAGE_ID'] == 'C6:FINAL_INVOICE'){
		// Если оплатили за один раз, то сделка выиграна
		if($arNewDeal_data['result']['UF_CRM_1649748896577'] == 0){
			$dealUpdateFields = [];
			$dealUpdateFields['STAGE_ID'] = 'C6:WON';
			sleep(10);
			$arDealUpdate_data = CRest::call("crm.deal.update", array(
				"id" => $deal_id,
				"fields" => $dealUpdateFields
			));
			//$arTests['arDealUpdate_data C6:FINAL_INVOICE'] = $arDealUpdate_data;
		}
	}

	// Деление платежей
	if(
		$arNewDeal_data['result']['CATEGORY_ID'] == '6' && 
		(
			$arNewDeal_data['result']['STAGE_ID'] == 'C6:NEW' ||
			$arNewDeal_data['result']['STAGE_ID'] == 'C6:PREPAYMENT_INVOICE' ||
			$arNewDeal_data['result']['STAGE_ID'] == 'C6:EXECUTING'
		)
	) {
		$arDealProducts_data = CRest::call("crm.deal.productrows.get", array(
			"id" => $deal_id
		));
		//$arTests['arDealProducts_data'] = $arDealProducts_data;
		$paymentCount = 0;
		if($arDealProducts_data['result']){
			foreach($arDealProducts_data['result'] as $arItem){
				sleep(2);
				$resCurProductsList = CRest::call(
					'crm.product.get',
					[
						"id" => $arItem['PRODUCT_ID']
					]
				);
				if(!$resCurProductsList['result']){
					sleep(1);
					$resCatalogProductGet = CRest::call(
						'catalog.product.get',
						[
							"id" => $arItem['PRODUCT_ID']
						]
					);
					$arItem['PRODUCT_ID'] = $resCatalogProductGet['result']['product']['property94']['value'];
					$resCurProductsList = CRest::call(
						'crm.product.get',
						[
							"id" => $arItem['PRODUCT_ID']
						]
					);
					sleep(1);
				}
				//$arTests['resCurProductsList'][] = $resCurProductsList;
				if($resCurProductsList['result']['PROPERTY_224']['value'] == '13692'){
					// KP
					// Доделать разделение платежей. сейчас стоит что делим сумму на 2, но если товары будут со свойством KP то платежи надо делить на 3 части
					$paymentCount = 3;
				}
			}
			sleep(2);
			if($arNewDeal_data['result']['UF_CRM_1651468177'] !== '' || $paymentCount !== 0){
				if($arNewDeal_data['result']['UF_CRM_1651468177'] == '1' && $paymentCount !== 3 && ($arNewDeal_data['result']['UF_CRM_1649748824651'] == '')){
					//если указывает "1" - то делем общую сумму на 1 и заполняем следующие поля. https://disk.yandex.ru/i/o-V6stdj8_BEQw. где сумма это результат деления, а дата +7 дней от сегодня.

					// ДАТА КОНЕЧНОГО СРОКА ОПЛАТЫ 1 / DEADLINE DATE 1
					// UF_CRM_1649748805347

					// СУММА КОНЕЧНОГО СРОКА ОПЛАТЫ 1 / DEADLINE AMOUNT 1
					// UF_CRM_1649748824651

					$new_date = date('d.m.Y', strtotime(date('d.m.Y')) + 60 * 60 * 24 * 7);
					$new_summ = $arNewDeal_data['result']['OPPORTUNITY']/1;
					$dealUpdateFields = [];
					if($arNewDeal_data['result']['UF_CRM_1649748805347'] == ''){
						$dealUpdateFields['UF_CRM_1649748805347'] = $new_date;
					}
					if($arNewDeal_data['result']['UF_CRM_1649748824651'] == ''){
						$dealUpdateFields['UF_CRM_1649748824651'] = $new_summ;
					}
					if($arNewDeal_data['result']['UF_CRM_UF_CRM_1649748896577'] == ''){
						$dealUpdateFields['UF_CRM_1649748896577'] = 0;
					}
					if(count($dealUpdateFields)>0){
						sleep(2);
						$arDealUpdate_data = CRest::call("crm.deal.update", array(
							"id" => $deal_id,
							"fields" => $dealUpdateFields
						));
					}
					//$arTests['arDealUpdate_data summ'] = $arDealUpdate_data;
				}elseif($arNewDeal_data['result']['UF_CRM_1651468177'] == '2' && $paymentCount !== 3 && ($arNewDeal_data['result']['UF_CRM_1649748824651'] == '' && $arNewDeal_data['result']['UF_CRM_1649748896577'] == '')){
					//если указывает "2" - то делем общую сумму на 2 и заполняем следующие поля. https://disk.yandex.ru/i/o-V6stdj8_BEQw и https://disk.yandex.ru/i/3RWwj2_IPPX3bg. где суммы это результат деления, а дата +70 дней от сегодня.

					// ДАТА КОНЕЧНОГО СРОКА ОПЛАТЫ 2 / DEADLINE DATE 2
					// UF_CRM_1649748878698

					// СУММА КОНЕЧНОГО СРОКА ОПЛАТЫ 2 / DEADLINE AMOUNT 2
					// UF_CRM_1649748896577

					$new_date1 = date('d.m.Y', strtotime(date('d.m.Y')) + 60 * 60 * 24 * 7);
					$new_date2 = date('d.m.Y', strtotime(date('d.m.Y')) + 60 * 60 * 24 * 70);
					$new_summ = $arNewDeal_data['result']['OPPORTUNITY']/2;
					$dealUpdateFields = [];

					if($arNewDeal_data['result']['UF_CRM_1649748805347'] == ''){
						$dealUpdateFields['UF_CRM_1649748805347'] = $new_date1;
					}
					if($arNewDeal_data['result']['UF_CRM_1649748824651'] == ''){
						$dealUpdateFields['UF_CRM_1649748824651'] = $new_summ;
					}

					if($arNewDeal_data['result']['UF_CRM_1649748878698'] == ''){
						$dealUpdateFields['UF_CRM_1649748878698'] = $new_date2;
					}
					if($arNewDeal_data['result']['UF_CRM_1649748896577'] == ''){
						$dealUpdateFields['UF_CRM_1649748896577'] = $new_summ;
					}
					if(count($dealUpdateFields)>0){
						sleep(2);
						$arDealUpdate_data = CRest::call("crm.deal.update", array(
							"id" => $deal_id,
							"fields" => $dealUpdateFields
						));
					}
					//$arTests['arDealUpdate_data summ'] = $arDealUpdate_data;
				}elseif($arNewDeal_data['result']['UF_CRM_1651468177'] == '3' || $paymentCount == 3 && ($arNewDeal_data['result']['UF_CRM_1649748824651'] == '' && $arNewDeal_data['result']['UF_CRM_1649748896577'] == '' && $arNewDeal_data['result']['UF_CRM_1649748943087'] == '')){
					//если указывает "3" - то пока не знаю. так как это к другому типу канцелярии будет относиться
			
					// ДАТА КОНЕЧНОГО СРОКА ОПЛАТЫ 3 / DEADLINE DATE 3
					// UF_CRM_1649748927468

					// СУММА КОНЕЧНОГО СРОКА ОПЛАТЫ 3 / DEADLINE AMOUNT 3
					// UF_CRM_1649748943087
					
					$new_date1 = date('d.m.Y', strtotime(date('d.m.Y')) + 60 * 60 * 24 * 7);
					$new_date2 = date('d.m.Y', strtotime(date('d.m.Y')) + 60 * 60 * 24 * 70);
					$new_date3 = date('d.m.Y', strtotime(date('d.m.Y')) + 60 * 60 * 24 * 120);
					$new_summ = $arNewDeal_data['result']['OPPORTUNITY']/3;
					$dealUpdateFields = [];
					if($arNewDeal_data['result']['UF_CRM_1649748805347'] == ''){
						$dealUpdateFields['UF_CRM_1649748805347'] = $new_date1;
					}
					if($arNewDeal_data['result']['UF_CRM_1649748824651'] == ''){
						$dealUpdateFields['UF_CRM_1649748824651'] = $new_summ;
					}

					if($arNewDeal_data['result']['UF_CRM_1649748878698'] == ''){
						$dealUpdateFields['UF_CRM_1649748878698'] = $new_date2;
					}
					if($arNewDeal_data['result']['UF_CRM_1649748896577'] == ''){
						$dealUpdateFields['UF_CRM_1649748896577'] = $new_summ;
					}

					if($arNewDeal_data['result']['UF_CRM_1649748927468'] == ''){
						$dealUpdateFields['UF_CRM_1649748927468'] = $new_date3;
					}
					if($arNewDeal_data['result']['UF_CRM_1649748943087'] == ''){
						$dealUpdateFields['UF_CRM_1649748943087'] = $new_summ;
					}
					
					if(count($dealUpdateFields)>0){
						$dealUpdateFields['UF_CRM_1651468177'] = 3;
						sleep(2);
						$arDealUpdate_data = CRest::call("crm.deal.update", array(
							"id" => $deal_id,
							"fields" => $dealUpdateFields
						));
					}
					//$arTests['arDealUpdate_data'] = $arDealUpdate_data;
				}
			}
			//$arTests['dealUpdateFields'] = $dealUpdateFields;
			//$arTests['arDealUpdate_data'] = $arDealUpdate_data;
		}
	}

	// Если сделка в стадии Payment 2, то переводим все КП дела в статус Odciski
	if($arNewDeal_data['result']['CATEGORY_ID'] == '6' && $arNewDeal_data['result']['STAGE_ID'] == 'C6:FINAL_INVOICE'){
		$arKancItems_data = CRest::call("crm.item.list", array(
			"entityTypeId" => 143,
			"filter" => ["parentId2" => $deal_id]
		));
		//$arTests['arKancItems_data'] = $arKancItems_data;
		if(count($arKancItems_data['result']['items']) >0){
			foreach($arKancItems_data['result']['items'] as $arItem){
				if($arItem['stageId'] !== 'DT143_6:UC_KP277Y'){
					$arKancItemUpdate_data = CRest::call("crm.item.update", array(
						"entityTypeId" => 143,
						"id" => $arItem['id'],
						"fields" => ['stageId' => 'DT143_6:UC_KP277Y']
					));
					//$arTests['arKancItemUpdate_data'] = $arKancItemUpdate_data;
				}
			}
		}
	}

	file_put_contents(
		$path_parts['dirname']."/ONCRMDEALUPDATE.log", 
		print_r($arTests, true), 
		FILE_APPEND
	);
}
?>