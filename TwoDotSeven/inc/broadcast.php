<?php
namespace TwoDot7\Broadcast;
use \TwoDot7\Mailer as Mailer;
use \TwoDot7\Database as Database;
#  _____                      _____ 
# /__   \__      _____       |___  |
#   / /\/\ \ /\ / / _ \         / / 
#  / /    \ V  V / (_) |  _    / /  
#  \/      \_/\_/ \___/  (_)  /_/   

/**
 * Broadcast Origin/Target from/to a User.
 * const USER 1
 * @example Can be accessed by other Namespaces as \TwoDot7\Broadcast\USER or, (int)1.
 */
const USER = 1;

/**
 * Broadcast Origin/Target from/to a registered Bit.
 * const BIT 2
 */
const BIT = 2;

/**
 * Broadcast Origin from the System.
 * const BIT 2
 */
const SYSTEM = 3;

/**
 * Broadcast Origin/Target from/to a Group.
 * const GROUP 4
 */
const GROUP = 4;

/**
 * Broadcast Source to Custom Key.
 * const CUSTOM 5
 */
const CUSTOM = 5;

/**
 * Broadcast Target Default.
 * const _DEFAULT 6
 */
const _DEFAULT = 6;

/**
 * Broadcast Visibility Private.
 * const _PRIVATE 7
 */
const _PRIVATE = 7;
/**
 * Broadcast Visibility Public.
 * const _PUBLIC 8
 */
const _PUBLIC = 8;

/**
 * Action wrapper class for Broadcasts.
 */
class Action {
	public static function Add($Data = array()) {
		// At least the Addition data must be sent in the API call.
		// Fields:
		// OriginType: Required USER / BIT / or SYSTEM
		// Origin: Required. UserName or BitID or System Function, which/who is calling the Addition API
		// TargetType: Optional. USER / Group or Custom.
		// Target: In case of USER: List of UserName(s),
		// Target: In case of Group: List of group(S),
		// Target: In case of Custom: 0.
		// Visible: Default: 0. Public: 1, Target only: 2.
		// Datatype: Index, Generated by the Util::Pack.
		// Data: Packed data.

		if (isset($Data['OriginType'])) switch ($Data['OriginType']) {
			case \TwoDot7\Broadcast\USER:
				if (!isset($Data['Origin']) || !\TwoDot7\Util\Redundant::UserName($Data['Origin']))
					return \TwoDot7\Exception\Error\NotFound::UserName();
				break;

			case \TwoDot7\Broadcast\BIT:
				if (!isset($Data['Origin']) || !\TwoDot7\Util\Redundant::Bit($_Data['Origin']))
					return \TwoDot7\Exception\Error\NotFound::Bit();
				break;

			case \TwoDot7\Broadcast\SYSTEM:
				if (!isset($Data['Origin']) || !\TwoDot7\Validate\Alphanumeric($Data['Origin'], 6, 255)) {
					\TwoDot7\Util\Log("Invalid Broadcast Origin by System: {$Data['Origin']}", "ALERT");
					return \TwoDot7\Exception\Error\Generic();
				}
				break;

			default:
				throw new \TwoDot7\Exception\InvalidArgument("Argument 'OriginType' is not valid.");
		} else throw new \TwoDot7\Exception\IncompleteArgument("OriginType is not specified.");

		if (isset($Data['TargetType'])) switch ($Data['TargetType']) {
			case \TwoDot7\Broadcast\USER:
				if (!isset($Data['Target'])) {
					throw new \TwoDot7\Exception\IncompleteArgument("'Target' is required argument.", 1);
				}
				elseif (is_array($Data['Target'])) {
					;
				} elseif (is_string($Data['Target'])) {
					$Data['Target'] = array($Data['Target']);
				} else {
					throw new \TwoDot7\Exception\InvalidArgument("Argument 'Target' is not valid.");
				}

				foreach ($Data['Target'] as $UserName) {
					if (!\TwoDot7\Util\Redundant::UserName($UserName)) 
						return \TwoDot7\Exception\Error\NotFound::UserName();
				}

				$Data['Target'] = json_encode($Data['Target']);
				break;

			case \TwoDot7\Broadcast\GROUP:
				if (!isset($Data['Target'])) {
					throw new \TwoDot7\Exception\IncompleteArgument("'Target' is required argument.", 1);
				}
				elseif (is_array($Data['Target'])) {
					;
				} elseif (is_string($Data['Target'])) {
					$Data['Target'] = array($Data['Target']);
				} else {
					throw new \TwoDot7\Exception\InvalidArgument("Argument 'Target' is not valid.");
				}

				foreach ($Data['Target'] as $Group) {
					if (!\TwoDot7\Util\Redundant::Group($Group)) 
						return \TwoDot7\Exception\Error\NotFound::Group();
				}

				$Data['Target'] = json_encode($Data['Target']);
				break;

			case \TwoDot7\Broadcast\CUSTOM:
			default:
				if (isset($Data['Target'])) \TwoDot7\Util\Log("WARNING: Target not supported for CUSTOM broadcasts.", "DEBUG");
				$Data['Target'] = json_encode(array());
		} else throw new \TwoDot7\Exception\InvalidArgument("TargetType is not a valid type.");
		
		if (isset($Data['Visible'])) switch ($Data['Visible']) {
			case \TwoDot7\Broadcast\_PRIVATE:
			case \TwoDot7\Broadcast\_PUBLIC:
				break;

			default:
				$Data['Visible'] = \TwoDot7\Broadcast\_PUBLIC;
		} else $Data['Visible'] = \TwoDot7\Broadcast\_PUBLIC;

		// Pack data.
		$Response = Utils::Pack($Data['Data']);
		// Set Datatype and Data.
		$Data['Datatype'] = $Response['Datatype'];
		$Data['Data'] = $Response['Data'];
		$Data['Timestamp'] = time();

		$Query = "INSERT INTO _broadcast (OriginType, Origin, TargetType, Target, Visible, Datatype, Data, Timestamp) VALUES (:OriginType, :Origin, :TargetType, :Target, :Visible, :Datatype, :Data, :Timestamp)";
		
		$Response = \TwoDot7\Database\Handler::Exec($Query, $Data)->rowCount();

		return array( 'Success' => (bool)$Response );
	}

	public static function Remove() {
		// Deletes a broadcast
	}

	public static function Update() {
		// Updates a broadcast
	}
}

class Feed {
	public static function _Public($Begin = 0) {
		$Query = "SELECT * FROM _broadcast WHERE Visible = :Visible AND Timestamp < :Timestamp ORDER BY ID DESC LIMIT 2";
		$Response = \TwoDot7\Database\Handler::Exec($Query, array(
			'Visible' => _PUBLIC,
			'Timestamp' => (!$Begin || $Begin === 0) ? time() : $Begin
			))->fetchAll(\PDO::FETCH_ASSOC);
		return json_encode($Response, JSON_PRETTY_PRINT);
	}

	public static function _User($UserName, $Begin = 0, $Direction = "<") {
		// Feed FOR a Particular User. Not, OF a user.

		if (!is_numeric($Begin) ||
			!($Direction === "<" ||
			$Direction === ">" ||
			$Direction === ">=" ||
			$Direction === "<=")) throw new \TwoDot7\Exception\InvalidArgument("Check arguments\' $Direction validity.");
			

		$DatabaseHandle = new \TwoDot7\Database\Handler;
		
		$Result = array();
		$Counter = 0;
		do {
			$Counter = 0;
			$Direction = 
			$Query = "SELECT * FROM _broadcast WHERE Timestamp {$Direction} :Timestamp ORDER BY ID DESC LIMIT ".\TwoDot7\Config\BROADCAST_FEED_UNIT.";";

			$Response = $DatabaseHandle->Query($Query, array(
				'Timestamp' => (!$Begin || $Begin === 0) ? time() : $Begin,
				));

			while ($Row = $Response->fetch(\PDO::FETCH_ASSOC)) {
				$Counter++;
				switch ($Row['OriginType']) {
					case USER:
						switch ($Row['TargetType']) {
							case USER:
								if ($Row['Origin'] == $UserName ||
									\TwoDot7\Util\Token::Exists(array(
										'JSON' => $Row['Target'],
										'Token' => $UserName
									))) {

									$TranslateTargetType = function($i) {
										switch ($i) {
											case USER: return 'with';
											case GROUP: return 'in';
											default: return '';
										}
									};
									$TranslateVisibleClass = function($i) {
										switch ($i) {
											case _PRIVATE: return "fa fa-lock";
											case _PUBLIC:
											default: return "fa fa-globe";
										}
									};

									$OP = Utils::GetUserMeta(json_encode(array($Row['Origin'])));
									$Row['Meta']['OP'] = isset($OP[0]) ? $OP[0] : $OP;
									$Row['Meta']['TaggedUsers'] = Utils::GetUserMeta($Row['Target']);
									
									$Row['Target'] = \TwoDot7\Util\Token::Get(array('JSON' => $Row['Target']));
									$Row['TargetType'] = $TranslateTargetType($Row['TargetType']);
									
									$Row['TimeAgo'] = Utils::timeAgo($Row['Timestamp']);
									$Row['VisibleClass'] = $TranslateVisibleClass($Row['Visible']);

									$Row['Data'] = Utils::Unpack($Row['Data']);

									array_push($Result, $Row);
									break;
								} else break;
							case GROUP:
								// IF Origin == $Username OR user belongs to one of the group.
							case _DEFAULT:
							default:
								if ($Row['Origin'] == $UserName ||
									$Row['Visible'] == _PUBLIC) {

									$TranslateTargetType = function($i) {
										switch ($i) {
											case USER: return 'with';
											case GROUP: return 'in';
											default: return '';
										}
									};
									$TranslateVisibleClass = function($i) {
										switch ($i) {
											case _PRIVATE: return "fa fa-lock";
											case _PUBLIC:
											default: return "fa fa-globe";
										}
									};

									$OP = Utils::GetUserMeta(json_encode(array($Row['Origin'])));
									$Row['Meta']['OP'] = isset($OP[0]) ? $OP[0] : $OP;
									$Row['Meta']['TaggedUsers'] = Utils::GetUserMeta($Row['Target']);
									
									$Row['Target'] = \TwoDot7\Util\Token::Get(array('JSON' => $Row['Target']));
									$Row['TargetType'] = $TranslateTargetType($Row['TargetType']);
									
									$Row['TimeAgo'] = Utils::timeAgo($Row['Timestamp']);
									$Row['VisibleClass'] = $TranslateVisibleClass($Row['Visible']);

									$Row['Data'] = Utils::Unpack($Row['Data']);

									array_push($Result, $Row);
									break;
								} else break;
						}
						break;
					
					default:

						break;
				}
				$Begin = $Row['Timestamp'];
			}

		} while ($Counter && count($Result) < \TwoDot7\Config\BROADCAST_FEED_UNIT);

		return $Result;
	}
}

class Utils {
	public static function Pack(&$Data) {
		// Packs the Raw broadcast data. Packs images and stuff as well.
		// Only supports the Text Data for now.
		// Data Schema: BroadcastText -> Contains the Text of broadcast. Supports Markdown.
		// 				BroadcastFileAttachements -> Contains various broadcast files. Not Supported yet.
		// 				BroadcastMeta -> Contains various meta.
		return array(
			'Datatype' => 1,
			'Data' => json_encode($Data['BroadcastText'])
			);
	}

	public static function Unpack(&$Data) {
		// Unpacks a Packed broadcast data.
		return json_decode($Data, true);
	}

	public static function GetUserMeta($TagJSON) {
		if (is_array($TagJSON) || !$TagJSON) return False;
		$TaggedUsers = \TwoDot7\Util\Token::Get(array('JSON' => $TagJSON));
		return \TwoDot7\User\Meta::Get($TaggedUsers);
	}

	/**
	 * Returns a Pretty Time Ago string from given Timestamp. Returns bare date if
	 * timestamp is way back in past.
	 * @param	int $Timestamp The timestamp.
	 * @return	string The Pretty string.
	 * @author	Prashant Sinha <firstname,lastname>@outlook.com
	 * @see		\Time()
	 * @since	v0.0 20140904
	 * @version	0.1
	 */
	public static function timeAgo($Timestamp) {
		/**
		 * @internal Get Time Difference and then return the String.
		 */
		$Ago = time() - $Timestamp;

		if ($Ago < 60) return "just now";
		elseif ($Ago < 120) return "a few minutes ago";
		elseif ($Ago < 3570) return round($Ago / 60)." minutes ago";
		elseif ($Ago < 86400) return "today, at ".date('g:iA', $Timestamp);
		elseif ($Ago < 172800) return "yesterday, at ".date('g:iA', $Timestamp);
		else return date('g:iA, j F Y', $Timestamp);
	}
}