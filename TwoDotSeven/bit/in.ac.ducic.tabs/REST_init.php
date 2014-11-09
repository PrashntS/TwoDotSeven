<?php
namespace TwoDot7\Bit\in_ac_ducic_tabs\REST;
use \TwoDot7\Bit\in_ac_ducic_tabs\Controller as Controller;
require in_ac_ducic_tabs."/CONTROLLER_init.php";

function init() {
	switch ($_GET['BitAction']) {
		case 'addIntoAddressBook';

			\TwoDot7\User\REST::AUTH(array(
				'Token' => array('in.ac.ducic.tabs.admin', 'in.ac.ducic.tabs.superuser')
				));

			if (!isset($_POST['FirstName']) &&
				!isset($_POST['LastName']) &&
				!isset($_POST['DisplayName']) &&
				!isset($_POST['NickName']) &&
				!isset($_POST['PrimaryEmail']) &&
				!isset($_POST['SecondEmail']) &&
				!isset($_POST['_AimScreenName']) &&
				!isset($_POST['HomeAddress']) &&
				!isset($_POST['HomeAddress2']) &&
				!isset($_POST['HomeCity']) &&
				!isset($_POST['HomeState']) &&
				!isset($_POST['HomeZipCode']) &&
				!isset($_POST['HomeCountry']) &&
				!isset($_POST['HomePhone']) &&
				!isset($_POST['WorkAddress']) &&
				!isset($_POST['WorkAddress2']) &&
				!isset($_POST['WorkCity']) &&
				!isset($_POST['WorkState']) &&
				!isset($_POST['WorkZipCode']) &&
				!isset($_POST['WorkCountry']) &&
				!isset($_POST['WorkPhone']) &&
				!isset($_POST['JobTitle']) &&
				!isset($_POST['Department']) &&
				!isset($_POST['Company']) &&
				!isset($_POST['CellularNumber']) &&
				!isset($_POST['FaxNumber'])) {

				header('HTTP/1.0 450 Invalid Request.', true, 450);
				echo "<pre>";
				echo "usage /dev/bit/in.ac.ducic.tabs/addIntoAddressBook\n";
				echo "Incomplete Request. Please include following in your request:\n";
				echo "POST: FirstName (<span style=\"color: #F00\">Required</span>)\n";
				echo "POST: LastName (Optional)\n";
				echo "POST: DisplayName (Optional)\n";
				echo "POST: NickName (Optional)\n";
				echo "POST: PrimaryEmail (<span style=\"color: #F00\">Required</span>)\n";
				echo "POST: SecondEmail (Optional)\n";
				echo "POST: _AimScreenName (Optional)\n";
				echo "POST: HomeAddress (Optional)\n";
				echo "POST: HomeAddress2 (Optional)\n";
				echo "POST: HomeCity (Optional)\n";
				echo "POST: HomeState (Optional)\n";
				echo "POST: HomeZipCode (Optional)\n";
				echo "POST: HomeCountry (Optional)\n";
				echo "POST: HomePhone (Optional)\n";
				echo "POST: WorkAddress (Optional)\n";
				echo "POST: WorkAddress2 (Optional)\n";
				echo "POST: WorkCity (Optional)\n";
				echo "POST: WorkState (Optional)\n";
				echo "POST: WorkZipCode (Optional)\n";
				echo "POST: WorkCountry (Optional)\n";
				echo "POST: WorkPhone (Optional)\n";
				echo "POST: JobTitle (Optional)\n";
				echo "POST: Department (Optional)\n";
				echo "POST: Company (Optional)\n";
				echo "POST: CellularNumber (Optional)\n";
				echo "POST: FaxNumber (Optional)\n";
				echo "</pre>";
				die();
			}


			if (!isset($_POST['FirstName']) ||
				!isset($_POST['PrimaryEmail'])) {
				header('HTTP/1.0 450 Invalid Request.', true, 450);
				echo "<pre>";
				echo "usage /dev/bit/in.ac.ducic.tabs/addIntoAddressBook\n";
				echo "Incomplete Request. Following Fields are Required:\n";
				echo "POST: FirstName (<span style=\"color: #F00\">Required</span>)\n";
				echo "POST: PrimaryEmail (<span style=\"color: #F00\">Required</span>)\n";
				echo "</pre>";
				die();
			}

			$Response = Controller\Utils::addIntoAddressBook($_POST);

			if($Response) {
				header('HTTP/1.0 251 Operation completed successfully.', true, 251);
				header('Content-Type: application/json');
				echo json_encode("Card Added into the AddressBook.");
			}
			else {
				header('HTTP/1.0 461 Error while Processing the Action.', true, 461);
				header('Content-Type: application/json');
				echo json_encode("Some Unknown Error Occured.");
			}
			die;
		
		case 'getJSON':

	 		$Response = Controller\Utils::getArray();
			if($Response) {
				header('HTTP/1.0 251 Operation completed successfully.', true, 251);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			else {
				header('HTTP/1.0 461 Error while Processing the Action.', true, 461);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			die;

		case 'getCardByID':

			if (!isset($_GET['ID'])) {
				header('HTTP/1.0 450 Invalid Request.', true, 450);
				echo "<pre>";
				echo "usage /dev/bit/in.ac.ducic.tabs/getCardByID\n";
				echo "Incomplete Request. Please include following in your request:\n";
				echo "GET: ID (<span style=\"color: #F00\">Required</span>)\n";
				echo "</pre>";
				die();
			}

			$ID = is_numeric($_GET['ID']) ? $_GET['ID'] : 0;
	 		$Response = Controller\Utils::getCardByID($ID);
			if(isset($Response[0])) {
				header('HTTP/1.0 251 Operation completed successfully.', true, 251);
				header('Content-Type: application/json');
				echo json_encode($Response[0], JSON_PRETTY_PRINT);
			}
			else {
				header('HTTP/1.0 461 Error while Processing the Action.', true, 461);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			die;

		case 'deleteCardByID':

			if (!isset($_POST['ID'])) {
				header('HTTP/1.0 450 Invalid Request.', true, 450);
				echo "<pre>";
				echo "usage /dev/bit/in.ac.ducic.tabs/deleteCardByID\n";
				echo "Incomplete Request. Please include following in your request:\n";
				echo "POST: ID (<span style=\"color: #F00\">Required</span>)\n";
				echo "</pre>";
				die();
			}

			$ID = is_numeric($_POST['ID']) ? $_POST['ID'] : 0;
	 		$Response = Controller\Utils::deleteCardByID($ID);
			if($Response) {
				header('HTTP/1.0 251 Operation completed successfully.', true, 251);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			else {
				header('HTTP/1.0 461 Error while Processing the Action.', true, 461);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			die;

		case 'updateCardByID':

			if (!isset($_POST['ID']) ||
				!isset($_POST['FirstName']) ||
				!isset($_POST['PrimaryEmail'])) {

				header('HTTP/1.0 450 Invalid Request.', true, 450);
				echo "<pre>";
				echo "usage /dev/bit/in.ac.ducic.tabs/updateCardByID\n";
				echo "Incomplete Request. Please include following in your request:\n";
				echo "POST: ID (<span style=\"color: #F00\">Required</span>)\n";
				echo "POST: FirstName (<span style=\"color: #F00\">Required</span>)\n";
				echo "POST: LastName (Optional)\n";
				echo "POST: DisplayName (Optional)\n";
				echo "POST: NickName (Optional)\n";
				echo "POST: PrimaryEmail (<span style=\"color: #F00\">Required</span>)\n";
				echo "POST: SecondEmail (Optional)\n";
				echo "POST: _AimScreenName (Optional)\n";
				echo "POST: HomeAddress (Optional)\n";
				echo "POST: HomeAddress2 (Optional)\n";
				echo "POST: HomeCity (Optional)\n";
				echo "POST: HomeState (Optional)\n";
				echo "POST: HomeZipCode (Optional)\n";
				echo "POST: HomeCountry (Optional)\n";
				echo "POST: HomePhone (Optional)\n";
				echo "POST: WorkAddress (Optional)\n";
				echo "POST: WorkAddress2 (Optional)\n";
				echo "POST: WorkCity (Optional)\n";
				echo "POST: WorkState (Optional)\n";
				echo "POST: WorkZipCode (Optional)\n";
				echo "POST: WorkCountry (Optional)\n";
				echo "POST: WorkPhone (Optional)\n";
				echo "POST: JobTitle (Optional)\n";
				echo "POST: Department (Optional)\n";
				echo "POST: Company (Optional)\n";
				echo "POST: CellularNumber (Optional)\n";
				echo "POST: FaxNumber (Optional)\n";
				echo "</pre>";
				die();
			}

			$ID = is_numeric($_POST['ID']) ? $_POST['ID'] : 0;

	 		$Response = Controller\Utils::updateCardByID($ID, $_POST);

			if($Response) {
				header('HTTP/1.0 251 Operation completed successfully.', true, 251);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			else {
				header('HTTP/1.0 461 Error while Processing the Action.', true, 461);
				header('Content-Type: application/json');
				echo json_encode($Response, JSON_PRETTY_PRINT);
			}
			die;

	 	default:
			header('HTTP/1.0 450 Invalid Request.', true, 450);
			echo "<pre>";
			echo "usage /dev/bit/in.ac.ducic.tabs/[addIntoAddressBook, getJSON, getCardByID, deleteCardByID, updateCardByID]\n";
			echo "Incomplete Request. Please read the Documentation.\n";
			echo "</pre>";
	 		break;
	 }
}
?>