<?php
require_once (__DIR__.'/include/crest.php');
require_once(__DIR__."/include/header.php");
$rand = rand(0,500);
?>
<ul>
	<li><a href="<?=INDEX_PATH?>?test=user.current&rand=<?=$rand?>">User Info</a>
	<?php
	/*
	<li>---</li>
	<li><a href="<?=INDEX_PATH?>?test=event.bind.oncrmdealadd">Add event handler ONCRM DEAL ADD</a>
	<li><a href="<?=INDEX_PATH?>?test=event.unbind.oncrmdealadd">Remove event handler ONCRM DEAL ADD</a>
	<li>---</li>
	*/?>
	<li>---</li>
	<li><a href="<?=INDEX_PATH?>?test=event.bind.onCrmDynamicItemAdd&rand=<?=$rand?>">Add event handler ONCRM VIZA ADD (smart process)</a>
	<li><a href="<?=INDEX_PATH?>?test=event.unbind.onCrmDynamicItemAdd&rand=<?=$rand?>">Remove event handler ONCRM VIZA ADD (smart process)</a>
	<li>---</li>
	<li>---</li>
	<li>---</li>
	<li><a href="<?=INDEX_PATH?>?test=event.bind.onCrmDynamicItemUpdate&rand=<?=$rand?>">Add event handler ONCRM VIZA UPDATE (smart process)</a>
	<li><a href="<?=INDEX_PATH?>?test=event.unbind.onCrmDynamicItemUpdate&rand=<?=$rand?>">Remove event handler ONCRM VIZA UPDATE (smart process)</a>
	<li>---</li>
	<li>---</li>
	<li><a href="<?=INDEX_PATH?>?test=event.bind.oncrmdealupdate&rand=<?=$rand?>">Add event handler ONCRM DEAL UPDATE</a>
	<li><a href="<?=INDEX_PATH?>?test=event.unbind.oncrmdealupdate&rand=<?=$rand?>">Remove event handler ONCRM DEAL UPDATE</a>
	<li>---</li>
	<li><a href="<?=INDEX_PATH?>?test=event.get&rand=<?=$rand?>">Show all binded events</a></li>
	<li>---</li>
</ul>
<?php
$dtime = DateTime::createFromFormat("d/m/Y H:i:s", date("d/m/Y H:i:s"));
$current_call_timestamp = $dtime->getTimestamp();
echo date('c', $current_call_timestamp);

$test = isset($_REQUEST["test"]) ? $_REQUEST["test"] : "";
switch($test)
{
	case 'user.current':
		$data = CRest::call('profile');
	break;

	/*
	case 'event.unbind.oncrmdealadd':
		$data = CRest::call("event.unbind", array(
			"EVENT" => "ONCRMDEALADD",
			"HANDLER" => FOLDER_PATH."event_dealadd.php",
		));
	break;

	case 'event.unbind.oncrmdealadd':
		$data = CRest::call("event.unbind", array(
			"EVENT" => "ONCRMDEALADD",
			"HANDLER" => FOLDER_PATH."event_dealadd.php",
		));
	break;
	*/
	case 'event.bind.onCrmDynamicItemAdd':
		$data = CRest::call("event.bind", array(
			"EVENT" => "onCrmDynamicItemAdd",
			"HANDLER" => FOLDER_PATH."event_kancadd.php",
		));
	break;

	case 'event.unbind.onCrmDynamicItemAdd':
		$data = CRest::call("event.unbind", array(
			"EVENT" => "onCrmDynamicItemAdd",
			"HANDLER" => FOLDER_PATH."event_kancadd.php",
		));
	break;


	case 'event.bind.onCrmDynamicItemUpdate':
		$data = CRest::call("event.bind", array(
			"EVENT" => "onCrmDynamicItemUpdate",
			"HANDLER" => FOLDER_PATH."event_kancupdate.php",
		));
	break;

	case 'event.unbind.onCrmDynamicItemUpdate':
		$data = CRest::call("event.unbind", array(
			"EVENT" => "onCrmDynamicItemUpdate",
			"HANDLER" => FOLDER_PATH."event_kancupdate.php",
		));
	break;


	case 'event.bind.oncrmdealupdate':
		$data = CRest::call("event.bind", array(
			"EVENT" => "ONCRMDEALUPDATE",
			"HANDLER" => FOLDER_PATH."event_dealupdate.php",
		));
	break;

	case 'event.unbind.oncrmdealupdate':
		$data = CRest::call("event.unbind", array(
			"EVENT" => "ONCRMDEALUPDATE",
			"HANDLER" => FOLDER_PATH."event_dealupdate.php",
		));
	break;

	case 'event.get':
		$data = CRest::call("event.get");
	break;

	default:
		$data = $_SESSION["query_data"];
	break;
}

echo '<pre>';
var_export($data);
echo '<br>';
echo '<br>';


echo '<br>';
echo '<br>';
echo '</pre>';
/******************************************************************/
require_once("include/footer.php");
?>